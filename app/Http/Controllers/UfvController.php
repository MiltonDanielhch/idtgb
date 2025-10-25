<?php

namespace App\Http\Controllers;
// app/Http/Controllers/UfvController.php
namespace App\Http\Controllers;

use App\Models\Ufv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UfvController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------- LISTADO (AJAX) ---------- */
    public function index()
    {
        $this->authorize('viewAny', Ufv::class);
        return view('admin.ufvs.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', Ufv::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Ufv::when($search, fn($q) => $q->where('fecha', 'like', "%{$search}%"))
            ->orderBy('fecha', 'desc')
            ->paginate($paginate);

        return view('admin.ufvs.list', compact('data'));
    }

    /* ---------- LECTURA ---------- */
    public function show(Ufv $ufv)
    {
        $this->authorize('view', $ufv);
        return view('admin.ufvs.read', compact('ufv'));
    }

    /* ---------- ALTA UNITARIA ---------- */
    public function create()
    {
        $this->authorize('create', Ufv::class);
        return view('admin.ufvs.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Ufv::class);

        $request->validate([
            'fecha' => 'required|date|before_or_equal:today|unique:ufvs,fecha',
            'valor' => 'required|numeric|min:0.00001',
        ], [
            'fecha.unique' => 'Ya existe un valor UFV para esa fecha.',
        ]);

        Ufv::create([
            'fecha' => $request->fecha,
            'valor' => $request->valor,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.ufvs.index')
            ->with(['message' => 'Valor UFV guardado.', 'alert-type' => 'success']);
    }

    /* ---------- IMPORTAR CSV ---------- */
    public function import(Request $request)
    {
        $this->authorize('create', Ufv::class);

        $request->validate([
            'archivo' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $file     = $request->file('archivo');
            $handle   = fopen($file->getRealPath(), 'r');
            $errors   = [];
            $toInsert = [];
            $now      = now();
            $userId   = auth()->id();

            // --- NUEVO: Detectar automáticamente el delimitador ---
            // Leemos la primera línea para ver si contiene un punto y coma.
            $firstLine = fgets($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
            // Volvemos al principio del archivo para que fgetcsv lo procese desde el inicio.
            rewind($handle);

            // Omitimos la fila de encabezado, usando el delimitador que acabamos de detectar.
            fgetcsv($handle, 0, $delimiter);

            // Obtener todas las fechas existentes para evitar duplicados
            $existingDates = Ufv::pluck('fecha')->map(fn($date) => $date->format('Y-m-d'))->flip();

            // Usamos el delimitador detectado en el bucle
            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($line) < 2) continue;

                $fecha = trim($line[0]);

                $valor = trim($line[1]);
                // --- NUEVO: Detección inteligente del formato de fecha ---
                // Primero intentamos con el formato día/mes/año
                $dateObject = \DateTime::createFromFormat('d/m/Y', $fecha);

                // Si falla, intentamos con el formato año-mes-día
                if (!$dateObject) {
                    $dateObject = \DateTime::createFromFormat('Y-m-d', $fecha);
                }

                if (!$dateObject) {
                    $errors[] = "Formato de fecha inválido: {$fecha} (se espera DD/MM/YYYY o YYYY-MM-DD)";
                    continue;
                }

                // Siempre guardamos la fecha en el formato estándar de la BD
                $fechaFormatted = $dateObject->format('Y-m-d');

                // Limpiamos el valor por si tiene caracteres extraños
                // $valor = str_replace([' ', '|'], '', $valor);
                $valor = str_replace([' ', '|', ','], ['', '', '.'], $valor);

                if (!is_numeric($valor) || $valor <= 0) {
                    $errors[] = "Valor inválido: {$valor}";
                    continue;
                }

                // Verificar duplicados
                if (isset($existingDates[$fechaFormatted])) {
                    $errors[] = "Fecha duplicada: {$fechaFormatted}";
                    continue;
                }

                // Preparar el array para la inserción masiva
                $toInsert[] = [
                    'fecha'      => $fechaFormatted,
                    'valor'      => $valor,
                    // 'created_by' => $userId,
                    // 'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            fclose($handle);

            // Realizar una única inserción masiva si hay datos para insertar
            if (!empty($toInsert)) {
                Ufv::insert($toInsert);
            }

            DB::commit();

            if (!empty($errors)) {
                return back()->with(['message' => 'Importado con advertencias: ' . implode(', ', $errors), 'alert-type' => 'warning']);
            }

            return redirect()->route('admin.ufvs.index')
                ->with(['message' => 'UFVs importados correctamente.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            // Log::error('Error importando UFVs: ' . $e->getMessage());
            return back()->withInput()->with(['message' => 'Error al importar el archivo: ' . $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}

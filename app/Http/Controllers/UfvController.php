<?php

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
            'fecha' => 'required|date|before_or_equal:end_of_current_month|unique:ufvs,fecha',
            'valor' => 'required|numeric|min:0.00001',
        ], [
            'fecha.unique' => 'Ya existe un valor UFV para esa fecha.',
            'fecha.before_or_equal' => 'La fecha no puede ser posterior al fin del mes actual.',
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
            $today    = now()->startOfDay();
            $endOfMonth = now()->endOfMonth()->startOfDay();
            $userId   = auth()->id();

            $firstLine = fgets($handle);
            $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
            rewind($handle);

            fgetcsv($handle, 0, $delimiter);

            $existingDates = Ufv::pluck('fecha')->map(fn($date) => $date->format('Y-m-d'))->flip();

            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (count($line) < 2) continue;

                $fecha = trim($line[0]);
                $valor = trim($line[1]);

                $dateObject = \DateTime::createFromFormat('d/m/Y', $fecha);
                if (!$dateObject) {
                    $dateObject = \DateTime::createFromFormat('Y-m-d', $fecha);
                }

                if (!$dateObject) {
                    $errors[] = "Formato de fecha inválido: {$fecha}";
                    continue;
                }
                
                if ($dateObject > $endOfMonth) {
                    $errors[] = "La fecha no puede ser posterior al fin del mes actual: {$fecha}";
                    continue;
                }

                $fechaFormatted = $dateObject->format('Y-m-d');

                // Bug #5: Mejorar conversión de separadores.
                // 1. Quitar separadores de miles (puntos)
                // 2. Reemplazar la coma decimal por un punto.
                $valor = str_replace('.', '', $valor);
                $valor = str_replace(',', '.', $valor);

                if (!is_numeric($valor) || $valor <= 0) {
                    $errors[] = "Valor inválido: {$valor}";
                    continue;
                }

                if (isset($existingDates[$fechaFormatted])) {
                    $errors[] = "Fecha duplicada: {$fechaFormatted}";
                    continue;
                }

                $toInsert[] = [
                    'fecha'      => $fechaFormatted,
                    'valor'      => $valor,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            fclose($handle);

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
            Log::error('Error importando UFVs: ' . $e->getMessage());
            return back()->withInput()->with(['message' => 'Error al importar el archivo: ' . $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ---------- EDICIÓN ---------- */
    public function edit(Ufv $ufv)
    {
        $this->authorize('update', $ufv);
        return view('admin.ufvs.edit', compact('ufv'));
    }

    public function update(Request $request, Ufv $ufv)
    {
        $this->authorize('update', $ufv);

        $request->validate([
            'fecha' => 'required|date|before_or_equal:end_of_current_month|unique:ufvs,fecha,' . $ufv->id,
            'valor' => 'required|numeric|min:0.00001',
        ], [
            'fecha.before_or_equal' => 'La fecha no puede ser posterior al fin del mes actual.',
        ]);

        $ufv->update([
            'fecha' => $request->fecha,
            'valor' => $request->valor,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.ufvs.index')
            ->with(['message' => 'Valor UFV actualizado.', 'alert-type' => 'success']);
    }

    /* ---------- ELIMINACIÓN ---------- */
    public function destroy(Ufv $ufv)
    {
        $this->authorize('delete', $ufv);
        $ufv->delete();

        return redirect()->route('admin.ufvs.index')
            ->with(['message' => 'Valor UFV eliminado.', 'alert-type' => 'success']);
    }
}

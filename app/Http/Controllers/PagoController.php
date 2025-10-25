<?php
// app/Http/Controllers/PagoController.php
namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Pago;
use App\Http\Requests\StorePagoRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PagoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------- LISTADO (AJAX) ---------- */
    public function index(Tramite $tramite)
    {
        $this->authorize('viewAny', Pago::class);
        return view('admin.tramites.pagos.browse', compact('tramite'));
    }

    public function list(Tramite $tramite)
    {
        $this->authorize('viewAny', Pago::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Pago::where('tramite_id', $tramite->id)
            ->when($search, fn($q) => $q->where('nro_operacion', 'like', "%{$search}%"))
            ->orderBy('fecha_pago', 'desc')
            ->paginate($paginate);

        return view('admin.tramites.pagos.list', compact('tramite', 'data'));
    }

    /* ---------- LECTURA ---------- */
    public function show(Tramite $tramite, Pago $pago)
    {
        $this->authorize('view', $pago);
        return view('admin.tramites.pagos.read', compact('tramite', 'pago'));
    }

    /* ---------- ALTA ---------- */
    public function create(Tramite $tramite)
    {
        $this->authorize('create', Pago::class);

        // Solo se permite registrar pago si el trámite NO está pagado
        if ($tramite->estado === 'Pagado') {
            return redirect()->route('admin.tramites.pagos.index', $tramite)
                ->with(['message' => 'El trámite ya está pagado.', 'alert-type' => 'warning']);
        }

        $bancos = ['Banco Unión', 'Banco Nacional', 'Banco Mercantil', 'Banco FIE', 'Banco BCP', 'Banco Sol', 'Otros'];

        return view('admin.tramites.pagos.create', compact('tramite', 'bancos'));
    }

    public function store(StorePagoRequest $request, Tramite $tramite)
    {
        $this->authorize('create', Pago::class);

        // Validación optimizada: verificar si ya existe un pago aplicado para este trámite.
        if (Pago::where('tramite_id', $tramite->id)->where('estado', 'Aplicado')->exists()) {
            return back()->withInput()
                ->with(['message' => 'Este trámite ya tiene un pago aplicado.', 'alert-type' => 'error']);
        }

        DB::beginTransaction();
        try {
            // dd('Punto 1: Entrando al try-catch');
            // 1. Instanciar el pago con todos los datos validados.
            $pago = new Pago($request->validated());
            $pago->fill([
                'tramite_id' => $tramite->id,
                'estado' => 'Pendiente',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // 2. Generar un nombre de archivo único para el QR sin depender del ID del pago.
            // Se usa el ID del trámite, timestamp y un hash corto para evitar colisiones.
            $uniqueHash = Str::random(8);
            $qrFileName = "tramites/{$tramite->id}/pagos/qr_{$pago->fecha_pago->timestamp}_{$uniqueHash}.svg";
            $pago->qr_path = $qrFileName;

            // 3. Actualizar el estado del trámite y del pago si corresponde.
            if ($request->monto >= $tramite->monto_final) {
                // Se usa withoutEvents para evitar un bucle infinito si un TramiteObserver
                // reacciona al evento 'updated' y vuelve a guardar el modelo.
                $tramite->withoutEvents(function () use ($tramite) {
                    $tramite->update(['estado' => 'Pagado']);
                });
                $pago->estado = 'Aplicado'; // Marcar el pago como aplicado
            }

            // 4. Guardar el pago en la base de datos UNA SOLA VEZ.
            $pago->save();

            // 5. Ahora que tenemos el ID, generamos el contenido del QR y guardamos el archivo.
            $qrContent = json_encode([
                'tramite' => $tramite->nro_tramite,
                'pago_id' => $pago->id,
                'monto' => $pago->monto,
                'fecha' => $pago->fecha_pago->format('Y-m-d'),
            ]);
            Storage::disk('public')->put($qrFileName, \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($qrContent));
            // dd('Punto 5: QR generado y guardado');

            DB::commit();

            return redirect()->route('admin.tramites.pagos.index', $tramite)
                ->with(['message' => 'Pago registrado con éxito. Se generó un código QR.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ---------- BORRADO (solo reversión lógica) ---------- */
    public function destroy(Tramite $tramite, Pago $pago)
    {
        $this->authorize('delete', $pago);

        if ($pago->tramite_id !== $tramite->id) {
            abort(404);
        }

        DB::beginTransaction();
        try {
            if ($pago->estado === 'Aplicado') {
                return back()->with(['message' => 'No se puede eliminar un pago aplicado.', 'alert-type' => 'error']);
            }

            $pago->update(['estado' => 'Reversado']);
            $tramite->update(['estado' => 'Borrador']);

            DB::commit();

            return redirect()->route('admin.tramites.pagos.index', $tramite)
                ->with(['message' => 'Pago reversado.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }
}

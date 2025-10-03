<?php
// app/Http/Controllers/PagoController.php
namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Pago;
use App\Http\Requests\StorePagoRequest;
use Illuminate\Support\Facades\DB;
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

        DB::beginTransaction();
        try {
            // Generar código de barras único si no viene
            $codigoBarras = $request->codigo_barras ?? 'IDTGB-' . Str::upper(Str::random(10));

            $pago = Pago::create([
                'tramite_id' => $tramite->id,
                'fecha_pago' => $request->fecha_pago,
                'monto' => $request->monto,
                'codigo_barras' => $codigoBarras,
                'nro_operacion' => $request->nro_operacion,
                'banco' => $request->banco,
                'estado' => 'Pendiente',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // Recalcular IDTGB y marcar como Pagado si el monto cubre el total
            if ($request->monto >= $tramite->monto_final) {
                $tramite->update(['estado' => 'Pagado']);
            }

            DB::commit();

            return redirect()->route('admin.tramites.pagos.index', $tramite)
                ->with(['message' => 'Pago registrado. Código: ' . $codigoBarras, 'alert-type' => 'success']);

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

<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Inmueble;
use App\Models\TipoTransmision;
use App\Models\Ufv;
use App\Http\Requests\StoreTramiteRequest;
use App\Http\Requests\UpdateTramiteRequest;
use App\Services\IdtgbCalculator;
use Illuminate\Support\Facades\DB;

class TramiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ----------  LISTADO (AJAX)  ---------- */
    public function index()
    {
        $this->authorize('viewAny', Tramite::class);
        return view('admin.tramites.browse');
    }

    public function list()
    {
        $this->authorize('viewAny', Tramite::class);

        $search   = request('search');
        $paginate = request('paginate', 10);

        $data = Tramite::with(['inmueble', 'tipoTransmision', 'user'])
            ->when($search, fn($q) => $q->where('nro_tramite', 'like', "%{$search}%"))
            ->orderByDesc('fecha_presentacion')
            ->paginate($paginate);

        return view('admin.tramites.list', compact('data'));
    }

    /* ----------  LECTURA  ---------- */
    public function show(Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        $tramite->load(['inmueble', 'adquirentes', 'disponentes', 'exenciones', 'user']);

        return view('admin.tramites.read', compact('tramite'));
    }

    /* ----------  ALTA  ---------- */
    public function create()
    {
        $this->authorize('create', Tramite::class);
        return view('admin.tramites.edit-add', [
            'tramite'    => new Tramite(),
            'inmuebles'  => Inmueble::orderBy('catastro')->get(),
            'tipos'      => TipoTransmision::orderBy('nombre')->get(),
        ]);
    }

    public function store(StoreTramiteRequest $request)
    {
         \Log::info('=== STORE TRAMITE ===');
        \Log::info('Request:', $request->all());
        \Log::info('Errores:', $request->validated());

        $this->authorize('create', Tramite::class);

        DB::beginTransaction();
        // try {
            // UFV del día
            $ufv = Ufv::whereDate('fecha', $request->fecha_presentacion)->value('valor') ?? 1;

            // Fecha de vencimiento (30 días hábiles)
            $vencimiento = \Carbon\Carbon::parse($request->fecha_presentacion)->addWeekdays(30);

            $tramite = Tramite::create(array_merge($request->validated(), [
                'user_id'           => auth()->id(),
                'ufv_aplicada'      => $ufv,
                'fecha_vencimiento' => $vencimiento,
                'estado'            => 'Borrador',
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]));

            // Cálculo automático
            app(IdtgbCalculator::class)->calcular($tramite);

            DB::commit();

            return redirect()->route('admin.tramites.index')
                ->with(['message' => 'Trámite creado.', 'alert-type' => 'success']);

        // } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        // }
    }

    /* ----------  EDICIÓN  ---------- */
    public function edit(Tramite $tramite)
    {
        $this->authorize('update', $tramite);
        return view('admin.tramites.edit-add', [
            'tramite'    => $tramite,
            'inmuebles'  => Inmueble::orderBy('catastro')->get(),
            'tipos'      => TipoTransmision::orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateTramiteRequest $request, Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        DB::beginTransaction();
        try {
            $tramite->update(array_merge($request->validated(), [
                'updated_by' => auth()->id(),
            ]));

            // Recálculo automático
            app(IdtgbCalculator::class)->calcular($tramite);

            DB::commit();

            return redirect()->route('admin.tramites.index')
                ->with(['message' => 'Trámite actualizado.', 'alert-type' => 'success']);

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
        }
    }

    /* ----------  BORRADO  ---------- */
    public function destroy(Tramite $tramite)
    {
        $this->authorize('delete', $tramite);

        if ($tramite->pagos()->where('estado', 'Aplicado')->exists()) {
            return back()->with(['message' => 'No se puede eliminar: tiene pagos aplicados.', 'alert-type' => 'error']);
        }

        $tramite->delete();

        return redirect()->route('admin.tramites.index')
            ->with(['message' => 'Trámite eliminado.', 'alert-type' => 'success']);
    }

    // public function a01(Tramite $tramite)
    // {
    //     $this->authorize('view', $tramite);

    //     // Ejemplo: generar PDF con DomPDF o devolver vista previa
    //     return view('admin.tramites.pdf.a01', compact('tramite'));
    // }
    public function a01(Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.tramites.pdf.a01', compact('tramite'));
        return $pdf->stream('Form-A01-'.$tramite->nro_tramite.'.pdf');
    }
}

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

        $data = Tramite::with(['tipoTransmision', 'user', 'inmuebles'])
            ->when($search, fn($q) => $q->where('nro_tramite', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate($paginate);

        return view('admin.tramites.list', compact('data'));
    }

    /* ----------  LECTURA  ---------- */
    public function show(Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        // Cargar todas las relaciones necesarias para la vista de detalle
        $tramite->load(['inmuebles', 'adquirentes.person', 'disponentes.person', 'exenciones', 'user', 'pagos']);

        return view('admin.tramites.read', compact('tramite'));
    }

    /* ----------  ALTA (Manejada por el Wizard) ----------
       La creación de trámites ahora se maneja exclusivamente a través del TramiteWizardController
       para una mejor experiencia de usuario y para gestionar la complejidad de los datos en pasos.
       Los métodos create() y store() se han eliminado de este controlador.
    */

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

            // Recálculo automático. Usamos withoutEvents para evitar un bucle infinito
            // con el TramiteObserver que escucha el evento 'updated'.
            $tramite->withoutEvents(function () use ($tramite) {
                app(IdtgbCalculator::class)->calculateAndSave($tramite);
            });

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

    public function a01(Tramite $tramite)
    {
        $this->authorize('view', $tramite);

        // Cargar relaciones para que estén disponibles en el PDF
        $tramite->load(['inmuebles', 'tipoTransmision', 'adquirentes.person', 'disponentes.person', 'exenciones']);

        // Genera el hash de validación si no existe
        $hash = $tramite->hash_validacion ?: $tramite->generateHashValidacion();

        // Genera la URL de validación y el código QR
        $validation_url = route('tramite.validar', ['hash' => $hash]);
        $qr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($validation_url));

        // Carga la vista del PDF y pasa los datos
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.tramites.pdf.a01', compact('tramite', 'qr', 'hash'));
        return $pdf->stream('Form-A01-'.$tramite->nro_tramite.'.pdf');
    }
}

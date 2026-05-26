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
use Illuminate\Support\Facades\Log;

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
        try {
            Log::info('LIST: Iniciando listado de trámites');
            $this->authorize('viewAny', Tramite::class);
            Log::info('LIST: Autorización pasada');

            $search   = request('search');
            $paginate = request('paginate', 10);
            Log::info('LIST: Parámetros - search: ' . $search . ', paginate: ' . $paginate);

            $data = Tramite::with(['tipoTransmision', 'user', 'inmuebles'])
                ->when($search, fn($q) => $q->where('nro_tramite', 'like', "%{$search}%"))
                ->orderByDesc('id')
                ->paginate($paginate);
            Log::info('LIST: Consulta completada, resultados: ' . $data->count());

            return view('admin.tramites.list', compact('data'));
        } catch (\Throwable $e) {
            Log::error('LIST: Error - ' . $e->getMessage());
            Log::error('LIST: Trace - ' . $e->getTraceAsString());
            throw $e;
        }
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

        if (in_array($tramite->estado, ['Pagado', 'Anulado', 'Finalizado'])) {
            return redirect()->route('admin.tramites.index')
                ->with(['message' => 'No se pueden editar trámites en estado Pagado, Anulado o Finalizado.', 'alert-type' => 'error']);
        }

        return view('admin.tramites.edit-add', [
            'tramite'    => $tramite,
            'inmuebles'  => Inmueble::orderBy('catastro')->get(),
            'tipos'      => TipoTransmision::orderBy('nombre')->get(),
        ]);
    }

    public function update(UpdateTramiteRequest $request, Tramite $tramite)
    {
        $this->authorize('update', $tramite);

        if (in_array($tramite->estado, ['Pagado', 'Anulado', 'Finalizado'])) {
            return back()->withErrors('No se pueden editar trámites en estado Pagado, Anulado o Finalizado.');
        }

        if (isset($request->monto_final) && $request->monto_final < 0) {
            return back()->withErrors('El monto final no puede ser negativo.')->withInput();
        }

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
            \Log::error('Error al actualizar trámite: ' . $e->getMessage());
            return back()->withInput()->with(['message' => 'Ocurrió un error inesperado al actualizar el trámite.', 'alert-type' => 'error']);
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
        // SOLUCIÓN DEFINITIVA: Extender tiempo de ejecución para PDFs complejos
        // Esto previene timeouts de 30 segundos en la primera carga
        set_time_limit(120); // 2 minutos para generación de PDF

        $this->authorize('view', $tramite);

        try {
            // Generar hash ANTES de cargar relaciones pesadas
            // Esto separa la operación de guardado de la generación del PDF
            if (!$tramite->hash_validacion) {
                $tramite->generateHashValidacion();
                $tramite->refresh(); // Recargar modelo con el hash guardado
            }

            $hash = $tramite->hash_validacion;

            // Cargar TODAS las relaciones anidadas necesarias en una sola consulta optimizada
            // Esto elimina el problema N+1 de raíz
            $tramite->load([
                'tipoTransmision',
                'adquirentes.parentesco',
                'adquirentes.person',
                'disponentes.person',
                'exenciones',
                'inmuebles.municipio.provincia.departamento'
            ]);

            // Genera la URL de validación y el código QR
            $validation_url = route('tramite.validar', ['hash' => $hash]);
            $qr = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(200)->generate($validation_url));

            // Carga la vista del PDF y pasa los datos
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.tramites.pdf.a01', compact('tramite', 'qr', 'hash'));

            Log::info('PDF A01 generado exitosamente', [
                'tramite_id' => $tramite->id,
                'nro_tramite' => $tramite->nro_tramite
            ]);

            return $pdf->stream('Form-A01-'.$tramite->nro_tramite.'.pdf');

        } catch (\Exception $e) {
            // Registro detallado del error para debugging
            Log::error('Error al generar PDF A01', [
                'tramite_id' => $tramite->id,
                'nro_tramite' => $tramite->nro_tramite,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with([
                'message' => 'Error al generar el PDF. Por favor intente nuevamente. Si el problema persiste, contacte al administrador.',
                'alert-type' => 'error'
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inmueble;
use App\Models\Person;
use Illuminate\Http\Request;
use App\Models\TipoTransmision;
use App\Models\Tramite;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TramiteWizardController extends Controller
{
    private function initializeWizard(Request $request)
    {
        if (!$request->session()->has('tramite_wizard_data')) {
            $tramite = new Tramite();
            $tramite->disponentes_collection = collect();
            $tramite->adquirentes_collection = collect();
            $request->session()->put('tramite_wizard_data', $tramite);
        }
    }

    public function createStep1(Request $request)
    {
        $this->initializeWizard($request);
        $tipos = TipoTransmision::all();
        $tramite = $request->session()->get('tramite_wizard_data');

        return view('admin.tramites.wizard.create_step_1', [
            'tipos' => $tipos,
            'tramite' => $tramite,
            'step_title' => 'Paso 1: Datos Generales',
            'current_step' => 1, 'total_steps' => 5, 'progress' => 20,
        ]);
    }

    public function postStep1(Request $request)
    {
        $validatedData = $request->validate([
            'nro_tramite' => 'required|string|max:20|unique:tramites,nro_tramite',
            'fecha_presentacion' => 'required|date',
            'fecha_transmision' => 'required|date',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'valor_declarado' => 'required|numeric|min:0',
            'base_imponible' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        $tramite = $request->session()->get('tramite_wizard_data');
        $tramite->fill($validatedData);
        $request->session()->put('tramite_wizard_data', $tramite);

        return redirect()->route('admin.tramites.wizard.create.step2');
    }

    public function createStep2(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if (!$tramite) return redirect()->route('admin.tramites.wizard.create.step1');

        return view('admin.tramites.wizard.create_step_2', [
            'tramite' => $tramite,
            'disponentes' => $tramite->disponentes_collection ?? collect(),
            'step_title' => 'Paso 2: Identificación de Disponentes',
            'current_step' => 2, 'total_steps' => 5, 'progress' => 40,
        ]);
    }

    public function addDisponente(Request $request)
    {
        $request->validate(['person_id' => 'required|exists:people,id']);
        $tramite = $request->session()->get('tramite_wizard_data');
        $person = Person::find($request->person_id);

        if ($tramite && $person && !$tramite->disponentes_collection->contains('id', $person->id)) {
            // Validar que la persona no esté ya como adquirente
            if ($tramite->adquirentes_collection->contains('id', $person->id)) {
                return redirect()->route('admin.tramites.wizard.create.step2')->withErrors('Esta persona ya ha sido agregada como adquirente.');
            }

            $tramite->disponentes_collection->push($person); // Añadir a la colección
            $request->session()->put('tramite_wizard_data', $tramite);
        }

        return redirect()->route('admin.tramites.wizard.create.step2');
    }

    public function removeDisponente(Request $request, $person_id)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if ($tramite && isset($tramite->disponentes_collection)) {
            $tramite->disponentes_collection = $tramite->disponentes_collection->reject(fn($p) => $p->id == $person_id);
            $request->session()->put('tramite_wizard_data', $tramite);
        }
        return redirect()->route('admin.tramites.wizard.create.step2');
    }

    public function postStep2(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if (!$tramite || $tramite->disponentes_collection->isEmpty()) {
            return redirect()->route('admin.tramites.wizard.create.step2')->withErrors(['Debe agregar al menos un disponente.']);
        }
        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    // ------------------- PASO 3: ADQUIRENTES -------------------

    public function createStep3(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        $parentescos = \App\Models\Parentesco::all();
        if (!$tramite) return redirect()->route('admin.tramites.wizard.create.step1');

        return view('admin.tramites.wizard.create_step_3', [
            'tramite' => $tramite,
            'adquirentes' => $tramite->adquirentes_collection ?? collect(),
            'step_title' => 'Paso 3: Identificación de Adquirentes',
            'parentescos' => $parentescos,
            'current_step' => 3, 'total_steps' => 5, 'progress' => 60,
        ]);
    }

    public function addAdquirente(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:people,id',
            'parentesco_id' => 'required|exists:parentescos,id',
        ]);
        $tramite = $request->session()->get('tramite_wizard_data');
        $person = Person::find($request->person_id);

        if ($tramite && $person && !$tramite->adquirentes_collection->contains('id', $person->id)) {
            // Validar que la persona no esté ya como disponente
            if ($tramite->disponentes_collection->contains('id', $person->id)) {
                return redirect()->route('admin.tramites.wizard.create.step3')->withErrors('Esta persona ya ha sido agregada como disponente.');
            }

            // Añadir el parentesco_id al objeto persona antes de guardarlo en la sesión
            $person->parentesco_id = $request->parentesco_id;
            $tramite->adquirentes_collection->push($person); // Añadir a la colección
            $request->session()->put('tramite_wizard_data', $tramite);
        }

        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    public function removeAdquirente(Request $request, $person_id)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if ($tramite && isset($tramite->adquirentes_collection)) {
            $tramite->adquirentes_collection = $tramite->adquirentes_collection->reject(fn($p) => $p->id == $person_id);
            $request->session()->put('tramite_wizard_data', $tramite);
        }
        return redirect()->route('admin.tramites.wizard.create.step3');
    }

    public function postStep3(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if (!$tramite || $tramite->adquirentes_collection->isEmpty()) {
            return redirect()->route('admin.tramites.wizard.create.step3')->withErrors(['Debe agregar al menos un adquirente.']);
        }
        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    // ------------------- PASO 4: INMUEBLE ------------------ -

    public function createStep4(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if (!$tramite) {
            return redirect()->route('admin.tramites.wizard.create.step1');
        }

        return view('admin.tramites.wizard.create_step_4', [
            'tramite' => $tramite,
            'inmueble' => $tramite->inmueble ?? null,
            'step_title' => 'Paso 4: Identificación del Inmueble',
            'current_step' => 4, 'total_steps' => 5, 'progress' => 80,
        ]);
    }

    public function addInmueble(Request $request)
    {
        $request->validate(['inmueble_id' => 'required|exists:inmuebles,id']);
        $tramite = $request->session()->get('tramite_wizard_data');
        $inmueble = Inmueble::find($request->inmueble_id);

        if ($tramite && $inmueble) {
            $tramite->inmueble = $inmueble;
            $tramite->inmueble_id = $inmueble->id; // Asegurarse de que el ID también se guarda en el objeto principal
            $request->session()->put('tramite_wizard_data', $tramite);
        }

        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    public function removeInmueble(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if ($tramite && isset($tramite->inmueble)) {
            $tramite->inmueble = null;
            $request->session()->put('tramite_wizard_data', $tramite);
        }
        return redirect()->route('admin.tramites.wizard.create.step4');
    }

    public function postStep4(Request $request)
    {
        $tramite = $request->session()->get('tramite_wizard_data');
        if (!$tramite || !$tramite->inmueble) {
            return redirect()->route('admin.tramites.wizard.create.step4')->withErrors(['Debe seleccionar un inmueble.']);
        }
        return redirect()->route('admin.tramites.wizard.create.step5');
    }

    // ------------------- PASO 5: RESUMEN Y GUARDAR ------------------ -

    public function createStep5(Request $request)
    {
        $tramiteData = $request->session()->get('tramite_wizard_data');

        if (!$tramiteData) {
            return redirect()->route('admin.tramites.wizard.create.step1');
        }

        return view('admin.tramites.wizard.create_step_5', [
            'tramite' => $tramiteData,
            'step_title' => 'Paso 5: Resumen y Confirmación',
            'current_step' => 5, 'total_steps' => 5, 'progress' => 100,
        ]);
    }

    public function store(Request $request)
    {
        $tramiteData = $request->session()->get('tramite_wizard_data');

        if (!$tramiteData || $tramiteData->disponentes_collection->isEmpty() || $tramiteData->adquirentes_collection->isEmpty() || !$tramiteData->inmueble) {
            return redirect()->route('admin.tramites.wizard.create.step1')->withErrors('Faltan datos para completar el trámite. Por favor, revise los pasos anteriores.');
        }

        try {
            DB::beginTransaction();

            // 1. Crear el Trámite principal
            $tramite = new Tramite();
            $tramite->fill($tramiteData->getAttributes());
            $tramite->user_id = auth()->id(); // Por ejemplo
            if ($tramite->fecha_presentacion) {
                $tramite->fecha_vencimiento = (new \DateTime($tramite->fecha_presentacion))->modify('+30 days')->format('Y-m-d');
            }
            $tramite->save();

            // 2. Adjuntar Disponentes
            $tramite->disponentes()->sync($tramiteData->disponentes_collection->pluck('id'));

            // 3. Adjuntar Adquirentes
            $adquirentes_sync_data = [];
            foreach ($tramiteData->adquirentes_collection as $adquirente) {
                // NOTA: Los siguientes valores son temporales. Debes implementar la lógica para calcularlos.
                $adquirentes_sync_data[$adquirente->id] = [
                    'parentesco_id' => $adquirente->parentesco_id,
                    'tasa_aplicada' => 0, // TODO: Implementar cálculo
                    'porcentaje' => 0, // TODO: Implementar cálculo
                    'idtgb_proporcional' => 0, // TODO: Implementar cálculo
                ];
            }
            $tramite->adquirentes()->sync($adquirentes_sync_data);

            // 4. Adjuntar Inmueble
            $tramite->inmuebles()->sync([$tramiteData->inmueble_id]);

            DB::commit();

            // Limpiar la sesión
            $request->session()->forget('tramite_wizard_data');

            return redirect()->route('admin.tramites.index')->with([
                'message'    => "Trámite creado con éxito. Número: {$tramite->nro_tramite}",
                'alert-type' => 'success',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.tramites.wizard.create.step5')->withErrors('Error al guardar el trámite: ' . $e->getMessage());
        }
    }

    public function cancelWizard(Request $request)
    {
        $request->session()->forget('tramite_wizard_data');

        return redirect()->route('admin.tramites.index')->with([
            'message'    => "Creación de trámite cancelada.",
            'alert-type' => 'info',
        ]);
    }

    /**
     * Proporciona una lista de personas para DataTables.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxPersonList(Request $request)
    {
        if ($request->ajax()) {
            $data = Person::select(['id', 'full_name', 'ci'])->latest()->get();
            return DataTables::of($data)->make(true);
        }
    }
}

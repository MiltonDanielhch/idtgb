<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Inmueble;
use App\Models\TipoTransmision;
use App\Models\Ufv;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\IdtgbCalculator;
use Barryvdh\DomPDF\Facade\Pdf;

class TramiteController extends Controller
{
    public function index()
    {
        $tramites = Tramite::with(['inmueble', 'tipoTransmision', 'user'])
                           ->orderBy('fecha_presentacion', 'desc')
                           ->paginate(20);
        return view('admin.tramites.index', compact('tramites'));
    }

    public function create()
    {
        $inmuebles = Inmueble::orderBy('catastro')->get();
        $tipos = TipoTransmision::orderBy('nombre')->get();
        return view('admin.tramites.create', compact('inmuebles', 'tipos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nro_tramite'        => 'required|string|max:15|unique:tramites',
            'fecha_presentacion' => 'required|date',
            'tipo_transmision_id'=> 'required|exists:tipos_transmision,id',
            'inmueble_id'        => 'required|exists:inmuebles,id',
            'valor_declarado'    => 'required|numeric|min:0',
            'base_imponible'     => 'required|numeric|min:0',
            'fecha_transmision'  => 'required|date',
            'observaciones'      => 'nullable|string',
        ]);

        // UFV del día
        $ufv = Ufv::whereDate('fecha', $request->fecha_presentacion)->value('valor') ?? 1;

        // Fecha de vencimiento (30 días hábiles desde presentación)
        $vencimiento = Carbon::parse($request->fecha_presentacion)->addWeekdays(30);

        $tramite = Tramite::create([
            'nro_tramite'        => $request->nro_tramite,
            'fecha_presentacion' => $request->fecha_presentacion,
            'tipo_transmision_id'=> $request->tipo_transmision_id,
            'inmueble_id'        => $request->inmueble_id,
            'valor_declarado'    => $request->valor_declarado,
            'base_imponible'     => $request->base_imponible,
            'total_idtgb'        => 0, // se calculará después
            'recargo_mora'       => 0,
            'monto_final'        => 0,
            'ufv_aplicada'       => $ufv,
            'estado'             => 'Borrador',
            'fecha_transmision'  => $request->fecha_transmision,
            'fecha_vencimiento'  => $vencimiento,
            'observaciones'      => $request->observaciones,
            'user_id'            => auth()->id(),
            'created_by'         => auth()->id(),
            'updated_by'         => auth()->id(),
        ]);

        // Aquí puedes llamar al motor de cálculo del IDTGB
        // $tramite->calcularIdtgb();
        // después de crear/actualizar
        $calc = new IdtgbCalculator();
        $calc->calcular($tramite);

        return redirect()->route('admin.tramites.index')
            ->with(['message' => 'Trámite creado.', 'alert-type' => 'success']);
    }

    public function edit(Tramite $tramite)
    {
        $inmuebles = Inmueble::orderBy('catastro')->get();
        $tipos = TipoTransmision::orderBy('nombre')->get();
        return view('admin.tramites.edit', compact('tramite', 'inmuebles', 'tipos'));
    }

    public function update(Request $request, Tramite $tramite)
    {
        $request->validate([
            'nro_tramite'        => 'required|string|max:15|unique:tramites,nro_tramite,'.$tramite->id,
            'fecha_presentacion' => 'required|date',
            'tipo_transmision_id'=> 'required|exists:tipos_transmision,id',
            'inmueble_id'        => 'required|exists:inmuebles,id',
            'valor_declarado'    => 'required|numeric|min:0',
            'base_imponible'     => 'required|numeric|min:0',
            'fecha_transmision'  => 'required|date',
            'observaciones'      => 'nullable|string',
            'estado'             => 'in:Borrador,Pagado,Observado,Anulado,Finalizado',
        ]);

        $tramite->update([
            'nro_tramite'        => $request->nro_tramite,
            'fecha_presentacion' => $request->fecha_presentacion,
            'tipo_transmision_id'=> $request->tipo_transmision_id,
            'inmueble_id'        => $request->inmueble_id,
            'valor_declarado'    => $request->valor_declarado,
            'base_imponible'     => $request->base_imponible,
            'fecha_transmision'  => $request->fecha_transmision,
            'observaciones'      => $request->observaciones,
            'estado'             => $request->estado,
            'updated_by'         => auth()->id(),
        ]);

        // después de crear/actualizar
        $calc = new IdtgbCalculator();
        $calc->calcular($tramite);

        return redirect()->route('admin.tramites.index')
            ->with(['message' => 'Trámite actualizado.', 'alert-type' => 'success']);
    }

    public function destroy(Tramite $tramite)
    {
        $tramite->delete();
        return redirect()->route('admin.tramites.index')
            ->with(['message' => 'Trámite eliminado.', 'alert-type' => 'success']);
    }

    public function a01(Tramite $tramite)
    {
        $pdf = Pdf::loadView('admin.tramites.pdf.a01', compact('tramite'));
        return $pdf->download('A01-'.$tramite->nro_tramite.'.pdf');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use App\Models\Pago;
use Barryvdh\DomPDF\Facade\Pdf;

class PagoController extends Controller
{
    public function create(Tramite $tramite)
    {
        return view('admin.tramites.pagar', compact('tramite'));
    }

    public function store(Tramite $tramite, Request $request)
    {
        if ($tramite->estado == 'Pagado') {
            return redirect()->back()->with(['message' => 'El trámite ya está pagado.', 'alert-type' => 'warning']);
        }

        $pago = Pago::create([
            'tramite_id'    => $tramite->id,
            'fecha_pago'    => $request->fecha_pago,
            'monto'         => $tramite->monto_final,
            'codigo_barras' => $request->codigo_barras,
            'nro_operacion' => $request->nro_operacion,
            'banco'         => $request->banco,
            'estado'        => 'Aplicado',
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        // Marcar trámite como pagado
        $tramite->update(['estado' => 'Pagado']);

        return redirect()->route('admin.pago.comprobante', $pago)
            ->with(['message' => 'Pago registrado.', 'alert-type' => 'success']);
    }

    public function comprobante(Pago $pago)
    {
        $pdf = Pdf::loadView('admin.pagos.comprobante', compact('pago'));
        return $pdf->download('Comprobante-'.$pago->codigo_barras.'.pdf');
    }
}

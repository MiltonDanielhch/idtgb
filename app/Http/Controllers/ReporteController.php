<?php

namespace App\Http\Controllers;

use App\Models\Tramite;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $reportData = null;
        $input = $request->all();

        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'tipo_reporte' => 'required|string'
            ]);

            switch ($request->tipo_reporte) {
                case 'recaudacion':
                    $reportData = $this->generarReporteRecaudacion($request);
                    if ($request->get('exportar') === 'pdf') {
                        return $reportData;
                    }
                    break;
                case 'tipos_tramite':
                    $reportData = $this->generarReporteTiposTramite($request);
                    if ($request->get('exportar') === 'pdf') {
                        return $reportData;
                    }
                    break;
            }
        }

        return view('admin.reportes.index', compact('reportData', 'input'));
    }

    private function generarReporteRecaudacion(Request $request)
    {
        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();

        $tramites = Tramite::whereIn('estado', ['Pagado', 'Finalizado'])
            ->whereBetween('updated_at', [$fechaInicio, $fechaFin])
            ->with('adquirentes.person')
            ->orderBy('updated_at', 'desc')
            ->get();

        $totalRecaudado = $tramites->sum('monto_final');

        $data = [
            'titulo' => 'Reporte de Recaudación',
            'fecha_inicio' => $fechaInicio->format('d/m/Y'),
            'fecha_fin' => $fechaFin->format('d/m/Y'),
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'tramites' => $tramites,
            'total_recaudado' => $totalRecaudado,
            'tipo_reporte' => 'recaudacion'
        ];

        if ($request->get('exportar') === 'pdf') {
            $pdf = Pdf::loadView('admin.reportes.pdf.recaudacion', $data);
            return $pdf->download('reporte-recaudacion-' . now()->format('Y-m-d') . '.pdf');
        }

        return $data;
    }

    private function generarReporteTiposTramite(Request $request)
    {
        $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fecha_fin)->endOfDay();

        $stats = Tramite::whereIn('estado', ['Pagado', 'Finalizado'])
            ->whereBetween('updated_at', [$fechaInicio, $fechaFin])
            ->with('tipoTransmision')
            ->select('tipo_transmision_id', DB::raw('count(*) as cantidad'), DB::raw('sum(monto_final) as total'))
            ->groupBy('tipo_transmision_id')
            ->get();

        $totalGeneral = $stats->sum('total');

        $data = [
            'titulo' => 'Reporte por Tipos de Trámite',
            'fecha_inicio' => $fechaInicio->format('d/m/Y'),
            'fecha_fin' => $fechaFin->format('d/m/Y'),
            'fecha_generacion' => now()->format('d/m/Y H:i:s'),
            'stats' => $stats,
            'total_general' => $totalGeneral,
            'tipo_reporte' => 'tipos_tramite'
        ];

        if ($request->get('exportar') === 'pdf') {
            $pdf = Pdf::loadView('admin.reportes.pdf.tipos_tramite', $data);
            return $pdf->download('reporte-tipos-tramite-' . now()->format('Y-m-d') . '.pdf');
        }

        return $data;
    }
}

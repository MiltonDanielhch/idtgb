<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tramite;
use App\Models\Pago;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Métricas de Trámites
        $totalTramites = Tramite::count();
        $tramitesHoy = Tramite::whereDate('created_at', Carbon::today())->count();
        $tramitesMes = Tramite::whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year)->count();
        $tramitesAnio = Tramite::whereYear('created_at', Carbon::now()->year)->count();

        // Métricas de Recaudación
        $totalRecaudado = Pago::where('estado', 'pagado')->sum('monto');
        $recaudadoHoy = Pago::where('estado', 'pagado')->whereDate('fecha_pago', Carbon::today())->sum('monto');
        $recaudadoMes = Pago::where('estado', 'pagado')->whereMonth('fecha_pago', Carbon::now()->month)->whereYear('fecha_pago', Carbon::now()->year)->sum('monto');
        $recaudadoAnio = Pago::where('estado', 'pagado')->whereYear('fecha_pago', Carbon::now()->year)->sum('monto');

        // Trámites por estado
        $tramitesPorEstado = Tramite::select('estado', \DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        // Recaudación por mes (últimos 12 meses)
        $recaudacionMensual = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $mes = $date->format('Y-m');
            $recaudacion = Pago::where('estado', 'pagado')
                ->whereYear('fecha_pago', $date->year)
                ->whereMonth('fecha_pago', $date->month)
                ->sum('monto');
            $recaudacionMensual[$mes] = $recaudacion;
        }

        return view('vendor.voyager.index', compact(
            'totalTramites',
            'tramitesHoy',
            'tramitesMes',
            'tramitesAnio',
            'totalRecaudado',
            'recaudadoHoy',
            'recaudadoMes',
            'recaudadoAnio',
            'tramitesPorEstado',
            'recaudacionMensual'
        ));
    }
}

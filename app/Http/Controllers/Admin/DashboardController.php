<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tramite;
use App\Models\Pago;
use App\Services\DashboardService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * @var DashboardService
     */
    protected $dashboardService;

    /**
     * DashboardController constructor.
     *
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Muestra la vista principal del dashboard.
     */
    public function index(Request $request)
    {
        // dd('DashboardController@index called'); // DEBUG
        $data = $this->dashboardService->getData($request);
        return view('vendor.voyager.index', $data);
    }

    /**
     * Proporciona los datos del dashboard como una respuesta JSON para AJAX.
     */
    public function fetchData(Request $request)
    {
        $data = $this->dashboardService->getJsonData($request);

        return response()->json($data);
    }
}

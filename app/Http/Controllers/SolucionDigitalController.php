<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SolucionDigitalController extends Controller
{
    public function settings_code()
    {
        // Devuelve null si la conexión o la tabla no existen
        // return rescue(function () {
        //     return DB::connection('solucionDigital')
        //              ->table('web_systems')
        //              ->where('code', setting('system.code-system'))
        //              ->first();
        // });
    }
}

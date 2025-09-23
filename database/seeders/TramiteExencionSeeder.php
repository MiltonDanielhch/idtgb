<?php

namespace Database\Seeders;

use App\Models\TramiteExencion;
use App\Models\Tramite;
use App\Models\Exencion;
use Illuminate\Database\Seeder;

class TramiteExencionSeeder extends Seeder
{
    public function run(): void
    {
        $tramite  = Tramite::first()->id;
        $exencion = Exencion::where('nombre', 'Vivienda única familiar')->first()->id;

        TramiteExencion::create([
            'tramite_id'     => $tramite,
            'exencion_id'    => $exencion,
            'monto_aplicado' => 500000.00, // ejemplo: 100 % de 500 000
        ]);
    }
}

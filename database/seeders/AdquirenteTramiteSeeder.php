<?php

namespace Database\Seeders;

use App\Models\AdquirenteTramite;
use App\Models\Tramite;
use App\Models\Person;
use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class AdquirenteTramiteSeeder extends Seeder
{
    public function run(): void
    {
        $tramite    = Tramite::first()->id;
        $hijo       = Parentesco::where('nombre', 'Hijo/a')->first()->id;
        $persona    = Person::where('person_type', 'Natural')->first()->id;

        AdquirenteTramite::create([
            'tramite_id'                  => $tramite,
            'persona_id'                  => $persona,
            'parentesco_id'               => $hijo,
            'tasa_aplicada'               => 1.00,
            'porcentaje'                  => 100.00,
            'idtgb_proporcional'          => 5000.00, // ejemplo 1 % de 500 000
            'es_beneficiario_exencion'    => false,
            'documento_sustento_exencion' => null,
        ]);
    }
}

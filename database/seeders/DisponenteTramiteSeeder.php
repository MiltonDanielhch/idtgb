<?php

namespace Database\Seeders;

use App\Models\DisponenteTramite;
use App\Models\Tramite;
use App\Models\Person;
use Illuminate\Database\Seeder;

class DisponenteTramiteSeeder extends Seeder
{
    public function run(): void
    {
        $tramite  = Tramite::first()->id;
        $persona  = Person::where('person_type', 'Natural')
                          ->where('estado_persona', 'Fallecido')
                          ->first()
                          ->id ?? Person::first()->id;

        DisponenteTramite::create([
            'tramite_id'         => $tramite,
            'persona_id'         => $persona,
            'tipo'               => 'Causante',
            'fecha_fallecimiento'=> now()->subMonths(2),
            'es_discapacitado'   => false,
        ]);
    }
}

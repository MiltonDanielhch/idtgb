<?php

namespace Database\Seeders;

use App\Models\Avaluo;
use App\Models\Inmueble;
use App\Models\Person;
use Illuminate\Database\Seeder;

class AvaluoSeeder extends Seeder
{
    public function run(): void
    {
        $inmueble = Inmueble::first()->id;
        $perito   = Person::where('person_type', 'Natural')->first()->id;

        Avaluo::create([
            'inmueble_id'     => $inmueble,
            'tipo_avaluo'     => 'Fiscal',
            'fecha_avaluo'    => now()->subDays(5),
            'valor'           => 500000.00,
            'perito_id'       => $perito,
            'documento_path'  => 'avaluos/AF-123456-2025.pdf',
            'estado'          => 'Vigente',
        ]);
    }
}

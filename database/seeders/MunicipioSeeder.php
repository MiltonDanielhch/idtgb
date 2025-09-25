<?php

namespace Database\Seeders;

use App\Models\{Provincia, Municipio};
use Illuminate\Database\Seeder;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        /* Provincia “Cercado” ya fue creada por ProvinciaSeeder */
        $prov = Provincia::where('nombre', 'Cercado')->firstOrFail();

        foreach (['Trinidad', 'San Javier', 'San Pedro'] as $municipio) {
            $prov->municipios()->firstOrCreate(['nombre' => $municipio]);
        }
    }
}

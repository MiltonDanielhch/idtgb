<?php

namespace Database\Seeders;

use App\Models\Provincia;
use Illuminate\Database\Seeder;

class MunicipioSeeder extends Seeder
{
    public function run(): void
    {
        $municipiosPorProvincia = [
            'Cercado'           => 'Trinidad',
            'José Ballivián'    => 'San Borja',
            'Vaca Díez'         => 'Riberalta',
            'Yacuma'            => 'Santa Ana del Yacuma',
            'Moxos'             => 'San Ignacio de Moxos',
            'Marbán'            => 'Loreto',
            'Iténez'            => 'Magdalena',
            'Mamoré'            => 'San Joaquín',
        ];

        foreach ($municipiosPorProvincia as $provinciaNombre => $municipioNombre) {
            $provincia = Provincia::where('nombre', $provinciaNombre)->firstOrFail();
            $provincia->municipios()->firstOrCreate(['nombre' => $municipioNombre]);
        }
    }
}

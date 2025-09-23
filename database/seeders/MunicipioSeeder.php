<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\Municipio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MunicipioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beni = Departamento::where('codigo', 'BE')->first()->id;

        $municipios = [
            'Trinidad',
            'San Ignacio de Moxos',
            'San Borja',
            'San Ramón',
            'Rurrenabaque',
            'Santa Rosa del Yacuma',
            'Santa Ana del Yacuma',
            'Loreto',
            'Reyes',
            'San Javier',
            'El Porvenir',
            'San Andrés',
            'Huacaraje',
            'Exaltación',
            'Puerto Siles',
            'Baures',
            'Yucumo',
            'Chorro de San Pedro',
            'San Carlos',
        ];

        foreach ($municipios as $nombre) {
            Municipio::firstOrCreate([
                'departamento_id' => $beni,
                'nombre'        => $nombre,
            ]);
        }
    }
}

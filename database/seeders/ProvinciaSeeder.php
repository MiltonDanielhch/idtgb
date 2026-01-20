<?php

namespace Database\Seeders;

use App\Models\{Departamento, Provincia};
use Illuminate\Database\Seeder;

class ProvinciaSeeder extends Seeder
{
    public function run(): void
    {
        $beni = Departamento::where('codigo', Departamento::CODIGO_BENI)->firstOrFail();

        $provincias = [
            'Cercado',
            'Moxos',
            'Yacuma',
            'Marbán',
            'Mamoré',
            'Iténez',
            'José Ballivián',
            'Vaca Díez',
        ];

        foreach ($provincias as $nombre) {
            $beni->provincias()->firstOrCreate(['nombre' => $nombre]);
        }
    }
}

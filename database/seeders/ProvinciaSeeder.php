<?php

namespace Database\Seeders;

use App\Models\{Departamento, Provincia};
use Illuminate\Database\Seeder;

class ProvinciaSeeder extends Seeder
{
    public function run(): void
    {
        $dep = Departamento::where('codigo', 'BE')->first();

        foreach (['Moxos', 'Yacuma', 'Cercado'] as $nombre) {
            $dep->provincias()->firstOrCreate(['nombre' => $nombre]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Tasa;
use App\Models\Departamento;
use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class TasaSeeder extends Seeder
{
    public function run(): void
    {
        // ID del departamento Beni
        $beni = Departamento::where('codigo', 'BE')->first()->id;

        // Mapa parentesco → tasa (según orden del seeder previo)
        $mapa = [
            1  => 1.00,  // Cónyuge
            2  => 1.00,  // Hijo/a
            3  => 1.00,  // Padre/Madre
            4  => 5.00,  // Hermano/a
            5  => 5.00,  // Abuelo/a
            6  => 5.00,  // Nieto/a
            7  => 10.00, // Tío/a
            8  => 10.00, // Sobrino/a
            9  => 10.00, // Primo/a
            10 => 20.00, // Sin parentesco
        ];

        foreach ($mapa as $parentescoId => $tasa) {
            Tasa::firstOrCreate(
                [
                    'departamento_id' => $beni,
                    'parentesco_id'   => $parentescoId,
                    'vigente_desde'   => '2025-01-01',
                ],
                [
                    'tasa'            => $tasa,
                    'vigente_hasta'   => null,
                ]
            );
        }
    }
}

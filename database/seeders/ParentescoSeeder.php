<?php

namespace Database\Seeders;

use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class ParentescoSeeder extends Seeder
{
    public function run(): void
    {
        $parentescos = [
            [
                'nombre' => 'Cónyuge o Conviviente',
                'categoria_tasa' => Parentesco::CATEGORIA_LINEA_DIRECTA,
            ],
            [
                'nombre' => 'Hijo/a',
                'categoria_tasa' => Parentesco::CATEGORIA_LINEA_DIRECTA,
            ],
            [
                'nombre' => 'Padre/Madre',
                'categoria_tasa' => Parentesco::CATEGORIA_LINEA_DIRECTA,
            ],
            [
                'nombre' => 'Hermano/a',
                'categoria_tasa' => Parentesco::CATEGORIA_COLATERAL,
            ],
            [
                'nombre' => 'Nieto/a',
                'categoria_tasa' => Parentesco::CATEGORIA_LINEA_DIRECTA,
            ],
            [
                'nombre' => 'Abuelo/a',
                'categoria_tasa' => Parentesco::CATEGORIA_LINEA_DIRECTA,
            ],
            [
                'nombre' => 'Tío/a o Sobrino/a',
                'categoria_tasa' => Parentesco::CATEGORIA_OTROS,
            ],
            [
                'nombre' => 'Sin parentesco',
                'categoria_tasa' => Parentesco::CATEGORIA_OTROS,
            ],
        ];

        foreach ($parentescos as $data) {
            Parentesco::firstOrCreate(
                ['nombre' => $data['nombre']],
                ['categoria_tasa' => $data['categoria_tasa']]
            );
        }
    }
}

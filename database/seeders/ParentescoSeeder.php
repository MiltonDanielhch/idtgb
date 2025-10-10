<?php

namespace Database\Seeders;

use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class ParentescoSeeder extends Seeder
{
    public function run(): void
    {
        $parentescos = [
            'Cónyuge o Conviviente',
            'Hijo/a',
            'Padre/Madre',
            'Hermano/a',
            'Nieto/a',
            'Abuelo/a',
            'Tío/a o Sobrino/a',
            'Sin parentesco',
        ];

        foreach ($parentescos as $nombre) {
            Parentesco::firstOrCreate(['nombre' => $nombre]);
        }
    }
}

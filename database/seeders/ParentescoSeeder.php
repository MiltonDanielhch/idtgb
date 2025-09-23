<?php

namespace Database\Seeders;

use App\Models\Parentesco;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParentescoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parentescos = [
            'Cónyuge',
            'Hijo/a',
            'Padre/Madre',
            'Hermano/a',
            'Abuelo/a',
            'Nieto/a',
            'Tío/a',
            'Sobrino/a',
            'Primo/a',
            'Sin parentesco',
        ];

        foreach ($parentescos as $nombre) {
            Parentesco::firstOrCreate(['nombre' => $nombre]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoTransmision;

class TipoTransmisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            'Herencia',
            'Donación',
            'Legado',
        ];

        foreach ($tipos as $nombre) {
            TipoTransmision::firstOrCreate(['nombre' => $nombre]);
        }
    }
}

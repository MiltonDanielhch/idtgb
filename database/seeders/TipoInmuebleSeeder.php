<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoInmueble;

class TipoInmuebleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            'Urbana',
            'Rural',
            'Mixta',
        ];

        foreach ($tipos as $nombre) {
            TipoInmueble::firstOrCreate(['nombre' => $nombre]);
        }
    }
}

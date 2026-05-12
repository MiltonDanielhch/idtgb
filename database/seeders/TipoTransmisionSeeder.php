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
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0');
        TipoTransmision::withTrashed()->forceDelete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $tipos = [
            1 => 'mortis causa',
            2 => 'Entre vivos',
        ];

        foreach ($tipos as $id => $nombre) {
            TipoTransmision::create(['id' => $id, 'nombre' => $nombre]);
        }
    }
}

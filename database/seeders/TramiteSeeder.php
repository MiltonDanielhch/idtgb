<?php

namespace Database\Seeders;

use App\Models\Tramite;
use App\Models\Inmueble;
use App\Models\TipoTransmision;
use App\Models\User;
use Illuminate\Database\Seeder;

class TramiteSeeder extends Seeder
{
    public function run(): void
    {
        $inmueble = Inmueble::first()->id;
        $tipo     = TipoTransmision::where('nombre', 'Herencia')->first()->id;
        $user     = User::first()->id;

        Tramite::create([
            'nro_tramite'        => 'BE-2025-0001',
            'fecha_presentacion' => now(),
            'tipo_transmision_id'=> $tipo,
            'inmueble_id'        => $inmueble,
            'valor_declarado'    => 500000.00,
            'base_imponible'     => 500000.00,
            'total_idtgb'        => 0, // se calculará después
            'recargo_mora'       => 0,
            'monto_final'        => 0,
            'estado'             => 'Borrador',
            'fecha_transmision'  => now()->subDays(10),
            'fecha_vencimiento'  => now()->addDays(20),
            'observaciones'      => 'Trámite de prueba IDTGB-Beni',
            'user_id'            => $user,
        ]);
    }
}

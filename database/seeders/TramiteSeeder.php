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
        // Asegurar que existan los datos base
        $user = User::firstOrFail();
        $herencia = TipoTransmision::where('nombre', 'Herencia')->firstOrFail();
        $donacion = TipoTransmision::where('nombre', 'Donación')->firstOrFail();

        // === Trámite 1: Herencia a hijo (1.5%) ===
        $tramite1 = Tramite::create([
            'nro_tramite'        => 'BE-2025-0001',
            'fecha_presentacion' => now(),
            'tipo_transmision_id'=> $herencia->id,
            'valor_declarado'    => 500000.00,
            'base_imponible'     => 500000.00,
            'total_idtgb'        => 7500.00, // 1.5% de 500,000
            'recargo_mora'       => 0.00,
            'monto_final'        => 7500.00,
            'ufv_aplicada'       => 1.00000,
            'estado'             => 'Finalizado',
            'fecha_transmision'  => now()->subDays(15),
            'fecha_vencimiento'  => now()->addDays(10),
            'observaciones'      => 'Herencia a hijo - Vivienda única familiar',
            'user_id'            => $user->id,
            'created_by'         => $user->id,
            'updated_by'         => $user->id,
        ]);

        // Vincular inmueble
        $inmueble1 = Inmueble::where('catastro', '12-3456-01-0101')->first();
        if ($inmueble1) {
            $tramite1->inmuebles()->attach($inmueble1->id);
        }

        // === Trámite 2: Donación a tercero (5%) ===
        $tramite2 = Tramite::create([
            'nro_tramite'        => 'BE-2025-0002',
            'fecha_presentacion' => now()->subDays(5),
            'tipo_transmision_id'=> $donacion->id,
            'valor_declarado'    => 420000.00,
            'base_imponible'     => 420000.00,
            'total_idtgb'        => 21000.00, // 5% de 420,000
            'recargo_mora'       => 0.00,
            'monto_final'        => 21000.00,
            'ufv_aplicada'       => 1.00000,
            'estado'             => 'Pagado',
            'fecha_transmision'  => now()->subDays(20),
            'fecha_vencimiento'  => now()->subDays(2),
            'observaciones'      => 'Donación a amigo sin parentesco',
            'user_id'            => $user->id,
            'created_by'         => $user->id,
            'updated_by'         => $user->id,
        ]);

        $inmueble2 = Inmueble::where('catastro', '13-1122-02-0303')->first();
        if ($inmueble2) {
            $tramite2->inmuebles()->attach($inmueble2->id);
        }

        // === Trámite 3: Herencia a cónyuge (0%) ===
        $tramite3 = Tramite::create([
            'nro_tramite'        => 'BE-2025-0003',
            'fecha_presentacion' => now()->subDays(10),
            'tipo_transmision_id'=> $herencia->id,
            'valor_declarado'    => 450000.00,
            'base_imponible'     => 450000.00,
            'total_idtgb'        => 0.00,
            'recargo_mora'       => 0.00,
            'monto_final'        => 0.00,
            'ufv_aplicada'       => 1.00000,
            'estado'             => 'Finalizado',
            'fecha_transmision'  => now()->subDays(30),
            'fecha_vencimiento'  => now()->subDays(10),
            'observaciones'      => 'Exención total por cónyuge',
            'user_id'            => $user->id,
            'created_by'         => $user->id,
            'updated_by'         => $user->id,
        ]);

        $inmueble3 = Inmueble::where('catastro', '16-7788-05-0606')->first();
        if ($inmueble3) {
            $tramite3->inmuebles()->attach($inmueble3->id);
        }
    }
}

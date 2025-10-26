<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tramite;
use App\Models\TipoTransmision;
use App\Models\User;

class TramiteSeeder extends Seeder
{
    public function run(): void
    {
        $userAdmin = User::find(1); // Assuming user with ID 1 is an admin
        $tipoTransmision1 = TipoTransmision::find(1); // Assuming a TipoTransmision with ID 1 exists
        $tipoTransmision2 = TipoTransmision::find(2); // Assuming a TipoTransmision with ID 2 exists

        // Tramite 1
        Tramite::updateOrCreate(
            ['nro_tramite' => 'BE-2025-0001'],
            [
                'fecha_presentacion' => now()->subDays(5),
                'tipo_transmision_id' => $tipoTransmision1->id,
                'valor_declarado' => 150000,
                'base_imponible' => 150000,
                'total_idtgb' => 7500,
                'monto_final' => 7500,
                'estado' => 'Finalizado',
                'fecha_transmision' => now()->subDays(10),
                'fecha_vencimiento' => now()->addDays(20),
                'user_id' => $userAdmin->id,
                'created_by' => $userAdmin->id,
                'updated_by' => $userAdmin->id,
            ]
        );

        // Tramite 2
        Tramite::updateOrCreate(
            ['nro_tramite' => 'BE-2025-0002'],
            [
                'fecha_presentacion' => now()->subDays(2),
                'tipo_transmision_id' => $tipoTransmision2->id,
                'valor_declarado' => 420000,
                'base_imponible' => 420000,
                'total_idtgb' => 21000,
                'monto_final' => 21000,
                'estado' => 'Finalizado',
                'fecha_transmision' => now()->subDays(5),
                'fecha_vencimiento' => now()->addDays(25),
                'user_id' => $userAdmin->id,
                'created_by' => $userAdmin->id,
                'updated_by' => $userAdmin->id,
            ]
        );
    }
}
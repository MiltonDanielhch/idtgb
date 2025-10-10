<?php

namespace Database\Seeders;

use App\Models\Pago;
use App\Models\Tramite;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        $userAdmin = 1; // ID del usuario admin

        // === Pago para Trámite 1: Herencia a hijo (7500 BOB) ===
        $tramite1 = Tramite::where('nro_tramite', 'BE-2025-0001')->first();
        if ($tramite1 && $tramite1->monto_final > 0) {
            Pago::updateOrCreate(
                ['tramite_id' => $tramite1->id],
                [
                    'fecha_pago' => now()->subDays(3),
                    'monto' => $tramite1->monto_final, // 7500.00
                    'codigo_barras' => '2222202500010000075000001', // Formato realista: entidad+gestión+nro+monto
                    'nro_operacion' => 'UNION-20250401-0001',
                    'banco' => 'Banco Unión',
                    'estado' => 'Aplicado',
                    'conciliado_el' => now()->subDays(2),
                    'created_by' => $userAdmin,
                    'updated_by' => $userAdmin,
                ]
            );
        }

        // === Pago para Trámite 2: Donación a tercero (21000 BOB) ===
        $tramite2 = Tramite::where('nro_tramite', 'BE-2025-0002')->first();
        if ($tramite2 && $tramite2->monto_final > 0) {
            Pago::updateOrCreate(
                ['tramite_id' => $tramite2->id],
                [
                    'fecha_pago' => now()->subDays(1),
                    'monto' => $tramite2->monto_final, // 21000.00
                    'codigo_barras' => '2222202500020000210000002',
                    'nro_operacion' => 'BISA-20250403-0002',
                    'banco' => 'Banco BISA',
                    'estado' => 'Aplicado',
                    'conciliado_el' => now(),
                    'created_by' => $userAdmin,
                    'updated_by' => $userAdmin,
                ]
            );
        }

        // === Trámite 3: Exento (0 BOB) → no requiere pago, pero podrías omitirlo ===
        // No se crea pago porque monto_final = 0
    }
}

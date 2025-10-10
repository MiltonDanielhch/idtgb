<?php

namespace Database\Seeders;

use App\Models\TramiteExencion;
use App\Models\Tramite;
use App\Models\Exencion;
use Illuminate\Database\Seeder;

class TramiteExencionSeeder extends Seeder
{
    public function run(): void
    {
        // === Trámite 3: Herencia a cónyuge → exención del 100% ===
        $tramiteConyuge = Tramite::where('nro_tramite', 'BE-2025-0003')->first();
        $exencionConyuge = Exencion::where('nombre', 'Cónyuge o conviviente')->first();

        if ($tramiteConyuge && $exencionConyuge) {
            // Monto aplicado = total IDTGB que se exime (en este caso, 0, pero se registra la exención)
            TramiteExencion::updateOrCreate(
                [
                    'tramite_id' => $tramiteConyuge->id,
                    'exencion_id' => $exencionConyuge->id,
                ],
                [
                    'monto_aplicado' => 0.00, // porque la tasa ya es 0%, pero se documenta
                ]
            );
        }

        // === Trámite hipotético: con discapacidad (si existiera) ===
        // $tramiteDiscap = Tramite::where('nro_tramite', 'BE-2025-0004')->first();
        // $exencionDiscap = Exencion::where('nombre', 'Discapacidad del adquirente')->first();
        // if ($tramiteDiscap && $exencionDiscap) {
        //     TramiteExencion::create([
        //         'tramite_id' => $tramiteDiscap->id,
        //         'exencion_id' => $exencionDiscap->id,
        //         'monto_aplicado' => 3750.00, // 50% de 7500
        //     ]);
        // }
    }
}

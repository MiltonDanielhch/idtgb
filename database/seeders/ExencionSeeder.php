<?php

namespace Database\Seeders;

use App\Models\Exencion;
use Illuminate\Database\Seeder;

class ExencionSeeder extends Seeder
{
    public function run(): void
    {
        $exenciones = [
            [
                'nombre'        => 'Cónyuge o conviviente',
                'descripcion'   => 'Exención total del IDTGB para cónyuge o conviviente (Ley IDTGB Beni)',
                'tipo'          => 'porcentaje',
                'valor'         => 100.00,
                'monto_maximo'  => null,
                'vigente_desde' => now()->format('Y-m-d'),
                'vigente_hasta' => null,
            ],
            [
                'nombre'        => 'Discapacidad del adquirente',
                'descripcion'   => 'Reducción del 50% del IDTGB si el adquirente tiene discapacidad certificada',
                'tipo'          => 'porcentaje',
                'valor'         => 50.00,
                'monto_maximo'  => null,
                'vigente_desde' => now()->format('Y-m-d'),
                'vigente_hasta' => null,
            ],
            [
                'nombre'        => 'Beneficio social - Vivienda de interés social',
                'descripcion'   => 'Descuento fijo de BOB 3.000 para viviendas con valor catastral ≤ BOB 250.000',
                'tipo'          => 'monto_fijo',
                'valor'         => 3000.00,
                'monto_maximo'  => 3000.00,
                'vigente_desde' => now()->format('Y-m-d'),
                'vigente_hasta' => null,
            ],
        ];

        foreach ($exenciones as $e) {
            Exencion::firstOrCreate(
                ['nombre' => $e['nombre']],
                $e
            );
        }
    }
}

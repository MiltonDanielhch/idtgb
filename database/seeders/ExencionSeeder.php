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
                'nombre'        => 'Vivienda única familiar',
                'descripcion'   => 'Exención del 100 % sobre el valor de una vivienda única (Beni 2025)',
                'tipo'          => 'porcentaje',
                'valor'         => 100.00,
                'monto_maximo'  => null,
                'vigente_desde' => '2025-01-01',
                'vigente_hasta' => null,
            ],
            [
                'nombre'        => 'Discapacidad causante',
                'descripcion'   => 'Reducción del 50 % si el causante era persona con discapacidad',
                'tipo'          => 'porcentaje',
                'valor'         => 50.00,
                'monto_maximo'  => null,
                'vigente_desde' => '2025-01-01',
                'vigente_hasta' => null,
            ],
            [
                'nombre'        => 'Zona rural catastral',
                'descripcion'   => 'Descuento fijo de BOB 2 000 en bienes rústicos',
                'tipo'          => 'monto_fijo',
                'valor'         => 2000.00,
                'monto_maximo'  => 2000.00,
                'vigente_desde' => '2025-01-01',
                'vigente_hasta' => null,
            ],
        ];

        foreach ($exenciones as $e) {
            Exencion::firstOrCreate(
                ['nombre' => $e['nombre'], 'vigente_desde' => $e['vigente_desde']],
                $e
            );
        }
    }
}

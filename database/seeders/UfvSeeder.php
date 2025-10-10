<?php

namespace Database\Seeders;

use App\Models\Ufv;
use Illuminate\Database\Seeder;

class UfvSeeder extends Seeder
{
    public function run(): void
    {
        // Sembrar UFVs recientes (últimos 30 días)
        $fechaInicio = now()->subDays(30);
        $ufvBase = 3.25000;

        for ($i = 0; $i <= 30; $i++) {
            $fecha = $fechaInicio->copy()->addDays($i);
            // Simular ligero incremento diario (inflación)
            $valor = $ufvBase + ($i * 0.0012);

            Ufv::updateOrCreate(
                ['fecha' => $fecha->format('Y-m-d')],
                ['valor' => round($valor, 5)]
            );
        }

        // Asegurar que haya una UFV para la fecha de hoy
        Ufv::updateOrCreate(
            ['fecha' => now()->format('Y-m-d')],
            ['valor' => round($ufvBase + 0.04, 5)] // Ej: 3.29000
        );
    }
}

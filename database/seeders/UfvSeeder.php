<?php
// database/seeders/UfvSeeder.php (La versión para pruebas)

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ufv;

class UfvSeeder extends Seeder
{
    public function run(): void
    {
        // Datos estáticos y predecibles para un entorno de pruebas
        $ufvs = [
            ['fecha' => '2023-01-01', 'valor' => 3.28],
            ['fecha' => '2023-06-01', 'valor' => 3.35],
            ['fecha' => '2023-12-01', 'valor' => 3.41],
            ['fecha' => '2024-01-01', 'valor' => 3.51],
            ['fecha' => '2024-06-01', 'valor' => 3.65],
            ['fecha' => '2024-12-01', 'valor' => 3.75],
        ];

        foreach ($ufvs as $ufv) {
            Ufv::firstOrCreate(
                ['fecha' => $ufv['fecha']],
                ['valor' => $ufv['valor']]
            );
        }
    }
}

// namespace Database\Seeders;

// use App\Models\Ufv;
// use Illuminate\Database\Seeder;

// class UfvSeeder extends Seeder
// {
//     public function run(): void
//     {
//         // Sembrar UFVs recientes (últimos 30 días)
//         $fechaInicio = now()->subDays(30);
//         $ufvBase = 3.25000;

//         for ($i = 0; $i <= 30; $i++) {
//             $fecha = $fechaInicio->copy()->addDays($i);
//             // Simular ligero incremento diario (inflación)
//             $valor = $ufvBase + ($i * 0.0012);

//             Ufv::updateOrCreate(
//                 ['fecha' => $fecha->format('Y-m-d')],
//                 ['valor' => round($valor, 5)]
//             );
//         }

//         // Asegurar que haya una UFV para la fecha de hoy
//         Ufv::updateOrCreate(
//             ['fecha' => now()->format('Y-m-d')],
//             ['valor' => round($ufvBase + 0.04, 5)] // Ej: 3.29000
//         );
//     }
// }

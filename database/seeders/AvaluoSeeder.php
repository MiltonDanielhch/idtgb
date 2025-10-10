<?php

namespace Database\Seeders;

use App\Models\Avaluo;
use App\Models\Inmueble;
use App\Models\Person;
use Illuminate\Database\Seeder;

class AvaluoSeeder extends Seeder
{
    public function run(): void
    {
        $userAdmin = 1; // Suponiendo que el admin tiene ID 1

        // === Avalúo Fiscal para inmueble urbano (Trinidad) ===
        $inmuebleUrbano = Inmueble::where('catastro', '12-3456-01-0101')->first();
        if ($inmuebleUrbano) {
            $perito1 = Person::where('ci', '1500004')->first(); // Una persona natural como perito

            Avaluo::updateOrCreate(
                ['inmueble_id' => $inmuebleUrbano->id, 'tipo_avaluo' => 'Fiscal'],
                [
                    'fecha_avaluo' => now()->subMonths(3),
                    'valor' => 500000.00,
                    'perito_id' => $perito1?->id,
                    'documento_path' => 'avaluos/fiscal_trinidad_2025.pdf',
                    'estado' => 'Vigente',
                    'created_by' => $userAdmin,
                    'updated_by' => $userAdmin,
                ]
            );
        }

        // === Avalúo Comercial para inmueble en Riberalta ===
        $inmuebleRiberalta = Inmueble::where('catastro', '13-1122-02-0303')->first();
        if ($inmuebleRiberalta) {
            $perito2 = Person::where('ci', '1500005')->first();

            Avaluo::updateOrCreate(
                ['inmueble_id' => $inmuebleRiberalta->id, 'tipo_avaluo' => 'Comercial'],
                [
                    'fecha_avaluo' => now()->subMonths(6),
                    'valor' => 420000.00,
                    'perito_id' => $perito2?->id,
                    'documento_path' => 'avaluos/comercial_riberalta_2025.pdf',
                    'estado' => 'Vigente',
                    'created_by' => $userAdmin,
                    'updated_by' => $userAdmin,
                ]
            );
        }

        // === Avalúo Pericial para inmueble rural (Santa Ana del Yacuma) ===
        $inmuebleRural = Inmueble::where('catastro', '15-5566-04-0505')->first();
        if ($inmuebleRural) {
            $perito3 = Person::where('ci', '1500006')->first();

            Avaluo::updateOrCreate(
                ['inmueble_id' => $inmuebleRural->id, 'tipo_avaluo' => 'Pericial'],
                [
                    'fecha_avaluo' => now()->subYears(2), // Más antiguo
                    'valor' => 200000.00,
                    'perito_id' => $perito3?->id,
                    'documento_path' => 'avaluos/pericial_yacuma_2023.pdf',
                    'estado' => 'Caducado', // Por antigüedad
                    'created_by' => $userAdmin,
                    'updated_by' => $userAdmin,
                ]
            );
        }

        // === Avalúo Fiscal caducado (para pruebas) ===
        if ($inmuebleUrbano) {
            Avaluo::updateOrCreate(
                ['inmueble_id' => $inmuebleUrbano->id, 'tipo_avaluo' => 'Fiscal', 'fecha_avaluo' => now()->subYears(3)],
                [
                    'valor' => 450000.00,
                    'perito_id' => null, // Sin perito
                    'documento_path' => 'avaluos/fiscal_antiguo_2022.pdf',
                    'estado' => 'Caducado',
                    'created_by' => $userAdmin,
                    'updated_by' => $userAdmin,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\DisponenteTramite;
use App\Models\Tramite;
use App\Models\Person;
use Illuminate\Database\Seeder;

class DisponenteTramiteSeeder extends Seeder
{
    public function run(): void
    {
        // === Trámite 1: Herencia → disponente fallecido ===
        $tramite1 = Tramite::where('nro_tramite', 'BE-2025-0001')->first();
        if ($tramite1) {
            // Buscar una persona fallecida (ej. Pedro Méndez)
            $personaFallecida = Person::where('first_name', 'Pedro')
                                      ->where('paternal_surname', 'Méndez')
                                      ->first();

            if ($personaFallecida) {
                DisponenteTramite::updateOrCreate(
                    [
                        'tramite_id' => $tramite1->id,
                        'persona_id' => $personaFallecida->id,
                    ],
                    [
                        'tipo' => 'Causante',
                        'fecha_fallecimiento' => now()->subMonths(2),
                        'es_discapacitado' => false,
                    ]
                );
            }
        }

        // === Trámite 2: Donación → disponente activo ===
        $tramite2 = Tramite::where('nro_tramite', 'BE-2025-0002')->first();
        if ($tramite2) {
            // Usar una persona jurídica o natural activa (ej. Gobernación o una persona)
            $personaDonante = Person::where('legal_name', 'GOBERNACIÓN DEL DEPARTAMENTO DE BENI')->first()
                             ?? Person::where('ci', '1500003')->first();

            if ($personaDonante) {
                DisponenteTramite::updateOrCreate(
                    [
                        'tramite_id' => $tramite2->id,
                        'persona_id' => $personaDonante->id,
                    ],
                    [
                        'tipo' => 'Donante',
                        'fecha_fallecimiento' => null, // ¡No aplica en donación!
                        'es_discapacitado' => false,
                    ]
                );
            }
        }

        // === Trámite 3: Herencia (otro caso) ===
        $tramite3 = Tramite::where('nro_tramite', 'BE-2025-0003')->first();
        if ($tramite3 && isset($personaFallecida)) {
            DisponenteTramite::updateOrCreate(
                [
                    'tramite_id' => $tramite3->id,
                    'persona_id' => $personaFallecida->id,
                ],
                [
                    'tipo' => 'Causante',
                    'fecha_fallecimiento' => now()->subMonths(2),
                    'es_discapacitado' => false,
                ]
            );
        }
    }
}

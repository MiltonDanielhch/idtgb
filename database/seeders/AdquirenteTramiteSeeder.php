<?php

namespace Database\Seeders;

use App\Models\AdquirenteTramite;
use App\Models\Tramite;
use App\Models\Person;
use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class AdquirenteTramiteSeeder extends Seeder
{
    public function run(): void
    {
        // === Trámite 1: Herencia a hijo ===
        $tramite1 = Tramite::where('nro_tramite', 'BE-2025-0001')->first();
        if ($tramite1) {
            $parentescoHijo = Parentesco::where('nombre', 'Hijo/a')->first();
            // Usar una persona real del Beni (primera persona natural con CI BN)
            $personaHijo = Person::where('ci', '1500000')->where('ci_complemento', 'BN')->first();

            if ($parentescoHijo && $personaHijo) {
                AdquirenteTramite::updateOrCreate(
                    [
                        'tramite_id' => $tramite1->id,
                        'persona_id' => $personaHijo->id,
                    ],
                    [
                        'parentesco_id' => $parentescoHijo->id,
                        'tasa_aplicada' => 1.50, // ✅ Tasa real del Beni
                        'porcentaje' => 100.00,
                        'idtgb_proporcional' => 7500.00, // 1.5% de 500,000
                        'es_beneficiario_exencion' => false,
                        'documento_sustento_exencion' => null,
                    ]
                );
            }
        }

        // === Trámite 2: Donación a tercero (sin parentesco) ===
        $tramite2 = Tramite::where('nro_tramite', 'BE-2025-0002')->first();
        if ($tramite2) {
            $parentescoSin = Parentesco::where('nombre', 'Sin parentesco')->first();
            $personaTercero = Person::where('ci', '1500001')->where('ci_complemento', 'BN')->first();

            if ($parentescoSin && $personaTercero) {
                AdquirenteTramite::updateOrCreate(
                    [
                        'tramite_id' => $tramite2->id,
                        'persona_id' => $personaTercero->id,
                    ],
                    [
                        'parentesco_id' => $parentescoSin->id,
                        'tasa_aplicada' => 5.00, // ✅ Tasa real del Beni
                        'porcentaje' => 100.00,
                        'idtgb_proporcional' => 21000.00, // 5% de 420,000
                        'es_beneficiario_exencion' => false,
                        'documento_sustento_exencion' => null,
                    ]
                );
            }
        }

        // === Trámite 3: Herencia a cónyuge ===
        $tramite3 = Tramite::where('nro_tramite', 'BE-2025-0003')->first();
        if ($tramite3) {
            $parentescoConyuge = Parentesco::where('nombre', 'Cónyuge o Conviviente')->first();
            $personaConyuge = Person::where('ci', '1500002')->where('ci_complemento', 'BN')->first();

            if ($parentescoConyuge && $personaConyuge) {
                AdquirenteTramite::updateOrCreate(
                    [
                        'tramite_id' => $tramite3->id,
                        'persona_id' => $personaConyuge->id,
                    ],
                    [
                        'parentesco_id' => $parentescoConyuge->id,
                        'tasa_aplicada' => 0.00, // ✅ Exento
                        'porcentaje' => 100.00,
                        'idtgb_proporcional' => 0.00,
                        'es_beneficiario_exencion' => true,
                        'documento_sustento_exencion' => 'cert_matrimonio.pdf',
                    ]
                );
            }
        }
    }
}

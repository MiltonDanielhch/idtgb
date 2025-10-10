<?php

namespace Database\Seeders;

use App\Models\Documento;
use App\Models\Tramite;
use App\Models\Person;
use Illuminate\Database\Seeder;

class DocumentoSeeder extends Seeder
{
    public function run(): void
    {
        // === Trámite 1: Herencia (BE-2025-0001) ===
        $tramite1 = Tramite::where('nro_tramite', 'BE-2025-0001')->first();
        if ($tramite1) {
            // Disponente: Pedro Méndez (fallecido)
            $disponente1 = Person::where('first_name', 'Pedro')
                                 ->where('paternal_surname', 'Méndez')
                                 ->first();
            // Adquirente: primera persona natural (hijo)
            $adquirente1 = Person::where('ci', '1500000')->first();

            if ($disponente1 && $adquirente1) {
                // Partida de defunción del disponente
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite1->id, 'tipo_doc' => 'Partida', 'persona_id' => $disponente1->id],
                    [
                        'file_path' => 'documentos/BE-2025-0001/partida_defuncion_pedro.pdf',
                        'hash_sha256' => 'a1b2c3d4e5f67890123456789012345678901234567890123456789012345678', // hash simulado
                        'vigente' => true,
                        'version' => 1,
                    ]
                );

                // CI del adquirente (hijo)
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite1->id, 'tipo_doc' => 'CI', 'persona_id' => $adquirente1->id],
                    [
                        'file_path' => 'documentos/BE-2025-0001/ci_hijo.pdf',
                        'hash_sha256' => 'b2c3d4e5f6789012345678901234567890123456789012345678901234567890',
                        'vigente' => true,
                        'version' => 1,
                    ]
                );

                // Testamento (a nombre del disponente)
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite1->id, 'tipo_doc' => 'Testamento', 'persona_id' => $disponente1->id],
                    [
                        'file_path' => 'documentos/BE-2025-0001/testamento_pedro.pdf',
                        'hash_sha256' => 'c3d4e5f678901234567890123456789012345678901234567890123456789012',
                        'vigente' => true,
                        'version' => 1,
                    ]
                );
            }
        }

        // === Trámite 2: Donación (BE-2025-0002) ===
        $tramite2 = Tramite::where('nro_tramite', 'BE-2025-0002')->first();
        if ($tramite2) {
            // Disponente: Gobernación del Beni
            $disponente2 = Person::where('legal_name', 'GOBERNACIÓN DEL DEPARTAMENTO DE BENI')->first();
            // Adquirente: tercero
            $adquirente2 = Person::where('ci', '1500001')->first();

            if ($disponente2 && $adquirente2) {
                // Escritura de donación
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite2->id, 'tipo_doc' => 'Escritura', 'persona_id' => $disponente2->id],
                    [
                        'file_path' => 'documentos/BE-2025-0002/escritura_donacion.pdf',
                        'hash_sha256' => 'd4e5f67890123456789012345678901234567890123456789012345678901234',
                        'vigente' => true,
                        'version' => 1,
                    ]
                );

                // CI del adquirente
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite2->id, 'tipo_doc' => 'CI', 'persona_id' => $adquirente2->id],
                    [
                        'file_path' => 'documentos/BE-2025-0002/ci_tercero.pdf',
                        'hash_sha256' => 'e5f6789012345678901234567890123456789012345678901234567890123456',
                        'vigente' => true,
                        'version' => 1,
                    ]
                );
            }
        }

        // === Trámite 3: Herencia a cónyuge (BE-2025-0003) ===
        $tramite3 = Tramite::where('nro_tramite', 'BE-2025-0003')->first();
        if ($tramite3) {
            $disponente3 = Person::where('first_name', 'Pedro')
                                 ->where('paternal_surname', 'Méndez')
                                 ->first();
            $adquirente3 = Person::where('ci', '1500002')->first(); // cónyuge

            if ($disponente3 && $adquirente3) {
                // Partida de defunción
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite3->id, 'tipo_doc' => 'Partida', 'persona_id' => $disponente3->id],
                    [
                        'file_path' => 'documentos/BE-2025-0003/partida_defuncion_pedro2.pdf',
                        'hash_sha256' => 'f678901234567890123456789012345678901234567890123456789012345678',
                        'vigente' => true,
                        'version' => 1,
                    ]
                );

                // Certificado de matrimonio (como sustento de exención)
                Documento::updateOrCreate(
                    ['tramite_id' => $tramite3->id, 'tipo_doc' => 'Otro', 'persona_id' => $adquirente3->id],
                    [
                        'file_path' => 'documentos/BE-2025-0003/cert_matrimonio.pdf',
                        'hash_sha256' => '7890123456789012345678901234567890123456789012345678901234567890',
                        'vigente' => true,
                        'version' => 1,
                    ]
                );
            }
        }
    }
}

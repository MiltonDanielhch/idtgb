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
        $tramite = Tramite::first()->id;
        $persona = Person::first()->id;

        $docs = [
            ['tipo_doc' => 'CI',           'file' => 'ci_juan.pdf'],
            ['tipo_doc' => 'Escritura',    'file' => 'escritura_herencia.pdf'],
            ['tipo_doc' => 'Avaluo',       'file' => 'avaluo_fiscal.pdf'],
        ];

        foreach ($docs as $d) {
            Documento::create([
                'tramite_id'     => $tramite,
                'tipo_doc'       => $d['tipo_doc'],
                'file_path'      => 'documentos/' . $d['file'],
                'hash_sha256'    => hash('sha256', $d['file']),
                'persona_id'     => $persona,
                'vigente'        => true,
                'version'        => 1,
            ]);
        }
    }
}

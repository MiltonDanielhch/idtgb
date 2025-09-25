<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Person;
use App\Models\Inmueble;
use App\Models\Municipio;

class CalculadoraDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Persona Natural
        Person::firstOrCreate(
            ['ci' => '0000001'],
            [
                'person_type'        => 'Natural',
                'first_name'         => 'Juan',
                'paternal_surname'   => 'Pérez',
                'maternal_surname'   => 'López',
                'birth_date'         => '1980-01-01',
                'email'              => 'juan.perez@mail.com',
                'phone'              => '70000001',
                'address'            => 'Calle Avaroa #123, Trinidad',
                'status'             => 1,
                'estado_persona'     => 'Activo',
            ]
        );

        // 2) Persona Jurídica
        Person::firstOrCreate(
            ['nit' => '000000001'],
            [
                'person_type'        => 'Jurídica',
                'legal_name'         => 'Empresa de Prueba S.R.L.',
                'email'              => 'info@empresaprueba.com',
                'phone'              => '70000002',
                'address'            => 'Av. Beni #456, Trinidad',
                'status'             => 1,
                'estado_persona'     => 'Activo',
            ]
        );

        // 3) Inmueble de demostración (Beni)
        $municipio = Municipio::whereHas('provincia.departamento', fn($q) => $q->where('nombre', 'Beni'))
                                ->first();

        Inmueble::firstOrCreate(
            ['catastro' => 'BE-DEMO-01-0101'],
            [
                'municipio_id'               => $municipio?->id ?? 1,
                'tipo_inmueble_id'           => 1,
                'direccion'                  => 'Calle Demo #100, Trinidad',
                'superficie_m2'              => 250.00,
                'valor_catastral'            => 100000.00,
                'matricula_rr'               => 'RR-DEMO-2025',
                'es_vivienda_unica_familiar' => true,
                'estado_inmueble'            => 'Activo',
            ]
        );
    }
}

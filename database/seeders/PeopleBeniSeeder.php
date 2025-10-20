<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Municipio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PeopleBeniSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener los IDs de los municipios principales del Beni
        $municipioTrinidad = Municipio::where('nombre', 'Trinidad')->first();
        $municipioRiberalta = Municipio::where('nombre', 'Riberalta')->first();
        $municipioSanBorja = Municipio::where('nombre', 'San Borja')->first();

        // Lista de IDs válidos para distribución aleatoria de personas naturales
        $municipioIds = array_filter([
            $municipioTrinidad?->id,
            $municipioRiberalta?->id,
            $municipioSanBorja?->id,
        ]);

        // Asignación por defecto a Trinidad si no se encuentra ningún municipio (evitar errores)
        $defaultMunicipioId = $municipioTrinidad?->id ?? null;


        // === PERSONAS NATURALES DEL BENI ===
        $personasNaturales = [
            // Trinidad (Capital)
            ['José', 'Antonio', 'Mendoza', 'Roca', 'Trinidad'],
            ['Rosa', 'María', 'Cubas', 'Barrientos', 'Trinidad'],
            ['Juan', 'Carlos', 'Villalba', 'Añez', 'Trinidad'],
            ['Carmen', 'Elena', 'Paredes', 'Gutiérrez', 'Trinidad'],

            // Riberalta (Ciudad secundaria importante)
            ['Fernando', 'Andrés', 'Suárez', 'Mamani', 'Riberalta'],
            ['Luz', 'Adriana', 'Quispe', 'Torrico', 'Riberalta'],

            // San Borja (Otra ciudad importante)
            ['Mario', 'Raúl', 'Rivero', 'Camacho', 'San Borja'],
            ['Yolanda', 'Beatriz', 'Flores', 'Aguilar', 'San Borja'],

            // Resto de Trinidad
            ['Hugo', 'Manuel', 'Ríos', 'Vargas', 'Trinidad'],
            ['Dora', 'Esther', 'López', 'Medina', 'Trinidad'],
            ['Ernesto', 'Javier', 'González', 'Rojas', 'Trinidad'],
            ['Silvia', 'Patricia', 'Ortiz', 'Chávez', 'Trinidad'],
            ['Walter', 'David', 'Barrientos', 'Pérez', 'Trinidad'],
            ['Norma', 'Lucía', 'Añez', 'Mendoza', 'Trinidad'],
            ['Carlos', 'Eduardo', 'Torrico', 'Suárez', 'Trinidad'],
            ['Pedro', 'José', 'Méndez', 'Rojas', 'Trinidad'], // ← Fallecido
        ];

        foreach ($personasNaturales as $i => $n) {
            $nombreMunicipio = $n[4];
            $isFallecido = ($n[0] === 'Pedro');

            // Asigna el ID del municipio correspondiente
            $municipioModel = Municipio::where('nombre', $nombreMunicipio)->first();
            $municipioId = $municipioModel?->id ?? $defaultMunicipioId;

            Person::create([
                'person_type'       => 'Natural',
                'tipo_doc'          => 'CI',
                'ci'                => sprintf('%07d', 1_500_000 + $i),
                'ci_complemento'    => 'BN',
                'first_name'        => $n[0],
                'middle_name'       => $n[1],
                'paternal_surname'  => $n[2],
                'maternal_surname'  => $n[3],
                'birth_date'        => now()->subYears($isFallecido ? rand(70, 85) : rand(28, 65))->format('Y-m-d'),
                'email'             => strtolower($n[0].'.'.$n[2]).'@gmail.com',
                'phone'             => '+591 3 '.rand(1000000, 9999999),
                'address'           => 'Av. '.Str::random(6).' #'.rand(100, 999).', '.$nombreMunicipio.', Beni',
                'municipio_id'      => $municipioId, // ✅ Campo añadido
                'gender'            => in_array($n[0], ['José', 'Juan', 'Fernando', 'Mario', 'Hugo', 'Ernesto', 'Walter', 'Carlos', 'Pedro']) ? 'Masculino' : 'Femenino',
                'status'            => 1,
                'estado_persona'    => $isFallecido ? 'Fallecido' : 'Activo',
                'registerUser_id'   => 1,
                'registerRole'      => 'admin',
            ]);
        }

        // === PERSONAS JURÍDICAS DEL BENI ===
        $personasJuridicas = [
            [
                'legal_name' => 'GOBERNACIÓN DEL DEPARTAMENTO DE BENI',
                'nit'        => '456789012',
                'email'      => 'info@beni.gob.bo',
                'phone'      => '+591 3 3522222',
                'address'    => 'Av. 18 de Noviembre esq. Sucre, Trinidad, Beni',
                'municipio'  => 'Trinidad',
            ],
            [
                'legal_name' => 'MUNICIPIO DE TRINIDAD',
                'nit'        => '123456789',
                'email'      => 'contacto@trinidad.bo',
                'phone'      => '+591 3 3521111',
                'address'    => 'Plaza Suárez s/n, Trinidad, Beni',
                'municipio'  => 'Trinidad',
            ],
            [
                'legal_name' => 'NOTARÍA DE FE PÚBLICA Nº 1 DE TRINIDAD',
                'nit'        => '789012345',
                'email'      => 'notaria1.trinidad@gmail.com',
                'phone'      => '+591 3 3523333',
                'address'    => 'Calle Pando #250, Trinidad, Beni',
                'municipio'  => 'Trinidad',
            ],
            [
                'legal_name' => 'FUNDACIÓN PARA EL DESARROLLO DEL BENI',
                'nit'        => '654321098',
                'email'      => 'fundacion.beni@gmail.com',
                'phone'      => '+591 3 3524444',
                'address'    => 'Av. América #500, Trinidad, Beni',
                'municipio'  => 'Trinidad',
            ],
            [
                'legal_name' => 'MUNICIPIO DE RIBERALTA',
                'nit'        => '234567890',
                'email'      => 'municipalidad@riberalta.gob.bo',
                'phone'      => '+591 38 8321111',
                'address'    => 'Plaza 15 de Abril s/n, Riberalta, Beni',
                'municipio'  => 'Riberalta',
            ],
        ];

        foreach ($personasJuridicas as $pj) {
            // Asigna el ID del municipio correspondiente para las personas jurídicas
            $municipioModel = Municipio::where('nombre', $pj['municipio'])->first();
            $municipioId = $municipioModel?->id ?? $defaultMunicipioId;

            Person::create([
                'person_type'       => 'Jurídica',
                'tipo_doc'          => 'NIT',
                'nit'               => $pj['nit'],
                'legal_name'        => $pj['legal_name'],
                'email'             => $pj['email'],
                'phone'             => $pj['phone'],
                'address'           => $pj['address'],
                'municipio_id'      => $municipioId, // ✅ Campo añadido
                'status'            => 1,
                'estado_persona'    => 'Activo',
                'registerUser_id'   => 1,
                'registerRole'      => 'admin',
            ]);
        }
    }
}

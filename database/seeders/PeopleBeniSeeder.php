<?php

namespace Database\Seeders;

use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PeopleBeniSeeder extends Seeder
{
    public function run(): void
    {
        // === PERSONAS NATURALES DEL BENI ===
        $personasNaturales = [
            ['José', 'Antonio', 'Mendoza', 'Roca'],
            ['Rosa', 'María', 'Cubas', 'Barrientos'],
            ['Juan', 'Carlos', 'Villalba', 'Añez'],
            ['Carmen', 'Elena', 'Paredes', 'Gutiérrez'],
            ['Fernando', 'Andrés', 'Suárez', 'Mamani'],
            ['Luz', 'Adriana', 'Quispe', 'Torrico'],
            ['Mario', 'Raúl', 'Rivero', 'Camacho'],
            ['Yolanda', 'Beatriz', 'Flores', 'Aguilar'],
            ['Hugo', 'Manuel', 'Ríos', 'Vargas'],
            ['Dora', 'Esther', 'López', 'Medina'],
            ['Ernesto', 'Javier', 'González', 'Rojas'],
            ['Silvia', 'Patricia', 'Ortiz', 'Chávez'],
            ['Walter', 'David', 'Barrientos', 'Pérez'],
            ['Norma', 'Lucía', 'Añez', 'Mendoza'],
            ['Carlos', 'Eduardo', 'Torrico', 'Suárez'],
            ['Pedro', 'José', 'Méndez', 'Rojas'], // ← Este será fallecido
        ];

        foreach ($personasNaturales as $i => $n) {
            $isFallecido = ($n[0] === 'Pedro');

            Person::create([
                'person_type'        => 'Natural',
                'tipo_doc'           => 'CI',
                'ci'                 => sprintf('%07d', 1_500_000 + $i),
                'ci_complemento'     => 'BN',
                'first_name'         => $n[0],
                'middle_name'        => $n[1],
                'paternal_surname'   => $n[2],
                'maternal_surname'   => $n[3],
                'birth_date'         => now()->subYears($isFallecido ? rand(70, 85) : rand(28, 65)),
                'email'              => strtolower($n[0].'.'.$n[2]).'@gmail.com',
                'phone'              => '+591 3 '.rand(1000000, 9999999),
                'address'            => 'Av. '.Str::random(6).' #'.rand(100, 999).', Trinidad, Beni',
                'gender'             => in_array($n[0], ['José', 'Juan', 'Fernando', 'Mario', 'Hugo', 'Ernesto', 'Walter', 'Carlos', 'Pedro']) ? 'Masculino' : 'Femenino',
                'status'             => 1,
                'estado_persona'     => $isFallecido ? 'Fallecido' : 'Activo', // ✅ Solo una vez
                'registerUser_id'    => 1,
                'registerRole'       => 'admin',
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
            ],
            [
                'legal_name' => 'MUNICIPIO DE TRINIDAD',
                'nit'        => '123456789',
                'email'      => 'contacto@trinidad.bo',
                'phone'      => '+591 3 3521111',
                'address'    => 'Plaza Suárez s/n, Trinidad, Beni',
            ],
            [
                'legal_name' => 'NOTARÍA DE FE PÚBLICA Nº 1 DE TRINIDAD',
                'nit'        => '789012345',
                'email'      => 'notaria1.trinidad@gmail.com',
                'phone'      => '+591 3 3523333',
                'address'    => 'Calle Pando #250, Trinidad, Beni',
            ],
            [
                'legal_name' => 'FUNDACIÓN PARA EL DESARROLLO DEL BENI',
                'nit'        => '654321098',
                'email'      => 'fundacion.beni@gmail.com',
                'phone'      => '+591 3 3524444',
                'address'    => 'Av. América #500, Trinidad, Beni',
            ],
            [
                'legal_name' => 'MUNICIPIO DE RIBERALTA',
                'nit'        => '234567890',
                'email'      => 'municipalidad@riberalta.gob.bo',
                'phone'      => '+591 38 8321111',
                'address'    => 'Plaza 15 de Abril s/n, Riberalta, Beni',
            ],
        ];

        foreach ($personasJuridicas as $pj) {
            Person::create([
                'person_type'        => 'Jurídica',
                'tipo_doc'           => 'NIT',
                'nit'                => $pj['nit'],
                'legal_name'         => $pj['legal_name'],
                'email'              => $pj['email'],
                'phone'              => $pj['phone'],
                'address'            => $pj['address'],
                'status'             => 1,
                'estado_persona'     => 'Activo',
                'registerUser_id'    => 1,
                'registerRole'       => 'admin',
            ]);
        }
    }
}

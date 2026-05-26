<?php

namespace Database\Seeders;

use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PeopleBeniSeeder extends Seeder
{
    public function run(): void
    {


        // === PERSONAS NATURALES DEL BENI ===
        $personasNaturales = [
            'José Antonio Mendoza Roca',
            'Rosa María Cubas Barrientos',
            'Juan Carlos Villalba Añez',
            'Carmen Elena Paredes Gutiérrez',
            'Fernando Andrés Suárez Mamani',
            'Luz Adriana Quispe Torrico',
            'Mario Raúl Rivero Camacho',
            'Yolanda Beatriz Flores Aguilar',
            'Hugo Manuel Ríos Vargas',
            'Dora Esther López Medina',
            'Ernesto Javier González Rojas',
            'Silvia Patricia Ortiz Chávez',
            'Walter David Barrientos Pérez',
            'Norma Lucía Añez Mendoza',
            'Carlos Eduardo Torrico Suárez',
            'Pedro José Méndez Rojas',
        ];

        foreach ($personasNaturales as $i => $nombre) {
            Person::create([
                'person_type'       => 'Natural',
                'tipo_doc'          => 'CI',
                'ci'                => sprintf('%07d', 1_500_000 + $i),
                'ci_complemento'    => 'BN',
                'nombre_completo'   => $nombre,
                'phone'             => '+591 3 '.rand(1000000, 9999999),
                'registerUser_id'   => 1,
                'registerRole'      => 'admin',
            ]);
        }

        // === PERSONAS JURÍDICAS DEL BENI ===
        $personasJuridicas = [
            [
                'legal_name' => 'GOBERNACIÓN DEL DEPARTAMENTO DE BENI',
                'nit'        => '456789012',
                'phone'      => '+591 3 3522222',
            ],
            [
                'legal_name' => 'MUNICIPIO DE TRINIDAD',
                'nit'        => '123456789',
                'phone'      => '+591 3 3521111',
            ],
            [
                'legal_name' => 'NOTARÍA DE FE PÚBLICA Nº 1 DE TRINIDAD',
                'nit'        => '789012345',
                'phone'      => '+591 3 3523333',
            ],
            [
                'legal_name' => 'FUNDACIÓN PARA EL DESARROLLO DEL BENI',
                'nit'        => '654321098',
                'phone'      => '+591 3 3524444',
            ],
            [
                'legal_name' => 'MUNICIPIO DE RIBERALTA',
                'nit'        => '234567890',
                'phone'      => '+591 38 8321111',
            ],
        ];

        foreach ($personasJuridicas as $pj) {
            Person::create([
                'person_type'       => 'Jurídica',
                'tipo_doc'          => 'NIT',
                'nit'               => $pj['nit'],
                'legal_name'        => $pj['legal_name'],
                'phone'             => $pj['phone'],
                'registerUser_id'   => 1,
                'registerRole'      => 'admin',
            ]);
        }
    }
}

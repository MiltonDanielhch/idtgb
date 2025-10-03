<?php
// database/seeders/PersonasActivasSeeder.php
namespace Database\Seeders;

use App\Models\Person;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PersonasActivasSeeder extends Seeder
{
    public function run(): void
    {
        $nombres = [
            ['Luis', 'Eduardo', 'Rojas', 'Pérez'],
            ['María', 'Elena', 'González', 'López'],
            ['Carlos', 'Alberto', 'Mamani', 'Quispe'],
            ['Ana', 'Lucía', 'Torres', 'Suárez'],
            ['Jorge', 'Manuel', 'Aguilar', 'Vargas'],
            ['Sandra', 'Patricia', 'Flores', 'Romero'],
            ['Roberto', 'Carlos', 'Mendoza', 'Silva'],
            ['Lucía', 'Andrea', 'Campos', 'Ramos'],
            ['Diego', 'Fernando', 'Herrera', 'Soto'],
            ['Valeria', 'Alejandra', 'Medina', 'Cruz'],
        ];

        foreach ($nombres as $i => $n) {
            Person::create([
                'person_type'        => 'Natural',
                'tipo_doc'           => 'CI',
                'ci'                 => sprintf('%07d', 1_000_000 + $i),
                'ci_complemento'     => Str::random(2),
                'first_name'         => $n[0],
                'middle_name'        => $n[1],
                'paternal_surname'   => $n[2],
                'maternal_surname'   => $n[3],
                'birth_date'         => now()->subYears(rand(25, 60)),
                'email'              => strtolower($n[0].'.'.$n[2]).'@mailinator.com',
                'phone'              => '+591 '.rand(60000000, 79999999),
                'address'            => 'Calle '.Str::random(6).' #'.rand(100, 999),
                'gender'             => rand(0, 1) ? 'Masculino' : 'Femenino',
                'status'             => Person::STATUS_ACTIVE,
                'estado_persona'     => 'Activo',
                'registerUser_id'    => 1, // admin
                'registerRole'       => 'admin',
            ]);
        }
    }
}

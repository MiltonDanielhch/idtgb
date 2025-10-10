<?php

namespace Database\Seeders;

use App\Models\Tasa;
use App\Models\Departamento;
use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class TasaSeeder extends Seeder
{
    public function run(): void
    {
        $beni = Departamento::where('codigo', 'BE')->firstOrFail();
        $hoy = now()->format('Y-m-d');

        // Mapeo: nombre de parentesco → tasa del Beni
        $tasasBeni = [
            'Cónyuge o Conviviente' => 0.00,
            'Hijo/a'                => 1.50,
            'Padre/Madre'           => 3.00,
            'Hermano/a'             => 3.00,
            'Nieto/a'               => 3.00,
            'Abuelo/a'              => 3.00,
            'Tío/a o Sobrino/a'     => 5.00,
            'Sin parentesco'        => 5.00,
        ];

        foreach ($tasasBeni as $nombreParentesco => $tasa) {
            $parentesco = Parentesco::where('nombre', $nombreParentesco)->first();

            if (!$parentesco) {
                // Si usaste nombres ligeramente distintos, ajusta aquí
                // Ej: si tienes "Cónyuge" en lugar de "Cónyuge o Conviviente"
                $alternativas = [
                    'Cónyuge' => 'Cónyuge o Conviviente',
                    'Hijo' => 'Hijo/a',
                    'Padre' => 'Padre/Madre',
                    'Hermano' => 'Hermano/a',
                    'Nieto' => 'Nieto/a',
                    'Abuelo' => 'Abuelo/a',
                    'Tío' => 'Tío/a o Sobrino/a',
                    'Sobrino' => 'Tío/a o Sobrino/a',
                ];
                foreach ($alternativas as $key => $value) {
                    if (str_contains($nombreParentesco, $key)) {
                        $parentesco = Parentesco::where('nombre', 'like', "%{$key}%")->first();
                        break;
                    }
                }
            }

            if ($parentesco) {
                Tasa::firstOrCreate(
                    [
                        'departamento_id' => $beni->id,
                        'parentesco_id'   => $parentesco->id,
                        'vigente_desde'   => $hoy,
                    ],
                    [
                        'tasa'          => $tasa,
                        'vigente_hasta' => null,
                    ]
                );
            }
        }
    }
}

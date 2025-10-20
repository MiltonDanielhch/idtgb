<?php

namespace Database\Seeders;

use App\Models\Tasa;
use App\Models\Departamento;
use App\Models\Parentesco;
use Illuminate\Database\Seeder;

class TasaSeeder extends Seeder
{
    /**
     * Define y persiste las tasas de impuestos para el Departamento del Beni (IDTGB).
     *
     * NOTA IMPORTANTE: Estas tasas siguen las alícuotas del Impuesto Departamental a la
     * Transmisión Gratuita de Bienes (IDTGB) para Beni (Ley Departamental N° 48).
     * Los valores se almacenan como porcentajes.
     * * Categorías: 1% (Línea directa), 10% (Hermanos), 20% (Otros).
     */
    public function run(): void
    {
        // 1. Obtener el Departamento de Beni
        $beni = Departamento::where('codigo', 'BE')->firstOrFail();
        $hoy = now()->format('Y-m-d');

        // Mapeo: nombre de parentesco → Tasa Legal del Beni (en porcentaje)
        $tasasBeniLegales = [
            // CÓDIGO 1%: Ascendientes, descendientes y cónyuge.
            'Cónyuge o Conviviente' => 1.00,
            'Hijo/a'                => 1.00,
            'Padre/Madre'           => 1.00,
            'Nieto/a'               => 1.00,
            'Abuelo/a'              => 1.00,

            // CÓDIGO 10%: Hermanos y sus descendientes.
            'Hermano/a'             => 10.00,

            // CÓDIGO 20%: Otros colaterales, legatarios y donatarios gratuitos (incluye tíos/sobrinos y sin parentesco).
            'Tío/a o Sobrino/a'     => 20.00,
            'Sin parentesco'        => 20.00,
        ];

        foreach ($tasasBeniLegales as $nombreParentesco => $tasa) {
            // 2. Intentar buscar el Parentesco por nombre exacto
            $parentesco = Parentesco::where('nombre', $nombreParentesco)->first();

            // 3. Lógica de búsqueda flexible (si el nombre exacto falla)
            if (!$parentesco) {
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

                // Buscar por LIKE en el nombre de parentesco, basado en palabras clave
                foreach ($alternativas as $key => $value) {
                    if (str_contains($nombreParentesco, $key)) {
                        $parentesco = Parentesco::where('nombre', 'like', "%{$key}%")->first();
                        break;
                    }
                }
            }

            // 4. Crear o actualizar el registro de la Tasa
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

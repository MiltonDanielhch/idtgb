<?php

namespace Database\Factories;

use App\Models\Municipio;
use App\Models\TipoInmueble;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inmueble>
 */
class InmuebleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'catastro' => $this->faker->unique()->numerify('##-####-##-####'),
            'tipo_inmueble_id' => TipoInmueble::factory(),
            'municipio_id' => Municipio::factory(),
            'direccion' => $this->faker->streetAddress(),
            'superficie_m2' => $this->faker->randomFloat(2, 50, 1000),
            'valor_catastral' => $this->faker->randomFloat(2, 10000, 500000),
            'es_vivienda_unica_familiar' => $this->faker->boolean(),
        ];
    }
}

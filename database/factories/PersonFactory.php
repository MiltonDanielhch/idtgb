<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'person_type' => 'Natural',
            'tipo_doc' => 'CI',
            'ci' => $this->faker->unique()->numerify('#######'),
            'first_name' => $this->faker->firstName,
            'paternal_surname' => $this->faker->lastName,
            'maternal_surname' => $this->faker->lastName,
            'birth_date' => $this->faker->date(),
        ];
    }
}
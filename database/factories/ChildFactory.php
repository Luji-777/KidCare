<?php

namespace Database\Factories;

use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        return [

            'first_name' => $faker->firstName(),
            'last_name' => $faker->lastName(),
            'gender' => $faker->randomElement(['male', 'female']),

            'birth_date' => $faker->date('Y-m-d', '-1 years'),
            'blood_type' => $faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'medical_history' => $faker->paragraph,
            'allergies' => $faker->randomElement(['peanut', 'sesame', 'berry', null]),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [

            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'gender' => $this->faker->randomElement(['male', 'female']),

            'birth_date' => $this->faker->date('Y-m-m', '-1 years'),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'medical_history' => $this->faker->paragraph,
            'allergies' => $this->faker->randomElement(['peanut', 'sesame', 'berrie', null]),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DoctorFactory extends Factory
{

    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone_number' => '9639' . $this->faker->numerify('########'),
            'password' => Hash::make('doctor123'),
            'address' => $this->faker->randomElement(['Damascus, Mezzeh', 'Damascus, Abu Rummaneh', 'Homs', 'Latakia']),
            'experience_years' => $this->faker->numberBetween(3, 20),
            'education' => $this->faker->randomElement(['PhD in Pediatrics', 'Master of Child Psychology', 'Board Certified Pediatric Surgeon', 'General Practitioner']),
            'fee' => $this->faker->randomElement([30000, 45000, 50000, 70000]),
            'commission_percentage' => 60,
            'profile_picture' => null,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'cv' => null,
        ];
    }
}

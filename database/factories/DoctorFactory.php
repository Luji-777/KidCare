<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DoctorFactory extends Factory
{

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone_number' => '9639' . fake()->numerify('########'),
            'password' => Hash::make('doctor123'),
            'address' => fake()->randomElement(['Damascus, Mezzeh', 'Damascus, Abu Rummaneh', 'Homs', 'Latakia']),
            'experience_years' => fake()->numberBetween(3, 20),
            'education' => fake()->randomElement(['PhD in Pediatrics', 'Master of Child Psychology', 'Board Certified Pediatric Surgeon', 'General Practitioner']),
            'fee' => fake()->randomElement([30000, 45000, 50000, 70000]),
            'commission_percentage' => 60,
            'profile_picture' => null,
            'gender' => fake()->randomElement(['male', 'female']),
            'cv' => null,
        ];
    }
}

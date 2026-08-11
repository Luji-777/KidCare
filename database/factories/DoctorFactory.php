<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class DoctorFactory extends Factory
{

    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        return [
            'first_name' => $faker->firstName(),
            'last_name' => $faker->lastName(),
            'email' => $faker->unique()->safeEmail(),
            'phone_number' => '9639' . $faker->numerify('########'),
            'password' => Hash::make('doctor123'),
            'address' => $faker->randomElement(['Damascus, Mezzeh', 'Damascus, Abu Rummaneh', 'Homs', 'Latakia']),
            'experience_years' => $faker->numberBetween(3, 20),
            'education' => $faker->randomElement(['PhD in Pediatrics', 'Master of Child Psychology', 'Board Certified Pediatric Surgeon', 'General Practitioner']),
            'fee' => $faker->randomElement([30000, 45000, 50000, 70000]),
            'commission_percentage' => 60,
            'profile_picture' => null,
            'gender' => $faker->randomElement(['male', 'female']),
            'cv' => null,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ParentModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ParentModelFactory extends Factory
{
    protected $model = ParentModel::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        return [
            'first_name'   => $faker->firstName,
            'last_name'    => $faker->lastName,
            'email'        => $faker->unique()->safeEmail,
            'password'     => \Illuminate\Support\Facades\Hash::make('password'),
            'phone_number' => $faker->phoneNumber,
            'address'      => $faker->address,

        ];
    }
}

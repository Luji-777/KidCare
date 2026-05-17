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
    return [
        'first_name'   => $this->faker->firstName,
        'last_name'    => $this->faker->lastName,
        'email'        => $this->faker->unique()->safeEmail,
        'password'     => \Illuminate\Support\Facades\Hash::make('password'),
        'phone_number' => $this->faker->phoneNumber,
        'address'      => $this->faker->address,
        // تم حذف email_verified_at و remember_token لأنهما غير موجودين بالـ migration
    ];
}
}

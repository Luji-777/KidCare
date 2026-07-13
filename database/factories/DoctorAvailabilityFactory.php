<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DoctorAvailability;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DoctorAvailability>
 */
class DoctorAvailabilityFactory extends Factory
{
    protected $model = DoctorAvailability::class;

    public function definition(): array
    {
        $faker = \Faker\Factory::create();
        $startTime = $faker->dateTimeBetween('09:00:00', '15:00:00')->format('H:i:00');


        $endTime = date('H:i:00', strtotime($startTime) + 3 * 3600);

        return [
            'day_of_week' => $faker->randomElement(['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday']),
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'is_booked'   => false,
        ];
    }
}

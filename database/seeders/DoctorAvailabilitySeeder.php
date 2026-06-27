<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Database\Seeder;

class DoctorAvailabilitySeeder extends Seeder
{

    public function run(): void
    {
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
        $timeSlots = [
            ['start' => '09:00:00', 'end' => '12:00:00'],
            ['start' => '12:00:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '18:00:00'],
        ];

        $doctors = Doctor::all();
        $slotIndex = 0;
        $dayIndex = 0;

        foreach ($doctors as $doctor) {
            for ($i = 0; $i < 3; $i++) {
                DoctorAvailability::create([
                    'doctor_id'   => $doctor->id,
                    'day_of_week' => $days[$dayIndex],
                    'start_time'  => $timeSlots[$slotIndex]['start'],
                    'end_time'    => $timeSlots[$slotIndex]['end'],
                    'is_booked'   => false,
                ]);

                $slotIndex++;
                if ($slotIndex >= count($timeSlots)) {
                    $slotIndex = 0;
                    $dayIndex++;
                    if ($dayIndex >= count($days)) {
                        $dayIndex = 0;
                    }
                }
            }
        }
    }
}

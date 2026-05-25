<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\DoctorAvailability;

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

        $departments = Department::with('doctors')->get();

        foreach ($departments as $department) {
            $doctors = $department->doctors;

            if ($doctors->isEmpty()) {
                continue;
            }


            $slotIndex = 0;
            $dayIndex = 0;


            for ($i = 0; $i < 3; $i++) {
                foreach ($doctors as $doctor) {


                    $currentDay = $days[$dayIndex];
                    $currentSlot = $timeSlots[$slotIndex];

                    DoctorAvailability::create([
                        'doctor_id'   => $doctor->id,
                        'day_of_week' => $currentDay,
                        'start_time'  => $currentSlot['start'],
                        'end_time'    => $currentSlot['end'],
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
}

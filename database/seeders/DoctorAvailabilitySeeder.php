<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use Illuminate\Database\Seeder;

class DoctorAvailabilitySeeder extends Seeder
{

    public function run(): void
{
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $timeSlots = [
        ['start' => '09:00:00', 'end' => '12:00:00'],
        ['start' => '12:00:00', 'end' => '15:00:00'],
        ['start' => '15:00:00', 'end' => '18:00:00'],
    ];

    $doctors = Doctor::all();

    foreach ($doctors as $doctor) {
        // إذا كان الدكتور رقم 1 (أحمد العلي)، نعطيه أوقات دوام في كل أيام الأسبوع لضمان نجاح التوليد دائماً
        if ($doctor->id === 1) {
            foreach ($days as $day) {
                foreach ($timeSlots as $slot) {
                    DoctorAvailability::create([
                        'doctor_id'   => $doctor->id,
                        'day_of_week' => $day,
                        'start_time'  => $slot['start'],
                        'end_time'    => $slot['end'],
                        'is_booked'   => false,
                    ]);
                }
            }
        } else {
            // باقي الدكاترة يتبعون النظام العشوائي القديم الخاص بك بدون تغيير
            $slotIndex = rand(0, 2);
            $dayIndex = rand(0, 4);
            for ($i = 0; $i < 3; $i++) {
                DoctorAvailability::create([
                    'doctor_id'   => $doctor->id,
                    'day_of_week' => $days[$dayIndex],
                    'start_time'  => $timeSlots[$slotIndex]['start'],
                    'end_time'    => $timeSlots[$slotIndex]['end'],
                    'is_booked'   => false,
                ]);

                $slotIndex = ($slotIndex + 1) % count($timeSlots);
                $dayIndex = ($dayIndex + 1) % count($days);
            }
        }
    }
}
}

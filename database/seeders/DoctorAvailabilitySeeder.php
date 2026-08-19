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
    /* public function run(): void
    {
        $days = [
            'Saturday',
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
        ];

        $timeSlots = [
            ['start' => '09:00:00', 'end' => '12:00:00'],
            ['start' => '12:00:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '18:00:00'],
        ];

        $doctors = Doctor::orderBy('department_id')
            ->orderBy('id')
            ->get();

        foreach ($doctors as $doctor) {

            $created = 0;


            $startDayIndex = ($doctor->id - 1) % count($days);

            for ($dayOffset = 0; $dayOffset < count($days); $dayOffset++) {

                if ($created >= 3) {
                    break;
                }

                // اختيار يوم مختلف
                $dayIndex = ($startDayIndex + $dayOffset) % count($days);
                $day = $days[$dayIndex];

                foreach ($timeSlots as $slot) {

                    if ($created >= 3) {
                        break;
                    }

                    // التحقق من عدم وجود تعارض
                    // مع دكتور من نفس القسم في نفس اليوم والوقت
                    $conflict = DoctorAvailability::whereHas(
                        'doctor',
                        function ($query) use ($doctor) {
                            $query->where(
                                'department_id',
                                $doctor->department_id
                            );
                        }
                    )
                        ->where('day_of_week', $day)
                        ->where('start_time', '<', $slot['end'])
                        ->where('end_time', '>', $slot['start'])
                        ->exists();

                    if (!$conflict) {

                        DoctorAvailability::create([
                            'doctor_id'   => $doctor->id,
                            'day_of_week' => $day,
                            'start_time'  => $slot['start'],
                            'end_time'    => $slot['end'],
                            'is_booked'   => false,
                        ]);

                        $created++;

                        // مهم:
                        // بعد ما أخذنا Slot بهذا اليوم،
                        // ننتقل لليوم التالي.
                        break;
                    }
                }
            }
        }
    }*/
    /*public function run(): void
{
    $days = [
        'Saturday',
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
    ];

    $timeSlots = [
        ['start' => '09:00:00', 'end' => '12:00:00'],
        ['start' => '12:00:00', 'end' => '15:00:00'],
        ['start' => '15:00:00', 'end' => '18:00:00'],
    ];

    $doctors = Doctor::orderBy('department_id')
        ->orderBy('id')
        ->get();

    // حفظ آخر Slot مستخدم لكل قسم
    $departmentSlotIndex = [];

    // حفظ آخر Day مستخدم لكل قسم
    $departmentDayIndex = [];

    foreach ($doctors as $doctor) {

        $departmentId = $doctor->department_id;

        // أول طبيب بهذا القسم
        if (!isset($departmentSlotIndex[$departmentId])) {
            $departmentSlotIndex[$departmentId] = 0;
            $departmentDayIndex[$departmentId] = 0;
        }

        $slotIndex = $departmentSlotIndex[$departmentId];
        $dayIndex = $departmentDayIndex[$departmentId];

        $slot = $timeSlots[$slotIndex];
        $day = $days[$dayIndex];

        DoctorAvailability::create([
            'doctor_id'   => $doctor->id,
            'day_of_week' => $day,
            'start_time'  => $slot['start'],
            'end_time'    => $slot['end'],
            'is_booked'   => false,
        ]);

        // الانتقال للـ Slot التالي
        $departmentSlotIndex[$departmentId] =
            ($slotIndex + 1) % count($timeSlots);

        // إذا خلصنا الـ Slots ننتقل لليوم التالي
        if ($slotIndex === count($timeSlots) - 1) {
            $departmentDayIndex[$departmentId] =
                ($dayIndex + 1) % count($days);
        }
    }
}*/
}

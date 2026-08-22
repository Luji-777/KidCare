<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = DB::table('doctors')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->select('id', 'department_id')
            ->get()
            ->groupBy('department_id');

        if ($departments->isEmpty()) {
            $this->command->warn('No active doctors found! Please run DoctorSeeder first.');
            return;
        }

        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        $timeSlots = [
            ['start' => '09:00:00', 'end' => '11:00:00'],
            ['start' => '11:00:00', 'end' => '13:00:00'],
            ['start' => '13:00:00', 'end' => '15:00:00'],
            ['start' => '15:00:00', 'end' => '17:00:00'],
            ['start' => '17:00:00', 'end' => '18:00:00'],
        ];

        $docOneSlots = [
            ['start' => '12:00:00', 'end' => '14:30:00'],
            ['start' => '14:30:00', 'end' => '17:00:00'],
        ];

        $availabilities = [];
        $doctorSlotCount = [];

        $todayDayName = Carbon::today()->format('l');
        $tomorrowDayName = Carbon::tomorrow()->format('l');

        $docOne = DB::table('doctors')->where('id', 1)->first();
        if ($docOne) {
            foreach ([$todayDayName, $tomorrowDayName] as $targetDay) {
                foreach ($docOneSlots as $slot) {
                    $availabilities[] = [
                        'doctor_id'   => 1,
                        'day_of_week' => $targetDay,
                        'start_time'  => $slot['start'],
                        'end_time'    => $slot['end'],
                        'is_booked'   => false,
                        'created_at'  => Carbon::now(),
                        'updated_at'  => Carbon::now(),
                    ];
                }
            }
            $doctorSlotCount[1] = count($docOneSlots) * 2;
        }

        foreach ($departments as $departmentId => $doctors) {
            $doctorIds = $doctors->pluck('id')->toArray();
            $doctorCount = count($doctorIds);

            foreach ($doctorIds as $docId) {
                if (!isset($doctorSlotCount[$docId])) {
                    $doctorSlotCount[$docId] = 0;
                }
            }

            $globalSlotCounter = 0;

            foreach ($days as $dayIndex => $day) {
                $activeSlotIndexes = match ($dayIndex % 3) {
                    0 => [0, 2, 4],
                    1 => [1, 3],
                    2 => [0, 3],
                };

                foreach ($activeSlotIndexes as $slotIdx) {
                    $assignedDoctorId = $doctorIds[$globalSlotCounter % $doctorCount];
                    $globalSlotCounter++;

                    if ($assignedDoctorId == 1 && in_array($day, [$todayDayName, $tomorrowDayName])) {
                        continue;
                    }

                    $availabilities[] = [
                        'doctor_id'   => $assignedDoctorId,
                        'day_of_week' => $day,
                        'start_time'  => $timeSlots[$slotIdx]['start'],
                        'end_time'    => $timeSlots[$slotIdx]['end'],
                        'is_booked'   => false,
                        'created_at'  => Carbon::now(),
                        'updated_at'  => Carbon::now(),
                    ];

                    $doctorSlotCount[$assignedDoctorId]++;
                }
            }

            foreach ($doctorIds as $docId) {
                if ($doctorSlotCount[$docId] === 0) {
                    $availabilities[] = [
                        'doctor_id'   => $docId,
                        'day_of_week' => 'Sunday',
                        'start_time'  => $timeSlots[4]['start'],
                        'end_time'    => $timeSlots[4]['end'],
                        'is_booked'   => false,
                        'created_at'  => Carbon::now(),
                        'updated_at'  => Carbon::now(),
                    ];
                    $doctorSlotCount[$docId]++;
                }
            }
        }

        foreach ($availabilities as $slot) {
            DB::table('doctor_availabilities')->updateOrInsert(
                [
                    'doctor_id'   => $slot['doctor_id'],
                    'day_of_week' => $slot['day_of_week'],
                    'start_time'  => $slot['start_time'],
                ],
                $slot
            );
        }
    }
}

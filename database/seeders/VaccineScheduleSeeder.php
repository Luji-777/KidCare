<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vaccine;
use App\Models\VaccineSchedule;
use Carbon\Carbon;

class VaccineScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $dtpBooster1 = Vaccine::where('name', 'LIKE', 'DTP Booster 1%')->first();
        if ($dtpBooster1) {
            $startDate = Carbon::now()->subYears(7);
            $endDate   = Carbon::now()->subDays(1);

            while ($startDate->lessThanOrEqualTo($endDate)) {
                VaccineSchedule::create([
                    'vaccine_id' => $dtpBooster1->id,
                    'date'       => $startDate->toDateString(),
                    'start_time' => '09:00:00',
                    'end_time'   => '13:00:00',
                    'status'     => 'finished',
                    'notes'      => 'Bi-annual DTP Booster 1 recurring session.',
                ]);

                $startDate->addMonths(6);
            }
        }

        $dtpBooster2 = Vaccine::where('name', 'LIKE', '%Booster 2%')->first();
        if ($dtpBooster2) {
            $startDate = Carbon::now()->subYears(7);
            $endDate   = Carbon::now()->subDays(1);

            while ($startDate->lessThanOrEqualTo($endDate)) {
                VaccineSchedule::create([
                    'vaccine_id' => $dtpBooster2->id,
                    'date'       => $startDate->toDateString(),
                    'start_time' => '09:00:00',
                    'end_time'   => '13:00:00',
                    'status'     => 'finished',
                    'notes'      => 'Triennial DTP Booster 2 recurring session.',
                ]);

                $startDate->addYears(3);
            }
        }

        $allVaccines = Vaccine::all();
        $nextScheduleDate = Carbon::tomorrow();

        foreach ($allVaccines as $vaccine) {
            VaccineSchedule::create([
                'vaccine_id' => $vaccine->id,
                'date'       => $nextScheduleDate->toDateString(),
                'start_time' => '09:00:00',
                'end_time'   => '12:00:00',
                'status'     => 'available',
                'notes'      => 'Upcoming daily scheduled session.',
            ]);

            $nextScheduleDate->addDay();
        }
    }
}

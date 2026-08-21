<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Child;
use App\Models\VaccineSchedule;
use App\Models\ChildVaccination;
use Carbon\Carbon;

class ChildVaccinationSeeder extends Seeder
{
    public function run(): void
    {
        $children = Child::take(10)->get();

        $finishedSchedules = VaccineSchedule::where('status', 'finished')
            ->with('vaccine')
            ->get();

        if ($children->isEmpty() || $finishedSchedules->isEmpty()) {
            return;
        }

        foreach ($children as $child) {
            $birthDate = Carbon::parse($child->birth_date);
            $recordsCount = 0;

            foreach ($finishedSchedules as $schedule) {
                if ($recordsCount >= 2) {
                    break;
                }

                $vaccine = $schedule->vaccine;
                if (!$vaccine) continue;

                $scheduleDate = Carbon::parse($schedule->date);

                $ageInMonthsAtSchedule = $birthDate->diffInMonths($scheduleDate, false);


                if (
                    $scheduleDate->greaterThanOrEqualTo($birthDate) &&
                    $ageInMonthsAtSchedule >= $vaccine->min_age_months &&
                    $ageInMonthsAtSchedule <= $vaccine->max_age_months
                ) {

                    ChildVaccination::updateOrCreate(
                        [
                            'child_id'   => $child->id,
                            'vaccine_id' => $vaccine->id,
                        ],
                        [
                            'given_date' => $schedule->date,
                            'notes'      => 'Administered during recurring session: ' . $schedule->notes,
                        ]
                    );

                    $recordsCount++;
                }
            }
        }
    }
}

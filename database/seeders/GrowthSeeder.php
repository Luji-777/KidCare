<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GrowthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $children = DB::table('children')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'first_name', 'gender', 'birth_date']);

        if ($children->isEmpty()) {
            $this->command->warn('No children found! Please run ChildSeeder first.');
            return;
        }

        $growthRecords = [];

        foreach ($children as $index => $child) {
            $birthDate = Carbon::parse($child->birth_date);
            $gender = $child->gender;

            $baseHeight = $gender === 'male' ? 50.0 : 49.0; // cm
            $baseWeight = $gender === 'male' ? 3.4 : 3.2;   // kg

            $milestones = [0, 3, 6, 12, 18, 24, 36, 48, 60];

            foreach ($milestones as $mIndex => $months) {
                $recordDate = $birthDate->copy()->addMonths($months);

                if ($recordDate->isFuture()) {
                    continue;
                }

                $height = $baseHeight + ($months * 1.2) + rand(-1, 2);
                $weight = $baseWeight + ($months * 0.45) + (rand(-3, 3) / 10);


                if ($index === 0 && $months === 18) {
                    $weight -= 1.8;
                }

                if ($index === 2 && $months === 12) {
                    $height += 6.5;
                    $weight += 2.2;
                }

                if ($index === 6 && $months >= 12) {
                    $weight *= 0.85;
                    $height *= 0.92;
                }

                $growthRecords[] = [
                    'child_id'   => $child->id,
                    'height'     => round($height, 1),
                    'weight'     => round($weight, 1),
                    'date'       => $recordDate->toDateString(),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];
            }
        }

        foreach ($growthRecords as $record) {
            DB::table('growths')->updateOrInsert(
                [
                    'child_id' => $record['child_id'],
                    'date'     => $record['date'],
                ],
                $record
            );
        }
    }
}

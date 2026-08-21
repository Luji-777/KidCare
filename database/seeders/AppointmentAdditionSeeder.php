<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Child;
use Illuminate\Support\Facades\DB;

class AppointmentAdditionSeeder extends Seeder
{
    public function run(): void
    {
        $additionsByDepartment = [
            'Pediatrics' => [
                ['item_name' => 'Growth & Development Assessment', 'price' => 15.00],
                ['item_name' => 'General Pediatrics Checkup', 'price' => 20.00],
                ['item_name' => 'Routine Blood Work / CBC', 'price' => 25.00],
                ['item_name' => 'Child Nutrition Consultation', 'price' => 18.00],
            ],
            'Dentistry' => [
                ['item_name' => 'Fluoride Treatment', 'price' => 25.00],
                ['item_name' => 'Dental X-Ray (Panoramic)', 'price' => 40.00],
                ['item_name' => 'Teeth Cleaning & Polishing', 'price' => 35.00],
                ['item_name' => 'Pit and Fissure Sealants', 'price' => 30.00],
            ],
            'Psychiatry' => [
                ['item_name' => 'Behavioral Assessment Session', 'price' => 50.00],
                ['item_name' => 'ADHD Screening Test', 'price' => 60.00],
                ['item_name' => 'Psychological Consultation (30 mins)', 'price' => 45.00],
                ['item_name' => 'IQ & Cognitive Ability Test', 'price' => 70.00],
            ],
        ];

        $first10ChildrenIds = Child::orderBy('id')->take(10)->pluck('id');

        if ($first10ChildrenIds->isEmpty()) {
            return;
        }

        $appointments = Appointment::whereIn('child_id', $first10ChildrenIds)
            ->where('status', 'completed')
            ->with(['doctor.department'])
            ->get();

        foreach ($appointments as $appointment) {
            $departmentName = $appointment->doctor->department->name ?? 'Pediatrics';

            $availableAdditions = $additionsByDepartment[$departmentName] ?? $additionsByDepartment['Pediatrics'];

            $randomKeys = (array) array_rand($availableAdditions, rand(1, 2));

            foreach ($randomKeys as $key) {
                $addition = $availableAdditions[$key];

                DB::table('appointment_additions')->insert([
                    'appointment_id' => $appointment->id,
                    'item_name'      => $addition['item_name'],
                    'price'          => $addition['price'],
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }
    }
}

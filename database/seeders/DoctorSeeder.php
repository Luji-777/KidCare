<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{

    public function run(): void
    {
        $departments = Department::all();


        foreach ($departments as $department) {
            Doctor::factory()->count(5)->create([
                'department_id' => $department->id,
            ]);
        }
    }
}

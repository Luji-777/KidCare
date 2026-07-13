<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{

   
        public function run(): void
    {
        $departments = Department::all();
        if ($departments->isEmpty()) return;

        // تثبيت الدكتور رقم 1 ببيانات محددة
        $firstDepartment = $departments->first();
        Doctor::create([
            'id'                    => 1, 
            'department_id'         => $firstDepartment->id,
            'first_name'            => 'أحمد',
            'last_name'             => 'العلي',
            'email'                 => 'doctor1@example.com',
            'phone_number'          => '963912345678',
            'password'              => Hash::make('doctor123'),
            'address'               => 'Damascus, Mezzeh',
            'experience_years'      => 10,
            'education'             => 'PhD in Pediatrics',
            'fee'                   => 50000,
            'commission_percentage' => 60,
            'profile_picture'       => null,
            
            'cv'                    => null,
        ]);

        // توليد باقي الدكاترة عشوائياً
        foreach ($departments as $department) {
            $count = ($department->id === $firstDepartment->id) ? 4 : 5;
            Doctor::factory()->count($count)->create([
                'department_id' => $department->id,
            ]);
        }
    }
}

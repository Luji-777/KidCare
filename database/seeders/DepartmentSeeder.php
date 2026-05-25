<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'name' => 'Pediatrics',
                'description' => 'Healthcare services for children',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dentistry',
                'description' => 'Diagnosis and treatment of dental and oral health',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Psychiatry',
                'description' => 'Mental health and psychological care for children',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Department::insert($departments);
    }
}

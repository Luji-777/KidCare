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
        $departments=[

              [
                'name' => 'Pediatrics',
                'description' => 'Healthcare services for children'
            ],

            [
                'name' => 'Dentistry',
                'description' => 'Diagnosis and treatment of dental and oral health'
            ],

            [
                'name' => 'Psychiatry',
                'description' => 'Mental health and psychological care for children'
            ],

            [
                'name' => 'Speech and Language',
                'description' => 'Evaluation and treatment of speech, language, and communication disorders'
            ],

            [
                'name' => 'Vaccination',
                'description' => 'Vaccination services for children'
            ],

        ];

        foreach($departments as $department){
            Department::create($department);
        }

        
    }
}

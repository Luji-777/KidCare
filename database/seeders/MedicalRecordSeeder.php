<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class MedicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $medicalDataByDepartment = [
            'Pediatrics' => [
                [
                    'diagnosis' => 'Acute Upper Respiratory Tract Infection',
                    'notes' => 'Child presented with low-grade fever and mild cough. Advised rest and fluids.',
                    'medications' => [
                        [
                            'name' => 'Paracetamol Syrup',
                            'dosage' => '5ml',
                            'frequency' => 'Every 6 hours',
                            'timing' => 'After meals',
                            'duration' => '5 days',
                        ],
                        [
                            'name' => 'Amoxicillin Oral Suspension',
                            'dosage' => '250mg / 5ml',
                            'frequency' => 'Twice daily',
                            'timing' => 'After meals',
                            'duration' => '7 days',
                        ],
                    ],
                ],
                [
                    'diagnosis' => 'Acute Gastroenteritis',
                    'notes' => 'Mild dehydration observed. Prescribed oral rehydration solutions.',
                    'medications' => [
                        [
                            'name' => 'ORS (Oral Rehydration Salts)',
                            'dosage' => '1 sachet in 200ml water',
                            'frequency' => 'As needed',
                            'timing' => 'Between meals',
                            'duration' => '3 days',
                        ],
                        [
                            'name' => 'Zinc Sulfate Syrup',
                            'dosage' => '5ml',
                            'frequency' => 'Once daily',
                            'timing' => 'Before meals',
                            'duration' => '10 days',
                        ],
                    ],
                ],
            ],
            'Dentistry' => [
                [
                    'diagnosis' => 'Early Childhood Dental Caries',
                    'notes' => 'Mild cavities on upper primary molars. Fluoride varnish applied.',
                    'medications' => [
                        [
                            'name' => 'Ibuprofen Pediatric Suspension',
                            'dosage' => '100mg / 5ml',
                            'frequency' => 'Every 8 hours',
                            'timing' => 'After meals',
                            'duration' => '3 days',
                        ],
                        [
                            'name' => 'Chlorhexidine Pediatric Mouthwash',
                            'dosage' => '10ml',
                            'frequency' => 'Twice daily',
                            'timing' => 'After brushing',
                            'duration' => '7 days',
                        ],
                    ],
                ],
                [
                    'diagnosis' => 'Gingivitis and Plaque Accumulation',
                    'notes' => 'Mild gum swelling. Cleaned and demonstrated proper oral hygiene.',
                    'medications' => [
                        [
                            'name' => 'Fluoride Rinse Solution',
                            'dosage' => '5ml',
                            'frequency' => 'Once daily',
                            'timing' => 'Before bed',
                            'duration' => '14 days',
                        ],
                    ],
                ],
            ],
            'Psychiatry' => [
                [
                    'diagnosis' => 'Attention Deficit Hyperactivity Disorder (ADHD) - Mild',
                    'notes' => 'Child shows mild restlessness in school. Behavioral therapy recommended.',
                    'medications' => [
                        [
                            'name' => 'Atomoxetine HCl',
                            'dosage' => '10mg',
                            'frequency' => 'Once daily',
                            'timing' => 'In the morning',
                            'duration' => '30 days',
                        ],
                    ],
                ],
                [
                    'diagnosis' => 'Childhood Anxiety Disorder',
                    'notes' => 'Separation anxiety symptoms reported. Family counseling advised.',
                    'medications' => [
                        [
                            'name' => 'Multivitamin & Magnesium Syrup',
                            'dosage' => '5ml',
                            'frequency' => 'Once daily',
                            'timing' => 'With food',
                            'duration' => '30 days',
                        ],
                    ],
                ],
            ],
        ];

        $completedAppointments = Appointment::where('status', 'completed')
            ->with(['doctor.department'])
            ->get();

        foreach ($completedAppointments as $appointment) {
            $departmentName = $appointment->doctor->department->name ?? 'Pediatrics';

            $availableTemplates = $medicalDataByDepartment[$departmentName] ?? $medicalDataByDepartment['Pediatrics'];

            $selectedTemplate = $availableTemplates[array_rand($availableTemplates)];

            $recordId = DB::table('medical_records')->insertGetId([
                'appointment_id' => $appointment->id,
                'diagnosis'      => $selectedTemplate['diagnosis'],
                'doctor_notes'   => $selectedTemplate['notes'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($selectedTemplate['medications'] as $medication) {
                DB::table('medications')->insert([
                    'record_id'  => $recordId,
                    'name'       => $medication['name'],
                    'dosage'     => $medication['dosage'],
                    'frequency'  => $medication['frequency'],
                    'timing'     => $medication['timing'],
                    'duration'   => $medication['duration'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

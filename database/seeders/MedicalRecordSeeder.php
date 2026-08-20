<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Medication;

class MedicalRecordSeeder extends Seeder
{
    public function run(): void
    {
        $completedAppointments = Appointment::where('status', 'completed')->get();

        $diagnoses = [
            '(Acute Tonsillitis)',
            '(Gastroenteritis)',
            '(Otitis Media)',
            '(Bronchitis)',
            '(Seasonal Allergy)'
        ];

        $notes = [
            'الطفل بحالة جيدة، يُنصح بإكمال جرعة المضاد الحيوي حتى النهاية والراحة التامة.',
            'ارتفاع خفيف في الحرارة، مراجعة العيادة في حال استمرار الأعراض لأكثر من 3 أيام.',
            'تحسن ملحوظ مقارنة بالحالة السابقة، يُفضل الاستمرار على الفيتامينات.',
            'يجب تجنب الأطعمة التي تسبب الحساسية والالتزام بمواعيد الدواء.'
        ];

        $sampleMedications = [
            ['name' => 'Amoxicillin', 'dosage' => '250mg', 'frequency' => 'كل 8 ساعات', 'timing' => 'بعد الطعام', 'duration' => '7 أيام'],
            ['name' => 'Paracetamol Syrup', 'dosage' => '5ml', 'frequency' => 'عند اللزوم', 'timing' => 'بعد الطعام', 'duration' => '3 أيام'],
            ['name' => 'Ibuprofen', 'dosage' => '100mg/5ml', 'frequency' => 'كل 12 ساعة', 'timing' => 'بعد الطعام', 'duration' => '5 أيام'],
            ['name' => 'Cetirizine Syrup', 'dosage' => '2.5ml', 'frequency' => 'مرة واحدة يومياً', 'timing' => 'قبل النوم', 'duration' => '5 أيام'],
            ['name' => 'Vitamin C Drops', 'dosage' => '1ml', 'frequency' => 'مرة واحدة يومياً', 'timing' => 'صباحاً', 'duration' => '30 يوم']
        ];

        foreach ($completedAppointments as $appointment) {
            $record = MedicalRecord::create([
                'appointment_id' => $appointment->id,
                'diagnosis'      => $diagnoses[array_rand($diagnoses)],
                'doctor_notes'   => $notes[array_rand($notes)],
            ]);

            $medKeys = (array) array_rand($sampleMedications, rand(1, 3));

            foreach ($medKeys as $key) {
                $med = $sampleMedications[$key];
                Medication::create([
                    'record_id' => $record->id,
                    'name'      => $med['name'],
                    'dosage'    => $med['dosage'],
                    'frequency' => $med['frequency'],
                    'timing'    => $med['timing'],
                    'duration'  => $med['duration'],
                ]);
            }
        }
    }
}

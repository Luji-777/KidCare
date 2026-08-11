<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vaccine;

class VaccineSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            // --- At Birth (0 - 1 Month) ---
            [
                'name'              => 'BCG (Tuberculosis)',
                'min_age_months'    => 0,
                'max_age_months'    => 1,
                'description'       => 'Single dose given at birth to protect against severe forms of tuberculosis.'
            ],
            [
                'name'              => 'Hepatitis B (HepB-0)',
                'min_age_months'    => 0,
                'max_age_months'    => 1,
                'description'       => 'Birth dose given within 24 hours of birth to prevent perinatal Hepatitis B transmission.'
            ],
            [
                'name'              => 'Oral Polio Vaccine (OPV-0)',
                'min_age_months'    => 0,
                'max_age_months'    => 1,
                'description'       => 'Birth dose for early immunity against poliomyelitis.'
            ],

            // --- 2 Months (Min 2 - Max 3 Months) ---
            [
                'name'              => 'Pentavalent 1 (DTP-HepB-Hib)',
                'min_age_months'    => 2,
                'max_age_months'    => 3,
                'description'       => 'First dose protecting against Diphtheria, Tetanus, Pertussis, Hepatitis B, and Haemophilus Influenzae type b.'
            ],
            [
                'name'              => 'Pneumococcal Conjugate 1 (PCV-1)',
                'min_age_months'    => 2,
                'max_age_months'    => 3,
                'description'       => 'First dose protecting against pneumococcal infections such as pneumonia and meningitis.'
            ],
            [
                'name'              => 'Rotavirus 1 (RV-1)',
                'min_age_months'    => 2,
                'max_age_months'    => 3,
                'description'       => 'First dose protecting against severe rotavirus gastroenteritis and diarrhea.'
            ],

            // --- 4 Months (Min 4 - Max 5 Months) ---
            [
                'name'              => 'Pentavalent 2 (DTP-HepB-Hib)',
                'min_age_months'    => 4,
                'max_age_months'    => 5,
                'description'       => 'Second dose protecting against Diphtheria, Tetanus, Pertussis, Hepatitis B, and Hib.'
            ],
            [
                'name'              => 'Inactivated Polio Vaccine (IPV-1)',
                'min_age_months'    => 4,
                'max_age_months'    => 5,
                'description'       => 'First injectable polio dose for enhanced immunity against poliovirus.'
            ],
            [
                'name'              => 'Pneumococcal Conjugate 2 (PCV-2)',
                'min_age_months'    => 4,
                'max_age_months'    => 5,
                'description'       => 'Second dose protecting against pneumococcal infections.'
            ],
            [
                'name'              => 'Rotavirus 2 (RV-2)',
                'min_age_months'    => 4,
                'max_age_months'    => 5,
                'description'       => 'Second dose protecting against rotavirus gastroenteritis.'
            ],

            // --- 6 Months (Min 6 - Max 8 Months) ---
            [
                'name'              => 'Pentavalent 3 (DTP-HepB-Hib)',
                'min_age_months'    => 6,
                'max_age_months'    => 8,
                'description'       => 'Third primary dose providing comprehensive protection against DTP, HepB, and Hib.'
            ],
            [
                'name'              => 'Inactivated Polio Vaccine (IPV-2)',
                'min_age_months'    => 6,
                'max_age_months'    => 8,
                'description'       => 'Second dose of injectable polio vaccine.'
            ],

            // --- 9 Months (Min 9 - Max 11 Months) ---
            [
                'name'              => 'Measles & Rubella 1 (MR-1)',
                'min_age_months'    => 9,
                'max_age_months'    => 11,
                'description'       => 'First dose protecting against Measles and Rubella viruses.'
            ],

            // --- 12 - 15 Months (Min 12 - Max 18 Months) ---
            [
                'name'              => 'MMR 1 (Measles, Mumps, Rubella)',
                'min_age_months'    => 12,
                'max_age_months'    => 18,
                'description'       => 'Combined first dose protecting against Measles, Mumps, and Rubella.'
            ],

            // --- 18 - 24 Months (Min 18 - Max 24 Months) ---
            [
                'name'              => 'DTP Booster 1',
                'min_age_months'    => 18,
                'max_age_months'    => 24,
                'description'       => 'First booster dose to prolong protection against Diphtheria, Tetanus, and Pertussis.'
            ],
            [
                'name'              => 'MMR 2 (Measles, Mumps, Rubella)',
                'min_age_months'    => 18,
                'max_age_months'    => 24,
                'description'       => 'Second dose ensuring full immunity against Measles, Mumps, and Rubella.'
            ],
            [
                'name'              => 'Hepatitis A 2 (HepA-2)',
                'min_age_months'    => 18,
                'max_age_months'    => 24,
                'description'       => 'Second dose for long-term Hepatitis A protection (given 6 months after HepA-1).'
            ],

            // ==========================================
            // 8. Age: 2 Years (24 - 36 Months)
            // ==========================================
            [
                'name'              => 'Typhoid Conjugate Vaccine',
                'min_age_months'    => 24,
                'max_age_months'    => 36,
                'description'       => 'Single dose protecting against typhoid fever.'
            ],

            // ==========================================
            // 9. Preschool Boosters: 4 to 6 Years (48 - 84 Months / 4 to 7 Years)
            // ==========================================
            [
                'name'              => 'DTaP / DTP Booster 2',
                'min_age_months'    => 48,
                'max_age_months'    => 84, // 4 to 7 years
                'description'       => 'Second booster dose before entering school for Diphtheria, Tetanus, and Pertussis.'
            ],
            [
                'name'              => 'Polio Booster (IPV Booster)',
                'min_age_months'    => 48,
                'max_age_months'    => 84, // 4 to 7 years
                'description'       => 'Preschool booster dose against Poliovirus.'
            ],
            [
                'name'              => 'Varicella 2 (Chickenpox Booster)',
                'min_age_months'    => 48,
                'max_age_months'    => 84, // 4 to 7 years
                'description'       => 'Second dose of chickenpox vaccine for preschool entry.'
            ],
        ];

        foreach ($vaccines as $vaccine) {
            Vaccine::updateOrCreate(
                ['name' => $vaccine['name']],
                $vaccine
            );
        }
    }
}

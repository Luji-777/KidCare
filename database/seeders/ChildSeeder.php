<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ChildSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all parent IDs ordered by ID to assign 2 children per parent
        $parentIds = DB::table('parent_models')->orderBy('id')->pluck('id')->toArray();

        if (empty($parentIds)) {
            $this->command->warn('No parents found! Please run ParentSeeder first.');
            return;
        }

        $childrenData = [
            // Parent 1: Louay Khneifas
            [
                'parent_index'    => 0,
                'first_name'      => 'Kareem',
                'last_name'       => 'Khneifas',
                'gender'          => 'male',
                'birth_date'      => '2020-05-14',
                'blood_type'      => 'A+',
                'medical_history' => 'Routine childhood vaccinations completed up to age 5. Mild asthma during winter.',
                'allergies'       => 'Dust mites, Penicillin',
            ],
            [
                'parent_index'    => 0,
                'first_name'      => 'Maya',
                'last_name'       => 'Khneifas',
                'gender'          => 'female',
                'birth_date'      => '2022-11-03',
                'blood_type'      => 'O+',
                'medical_history' => 'No prior hospitalizations. Regular developmental checkups are clear.',
                'allergies'       => 'None reported',
            ],

            // Parent 2: Ahmad Al-Rahali
            [
                'parent_index'    => 1,
                'first_name'      => 'Sami',
                'last_name'       => 'Al-Rahali',
                'gender'          => 'male',
                'birth_date'      => '2019-08-22',
                'blood_type'      => 'B+',
                'medical_history' => 'Tonsillectomy in 2024. Normal growth metrics.',
                'allergies'       => 'Peanuts',
            ],
            [
                'parent_index'    => 1,
                'first_name'      => 'Lina',
                'last_name'       => 'Al-Rahali',
                'gender'          => 'female',
                'birth_date'      => '2023-01-15',
                'blood_type'      => 'B+',
                'medical_history' => 'Minor seasonal eczema managed with topical emollients.',
                'allergies'       => 'None reported',
            ],

            // Parent 3: Jana Hassan
            [
                'parent_index'    => 2,
                'first_name'      => 'Zaid',
                'last_name'       => 'Hassan',
                'gender'          => 'male',
                'birth_date'      => '2021-04-10',
                'blood_type'      => 'O-',
                'medical_history' => 'All vaccinations up to date. Mild viral fever episode in early 2025.',
                'allergies'       => 'Amoxicillin',
            ],
            [
                'parent_index'    => 2,
                'first_name'      => 'Tala',
                'last_name'       => 'Hassan',
                'gender'          => 'female',
                'birth_date'      => '2024-02-28',
                'blood_type'      => 'A+',
                'medical_history' => 'Healthy infant checkups. On-schedule vaccination record.',
                'allergies'       => 'None reported',
            ],

            // Parent 4: Batoul Khodari
            [
                'parent_index'    => 3,
                'first_name'      => 'Yaseen',
                'last_name'       => 'Khodari',
                'gender'          => 'male',
                'birth_date'      => '2019-12-05',
                'blood_type'      => 'AB+',
                'medical_history' => 'History of recurrent otitis media in toddlerhood. Resolved.',
                'allergies'       => 'Pollen, Dairy products',
            ],
            [
                'parent_index'    => 3,
                'first_name'      => 'Salma',
                'last_name'       => 'Khodari',
                'gender'          => 'female',
                'birth_date'      => '2022-06-19',
                'blood_type'      => 'A-',
                'medical_history' => 'No major illness. Up-to-date pediatric record.',
                'allergies'       => 'Eggs',
            ],

            // Parent 5: Lojain Qaraoush
            [
                'parent_index'    => 4,
                'first_name'      => 'Hamza',
                'last_name'       => 'Qaraoush',
                'gender'          => 'male',
                'birth_date'      => '2020-09-30',
                'blood_type'      => 'O+',
                'medical_history' => 'Mild iron deficiency anemia treated with dietary supplements.',
                'allergies'       => 'None reported',
            ],
            [
                'parent_index'    => 4,
                'first_name'      => 'Mariam',
                'last_name'       => 'Qaraoush',
                'gender'          => 'female',
                'birth_date'      => '2023-07-12',
                'blood_type'      => 'B-',
                'medical_history' => 'Normal developmental milestones achieved.',
                'allergies'       => 'None reported',
            ],

            // Parent 6: Tarek Al-Masri
            [
                'parent_index'    => 5,
                'first_name'      => 'Omar',
                'last_name'       => 'Al-Masri',
                'gender'          => 'male',
                'birth_date'      => '2021-03-18',
                'blood_type'      => 'A+',
                'medical_history' => 'Routine growth checks normal. Fully vaccinated.',
                'allergies'       => 'Tree nuts',
            ],
            [
                'parent_index'    => 5,
                'first_name'      => 'Naya',
                'last_name'       => 'Al-Masri',
                'gender'          => 'female',
                'birth_date'      => '2024-09-05',
                'blood_type'      => 'O+',
                'medical_history' => 'Healthy infant. Regular well-child visits.',
                'allergies'       => 'None reported',
            ],

            // Parent 7: Nour Al-Din
            [
                'parent_index'    => 6,
                'first_name'      => 'Jad',
                'last_name'       => 'Al-Din',
                'gender'          => 'male',
                'birth_date'      => '2019-10-11',
                'blood_type'      => 'AB-',
                'medical_history' => 'Mild bronchitis treated in 2023. Fully recovered.',
                'allergies'       => 'Dust mites',
            ],
            [
                'parent_index'    => 6,
                'first_name'      => 'Farah',
                'last_name'       => 'Al-Din',
                'gender'          => 'female',
                'birth_date'      => '2022-04-25',
                'blood_type'      => 'A+',
                'medical_history' => 'All standard pediatric immunizations completed.',
                'allergies'       => 'None reported',
            ],

            // Parent 8: Omar Kabbani
            [
                'parent_index'    => 7,
                'first_name'      => 'Faris',
                'last_name'       => 'Kabbani',
                'gender'          => 'male',
                'birth_date'      => '2020-01-08',
                'blood_type'      => 'B+',
                'medical_history' => 'Fractured left forearm in 2025; healed without complications.',
                'allergies'       => 'None reported',
            ],
            [
                'parent_index'    => 7,
                'first_name'      => 'Leen',
                'last_name'       => 'Kabbani',
                'gender'          => 'female',
                'birth_date'      => '2023-10-31',
                'blood_type'      => 'O+',
                'medical_history' => 'Healthy growth pattern. Immunizations up to date.',
                'allergies'       => 'Strawberries',
            ],

            // Parent 9: Reem Al-Saleh
            [
                'parent_index'    => 8,
                'first_name'      => 'Amir',
                'last_name'       => 'Al-Saleh',
                'gender'          => 'male',
                'birth_date'      => '2021-11-17',
                'blood_type'      => 'A-',
                'medical_history' => 'Normal physical development. Mild viral croup episode in 2024.',
                'allergies'       => 'Cat dander',
            ],
            [
                'parent_index'    => 8,
                'first_name'      => 'Yara',
                'last_name'       => 'Al-Saleh',
                'gender'          => 'female',
                'birth_date'      => '2024-05-02',
                'blood_type'      => 'O+',
                'medical_history' => 'Infant checkups complete. No underlying medical conditions.',
                'allergies'       => 'None reported',
            ],

            // Parent 10: Youssef Al-Hamwi
            [
                'parent_index'    => 9,
                'first_name'      => 'Adnan',
                'last_name'       => 'Al-Hamwi',
                'gender'          => 'male',
                'birth_date'      => '2020-07-29',
                'blood_type'      => 'O+',
                'medical_history' => 'Up-to-date immunizations. Mild asthma triggered by cold weather.',
                'allergies'       => 'Seafood',
            ],
            [
                'parent_index'    => 9,
                'first_name'      => 'Ayla',
                'last_name'       => 'Al-Hamwi',
                'gender'          => 'female',
                'birth_date'      => '2023-03-14',
                'blood_type'      => 'B+',
                'medical_history' => 'Routine child health tracking normal. No major illness.',
                'allergies'       => 'None reported',
            ],
        ];

        foreach ($childrenData as $child) {
            $pIdx = $child['parent_index'];
            if (!isset($parentIds[$pIdx])) {
                continue;
            }

            DB::table('children')->updateOrInsert(
                [
                    'parent_id'  => $parentIds[$pIdx],
                    'first_name' => $child['first_name'],
                    'last_name'  => $child['last_name'],
                ],
                [
                    'gender'          => $child['gender'],
                    'birth_date'      => $child['birth_date'],
                    'blood_type'      => $child['blood_type'],
                    'image'           => null,
                    'medical_history' => $child['medical_history'],
                    'allergies'       => $child['allergies'],
                    'created_at'      => Carbon::now(),
                    'updated_at'      => Carbon::now(),
                ]
            );
        }
    }
}

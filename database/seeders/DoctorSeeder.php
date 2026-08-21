<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{


    public function run(): void
    {
        $defaultPassword = Hash::make('doctor123');

        $genPedId  = DB::table('departments')->where('name', 'Pediatrics')->value('id');
        $cardioId  = DB::table('departments')->where('name', 'Dentistry')->value('id');
        $neuroId   = DB::table('departments')->where('name', 'Psychiatry')->value('id');

        if (!$genPedId || !$cardioId || !$neuroId) {
            $this->command->warn('Departments not found! Please run DepartmentSeeder first.');
            return;
        }
        $doctors = [

            [
                'department_id'         => $genPedId,
                'first_name'            => 'Tariq',
                'last_name'             => 'Al-Khatib',
                'address'               => 'Damascus, Abu Rummaneh',
                'email'                 => 'dr.tariq.khatib@example.com',
                'phone_number'          => '963944111222',
                'experience_years'      => 14,
                'education'             => 'M.D. in Pediatrics - Damascus University',
                'fee'                   => '30',
                'commission_percentage' => 15,
                'gender'                => 'male',
                'is_active'             => true,
            ],
            [
                'department_id'         => $genPedId,
                'first_name'            => 'Maya',
                'last_name'             => 'Al-Sabbagh',
                'address'               => 'Damascus, Al-Mazza',
                'email'                 => 'dr.maya.sabbagh@example.com',
                'phone_number'          => '963955222333',
                'experience_years'      => 8,
                'education'             => 'M.D. in General Pediatrics - Syrian Private University',
                'fee'                   => '25',
                'commission_percentage' => 10,
                'gender'                => 'female',
                'is_active'             => true,
            ],
            [
                'department_id'         => $genPedId,
                'first_name'            => 'Bassam',
                'last_name'             => 'Nassar',
                'address'               => 'Aleppo, Al-Shahbaa',
                'email'                 => 'dr.bassam.nassar@example.com',
                'phone_number'          => '963966333444',
                'experience_years'      => 18,
                'education'             => 'Ph.D. in Pediatric Medicine - Aleppo University',
                'fee'                   => '40',
                'commission_percentage' => 20,
                'gender'                => 'male',
                'is_active'             => true,
            ],
            [
                'department_id'         => $genPedId,
                'first_name'            => 'Rania',
                'last_name'             => 'Al-Haddad',
                'address'               => 'Homs, Al-Inshaat',
                'email'                 => 'dr.rania.haddad@example.com',
                'phone_number'          => '963933444555',
                'experience_years'      => 6,
                'education'             => 'M.D. in Child Health - Al-Baath University',
                'fee'                   => '25',
                'commission_percentage' => 10,
                'gender'                => 'female',
                'is_active'             => true,
            ],
            [
                'department_id'         => $genPedId,
                'first_name'            => 'Karem',
                'last_name'             => 'Al-Zein',
                'address'               => 'Lattakia, Project Seventh',
                'email'                 => 'dr.karem.zein@example.com',
                'phone_number'          => '963988555666',
                'experience_years'      => 11,
                'education'             => 'M.D. in Pediatrics - Tishreen University',
                'fee'                   => '35',
                'commission_percentage' => 15,
                'gender'                => 'male',
                'is_active'             => true,
            ],

            [
                'department_id'         => $cardioId,
                'first_name'            => 'Fadi',
                'last_name'             => 'Al-Mansour',
                'address'               => 'Damascus, Malki',
                'email'                 => 'dr.fadi.mansour@example.com',
                'phone_number'          => '963944666777',
                'experience_years'      => 20,
                'education'             => 'Fellowship in Pediatric Cardiology - Sorbonne University',
                'fee'                   => '65',
                'commission_percentage' => 25,
                'gender'                => 'male',
                'is_active'             => true,
            ],
            [
                'department_id'         => $cardioId,
                'first_name'            => 'Hiba',
                'last_name'             => 'Al-Ahmad',
                'address'               => 'Aleppo, Al-Jamiliyah',
                'email'                 => 'dr.hiba.ahmad@example.com',
                'phone_number'          => '963955777888',
                'experience_years'      => 12,
                'education'             => 'M.D. Specialization in Pediatric Cardiology - Aleppo University',
                'fee'                   => '50',
                'commission_percentage' => 20,
                'gender'                => 'female',
                'is_active'             => true,
            ],
            [
                'department_id'         => $cardioId,
                'first_name'            => 'Wael',
                'last_name'             => 'Khouri',
                'address'               => 'Tartous, Corniche Road',
                'email'                 => 'dr.wael.khouri@example.com',
                'phone_number'          => '963966888999',
                'experience_years'      => 15,
                'education'             => 'M.D. in Congenital Heart Disease - Damascus University',
                'fee'                   => '55',
                'commission_percentage' => 20,
                'gender'                => 'male',
                'is_active'             => true,
            ],
            [
                'department_id'         => $cardioId,
                'first_name'            => 'Dalia',
                'last_name'             => 'Al-Chahine',
                'address'               => 'Damascus, Kafarsouseh',
                'email'                 => 'dr.dalia.chahine@example.com',
                'phone_number'          => '963933999000',
                'experience_years'      => 9,
                'education'             => 'M.D. in Pediatric Cardiology - Damascus University',
                'fee'                   => '45',
                'commission_percentage' => 15,
                'gender'                => 'female',
                'is_active'             => true,
            ],
            [
                'department_id'         => $cardioId,
                'first_name'            => 'Samer',
                'last_name'             => 'Al-Jabi',
                'address'               => 'Hama, Al-Aasi Street',
                'email'                 => 'dr.samer.jabi@example.com',
                'phone_number'          => '963988000111',
                'experience_years'      => 22,
                'education'             => 'Ph.D. in Pediatric Interventional Cardiology - Heidelberg University',
                'fee'                   => '75',
                'commission_percentage' => 25,
                'gender'                => 'male',
                'is_active'             => true,
            ],

            [
                'department_id'         => $neuroId,
                'first_name'            => 'Ghassan',
                'last_name'             => 'Al-Husseini',
                'address'               => 'Damascus, Rawda',
                'email'                 => 'dr.ghassan.husseini@example.com',
                'phone_number'          => '963944222444',
                'experience_years'      => 16,
                'education'             => 'Fellowship in Pediatric Neurology - American University of Beirut',
                'fee'                   => '60',
                'commission_percentage' => 20,
                'gender'                => 'male',
                'is_active'             => true,
            ],
            [
                'department_id'         => $neuroId,
                'first_name'            => 'Salma',
                'last_name'             => 'Al-Atrash',
                'address'               => 'Lattakia, Al-Sulaibiyyah',
                'email'                 => 'dr.salma.atrash@example.com',
                'phone_number'          => '963955333555',
                'experience_years'      => 10,
                'education'             => 'M.D. in Pediatric Neurology - Tishreen University',
                'fee'                   => '45',
                'commission_percentage' => 15,
                'gender'                => 'female',
                'is_active'             => true,
            ],
            [
                'department_id'         => $neuroId,
                'first_name'            => 'Ibrahim',
                'last_name'             => 'Al-Kourd',
                'address'               => 'Aleppo, Al-Mogambo',
                'email'                 => 'dr.ibrahim.kourd@example.com',
                'phone_number'          => '963966444666',
                'experience_years'      => 13,
                'education'             => 'M.D. in Pediatric Epilepsy & Neurology - Aleppo University',
                'fee'                   => '50',
                'commission_percentage' => 20,
                'gender'                => 'male',
                'is_active'             => true,
            ],
            [
                'department_id'         => $neuroId,
                'first_name'            => 'Lina',
                'last_name'             => 'Al-Bitar',
                'address'               => 'Damascus, Al-Midan',
                'email'                 => 'dr.lina.bitar@example.com',
                'phone_number'          => '963933555777',
                'experience_years'      => 7,
                'education'             => 'M.D. in Pediatric Neurology - Damascus University',
                'fee'                   => '40',
                'commission_percentage' => 15,
                'gender'                => 'female',
                'is_active'             => true,
            ],
            [
                'department_id'         => $neuroId,
                'first_name'            => 'Mahmoud',
                'last_name'             => 'Al-Rifai',
                'address'               => 'Homs, Al-Mahatta',
                'email'                 => 'dr.mahmoud.rifai@example.com',
                'phone_number'          => '963988666888',
                'experience_years'      => 19,
                'education'             => 'Ph.D. in Developmental Neuropediatrics - Cairo University',
                'fee'                   => '70',
                'commission_percentage' => 25,
                'gender'                => 'male',
                'is_active'             => true,
            ],
        ];

        foreach ($doctors as $doctor) {
            DB::table('doctors')->updateOrInsert(
                ['email' => $doctor['email']],
                [
                    'department_id'         => $doctor['department_id'],
                    'first_name'            => $doctor['first_name'],
                    'last_name'             => $doctor['last_name'],
                    'address'               => $doctor['address'],
                    'phone_number'          => $doctor['phone_number'],
                    'password'              => $defaultPassword,
                    'experience_years'      => $doctor['experience_years'],
                    'education'             => $doctor['education'],
                    'profile_picture'       => null,
                    'cv'                    => null,
                    'fee'                   => $doctor['fee'],
                    'commission_percentage' => $doctor['commission_percentage'],
                    'gender'                => $doctor['gender'],
                    'fcm_token'             => null,
                    'deleted_at'            => null,
                    'deletion_reason'       => null,
                    'is_active'             => $doctor['is_active'],
                    'created_at'            => Carbon::now(),
                    'updated_at'            => Carbon::now(),
                ]
            );
        }
    }
}

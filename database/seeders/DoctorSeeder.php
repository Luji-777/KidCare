<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {


        $doctors = [
            [
                'first_name' => 'Samer',
                'last_name' => 'Al-Ali',
                'email' => 'samer.ali@example.com',
                'phone_number' => '963911111111',
                'address' => 'Damascus, Mezzeh',
                'experience_years' => 10,
                'education' => 'PhD in Pediatrics',
                'department_id' => Department::inRandomOrder()->first()->id,
                'fee' => 50000,
            ],
            [
                'first_name' => 'Mouna',
                'last_name' => 'Haddad',
                'email' => 'mouna.h@example.com',
                'phone_number' => '963922222222',
                'address' => 'Damascus, Abu Rummaneh',
                'experience_years' => 7,
                'education' => 'Master of Child Psychology',
                'department_id' => Department::inRandomOrder()->first()->id,
                'fee' => 45000,
            ],
            [
                'first_name' => 'Fadi',
                'last_name' => 'Mansour',
                'email' => 'fadi.m@example.com',
                'phone_number' => '963933333333',
                'address' => 'Homs, City Center',
                'experience_years' => 15,
                'department_id' => Department::inRandomOrder()->first()->id,
                'education' => 'Board Certified Pediatric Surgeon',
                'fee' => 70000,
            ],
            [
                'first_name' => 'Rania',
                'last_name' => 'Yassin',
                'email' => 'rania.y@example.com',
                'phone_number' => '963944444444',
                'address' => 'Latakia, Project 10',
                'experience_years' => 5,
                'department_id' => Department::inRandomOrder()->first()->id,
                'education' => 'General Practitioner',
                'fee' => 30000,
            ],
        ];

        foreach ($doctors as $doctor) {
            Doctor::create([
                'first_name'       => $doctor['first_name'],
                'last_name'        => $doctor['last_name'],
                'email'            => $doctor['email'],
                'phone_number'     => $doctor['phone_number'],
                'password'         => Hash::make('doctor123'),
                'address'          => $doctor['address'],
                'experience_years' => $doctor['experience_years'],
                'education'        => $doctor['education'],
                'department_id'    => $doctor['department_id'],
                'fee'              => $doctor['fee'],
                'profile_picture'  => null,
                'cv'               => null,
            ]);
        }
    }
}

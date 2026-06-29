<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $this->call([
        DepartmentSeeder::class,
        DoctorSeeder::class,
        DoctorAvailabilitySeeder::class,
        ChildSeeder::class, 
        AppointmentSeeder::class,
        AdminSeeder::class,
    ]);
}
}

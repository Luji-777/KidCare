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
        // User::factory(10)->create();

        $this->call([
            AdminSeeder::class,
            ReceptionistSeeder::class,
            DepartmentSeeder::class,
            DoctorSeeder::class,
            ChildSeeder::class,
            DoctorAvailabilitySeeder::class,
            ParentSeeder::class,
            AppointmentSeeder::class,
            Appointment_additionsSeeder::class,
            TransactionSeeder::class,

        ]);
    }
}

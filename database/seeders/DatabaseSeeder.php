<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{

    public function run(): void
    {

        $this->call([
            AdminSeeder::class,
            ReceptionistSeeder::class,
            DepartmentSeeder::class,
            ParentSeeder::class,
            ChildSeeder::class,
            GrowthSeeder::class,
            DoctorSeeder::class,
            DoctorAvailabilitySeeder::class,
            AppointmentSeeder::class,
            AppointmentAdditionSeeder::class,
            MedicalRecordSeeder::class,
            TransactionSeeder::class,
            VaccineSeeder::class,
            VaccineScheduleSeeder::class,
            ChildVaccinationSeeder::class,


        ]);
    }
}

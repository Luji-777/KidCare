<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Receptionist;
use Illuminate\Support\Facades\Hash;

class ReceptionistSeeder extends Seeder
{
    public function run(): void
    {

        $receptionist = [
            'name'         => 'Receptionist',
            'phone_number' => '963968539431',
            'password'     => Hash::make('Receptionist12345'),
        ];

        Receptionist::create($receptionist);
    }
}

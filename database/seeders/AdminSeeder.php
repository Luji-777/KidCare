<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = [
            'name'         => 'Super Admin',
            'phone_number' => '963968539430',
            'password'     => Hash::make('admin12345'),
        ];


        Admin::create($admin);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParentModel;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parents = [
            [
                'first_name'   => 'Louay',
                'last_name'    => 'Khneifas',
                'phone_number' => '963992829962',
                'address'      => 'Damascus',
                'email'        => 'louay@example.com',
            ],
            [
                'first_name'   => 'Ahmad',
                'last_name'    => 'Al-Rahali',
                'phone_number' => '963964831822',
                'address'      => 'Aleppo',
                'email'        => 'ahmad@example.com',
            ],
            [
                'first_name'   => 'Jana',
                'last_name'    => 'Hassan',
                'phone_number' => '963932977738',
                'address'      => 'Lattakia',
                'email'        => 'jana@example.com',
            ],
            [
                'first_name'   => 'Batoul',
                'last_name'    => 'Khodari',
                'phone_number' => '963933919220',
                'address'      => 'Homs',
                'email'        => 'batoul@example.com',
            ],
            [
                'first_name'   => 'Lojain',
                'last_name'    => 'Qaraoush',
                'phone_number' => '963968539434',
                'address'      => 'Tartous',
                'email'        => 'lojain@example.com',
            ],
        ];

        foreach ($parents as $data) {
            ParentModel::create([
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'address'        => $data['address'],
                'email'          => $data['email'],
                'phone_number'   => $data['phone_number'],
                'password'       => Hash::make('password123'), // كلمة مرور افتراضية
                'otp_code'       => rand(1000, 9999),
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);
        }
    }
}

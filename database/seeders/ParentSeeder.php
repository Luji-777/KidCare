<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ParentModel;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ParentSeeder extends Seeder
{

    public function run(): void
    {
        $defaultPassword = Hash::make('Password123!');

        $parents = [
            [
                'first_name'   => 'Louay',
                'last_name'    => 'Khneifas',
                'phone_number' => '963992829962',
                'address'      => 'Damascus, Al-Mazza',
                'email'        => 'louay@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Ahmad',
                'last_name'    => 'Al-Rahali',
                'phone_number' => '963964831822',
                'address'      => 'Aleppo, Al-Jamiliyah',
                'email'        => 'ahmad@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Jana',
                'last_name'    => 'Hassan',
                'phone_number' => '963932977738',
                'address'      => 'Lattakia, Project Seventh',
                'email'        => 'jana@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Batoul',
                'last_name'    => 'Khodari',
                'phone_number' => '963933919220',
                'address'      => 'Homs, Al-Hamidiyah',
                'email'        => 'batoul@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Lojain',
                'last_name'    => 'Qaraoush',
                'phone_number' => '963968539434',
                'address'      => 'Tartous, Corniche Road',
                'email'        => 'lojain@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Tarek',
                'last_name'    => 'Al-Masri',
                'phone_number' => '963944123456',
                'address'      => 'Damascus, Abu Rummaneh',
                'email'        => 'tarek@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Nour',
                'last_name'    => 'Al-Din',
                'phone_number' => '963955234567',
                'address'      => 'Hama, Al-Aasi Street',
                'email'        => 'nour@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Omar',
                'last_name'    => 'Kabbani',
                'phone_number' => '963988345678',
                'address'      => 'Damascus, Malki',
                'email'        => 'omar@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Reem',
                'last_name'    => 'Al-Saleh',
                'phone_number' => '963933456789',
                'address'      => 'Lattakia, Al-Sulaibiyyah',
                'email'        => 'reem@example.com',
                'is_blocked'   => false,
                'block_reason' => null,
            ],
            [
                'first_name'   => 'Youssef',
                'last_name'    => 'Al-Hamwi',
                'phone_number' => '963966567890',
                'address'      => 'Aleppo, Al-Shahbaa',
                'email'        => 'youssef@example.com',
                'is_blocked'   => true,
                'block_reason' => 'Repeated policy violations',
            ],
        ];

        foreach ($parents as $parent) {
            DB::table('parent_models')->updateOrInsert(
                ['email' => $parent['email']],
                [
                    'first_name'     => $parent['first_name'],
                    'last_name'      => $parent['last_name'],
                    'phone_number'   => $parent['phone_number'],
                    'address'        => $parent['address'],
                    'password'       => $defaultPassword,
                    'otp_code'       => null,
                    'otp_expires_at' => null,
                    'fcm_token'      => null,
                    'is_blocked'     => $parent['is_blocked'],
                    'block_reason'   => $parent['block_reason'],
                    'deleted_at'     => null,
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now(),
                ]
            );
        }
    }
}

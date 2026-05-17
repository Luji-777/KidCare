<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\ParentModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // تأكدي من وجود هذا السطر
use Carbon\Carbon; // تأكدي من وجود هذا السطر

class ChildSeeder extends Seeder
{
    public function run(): void
    {
        // 1. مصفوفة الآباء الخاصة بكِ بالأسماء والبيانات الحقيقية
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

        // 2. حلقة التكرار لإنشاء الآباء وتوليد أطفال تابعين لهم تلقائياً
        foreach ($parents as $data) {

            // إنشاء الأب أولاً وحفظه في متغير $parent
            $parent = ParentModel::create([
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'address'        => $data['address'],
                'email'          => $data['email'],
                'phone_number'   => $data['phone_number'],
                'password'       => Hash::make('password123'), // كلمة المرور الخاصة بكِ لتجربة الـ Postman
                'otp_code'       => rand(1000, 9999),
                'otp_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            // الحركة الاحترافية: ننشئ فوراً عدد عشوائي (من 1 إلى 3 أطفال) يتبعون لهذا الأب المحدد
            Child::factory()->count(rand(1, 3))->create([
                'parent_id' => $parent->id,
                'last_name' => $parent->last_name, // الطفل يأخذ كنية الأب الحقيقية (مثل Khneifas أو Hassan)
            ]);
        }
    }
}

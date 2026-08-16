<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorAvailability;
use App\Models\Appointment;
use App\Models\Child;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void

    {
        $childIds = Child::pluck('id')->toArray();
        if (empty($childIds)) $childIds = [1, 2, 3];

        // 1. جلب جدول دوام الدكتور رقم 1 (أحمد العلي)
        $doctor1Availabilities = DoctorAvailability::where('doctor_id', 1)->get();

        if ($doctor1Availabilities->isNotEmpty()) {

            // --- أولاً: ملء بيانات اليوم الحالي (Today) بشكل ديناميكي ذكي ---
            $today = Carbon::today();
            $now = Carbon::now();

            // أ) موعد مكتمل (في الماضي بالنسبة للساعة الحالية من اليوم) -> لتعبئة خانة Completed و Revenue
            Appointment::create([
                'child_id'        => Arr::random($childIds),
                'doctor_id'       => 1,
                'date'            => $today->toDateString(),
                'time'            => $now->copy()->subHours(2)->format('H:i:s'), // دائماً قبل ساعتين من تشغيل السيدر
                'status'          => 'completed',
                'price'           => 100,
                // 'base_price' => 100,
                'currency'        => 'USD',
                'payment_status'  => 'paid_online',
                'doctor_earnings' => 60,
            ]);

            // ب) موعد قادم فوراً (Next Patient) -> يظهر مباشرة كأول مريض قادم
            Appointment::create([
                'child_id'        => Arr::random($childIds),
                'doctor_id'       => 1,
                'date'            => $today->toDateString(),
                'time'            => $now->copy()->addMinutes(30)->format('H:i:s'), // دائماً بعد نصف ساعة من تشغيل السيدر
                'status'          => 'confirmed',
                'price'           => 100,
                //'base_price' => 100,
                'currency'        => 'USD',
                'payment_status'  => 'paid_online',
                'doctor_earnings' => 60,
            ]);

            // ج) موعد متبقي لاحقاً اليوم (Remaining Patient) -> ليملأ القائمة السفلية لليوم
            Appointment::create([
                'child_id'        => Arr::random($childIds),
                'doctor_id'       => 1,
                'date'            => $today->toDateString(),
                'time'            => $now->copy()->addHours(3)->format('H:i:s'), // دائماً بعد 3 ساعات من تشغيل السيدر
                'status'          => 'confirmed',
                'price'           => 100,
                // 'base_price' => 100,
                'currency'        => 'USD',
                'payment_status'  => 'paid_online',
                'doctor_earnings' => 60,
            ]);


            // --- ثانياً: توليد مواعيد حقيقية للأيام القادمة والماضية لتعبئة الإحصائيات العامة ---

            // 1. مواعيد ماضية (خلال الـ 15 يوماً السابقة) لزيادة الأرباح الشهرية والسنوية بشكل منطقي
            for ($dayOffset = 1; $dayOffset <= 15; $dayOffset++) {
                $pastDate = Carbon::today()->subDays($dayOffset);

                // ننشئ موعدين مكتملين في كل يوم مضى
                for ($i = 0; $i < 2; $i++) {
                    Appointment::create([
                        'child_id'        => Arr::random($childIds),
                        'doctor_id'       => 1,
                        'date'            => $pastDate->toDateString(),
                        'time'            => sprintf('%02d:00:00', rand(9, 16)), // بين الـ 9 صباحاً والـ 4 عصراً
                        'status'          => 'completed',
                        'price'           => 100,
                        //'base_price' => 100,
                        'currency'        => 'USD',
                        'payment_status'  => 'fully_paid',
                        'doctor_earnings' => 60,
                    ]);
                }
            }

            // 2. مواعيد مستقبلية (خلال الـ 15 يوماً القادمة) لكي يجد الطبيب مواعيد عند تصفح الأيام القادمة
            for ($dayOffset = 1; $dayOffset <= 15; $dayOffset++) {
                $futureDate = Carbon::today()->addDays($dayOffset);
                $dayOfWeekName = $futureDate->format('l');

                // مطابقة الأوقات مع جدول دوامه الفعلي
                $availabilitiesForToday = $doctor1Availabilities->where('day_of_week', $dayOfWeekName);
                if ($availabilitiesForToday->isEmpty()) {
                    $availabilitiesForToday = $doctor1Availabilities;
                }

                $chosenSlots = $availabilitiesForToday->random(min(2, $availabilitiesForToday->count()));

                foreach ($chosenSlots as $slot) {
                    Appointment::create([
                        'child_id'        => Arr::random($childIds),
                        'doctor_id'       => 1,
                        'date'            => $futureDate->toDateString(),
                        'time'            => $slot->start_time,
                        'status'          => 'confirmed',
                        'price'           => 100,
                        // 'base_price' => 100,
                        'currency'        => 'USD',
                        'payment_status'  => 'paid_online',
                        'doctor_earnings' => 60,
                    ]);
                }
            }
        }

        // 3. مواعيد عشوائية لباقي الدكاترة لضمان حيوية قاعدة البيانات بالكامل
        $doctorIds = Doctor::where('id', '>', 1)->pluck('id')->toArray();
        if (!empty($doctorIds)) {
            for ($i = 1; $i <= 40; $i++) {
                $isPast = $i % 2 === 0;
                $date = $isPast
                    ? Carbon::now()->subDays(rand(1, 20))->toDateString()
                    : Carbon::now()->addDays(rand(1, 20))->toDateString();

                $price = rand(50, 150);

                Appointment::create([
                    'child_id'        => Arr::random($childIds),
                    'doctor_id'       => Arr::random($doctorIds),
                    'date'            => $date,
                    'time'            => sprintf('%02d:00:00', rand(9, 17)),
                    'status'          => $isPast ? 'completed' : 'confirmed',
                    'price'           => $price,
                    //'base_price'      => $price,
                    'currency'        => 'USD',
                    'payment_status'  => $isPast ? 'fully_paid' : 'paid_online',
                    'doctor_earnings' => $price * 0.6,
                ]);
            }
        }
    }

    /* public function run(): void
    {
        $childIds = Child::pluck('id')->toArray();
        $doctorIds = Doctor::pluck('id')->toArray();

        if (empty($doctorIds) || empty($childIds)) {
            $this->command->warn('يرجى التأكد من وجود أطباء وأطفال في قاعدة البيانات أولاً!');
            return;
        }


        for ($i = 1; $i <= 20; $i++) {

            $isPast = $i <= 10;

            $date = $isPast
                ? Carbon::today()->subDays(rand(1, 20))->toDateString()
                : Carbon::today()->addDays(rand(1, 20))->toDateString();

            Appointment::create([
                'child_id'        => Arr::random($childIds),
                'doctor_id'       => Arr::random($doctorIds),
                'date'            => $date,
                'time'            => sprintf('%02d:00:00', rand(9, 16)),
                'status'          => $isPast ? 'completed' : 'confirmed',
                'price'           => 100.00,
                'currency'        => 'USD',
                'payment_status'  => $isPast ? 'fully_paid' : 'paid_online',
                'doctor_earnings' => 50.00,
            ]);
        }
    }*/
}

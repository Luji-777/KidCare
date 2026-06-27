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

        // 1. جلب الأوقات المتاحة للدكتور رقم 1 حصراً من الداتابيز
        $doctor1Availabilities = DoctorAvailability::where('doctor_id', 1)->get();

        if ($doctor1Availabilities->isNotEmpty()) {
            
            // نمشي على الـ 10 أيام القادمة (ابتداءً من اليوم)
            for ($dayOffset = 0; $dayOffset < 10; $dayOffset++) {
                
                $currentCarbonDate = Carbon::today()->addDays($dayOffset);
                $dayOfWeekName = $currentCarbonDate->format('l'); // بيعطينا اسم اليوم مثل 'Sunday' أو 'Monday'

                // بنجيب الأوقات المتاحة للدكتور يلي بتوافق هاد اليوم من الأسبوع
                $availabilitiesForToday = $doctor1Availabilities->where('day_of_week', $dayOfWeekName);

                // إذا الدكتور ما عنده دوام بهاد اليوم (مثلاً الجمعة أو السبت)، بنعمل خيار بديل 
                // أو بناخد أي وقتين عشوائيين من الأوقات المتاحة عنده كرمال ما نضيع اليوم
                if ($availabilitiesForToday->isEmpty()) {
                    $availabilitiesForToday = $doctor1Availabilities;
                }

                // بناخد وقتين متاحين (بشكل عشوائي أو أول وقتين) كرمال ننشئ الموعدين
                $chosenSlots = $availabilitiesForToday->random(min(2, $availabilitiesForToday->count()));

                // توليد الموعدين لهذا اليوم
                foreach ($chosenSlots as $slot) {
                    Appointment::create([
                        'child_id'        => Arr::random($childIds),
                        'doctor_id'       => 1,
                        'date'            => $currentCarbonDate->toDateString(), // نفس اليوم
                        'time'            => $slot->start_time,                 // الوقت المطابق لدوامه
                        'status'          => 'confirmed',
                        'price'           => 100,
                        'currency'        => 'USD',
                        'payment_status'  => 'paid_online',
                        'doctor_earnings' => 80,
                    ]);
                }
            }
        }

        // 2. باقي المواعيد العشوائية لباقي الدكاترة (كما هي بدون تغيير)
        $doctorIds = Doctor::where('id', '>', 1)->pluck('id')->toArray();

        for ($i = 1; $i <= 20; $i++) {
            $isPast = $i % 2 === 0;

            if ($isPast) {
                $date = Carbon::now()->subDays(rand(1, 30))->toDateString();
                $status = Arr::random(['completed', 'cancelled']);
                $paymentStatus = Arr::random(['paid_online', 'fully_paid']);
            } else {
                $date = Carbon::now()->addDays(rand(1, 30))->toDateString();
                $status = Arr::random(['pending', 'confirmed']);
                $paymentStatus = Arr::random(['unpaid', 'partially_paid']);
            }

            $price = rand(50, 200);

            Appointment::create([
                'child_id'        => Arr::random($childIds),
                'doctor_id'       => Arr::random($doctorIds),
                'date'            => $date,
                'time'            => sprintf('%02d:00:00', rand(9, 17)),
                'status'          => $status,
                'price'           => $price,
                'currency'        => 'USD',
                'payment_status'  => $paymentStatus,
                'doctor_earnings' => $price * 0.8,
            ]);
        }
    }
}
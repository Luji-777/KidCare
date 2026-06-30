<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Child;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $childIds = Child::pluck('id')->toArray();
        $doctorIds = Doctor::pluck('id')->toArray();

        if (empty($childIds) || empty($doctorIds)) {
            $this->command->warn('يرجى عمل Seed لجدول الأطفال والأطباء أولاً!');
            return;
        }


        $statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
        $paymentStatuses = ['unpaid', 'paid_online', 'partially_paid', 'fully_paid']; //


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
            $doctorEarnings = $price * 0.8;

            Appointment::create([
                'child_id'        => Arr::random($childIds),
                'doctor_id'       => Arr::random($doctorIds),
                'date'            => $date,
                'time'            => sprintf('%02d:00:00', rand(9, 17)),
                'status'          => $status,
                'price'           => $price,
                'currency'        => 'USD',
                'payment_status'  => $paymentStatus,
                'doctor_earnings' => $doctorEarnings,
                'booking_source'  => Arr::random(['online', 'reception']),
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ]);
        }

        $this->command->info('تمت إضافة 20 موعد بنجاح وبمتوافقية كاملة مع الميجريشن!');
    }
}

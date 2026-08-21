<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Child;
use App\Models\Doctor;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $children = Child::all();
        $doctor = Doctor::find(1);

        if ($children->isEmpty() || !$doctor) {
            return;
        }

        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $hours = ['09:00', '10:30', '12:00', '14:30', '16:00'];

        // 1. إنشاء 20 موعداً كتمت (completed) في الـ 20 يوماً الماضية
        for ($i = 1; $i <= 20; $i++) {
            $pastDate = $today->copy()->subDays($i)->toDateString();
            $time = $hours[array_rand($hours)];

            $this->createAppointment(
                $children->random(),
                $doctor,
                $pastDate,
                $time,
                'completed',
                'fully_paid'
            );
        }

        // 2. إنشاء 5 مواعيد لبكرة (جميع أوقات غداً المتاحة)
        foreach ($hours as $time) {
            $this->createAppointment(
                $children->random(),
                $doctor,
                $tomorrow->toDateString(),
                $time,
                'confirmed',
                'paid_online'
            );
        }

        // 3. إنشاء 5 مواعيد قادمة باقي هذا الأسبوع
        for ($i = 2; $i <= 6; $i++) {
            $futureDate = $today->copy()->addDays($i)->toDateString();
            $time = $hours[array_rand($hours)];

            $this->createAppointment(
                $children->random(),
                $doctor,
                $futureDate,
                $time,
                'confirmed',
                'paid_online'
            );
        }
    }

    private function createAppointment(
        Child $child,
        Doctor $doctor,
        string $date,
        string $time,
        string $status,
        string $paymentStatus
    ): void {
        $price = (float) $doctor->fee;
        $commissionRate = (float) $doctor->commission_percentage;
        $doctorEarnings = $price * ($commissionRate / 100);

        Appointment::create([
            'child_id'        => $child->id,
            'doctor_id'       => $doctor->id,
            'date'            => $date,
            'time'            => $time,
            'status'          => $status,
            'price'           => $price,
            'currency'        => 'USD',
            'payment_status'  => $paymentStatus,
            'doctor_earnings' => $doctorEarnings,
            'booking_source'  => 'online',
        ]);
    }
}

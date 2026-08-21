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
        $doctors = Doctor::all();

        if ($children->isEmpty() || $doctors->isEmpty()) {
            return;
        }

        $today = Carbon::today();
        $startOfYear = Carbon::now()->startOfYear();

        // الساعات المتاحة العامة لباقي الأطباء
        $generalWorkingHours = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

        // أوقات الطبيب رقم 1 الخاصة
        $docOneHours = ['12:00', '14:30'];

        // مصفوفة لتتبع المواعيد الحالية لمنع التضارب نهائياً [doctor_id][date][time] = true
        $bookedSlots = [];

        // 1. المواعيد الخاصة للطبيب رقم 1 (اليوم وغداً)
        $doctorOne = Doctor::find(1);
        if ($doctorOne) {
            $tomorrow = $today->copy()->addDay();

            foreach ([$today->toDateString(), $tomorrow->toDateString()] as $dateStr) {
                foreach ($docOneHours as $time) {
                    $child = $children->random();
                    $this->createAppointmentRecord(
                        $child,
                        $doctorOne,
                        $dateStr,
                        $time,
                        'confirmed',
                        $bookedSlots
                    );
                }
            }
        }

        // 2. إنشاء موعد لكل طفل أولاً (ضمان تغطية جميع الأطفال)
        foreach ($children as $child) {
            $doctor = $doctors->random();
            $isPast = rand(0, 1) === 1;

            $this->generateUniqueAppointment(
                $child,
                $doctor,
                $isPast,
                $startOfYear,
                $today,
                $generalWorkingHours,
                $bookedSlots
            );
        }

        // 3. إنشاء 60 موعداً إضافياً عشوائياً
        for ($i = 0; $i < 60; $i++) {
            $child = $children->random();
            $doctor = $doctors->random();
            $isPast = rand(0, 1) === 1;

            $this->generateUniqueAppointment(
                $child,
                $doctor,
                $isPast,
                $startOfYear,
                $today,
                $generalWorkingHours,
                $bookedSlots
            );
        }
    }

    private function generateUniqueAppointment(
        Child $child,
        Doctor $doctor,
        bool $isPast,
        Carbon $startOfYear,
        Carbon $today,
        array $workingHours,
        array &$bookedSlots
    ): void {
        $maxAttempts = 30; // محاولات للبحث عن وقت غير محجوز
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $attempt++;

            if ($isPast) {
                // مواعيد سابقة من بداية السنة وحتى الأمس
                $daysDiff = max(1, $startOfYear->diffInDays($today->copy()->subDay()));
                $date = $startOfYear->copy()->addDays(rand(0, $daysDiff))->toDateString();
                $status = 'completed';
            } else {
                // مواعيد قادمة ابتداءً من اليوم
                $daysToAdd = (rand(1, 100) <= 80) ? rand(0, 7) : rand(8, 60);
                $date = $today->copy()->addDays($daysToAdd)->toDateString();
                $status = 'confirmed';
            }

            // إذا كان الطبيب رقم 1 واليوم هو اليوم أو غداً، نستخدم أوقاته الخاصة حصراً
            if ($doctor->id === 1 && in_array($date, [$today->toDateString(), $today->copy()->addDay()->toDateString()])) {
                $time = ['12:00', '14:30'][array_rand(['12:00', '14:30'])];
            } else {
                $time = $workingHours[array_rand($workingHours)];
            }

            if (!isset($bookedSlots[$doctor->id][$date][$time])) {
                $this->createAppointmentRecord($child, $doctor, $date, $time, $status, $bookedSlots);
                break;
            }
        }
    }

    private function createAppointmentRecord(
        Child $child,
        Doctor $doctor,
        string $date,
        string $time,
        string $status,
        array &$bookedSlots
    ): void {
        $bookedSlots[$doctor->id][$date][$time] = true;

        if ($status === 'completed') {
            $paymentStatus = 'fully_paid';
            $bookingSource = (rand(0, 1) === 1) ? 'online' : 'reception';
        } else {
            $bookingSource = (rand(0, 1) === 1) ? 'online' : 'reception';
            $paymentStatus = ($bookingSource === 'online') ? 'paid_online' : 'unpaid';
        }

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
            'booking_source'  => $bookingSource,
        ]);
    }
}

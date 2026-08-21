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
        $workingHours = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'];

        foreach ($children as $child) {
            $isPast = rand(0, 1) === 1;
            $doctor = $doctors->random();

            $this->createAppointment($child, $doctor, $isPast, $startOfYear, $today, $workingHours);
        }

        $extraAppointmentsCount = 60;
        $doctorsArray = $doctors->values()->all();
        $doctorsCount = count($doctorsArray);

        for ($i = 0; $i < $extraAppointmentsCount; $i++) {
            $child = $children->random();

            $doctorIndex = rand(0, rand(0, $doctorsCount - 1));
            $doctor = $doctorsArray[$doctorIndex];

            $isPast = rand(0, 1) === 1;

            $this->createAppointment($child, $doctor, $isPast, $startOfYear, $today, $workingHours);
        }
    }

    private function createAppointment(
        Child $child,
        Doctor $doctor,
        bool $isPast,
        Carbon $startOfYear,
        Carbon $today,
        array $workingHours
    ): void {
        if ($isPast) {
            $daysDiff = max(1, $startOfYear->diffInDays($today->copy()->subDay()));
            $date = $startOfYear->copy()->addDays(rand(0, $daysDiff))->toDateString();
            $status = 'completed';
            $paymentStatus = 'fully_paid';
            $bookingSource = (rand(0, 1) === 1) ? 'online' : 'reception';
        } else {
            if (rand(1, 100) <= 80) {
                $daysToAdd = rand(0, 7);
            } else {
                $daysToAdd = rand(8, 60);
            }

            $date = $today->copy()->addDays($daysToAdd)->toDateString();
            $status = 'confirmed';
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
            'time'            => $workingHours[array_rand($workingHours)],
            'status'          => $status,
            'price'           => $price,
            'currency'        => 'USD',
            'payment_status'  => $paymentStatus,
            'doctor_earnings' => $doctorEarnings,
            'booking_source'  => $bookingSource,
        ]);
    }
}

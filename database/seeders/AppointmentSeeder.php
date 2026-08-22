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
        $doctors = Doctor::with('availabilities')->get();

        if ($children->isEmpty() || $doctors->isEmpty()) {
            return;
        }

        $today = Carbon::today();
        $startOfYear = Carbon::now()->startOfYear();

        $bookedSlots = [];
        $childIndex = 0;
        $totalChildren = $children->count();

        $maxTotalAppointments = 200;
        $createdCount = 0;

        $doctorOne = $doctors->firstWhere('id', 1);
        if ($doctorOne) {
            $docOneHours = ['12:00', '14:30'];
            foreach ([$today->toDateString(), $today->copy()->addDay()->toDateString()] as $dateStr) {
                foreach ($docOneHours as $time) {
                    if ($createdCount >= $maxTotalAppointments) break 2;

                    $child = $children[$childIndex % $totalChildren];
                    $childIndex++;

                    $this->createAppointmentRecord($child, $doctorOne, $dateStr, $time, 'confirmed', $bookedSlots);
                    $createdCount++;
                }
            }
        }

        $currentMonth = $today->month;
        $appointmentsPerMonth = (int) floor(($maxTotalAppointments - $createdCount) / $currentMonth);

        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthCreated = 0;
            
            $daysInMonth = Carbon::create($today->year, $month, 1)->daysInMonth;
            $step = max(1, (int) floor($daysInMonth / max(1, $appointmentsPerMonth)));

            for ($day = 1; $day <= $daysInMonth; $day += $step) {
                if ($monthCreated >= $appointmentsPerMonth || $createdCount >= $maxTotalAppointments) {
                    break;
                }

                $carbonDate = Carbon::create($today->year, $month, $day);
                
                if ($carbonDate->gt($today)) {
                    break;
                }

                $dateStr = $carbonDate->toDateString();
                $dayName = strtolower($carbonDate->format('l'));
                $isPast = $carbonDate->lt($today);

                foreach ($doctors as $doctor) {
                    if ($monthCreated >= $appointmentsPerMonth || $createdCount >= $maxTotalAppointments) {
                        break;
                    }

                    if ($doctor->id === 1 && in_array($dateStr, [$today->toDateString(), $today->copy()->addDay()->toDateString()])) {
                        continue;
                    }

                    $availabilities = $doctor->availabilities
                        ->filter(fn($a) => strtolower($a->day_of_week) === $dayName)
                        ->sortBy('start_time');

                    foreach ($availabilities as $availability) {
                        if ($monthCreated >= $appointmentsPerMonth || $createdCount >= $maxTotalAppointments) {
                            break;
                        }

                        $time = Carbon::parse($availability->start_time)->format('H:i');

                        if (!isset($bookedSlots[$doctor->id][$dateStr][$time])) {
                            $child = $children[$childIndex % $totalChildren];
                            $childIndex++;

                            $status = $isPast ? 'completed' : 'confirmed';

                            $this->createAppointmentRecord(
                                $child,
                                $doctor,
                                $dateStr,
                                $time,
                                $status,
                                $bookedSlots
                            );
                            $createdCount++;
                            $monthCreated++;
                        }
                    }
                }
            }
        }

        if ($doctorOne) {
            $daysInCurrentMonth = $today->daysInMonth;

            for ($day = 1; $day <= $daysInCurrentMonth; $day += 2) {
                $carbonDate = Carbon::create($today->year, $today->month, $day);
                $dateStr = $carbonDate->toDateString();
                $dayName = strtolower($carbonDate->format('l'));
                $isPast = $carbonDate->lt($today);

                $availabilities = $doctorOne->availabilities
                    ->filter(fn($a) => strtolower($a->day_of_week) === $dayName)
                    ->sortBy('start_time');

                foreach ($availabilities as $availability) {
                    $time = Carbon::parse($availability->start_time)->format('H:i');

                    if (!isset($bookedSlots[$doctorOne->id][$dateStr][$time])) {
                        $child = $children[$childIndex % $totalChildren];
                        $childIndex++;

                        $status = $isPast ? 'completed' : 'confirmed';

                        $this->createAppointmentRecord(
                            $child,
                            $doctorOne,
                            $dateStr,
                            $time,
                            $status,
                            $bookedSlots
                        );
                    }
                }
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
            $bookingSource = ($child->id % 2 === 0) ? 'online' : 'reception';
        } else {
            $bookingSource = ($child->id % 2 === 0) ? 'online' : 'reception';
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
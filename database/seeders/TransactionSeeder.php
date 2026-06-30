<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Models\Appointment_additions;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {

        $appointments = Appointment::take(5)->get();

        foreach ($appointments as $index => $appointment) {

            $appointment->update([
                'date' => Carbon::now()->subDays($index)->format('Y-m-d'),
                'price' => 100.00,
                'booking_source' => $index % 2 == 0 ? 'online' : 'reception',
            ]);

            Transaction::create([
                'appointment_id' => $appointment->id,
                'stripe_payment_intent_id' => $appointment->booking_source == 'online' ? 'pi_test_' . uniqid() : null,
                'amount' => 100.00,
                'currency' => 'USD',
                'status' => 'succeeded',
                'payment_method' => $appointment->booking_source == 'online' ? 'stripe' : 'cash',
                'type' => 'fixed',
                'created_at' => Carbon::now()->subDays($index),
            ]);

            $addition1 = Appointment_additions::create([
                'appointment_id' => $appointment->id,
                'item_name' => 'Medical Supplies',
                'price' => 25.00,
            ]);

            $addition2 = Appointment_additions::create([
                'appointment_id' => $appointment->id,
                'item_name' => 'Lab Test',
                'price' => 50.00,
            ]);

            Transaction::create([
                'appointment_id' => $appointment->id,
                'stripe_payment_intent_id' => null,
                'amount' => 75.00,
                'currency' => 'USD',
                'status' => 'succeeded',
                'payment_method' => 'cash',
                'type' => 'additions',
                'created_at' => Carbon::now()->subDays($index),
            ]);
        }
    }
}

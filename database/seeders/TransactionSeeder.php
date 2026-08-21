<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        Appointment::whereIn('status', ['confirmed', 'completed'])
            ->with('additions')
            ->chunk(100, function ($appointments) {
                foreach ($appointments as $appointment) {

                    $isOnline = $appointment->booking_source === 'online';
                    $fixedPaymentMethod = $isOnline ? 'stripe' : 'cash';

                    if ($appointment->status === 'confirmed') {
                        // فقط أونلاين، أما الرسبشن فلا يتم إنشاء شيء له
                        if ($isOnline) {
                            $this->createTransaction(
                                $appointment->id,
                                $appointment->price,
                                'succeeded',
                                'stripe',
                                'fixed',
                                'pi_' . Str::random(24)
                            );
                        }
                    } elseif ($appointment->status === 'completed') {

                        $this->createTransaction(
                            $appointment->id,
                            $appointment->price,
                            'succeeded',
                            $fixedPaymentMethod,
                            'fixed',
                            $isOnline ? 'pi_' . Str::random(24) : null
                        );

                        $additionsTotal = $appointment->additions->sum(function ($addition) {
                            return $addition->pivot->price ?? $addition->price ?? 0;
                        });

                        if ($additionsTotal > 0) {
                            $this->createTransaction(
                                $appointment->id,
                                $additionsTotal,
                                'succeeded',
                                'cash',
                                'additions',
                                null
                            );
                        }
                    }
                }
            });
    }

    private function createTransaction(
        int $appointmentId,
        float $amount,
        string $status,
        string $paymentMethod,
        string $type,
        ?string $stripeIntentId = null
    ): void {
        DB::table('transactions')->insert([
            'appointment_id'           => $appointmentId,
            'stripe_payment_intent_id' => $stripeIntentId,
            'amount'                   => $amount,
            'currency'                 => 'USD',
            'status'                   => $status,
            'payment_method'           => $paymentMethod,
            'type'                     => $type,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }
}

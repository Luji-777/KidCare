<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;
use Exception;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{

    public function getSummary($appointment_id)
    {

        $appointment = Appointment::with(['child', 'doctor.department'])
            ->where('id', $appointment_id)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment is not found'], 404);
        }


        if ($appointment->child->parent_id !== auth()->id()) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $patient_full_name = trim($appointment->child->first_name . ' ' . $appointment->child->last_name);
        $doctor_full_name  = trim($appointment->doctor->first_name . ' ' . $appointment->doctor->last_name);


        $patient_age = Carbon::parse($appointment->child->birth_date)->age;

        return response()->json([
            "patient_name"      => $patient_full_name,
            "patient_age"       => (string)$patient_age,
            "patient_image_url" => $appointment->child->image ? url('storage/' . $appointment->child->image) : '',
            "doctor_name"       => $doctor_full_name,
            "department_name"   => $appointment->doctor->department->name,
            "date_time"         => $appointment->date . ' ' . $appointment->time,
            "price"             => (string)$appointment->price,
            "currency"          => $appointment->currency,
        ], 200);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|string',
            'currency'       => 'required|string|max:3',
        ]);

        $pendingAppointmentId = $request->appointment_id;
        $appointmentData = Cache::get("pending_appointment_{$pendingAppointmentId}");


        if (!$appointmentData) {
            return response()->json(['message' => 'Appointment session expired or not found.'], 404);
        }


        if ($appointmentData['parent_id'] !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized action for this financial transaction.'], 403);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {

            $amountInCents = round($appointmentData['price'] * 100);


            $intent = PaymentIntent::create([
                'amount'   => $amountInCents,
                'currency' => strtolower($request->currency),
                'metadata' => [
                    'pending_appointment_id' => $pendingAppointmentId,
                    'parent_id'              => auth()->id()
                ]
            ]);


            $transaction = Transaction::create([
                'appointment_id'           => null,
                'stripe_payment_intent_id' => $intent->id,
                'amount'                   => $appointmentData['price'],
                'currency'                 => $request->currency,
                'status'                   => $intent->status,
            ]);


            return response()->json([
                "status"         => $intent->status,
                "client_secret"  => $intent->client_secret,
                "transaction_id" => (string)$transaction->id
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Stripe payment initialization failed: ' . $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }


        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;


            $pendingAppointmentId = $paymentIntent->metadata->pending_appointment_id ?? null;

            if ($pendingAppointmentId) {

                $appointmentData = Cache::get("pending_appointment_{$pendingAppointmentId}");

                if ($appointmentData) {


                    $alreadyExists = Appointment::where('doctor_id', $appointmentData['doctor_id'])
                        ->where('date', $appointmentData['date'])
                        ->where('time', $appointmentData['time'])
                        ->exists();

                    if (!$alreadyExists) {

                        DB::beginTransaction();
                        try {

                            $appointment = Appointment::create([
                                'child_id'       => $appointmentData['child_id'],
                                'doctor_id'      => $appointmentData['doctor_id'],
                                'date'           => $appointmentData['date'],
                                'time'           => $appointmentData['time'],
                                'status'         => 'confirmed',
                                'payment_status' => 'paid_online',
                                'price'          => $appointmentData['price'],
                            ]);


                            $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntent->id)->first();

                            if ($transaction) {
                                $transaction->update([
                                    'appointment_id' => $appointment->id,
                                    'status'         => 'succeeded'
                                ]);
                            }

                            DB::commit();


                            Cache::forget("pending_appointment_{$pendingAppointmentId}");
                            Cache::forget("booked_slot_{$appointmentData['doctor_id']}_{$appointmentData['date']}_{$appointmentData['time']}");
                        } catch (Exception $e) {
                            DB::rollBack();

                            logger('Webhook failed to create appointment: ' . $e->getMessage());
                            return response()->json(['error' => 'Database operation failed'], 500);
                        }
                    }
                }
            }
        }


        return response()->json(['status' => 'success'], 200);
    }
}

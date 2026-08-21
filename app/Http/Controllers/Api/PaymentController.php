<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Models\Notification as DBNotification;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;
use Exception;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Doctor;
use App\Models\Child;
use App\Models\ParentModel;
use App\Models\DoctorNotification;
use App\Services\FirebaseNotificationService;


class PaymentController extends Controller
{
    //--------------Patient--------------
    public function getSummary($appointment_id)
    {

        $appointmentData = Cache::get("pending_appointment_{$appointment_id}");

        if ($appointmentData) {

            $child = Child::find($appointmentData['child_id']);
            $doctor = Doctor::with('department')->find($appointmentData['doctor_id']);

            if (!$child || !$doctor) {
                return response()->json(['message' => __('messages.appointment_details_not_found')], 404);
            }


            if ($child->parent_id !== auth()->id()) {
                return response()->json(['message' => __('messages.unauthorized')], 403);
            }

            $birthDate = Carbon::parse($child->birth_date);
            $now = Carbon::now();

            $years = (int) $birthDate->diffInYears($now);
            $months = (int) $birthDate->diffInMonths($now);
            $days = (int) $birthDate->diffInDays($now);

            if ($years >= 1) {
                $age = $years;
                $ageType = 'year';
            } elseif ($months >= 1) {
                $age = $months;
                $ageType = 'month';
            } else {
                $age = $days;
                $ageType = 'day';
            }

            $patient_full_name = trim($child->first_name . ' ' . $child->last_name);
            $doctor_full_name  = trim($doctor->first_name . ' ' . $doctor->last_name);
            $patient_image     = $child->image ?? '';
            $cleanDeptKey = str_replace('messages.', '', strtolower(trim($doctor->department->name ?? '')));
            $department_name = __('messages.' . $cleanDeptKey);
            $date_time         = $appointmentData['date'] . ' ' . $appointmentData['time'];
            $price             = (string)$appointmentData['price'];
            $currency          = 'USD';
        } else {

            $appointment = Appointment::with(['child', 'doctor.department'])
                ->where('id', $appointment_id)
                ->first();

            if (!$appointment) {
                return response()->json(['message' => __('messages.appointment_not_found')], 404);
            }


            if ($appointment->child->parent_id !== auth()->id()) {
                return response()->json(['message' =>  __('messages.unauthorized')], 403);
            }

            $patient_full_name = trim($appointment->child->first_name . ' ' . $appointment->child->last_name);
            $doctor_full_name  = trim($appointment->doctor->first_name . ' ' . $appointment->doctor->last_name);
            $patient_image     = $appointment->child->image ?? '';
            $cleanDeptKey = str_replace('messages.', '', strtolower(trim($doctor->department->name ?? '')));
            $department_name = __('messages.' . $cleanDeptKey);
            $date_time         = $appointment->date . ' ' . $appointment->time;
            $price             = (string)$appointment->price;
            $currency          = $appointment->currency ?? 'USD';
        }

        return response()->json([
            "patient_name"      => $patient_full_name,
            "patient_age"       => (int) $age,
            "age_type"          => $ageType,
            "patient_image_url" => $patient_image,
            "doctor_name"       => $doctor_full_name,
            "department_name"   => $department_name,
            "date_time"         => $date_time,
            "price"             => $price,
            "currency"          => $currency,
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
            return response()->json(['message' => __('messages.appointment_session_expired')], 404);
        }

        if ($appointmentData['parent_id'] !== auth()->id()) {
            return response()->json(['message' => __('messages.unauthorized_transaction')], 403);
        }

        $cacheKeyTransaction = "transaction_for_{$pendingAppointmentId}";
        if (Cache::has($cacheKeyTransaction)) {
            $existingTransactionData = Cache::get($cacheKeyTransaction);

            return response()->json([
                "status"         => $existingTransactionData['status'],
                "client_secret"  => $existingTransactionData['client_secret'],
                "transaction_id" => (string)$existingTransactionData['transaction_id']
            ], 200);
        }

        $lockKey = "lock_checkout_{$pendingAppointmentId}";
        if (Cache::has($lockKey)) {
            return response()->json(['message' => __('messages.payment_processing_wait')], 400);
        }
        Cache::put($lockKey, true, now()->addSeconds(10));

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

            $transactionDataToCache = [
                'status'        => $intent->status,
                'client_secret' => $intent->client_secret,
                'transaction_id' => $transaction->id
            ];
            Cache::put($cacheKeyTransaction, $transactionDataToCache, now()->addMinutes(15));

            Cache::forget($lockKey);

            return response()->json([
                "status"         => $intent->status,
                "client_secret"  => $intent->client_secret,
                "transaction_id" => (string)$transaction->id
            ], 200);
        } catch (Exception $e) {
            Cache::forget($lockKey);
            return response()->json(['error' => __('messages.stripe_init_failed') . $e->getMessage()], 500);
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
                            $doctor = Doctor::find($appointmentData['doctor_id']);
                            $doctorEarnings = $appointmentData['price'] * ($doctor->commission_percentage / 100);

                            $appointment = Appointment::create([
                                'child_id'       => $appointmentData['child_id'],
                                'doctor_id'      => $appointmentData['doctor_id'],
                                'date'           => $appointmentData['date'],
                                'time'           => $appointmentData['time'],
                                'status'         => 'confirmed',
                                'payment_status' => 'paid_online',
                                'price'          => $appointmentData['price'],
                                'doctor_earnings' => $doctorEarnings,

                            ]);

                            $child = Child::find($appointment->child_id);

                            $parent = ParentModel::find($child->parent_id);

                            $doctor = Doctor::find($appointment->doctor_id);

                            $doctorTitle = __('messages.notification_new_appointment_title');

                            $doctorBody = __('messages.notification_new_appointment_doctor_body', [
                                'child' => $child->first_name . ' ' . $child->last_name,
                                'date'  => $appointment->date,
                                'time'  => $appointment->time,
                            ]);

                            DoctorNotification::create([
                                'doctor_id' => $appointment->doctor_id,
                                'title'     => $doctorTitle,
                                'message'   => $doctorBody,
                            ]);

                            // Push Notification للطبيب
                            if ($doctor && $doctor->fcm_token) {

                                $doctorMessage = CloudMessage::withTarget(
                                    'token',
                                    $doctor->fcm_token
                                )->withNotification(
                                    FirebaseNotification::create(
                                        $doctorTitle,
                                        $doctorBody
                                    )
                                )->withData([
                                    'type'           => 'new_appointment',
                                    'appointment_id' => (string) $appointment->id,
                                    'child_id'       => (string) $child->id,
                                    'date'           => \Carbon\Carbon::parse($appointment->date)->toDateString(),
                                    'time'           => $appointment->time,
                                    'sound'          => 'default',
                                ]);

                                app('firebase.messaging')->send($doctorMessage);
                            }

                            DBNotification::create([
                                'parent_id' => $parent->id,
                                'title' => __('messages.appointment_confirmed_title'),
                                'message' => __('messages.appointment_confirmed_body')
                            ]);

                            if ($parent && $parent->fcm_token) {

                                $message = CloudMessage::withTarget(
                                    'token',
                                    $parent->fcm_token
                                )
                                    ->withNotification(
                                        FirebaseNotification::create(
                                            __('messages.notification_appointment_confirmed_title'),
                                            __('messages.notification_appointment_confirmed_body')
                                        )
                                    )
                                    ->withData([
                                        'appointment_id' => (string) $appointment->id,
                                        'sound' => 'default'
                                    ]);

                                app('firebase.messaging')->send($message);
                            }
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


    //-----------Dashboard--------------
    public function completePayment($appointment_id)
    {

        return DB::transaction(function () use ($appointment_id) {

            $appointment = Appointment::with('additions')->findOrFail($appointment_id);
            $additionsTotal = $appointment->additions->sum('price');

            if ($appointment->status === 'cancelled_by_patient'  || $appointment->status === 'cancelled_by_clinic') {
                return response()->json([
                    'status'  => 'messages.error',
                    'message' => __('messages.cannot_complete_payment_for_cancelled_appointment')
                ], 400);
            }

            if ($appointment->booking_source == 'online') {

                if ($additionsTotal > 0) {
                    $appointment->transactions()->create([
                        'amount'         => $additionsTotal,
                        'payment_method' => 'cash',
                        'type'           => 'additions',
                        'status'         => 'succeeded',
                    ]);
                }
            } elseif ($appointment->booking_source == 'reception') {

                $appointment->transactions()->create([
                    'amount'         => $appointment->price,
                    'payment_method' => 'cash',
                    'type'           => 'fixed',
                    'status'         => 'succeeded',
                ]);

                if ($additionsTotal > 0) {
                    $appointment->transactions()->create([
                        'amount'         => $additionsTotal,
                        'payment_method' => 'cash',
                        'type'           => 'additions',
                        'status'         => 'succeeded',
                    ]);
                }
            }

            $appointment->update(['status' => 'completed']);
            $appointment->update(['payment_status' => 'fully_paid']);

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.payment_completed_successfully'),
            ], 200);
        });
    }

    public function getSummaryForReception($appointment_id)
    {

        $appointment = Appointment::with(['additions', 'transactions', 'doctor', 'child'])
            ->findOrFail($appointment_id);

        if ($appointment->status === 'cancelled_by_clinic' || $appointment->status === 'cancelled_by_patient') {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.cannot_view_summary_for_cancelled_appointment')
            ], 400);
        }

        $fixedPrice = $appointment->price;
        $additionsTotal = $appointment->additions->sum('price');

        $amountPaidOnline = $appointment->transactions()
            ->where('payment_method', 'stripe')
            ->where('status', 'succeeded')
            ->sum('amount');

        if ($appointment->booking_source == 'online') {

            $amountToPayCash = $additionsTotal;
            $fixedPriceStatus = 'Paid Online';
        } else {

            $amountToPayCash = $fixedPrice + $additionsTotal;
            $fixedPriceStatus = 'Pending Cash Payment';
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'appointment_id'   => $appointment->id,
                'booking_source'   => $appointment->booking_source,
                'appointment_date' => $appointment->date,
                'patient_name'     => $appointment->child ? $appointment->child->first_name . ' ' . $appointment->child->last_name : null,
                'doctor_name'      => $appointment->doctor ? $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name : null,

                'fixed_price'        => $fixedPrice,
                'fixed_price_status' => $fixedPriceStatus,

                'additions' => $appointment->additions->map(function ($addition) {
                    return [
                        'item_name' => $addition->item_name,
                        'price'     => $addition->price
                    ];
                }),

                'totals' => [
                    'fixed_price_total' => $fixedPrice,
                    'additions_total'   => $additionsTotal,
                    'already_paid_online' => $amountPaidOnline,
                    'required_cash_now'  => $amountToPayCash
                ]
            ]
        ], 200);
    }
}

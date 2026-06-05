<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\DoctorAvailability;
use App\Models\Appointment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use App\Models\Transaction;
use Carbon\Carbon;
use Stripe\Refund;
use Illuminate\Support\Facades\DB;
use Exception;



class AppointmentController extends Controller
{

    public function store(StoreAppointmentRequest $request)
    {

        $child = auth()->user()->children()->where('id', $request->child_id)->first();
        if (!$child) {
            return response()->json(['message' => 'Child not found'], 404);
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');
        $time = Carbon::parse($request->time)->format('H:i');

        $appointmentDateTime = Carbon::parse("$date $time");

        if ($appointmentDateTime->isPast()) {
            return response()->json([
                'message' => 'You cannot book an appointment in the past.'
            ], 400);
        }

        $day = strtolower(Carbon::parse($date)->format('l'));

        $availability = DoctorAvailability::where('doctor_id', $request->doctor_id)
            ->where('day_of_week', $day)
            ->first();

        if (!$availability) {
            return response()->json(['message' => 'Doctor is not available on this day'], 400);
        }

        $start = Carbon::parse($availability->start_time)->format('H:i');
        $end = Carbon::parse($availability->end_time)->format('H:i');

        if ($time < $start || $time >= $end) {
            return response()->json(['message' => 'Time is outside doctor working hours'], 400);
        }

        $isBooked = Appointment::where('doctor_id', $request->doctor_id)
            ->where('date', $date)
            ->where('time', $time)
            ->where('status', '!=', 'canceled')
            ->exists();

        if ($isBooked) {
            return response()->json(['message' => 'Time already booked'], 400);
        }

        $cacheKeySlot = "booked_slot_{$request->doctor_id}_{$date}_{$time}";
        if (Cache::has($cacheKeySlot)) {
            return response()->json(['message' => 'This time is temporarily locked for payment'], 400);
        }

        $doctor = \App\Models\Doctor::findOrFail($request->doctor_id);

        $pendingAppointmentId = (string) Str::uuid();
        $appointmentData = [
            'child_id'  => $request->child_id,
            'doctor_id' => $request->doctor_id,
            'date'      => $date,
            'time'      => $time,
            'price'     => $doctor->fee,
            'parent_id' => auth()->id()
        ];


        Cache::put("pending_appointment_{$pendingAppointmentId}", $appointmentData, now()->addMinutes(15));
        Cache::put($cacheKeySlot, true, now()->addMinutes(15));


        return response()->json([
            'message'        => 'Appointment locked temporarily. Proceed to checkout to pay.',
            'appointment_id' => $pendingAppointmentId,
        ], 201);
    }
    
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $doctorId = $request->doctor_id ?? $appointment->doctor_id;

        $childId = $request->child_id ?? $appointment->child_id;

        $date = $request->date
            ? Carbon::parse($request->date)->format('Y-m-d')
            : $appointment->date;

        $time = $request->time
            ? Carbon::parse($request->time)->format('H:i')
            : $appointment->time;

        $day = strtolower(Carbon::parse($date)->format('l'));

        $availability = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('day_of_week', $day)
            ->first();

        if (!$availability) {
            return response()->json([
                'message' => 'Doctor is not available on this day'
            ], 400);
        }

        $start = Carbon::parse($availability->start_time)->format('H:i');
        $end = Carbon::parse($availability->end_time)->format('H:i');

        if ($time < $start || $time >= $end) {
            return response()->json([
                'message' => 'Time is outside doctor working hours'
            ], 400);
        }

        $isBooked = Appointment::where('doctor_id', $doctorId)
            ->where('date', $date)
            ->where('time', $time)
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($isBooked) {
            return response()->json([
                'message' => 'Time already booked'
            ], 400);
        }
        $doctor = \App\Models\Doctor::findOrFail($request->doctor_id);


        $appointment->update([
            'child_id' => $childId,
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $time,
            'price' => $request->price ?? $appointment->price,
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }

    public function index()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->latest()
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price',
                'created_at'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }

    public function show(Appointment $appointment)
    {
        $isOwner = auth()->user()
            ->children()
            ->where('id', $appointment->child_id)
            ->exists();

        if (!$isOwner) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }


        return response()->json([
            'appointment' => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'child_id' => $appointment->child_id,
                'date' => $appointment->date,
                'time' => $appointment->time,
                'status' => $appointment->status,
                'price' => $appointment->price,
            ]
        ]);
    }

    public function destroy(Appointment $appointment)
    {

        $isOwner = auth()->user()
            ->children()
            ->where('id', $appointment->child_id)
            ->exists();

        if (!$isOwner) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointmentDateTime = Carbon::parse("{$appointment->date} {$appointment->time}");


        if ($appointmentDateTime->isPast()) {
            return response()->json(['message' => 'Cannot cancel a past appointment.'], 400);
        }

        $hoursRemaining = now()->diffInHours($appointmentDateTime, false);

        $refundPercentage = 1.00;
        $message = 'Appointment canceled. Full refund has been initiated.';

        if ($hoursRemaining < 48) {
            $refundPercentage = 0.75;
            $message = 'Appointment canceled. Refund initiated with a 25% cancellation fee deducted.';
        }

        $transaction = Transaction::where('appointment_id', $appointment->id)
            ->where('status', 'succeeded')
            ->first();

        Stripe::setApiKey(config('services.stripe.secret'));

        DB::beginTransaction();

        try {
            if ($transaction) {

                $refundAmountInCents = round(($transaction->amount * $refundPercentage) * 100);


                $refund = Refund::create([
                    'payment_intent' => $transaction->stripe_payment_intent_id,
                    'amount'         => $refundAmountInCents,
                    'metadata'       => [
                        'appointment_id' => $appointment->id,
                        'reason'         => $hoursRemaining < 48 ? 'Canceled within 48 hours' : 'Canceled well in advance'
                    ]
                ]);

                Transaction::create([
                    'appointment_id'           => $appointment->id,
                    'stripe_payment_intent_id' => $refund->id,
                    'amount'                   => ($transaction->amount * $refundPercentage),
                    'currency'                 => $transaction->currency,
                    'status'                   => 'refunded',
                ]);
            }

            $appointment->update([
                'status' => 'canceled'
            ]);

            DB::commit();

            return response()->json([
                'message' => $message,
                'refund_amount' => $transaction ? ($transaction->amount * $refundPercentage) : 0
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Cancellation and Refund failed: ' . $e->getMessage()], 500);
        }
    }

    public function upcoming()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }
    public function past()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->whereDate('date', '<', now()->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }

    public function upcomingByChild($childId)
    {
        $appointments = Appointment::whereHas('child', function ($query) use ($childId) {
            $query->where('parent_id', auth()->id())
                ->where('id', $childId);
        })
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }

    public function pastByChild($childId)
    {
        $appointments = Appointment::whereHas('child', function ($query) use ($childId) {
            $query->where('parent_id', auth()->id())
                ->where('id', $childId);
        })
            ->whereDate('date', '<', now()->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Services\FirebaseNotificationService;
use App\Models\DoctorAvailability;
use App\Models\DoctorNotification;
use App\Models\Appointment;
use App\Models\ParentModel;
use App\Models\Receptionist;
use App\Models\Child;
use App\Models\Notification as DBNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Stripe\Stripe;
use Stripe\Refund;
use Stripe\PaymentIntent;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Doctor;



class AppointmentController extends Controller
{

    public function store(StoreAppointmentRequest $request)
    {


        $child = auth()->user()->children()->where('id', $request->child_id)->first();
        if (!$child) {
            return response()->json(['message' => __('messages.child_not_found')], 404);
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');
        $time = Carbon::parse($request->time)->format('H:i');

        $appointmentDateTime = Carbon::parse("$date $time");

        if ($appointmentDateTime->isPast()) {
            return response()->json([
                'message' => __('messages.cannot_book_past')
            ], 400);
        }

        $day = strtolower(Carbon::parse($date)->format('l'));

        $availability = DoctorAvailability::where('doctor_id', $request->doctor_id)
            ->where('day_of_week', $day)
            ->first();

        if (!$availability) {
            return response()->json(['message' => __('messages.doctor_not_available_day')], 400);
        }

        $start = Carbon::parse($availability->start_time)->format('H:i');
        $end = Carbon::parse($availability->end_time)->format('H:i');

        if ($time < $start || $time >= $end) {
            return response()->json(['message' => __('messages.outside_working_hours')], 400);
        }

        $isBooked = Appointment::where('doctor_id', $request->doctor_id)
            ->where('date', $date)
            ->where('time', $time)
            ->where('status', '!=', 'cancelled_by_patient')
            ->where('status', '!=', 'cancelled_by_clinic')
            ->exists();

        if ($isBooked) {
            return response()->json(['message' => __('messages.time_already_booked')], 400);
        }

        $cacheKeySlot = "booked_slot_{$request->doctor_id}_{$date}_{$time}";
        if (Cache::has($cacheKeySlot)) {
            return response()->json(['message' => __('messages.slot_temporarily_locked')], 400);
        }
        $doctor = \App\Models\Doctor::findOrFail($request->doctor_id);

        $pendingAppointmentId = (string) Str::uuid();
        $appointmentData = [
            'child_id'  => $request->child_id,
            'doctor_id' => $request->doctor_id,
            'date'      => $date,
            'time'      => $time,
            'price'     => $doctor->fee,
            'parent_id' => auth()->id(),


        ];


        Cache::put("pending_appointment_{$pendingAppointmentId}", $appointmentData, now()->addMinutes(15));
        Cache::put($cacheKeySlot, true, now()->addMinutes(15));


        return response()->json([
            'status'         => 'success',
            'message'        => __('messages.appointment_locked_success'),
            'appointment_id' => $pendingAppointmentId,
        ], 201);
    }
    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $isOwner = auth()->user()
            ->children()
            ->where('id', $appointment->child_id)
            ->exists();

        if (!$isOwner) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized'),
            ], 403);
        }
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
                'message' => __('messages.doctor_not_available_day')
            ], 400);
        }

        $start = Carbon::parse($availability->start_time)->format('H:i');
        $end = Carbon::parse($availability->end_time)->format('H:i');

        if ($time < $start || $time >= $end) {
            return response()->json([
                'message' => __('messages.outside_working_hours')
            ], 400);
        }

        $isBooked = Appointment::where('doctor_id', $doctorId)
            ->where('date', $date)
            ->where('time', $time)
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($isBooked) {
            return response()->json([
                'message' => __('messages.time_already_booked')
            ], 400);
        }
        $doctor = \App\Models\Doctor::findOrFail($doctorId);


        $appointment->update([
            'child_id' => $childId,
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $time,
            'price' => $request->price ?? $appointment->price,
        ]);

        return response()->json([
            'status'      => 'success',
            'message'     => __('messages.appointment_updated_success'),
            'appointment' => [
                'id'        => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'child_id'  => $appointment->child_id,
                'date'      => $appointment->date,
                'time'      => $appointment->time,
                'price'     => $appointment->price,
                'status'    => __('messages.' . $appointment->status),
            ]
        ], 200);
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
            'status'       => 'success',
            'message'      => __('messages.index_success'),
            'appointments' => $appointments->map(fn($app) => [
                'id' => $app->id,
                'doctor_id' => $app->doctor_id,
                'child_id' => $app->child_id,
                'date' => $app->date,
                'time' => $app->time,
                'price' => $app->price,
                'created_at' => $app->created_at,
                'status' => __('messages.' . $app->status)
            ])
        ], 200);
    }

    public function show(Appointment $appointment)
    {
        $currentUser = auth()->user();

        if ($currentUser instanceof ParentModel) {
            $isOwner = $currentUser->children()->where('id', $appointment->child_id)->exists();
            if (!$isOwner) {
                return response()->json(['message' => __('messages.unauthorized')], 403);
            }
        } elseif (!($currentUser instanceof Receptionist)) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        return response()->json([
            'status'      => 'success',
            'message'     => __('messages.show_success'),
            'appointment' => [
                'id'        => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'child_id'  => $appointment->child_id,
                'date'      => $appointment->date,
                'time'      => $appointment->time,
                'price'     => $appointment->price,
                'status'    => __('messages.' . $appointment->status),
            ]
        ], 200);
    }

    public function destroy(Appointment $appointment, FirebaseNotificationService $firebase)
    {
        $isOwner = auth()->user()
            ->children()
            ->where('id', $appointment->child_id)
            ->exists();

        if (!$isOwner) {
            return response()->json([
                'message' => __('messages.unauthorized')
            ], 403);
        }
        if ($appointment->status !== 'confirmed') {
            return response()->json([
                'message' => __('messages.cannot_cancel_appointment')
            ], 400);
        }

        $appointmentDateTime = Carbon::parse("{$appointment->date} {$appointment->time}");

        if ($appointmentDateTime->isPast()) {
            return response()->json([
                'message' => __('messages.cannot_cancel_past')
            ], 400);
        }

        $hoursRemaining = now()->diffInHours($appointmentDateTime, false);

        $refundPercentage = 1.00;
        $message = __('messages.cancel_full_refund');

        if ($hoursRemaining < 48) {
            $refundPercentage = 0.75;
            $message = __('messages.cancel_fee_deducted');
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
                    'amount' => $refundAmountInCents,
                    'metadata' => [
                        'appointment_id' => $appointment->id,
                        'reason' => $hoursRemaining < 48
                            ? 'Canceled within 48 hours'
                            : 'Canceled well in advance'
                    ]
                ]);

                Transaction::create([
                    'appointment_id' => $appointment->id,
                    'stripe_payment_intent_id' => $refund->id,
                    'amount' => $transaction->amount * $refundPercentage,
                    'currency' => $transaction->currency,
                    'status' => 'refunded',
                ]);
            }

            $appointment->update([
                'status' => 'cancelled_by_patient'
            ]);

            $refundAmount = $transaction
                ? ($transaction->amount * $refundPercentage)
                : 0;

            if ($hoursRemaining < 48) {
                $notificationBody = __('messages.notif_cancel_fee', [
                    'amount' => $refundAmount
                ]);
            } else {
                $notificationBody = __('messages.notif_cancel_full', [
                    'amount' => $refundAmount
                ]);
            }
            $parent = auth()->user();
            $child = Child::find($appointment->child_id);
            $doctor = Doctor::find($appointment->doctor_id);

            DoctorNotification::create([
                'doctor_id' => $appointment->doctor_id,
                'title' => 'Appointment Cancelled',
                'message' => $child->first_name . ' ' .
                    $child->last_name .
                    ' cancelled the appointment on ' .
                    $appointment->date .
                    ' at ' .
                    $appointment->time,
            ]);

            DBNotification::create([
                'parent_id' => $parent->id,
                'message' => $notificationBody
            ]);

            DB::commit();
            Cache::forget("booked_slot_{$appointment->doctor_id}_{$appointment->date}_{$appointment->time}");
            // Push Notification للطبيب
            if ($doctor && !empty($doctor->fcm_token)) {

                $firebase->send(
                    $doctor->fcm_token,

                    __('messages.notification_appointment_cancelled_title'),

                    __('messages.notification_appointment_cancelled_doctor_body', [
                        'child' => $child->first_name . ' ' . $child->last_name,
                        'date' => $appointment->date,
                        'time' => $appointment->time,
                    ])
                );
            }

            if (!empty($parent->fcm_token)) {

                $firebase->send(
                    $parent->fcm_token,

                    __('messages.notification_appointment_cancelled_title'),

                    $notificationBody
                );
            }

            return response()->json([
                'message' => $message,
                'refund_amount' => $refundAmount
            ], 200);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Cancellation and Refund failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function upcoming()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->with([
                'child:id,first_name,image,gender',
                'doctor:id,first_name,last_name,department_id',
                'doctor.department:id,name',
            ])
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $formattedAppointments = $appointments->map(function ($appointment) {
            return [
                'id'          => $appointment->id,
                'status'      => __('messages.' . $appointment->status),
                'price'       => $appointment->price,
                'date'        => $appointment->date,
                'time'        => $appointment->time,
                'child' => [
                    'id'         => $appointment->child_id,
                    'first_name' => $appointment->child?->first_name,
                    'image'      => $appointment->child?->image,
                    'gender'     => $appointment->child?->gender,
                ],
                'doctor' => [
                    'id'         => $appointment->doctor_id,
                    'full_name'  => $appointment->doctor ? $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name : null,
                    'department' => $appointment->doctor?->department?->name,
                ]
            ];
        });

        return response()->json([
            'status'       => 'success',
            'message'      => __('messages.upcoming_success'),
            'appointments' => $formattedAppointments
        ], 200);
    }

    public function past()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->with([
                'child:id,first_name,image,gender',
                'doctor:id,first_name,last_name,department_id',
                'doctor.department:id,name',
            ])
            ->whereDate('date', '<', now()->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->where('status', '!=', 'cancelled_by_patient')
            ->where('status', '!=', 'cancelled_by_clinic')
            ->get();

        $formattedAppointments = $appointments->map(function ($appointment) {
            return [
                'id'          => $appointment->id,
                'status'      => __('messages.' . $appointment->status),
                'price'       => $appointment->price,
                'date'        => $appointment->date,
                'time'        => $appointment->time,
                'child' => [
                    'id'         => $appointment->child_id,
                    'first_name' => $appointment->child?->first_name,
                    'image'      => $appointment->child?->image,
                    'gender'     => $appointment->child?->gender,
                ],
                'doctor' => [
                    'id'         => $appointment->doctor_id,
                    'full_name'  => $appointment->doctor ? $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name : null,
                    'department' => $appointment->doctor?->department?->name,
                ]
            ];
        });

        return response()->json([
            'status'       => 'success',
            'message'      => __('messages.past_success'),
            'appointments' => $formattedAppointments
        ], 200);
    }

    public function upcomingByChild($childId)
    {
        $currentUser = auth()->user();

        $childExists = Child::where('id', $childId)->exists();
        if (!$childExists) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.child_not_found')
            ], 404);
        }

        $query = Appointment::query();

        if ($currentUser instanceof ParentModel) {
            $query->whereHas('child', function ($q) use ($childId, $currentUser) {
                $q->where('parent_id', $currentUser->id)
                    ->where('id', $childId);
            });
        } elseif ($currentUser instanceof Receptionist) {
            $query->where('child_id', $childId);
        } else {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $appointments = $query->with([
            'child:id,first_name,image,gender',
            'doctor:id,first_name,last_name,department_id',
            'doctor.department:id,name',
        ])
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $formattedAppointments = $appointments->map(function ($appointment) {
            return [
                'id'     => $appointment->id,
                'status' => __('messages.' . $appointment->status),
                'price'  => $appointment->price,
                'date'   => $appointment->date,
                'time'   => $appointment->time,
                'child'  => [
                    'id'         => $appointment->child_id,
                    'first_name' => $appointment->child?->first_name,
                    'image'      => $appointment->child?->image,
                    'gender'     => $appointment->child?->gender,
                ],
                'doctor' => [
                    'id'         => $appointment->doctor_id,
                    'full_name'  => $appointment->doctor ? $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name : null,
                    'department' => $appointment->doctor?->department?->name,
                ]
            ];
        });

        return response()->json([
            'status'       => 'success',
            'message'      => __('messages.upcoming_child_success'),
            'appointments' => $formattedAppointments
        ], 200);
    }

    public function pastByChild($childId)
    {
        $currentUser = auth()->user();

        $childExists = Child::where('id', $childId)->exists();
        if (!$childExists) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.child_not_found')
            ], 404);
        }

        $query = Appointment::query();

        if ($currentUser instanceof ParentModel) {
            $query->whereHas('child', function ($q) use ($childId, $currentUser) {
                $q->where('parent_id', $currentUser->id)
                    ->where('id', $childId);
            });
        } elseif ($currentUser instanceof Receptionist) {
            $query->where('child_id', $childId);
        } else {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $appointments = $query->with([
            'child:id,first_name,image,gender',
            'doctor:id,first_name,last_name,department_id',
            'doctor.department:id,name',
        ])
            ->whereDate('date', '<', now()->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->where('status', '!=', 'cancelled_by_patient')
            ->where('status', '!=', 'cancelled_by_clinic')
            ->get();

        $formattedAppointments = $appointments->map(function ($appointment) {
            return [
                'id'     => $appointment->id,
                'status' => __('messages.' . $appointment->status),
                'price'  => $appointment->price,
                'date'   => $appointment->date,
                'time'   => $appointment->time,
                'child'  => [
                    'id'         => $appointment->child_id,
                    'first_name' => $appointment->child?->first_name,
                    'image'      => $appointment->child?->image,
                    'gender'     => $appointment->child?->gender,
                ],
                'doctor' => [
                    'id'         => $appointment->doctor_id,
                    'full_name'  => $appointment->doctor ? $appointment->doctor->first_name . ' ' . $appointment->doctor->last_name : null,
                    'department' => $appointment->doctor?->department?->name,
                ]
            ];
        });

        return response()->json([
            'status'       => 'success',
            'message'      => __('messages.past_child_success'),
            'appointments' => $formattedAppointments
        ], 200);
    }

    public function getClosestAppointmentPerDoctor($departmentId)
    {
        $doctors = Doctor::where('department_id', $departmentId)
            ->select('id', 'first_name', 'last_name', 'profile_picture')
            ->get();

        if ($doctors->isEmpty()) {
            return response()->json([
                'status'  => 'success',
                'message' => __('messages.no_doctors_in_department'),
                'data'    => []
            ], 200);
        }
        $result = [];
        $maxDaysToCheck = 14;
        $now = Carbon::now();

        foreach ($doctors as $doctor) {
            $closestAppointment = null;
            $baseDate = Carbon::today();

            for ($i = 0; $i < $maxDaysToCheck; $i++) {
                $currentDate = $baseDate->copy()->addDays($i);
                $dateString = $currentDate->toDateString();

                $dayName = $currentDate->format('l');
                $dayNameLower = strtolower($dayName);


                $availability = DoctorAvailability::where('doctor_id', $doctor->id)
                    ->where(function ($query) use ($dayName, $dayNameLower) {
                        $query->whereRaw('TRIM(day_of_week) = ?', [$dayName])
                            ->orWhereRaw('LOWER(TRIM(day_of_week)) = ?', [$dayNameLower]);
                    })
                    ->first();

                if ($availability) {

                    $startTimeInstance = Carbon::parse($dateString . ' ' . trim($availability->start_time));
                    $endTimeInstance = Carbon::parse($dateString . ' ' . trim($availability->end_time));

                    while ($startTimeInstance->lt($endTimeInstance)) {
                        if ($startTimeInstance->lte($now)) {
                            $startTimeInstance->addMinutes(30);
                            continue;
                        }

                        $timeWithSeconds = $startTimeInstance->format('H:i:00');
                        $timeWithoutSeconds = $startTimeInstance->format('H:i');

                        $isBooked = Appointment::where('doctor_id', $doctor->id)
                            ->where('date', $dateString)
                            ->where('status', '!=', 'cancelled_by_patient')
                            ->where('status', '!=', 'cancelled_by_clinic')
                            ->where(function ($q) use ($timeWithSeconds, $timeWithoutSeconds) {
                                $q->where('time', $timeWithoutSeconds)
                                    ->orWhere('time', $timeWithSeconds);
                            })
                            ->exists();

                        if (!$isBooked) {
                            $closestAppointment = [
                                'date'      => $dateString,
                                'time'      => $timeWithoutSeconds,
                                'day_name'  => __("messages.days." . $dayNameLower)
                            ];
                            break 2;
                        }

                        $startTimeInstance->addMinutes(30);
                    }
                }
            }
            $result[] = [
                'doctor_id'           => $doctor->id,
                'doctor_name'         => trim($doctor->first_name . ' ' . $doctor->last_name),
                'profile_picture_url' => $doctor->profile_picture,
                'closest_appointment' => $closestAppointment
            ];
        }
        return response()->json([
            'status'  => 'success',
            'message' => __('messages.closest_appointments_fetched'),
            'data'    => $result
        ], 200);
    }

    public function appointmentDetails($id)
    {
        $doctor = auth()->user();

        $appointment = Appointment::with('child')
            ->where('doctor_id', $doctor->id)
            ->findOrFail($id);

        $child = $appointment->child;

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

        return response()->json([
            'status' => true,
            'data' => [
                'appointment_id'   => $appointment->id,
                'date'             => $appointment->date,
                'day'              => Carbon::parse($appointment->date)->format('l'),
                'time'             => $appointment->time,
                'status'           => $appointment->status,
                'consultation_fee' => $appointment->price,
                'currency'         => $appointment->currency,
                'payment_status'   => $appointment->payment_status,

                'child' => [
                    'id'       => $child->id,
                    'name'     => $child->first_name . ' ' . $child->last_name,
                    'image'    => $child->image,
                    'gender'   => $child->gender,
                    'age'      => (int) $age,
                    'age_type' => $ageType,
                ],
            ]
        ]);
    }

    public function addMedicalRequests(Request $request, $appointmentId)
    {
        $request->validate([
            'required_tests' => 'nullable|string',
            'required_imaging' => 'nullable|string',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);

        $appointment->update([
            'required_tests' => $request->required_tests,
            'required_imaging' => $request->required_imaging,
        ]);

        return response()->json([
            'message' =>  __('messages.Medical_requests'),
            //'appointment' => $appointment
        ]);
    }

    public function checkIn(Appointment $appointment, FirebaseNotificationService $firebase)
    {
        if ($appointment->status !== 'confirmed') {
            return response()->json([
                'message' => __('messages.invalid_status_for_checkin')
            ], 400);
        }

        if (!\Carbon\Carbon::parse($appointment->date)->isToday()) {
            return response()->json([
                'message' => __('messages.checkin_today_only')
            ], 400);
        }

        DB::beginTransaction();

        try {

            $appointment->update([
                'status' => 'checked_in',
            ]);

            $child = Child::find($appointment->child_id);
            $doctor = Doctor::find($appointment->doctor_id);

            $notifTitle = 'Patient Arrived';
            $notifMessage = "{$child->first_name} {$child->last_name} is now in the waiting room.";

            DoctorNotification::create([
                'doctor_id' => $appointment->doctor_id,
                'title' => $notifTitle,
                'message' => $notifMessage,
            ]);

            DB::commit();

            if ($doctor && !empty($doctor->fcm_token)) {
                $firebase->send(
                    $doctor->fcm_token,
                    $notifTitle,
                    $notifMessage
                );
            }

            return response()->json([
                'message' => __('messages.patient_checked_in_successfully'),
                'appointment' => $appointment
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Check-in failed: ' . $e->getMessage()
            ], 500);
        }
    }
}

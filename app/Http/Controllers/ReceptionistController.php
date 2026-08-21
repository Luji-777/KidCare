<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\ParentModel;
use App\Models\Receptionist;
use App\Models\Appointment;
use App\Models\DoctorAvailability;
use App\Models\Doctor;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Services\FirebaseNotificationService;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Notification as DBNotification;
use Stripe\Stripe;
use Stripe\Refund;
use Illuminate\Support\Facades\DB;
use App\Models\DoctorNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Str;

class ReceptionistController extends Controller
{
    public function loginReceptionist(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'password'     => 'required|string|min:6|max:255'
        ]);

        $admin = Receptionist::where('phone_number', $request->phone_number)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.invalid_credentials')
            ], 401);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('messages.login_welcome_back'),
            'user'    => [
                'id'           => $admin->id,
                'phone_number' => $admin->phone_number,
                'name'   => $admin->name,
            ],
            'Token'   => $token,
        ], 200);
    }
    public function SetReceptionistPassword(Request $request)
    {

        $request->validate([
            'phone_number' => 'required',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);


        $admin = Receptionist::where('phone_number', $request->phone_number)->first();

        if (!$admin) {
            return response()->json(
                [
                    'status' => __('messages.error'),
                    'message' =>  __('messages.user_not_found'),
                ],
                404
            );
        }

        $admin->update([
            'password'       => Hash::make($request->password)
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' =>  __('messages.password_updated_success'),
            'token'   => $token
        ], 200);
    }
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->tokens()->delete();

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.logout_succssfuly')
            ], 200);
        }

        return response()->json([
            'status'  => __('messages.error'),
            'message' => __('messages.no_active_session')
        ], 401);
    }
    public function getTodayAddedChildrenCount()
    {
        $todayCount = Child::whereDate('created_at', Carbon::today())->count();
        return response()->json([
            'status' => 'success',
            'today_added_children_count' => $todayCount
        ], 200);
    }
    public function addParent(Request $request)
    {
        $validated = $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'address'      => 'required|string|max:255',
            'phone_number' => 'required|digits_between:9,15|unique:parent_models,phone_number',
            'email'        => 'required|string|email|max:255|unique:parent_models,email',
        ]);
        $randomPassword = Str::random(10);
        $newParent = ParentModel::create([
            'first_name'   => $validated['first_name'],
            'last_name'    => $validated['last_name'],
            'address'      => $validated['address'],
            'email'        => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password'     => bcrypt($randomPassword),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.parent_added_successfully'),
            'data' => $newParent,
            'generated_password' => $randomPassword
        ], 201);
    }
    public function store(StoreAppointmentRequest $request)
    {
        $currentUser = $request->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $child = Child::find($request->child_id);
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
            ->where('status', '!=', 'canceled')
            ->exists();

        if ($isBooked) {
            return response()->json(['message' => __('messages.time_already_booked')], 400);
        }

        $cacheKeySlot = "booked_slot_{$request->doctor_id}_{$date}_{$time}";
        if (Cache::has($cacheKeySlot)) {
            return response()->json(['message' => __('messages.slot_temporarily_locked')], 400);
        }

        $doctor = Doctor::findOrFail($request->doctor_id);

        $appointment = Appointment::create([
            'child_id'        => $request->child_id,
            'doctor_id'       => $request->doctor_id,
            'date'            => $date,
            'time'            => $time,
            'price'           => $doctor->fee,
            'status'          => 'confirmed',
            'booking_source'  => 'reception',
            'payment_status'  => 'unpaid',
        ]);

        return response()->json([
            'status'      => 'success',
            'message' => __('messages.appointment_booked_successfully'),
            'appointment' => $appointment,
        ], 201);
    }
    public function updateReception(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $currentUser = $request->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => '' . __('messages.unauthorized'),
            ], 403);
        }

        $doctorId = $request->doctor_id ?? $appointment->doctor_id;
        $childId  = $request->child_id ?? $appointment->child_id;

        $childExists = Child::where('id', $childId)->exists();
        if (!$childExists) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.child_not_found')
            ], 404);
        }

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
            ->where('status', '!=', 'canceled')
            ->exists();

        if ($isBooked) {
            return response()->json([
                'message' => __('messages.time_already_booked')
            ], 400);
        }
        $appointment->update([
            'child_id'  => $childId,
            'doctor_id' => $doctorId,
            'date'      => $date,
            'time'      => $time,
            'price'     => $request->price ?? $appointment->price,
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
    public function indexReception()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->withTrashed();
        })
            ->with([
                'child' => function ($query) {
                    $query->withTrashed()->select('id', 'first_name', 'last_name');
                },
                'doctor' => function ($query) {
                    $query->withTrashed()->select('id', 'first_name', 'last_name');
                }
            ])
            ->orderBy('date', 'desc')
            ->orderBy('time', 'asc')

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
                'id'          => $app->id,
                'doctor_id'   => $app->doctor_id,
                'doctor_name' => $app->doctor
                    ? trim($app->doctor->first_name . ' ' . $app->doctor->last_name)
                    : null,
                'child_id'    => $app->child_id,
                'child_name'  => $app->child
                    ? trim($app->child->first_name . ' ' . $app->child->last_name)
                    : null,
                'date'        => $app->date,
                'time'        => $app->time,
                'price'       => $app->price,
                'created_at'  => $app->created_at,
                'status'      => __('messages.' . $app->status)
            ])
        ], 200);
    }
    public function destroy(Appointment $appointment, FirebaseNotificationService $firebase)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => '' . __('messages.unauthorized'),
            ], 403);
        }

        if (in_array($appointment->status, ['completed', 'cancelled_by_clinic', 'cancelled_by_patient', 'missed'])) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.cannot_cancel_this_appointment'),
            ], 400);
        }

        DB::beginTransaction();

        try {
            $refundAmount = 0;

            if ($appointment->booking_source === 'online') {

                $transaction = Transaction::where('appointment_id', $appointment->id)
                    ->where('status', 'succeeded')
                    ->first();

                if ($transaction) {
                    Stripe::setApiKey(config('services.stripe.secret'));

                    $refundAmountInCents = round($transaction->amount * 100);

                    $refund = Refund::create([
                        'payment_intent' => $transaction->stripe_payment_intent_id,
                        'amount' => $refundAmountInCents,
                        'metadata' => [
                            'appointment_id' => $appointment->id,
                            'reason' =>  __('messages.cancelled_by_clinic')
                        ]
                    ]);

                    $refundAmount = $transaction->amount;

                    Transaction::create([
                        'appointment_id' => $appointment->id,
                        'stripe_payment_intent_id' => $refund->id,
                        'amount' => $refundAmount,
                        'currency' => $transaction->currency,
                        'status' => 'refunded',
                    ]);
                }
            }

            $appointment->update([
                'status' => 'cancelled_by_clinic'
            ]);

            $child = Child::find($appointment->child_id);
            $parent = $child ? ParentModel::find($child->parent_id) : null;
            $doctor = Doctor::find($appointment->doctor_id);

            $notifTitle = 'Appointment Cancelled';
            $notifBodyParent = __('messages.notif_clinic_cancelled_appointment', [
                'date' => $appointment->date,
                'time' => $appointment->time,
                'amount' => $refundAmount
            ]);
            $notifBodyDoctor = "Appointment for " . ($child ? "{$child->first_name} {$child->last_name}" : "Patient") . " on {$appointment->date} at {$appointment->time} was cancelled by receptionist.";

            if ($parent) {
                DBNotification::create([
                    'parent_id' => $parent->id,
                    'message' => $notifBodyParent,
                ]);
            }

            if ($doctor) {
                DoctorNotification::create([
                    'doctor_id' => $doctor->id,
                    'title' => $notifTitle,
                    'message' => $notifBodyDoctor,
                ]);
            }

            DB::commit();

            Cache::forget("booked_slot_{$appointment->doctor_id}_{$appointment->date}_{$appointment->time}");

            if ($parent && !empty($parent->fcm_token)) {
                $firebase->send($parent->fcm_token, $notifTitle, $notifBodyParent);
            }

            if ($doctor && !empty($doctor->fcm_token)) {
                $firebase->send($doctor->fcm_token, $notifTitle, $notifBodyDoctor);
            }

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.appointment_canceled_success'),
                'refund_amount' => $refundAmount,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Cancellation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function pastByDoctor($doctorId)
    {
        $currentUser = auth()->user();

        $doctorExists = Doctor::where('id', $doctorId)->exists();
        if (!$doctorExists) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.doctor_not_found')
            ], 404);
        }

        $query = Appointment::query()->where('doctor_id', $doctorId);

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized'),
            ], 403);
        }


        $appointments = $query->with([
            'child:id,first_name,image,gender',
            'doctor:id,first_name,last_name,department_id',
            'doctor.department:id,name',
        ])
            ->whereDate('date', '<', now()->toDateString())
            ->where('status', '!=', 'cancelled_by_patient')
            ->where('status', '!=', 'cancelled_by_clinic')
            ->where('status', '!=', 'missed')
            ->orderByDesc('date')
            ->orderByDesc('time')
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
            'message'      => __('messages.past_doctor_success'),
            'appointments' => $formattedAppointments
        ], 200);
    }
    public function upcomingByDoctor($doctorId)
    {
        $currentUser = auth()->user();

        $doctorExists = Doctor::where('id', $doctorId)->exists();
        if (!$doctorExists) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.doctor_not_found')
            ], 404);
        }

        $query = Appointment::query()->where('doctor_id', $doctorId);
        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized'),
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
            'message'      => __('messages.upcoming_doctor_success'),
            'appointments' => $formattedAppointments
        ], 200);
    }

    public function getByDateForReception($date)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 403);
        }

        try {
            $formattedDate = Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.invalid_date_format')
            ], 400);
        }

        $appointments = Appointment::with([
            'child:id,first_name,parent_id,gender,image',
            'child.parent:id,first_name,last_name,phone_number',
            'doctor:id,first_name,last_name,department_id',
            'doctor.department:id,name'
        ])
            ->whereDate('date', $formattedDate)
            ->where('status', '!=', 'cancelled_by_patient')
            ->where('status', '!=', 'cancelled_by_clinic')
            ->orderBy('time', 'asc')
            ->get();

        $formattedAppointments = $appointments->map(function ($appointment) {
            $parent = $appointment->child?->parent;

            return [
                'id'               => $appointment->id,
                'time'             => Carbon::parse($appointment->time)->format('H:i'),
                'status'           => __('messages.' . $appointment->status),
                'payment_status'   => $appointment->payment_status,
                'price'            => $appointment->price,

                'patient' => [
                    'child_id'   => $appointment->child_id,
                    'child_name' => $appointment->child?->first_name,
                    'gender' => $appointment->child->gender,
                    'child_image' => $appointment->child?->image,
                    'parent_name' => $parent ? $parent->first_name . ' ' . $parent->last_name : null,
                    'parent_phone' => $parent?->phone_number,
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
            'date_queries' => $formattedDate,
            'total_appointments' => $appointments->count(),
            'appointments' => $formattedAppointments
        ], 200);
    }


    public function blockParent(Request $request, ParentModel $parent, FirebaseNotificationService $firebase)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.unauthorized'),
            ], 403);
        }

        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $reason = $request->reason ?? 'Blocked by clinic receptionist';

            $parent->update([
                'is_blocked'   => true,
                'block_reason' => $reason,
            ]);

            $notifTitle = 'Account Blocked';
            $notifMessage = __('messages.account_blocked_notification', ['reason' => $reason]);

            DBNotification::create([
                'parent_id' => $parent->id,
                'message'   => $notifMessage,
            ]);

            DB::commit();

            if (!empty($parent->fcm_token)) {
                $firebase->send($parent->fcm_token, $notifTitle, $notifMessage);
            }

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.user_blocked_successfully'),
                'parent'  => [
                    'id'         => $parent->id,
                    'name'       => $parent->name ?? $parent->first_name,
                    'is_blocked' => $parent->is_blocked,
                    'reason'     => $parent->block_reason,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to block parent: ' . $e->getMessage()
            ], 500);
        }
    }
    public function revokeTokens(ParentModel $parent)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => '' . __('messages.unauthorized'),
            ], 403);
        }

        try {
            $parent->tokens()->delete();

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.user_tokens_revoked_successfully'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to revoke tokens: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showAllProfiles(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 401);
        }

        if (!$currentUser instanceof Receptionist) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized_role')
            ], 403);
        }

        $parents = ParentModel::with(['children' => function ($query) {
            $query->select('parent_id', 'image', 'first_name');
        }])->get();

        $formattedUsers = $parents->map(function ($parent) {
            return [
                'id'           => $parent->id,
                'first_name'   => $parent->first_name,
                'last_name'    => $parent->last_name,
                'email'        => $parent->email,
                'phone_number' => $parent->phone_number,
                'address'      => $parent->address,
                'children_count'     => $parent->children->count()
            ];
        });

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.all_parent_profiles_fetched_successfully'),
            'users'   => $formattedUsers
        ], 200);
    }
}

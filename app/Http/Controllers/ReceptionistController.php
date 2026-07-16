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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
                'status' => 'error',
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
                    'status' => 'error',
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
                'status' => 'error',
                'message' => 'Unauthorized. This resource is only accessible by receptionists.'
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
            'message'     => 'Appointment booked successfully.',
            'appointment' => $appointment,
        ], 201);
    }
    public function updateReception(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $currentUser = $request->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. This resource is only accessible by receptionists.',
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
        $currentUser = request()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. This resource is only accessible by receptionists.'
            ], 403);
        }
        $appointments = Appointment::orderBy('date', 'asc')
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
                'id'         => $app->id,
                'doctor_id'  => $app->doctor_id,
                'child_id'   => $app->child_id,
                'date'       => $app->date,
                'time'       => $app->time,
                'price'      => $app->price,
                'created_at' => $app->created_at,
                'status'     => __('messages.' . $app->status)
            ])
        ], 200);
    }
    public function destroy(Appointment $appointment)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Only receptionists can delete or cancel appointments.',
            ], 403);
        }
        if ($appointment->status === 'completed') {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.cannot_cancel_completed_appointment'),
            ], 400);
        }

        $appointment->update([
            'status' => 'cancelled'
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.appointment_canceled_success'),
        ], 200);
    }
}

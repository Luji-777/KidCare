<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\DoctorAvailability;
use App\Models\Medication;
use App\Models\Growth;
use App\Models\Child;
use Carbon\Carbon;

class DoctorController extends Controller
{
    public function sendOtpDoctor(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15'
        ]);

        $doctor = Doctor::where('phone_number', $request->phone_number)->first();

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' =>  __('messages.phone_not_registered'),
            ], 404);
        }

        $otp = rand(1000, 9999);

        session(['otp' => $otp]);
        session(['otp_phone' => $request->phone_number]);

        // أرسل عبر واتساب
        sendWhatsAppMessage(
            $request->phone_number,
            "Your confirmation code is: {$otp}. Do not share it with anyone."
        );

        $doctor->otp_code = $otp;
        $doctor->otp_expires_at = Carbon::now()->addMinutes(10);
        $doctor->save();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.otp_sent_success'),
            'otp'     => $otp
        ]);
    }
    public function verifyOtpDoctor(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'otp'   => 'required|string|max:255'
        ]);

        $doctor = Doctor::where('phone_number', $request->phone_number)->firstOrFail();
        if ($doctor->otp_code !== $request->otp || Carbon::now()->gt($doctor->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.otp_invalid_expired'),
                'otp' => $doctor->otp_code
            ], 422);
        }
        return response()->json([
            'status' => 'success',
            'message' => __('messages.phone_verified_success')
        ]);
    }
    public function setPasswordDoctor(Request $request)
    {

        $request->validate([
            'phone_number' => 'required',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);

        $doctor = Doctor::where('phone_number', $request->phone_number)->first();

        if (!$doctor) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' =>  __('messages.doctor_not_found'),
                ],
                404
            );
        }

        $doctor->update([
            'password'       => Hash::make($request->password)
        ]);

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('messages.password_updated_success'),
            'token'   => $token
        ], 200);
    }
    public function loginDoctor(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'password'     => 'required|string|min:6|max:255'
        ]);

        $doctor = Doctor::where('phone_number', $request->phone_number)->first();

        if (!$doctor || !Hash::check($request->password, $doctor->password)) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.invalid_credentials')
            ], 401);
        }

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('messages.login_welcome_back'),
            'user'    => [
                'id'           => $doctor->id,
                'phone_number' => $doctor->phone_number,
                'first_name'   => $doctor->first_name,
                'last_name'    => $doctor->last_name,
            ],
            'Token'   => $token,
        ], 200);
    }
    public function index()
    {

        $doctors = Doctor::with('department')
            ->orderBy('first_name', 'asc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.doctors_fetched_success'),
            'data'   => $doctors
        ], 200);
    }

    public function store(StoreDoctorRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('doctors/photos', 'public');
        }

        if ($request->hasFile('cv')) {
            $data['cv'] = $request->file('cv')->store('doctors/cvs', 'public');
        }

        $doctor = Doctor::create($data);

        return response()->json([
            'status' => 'success',
            'message' => __('messages.doctor_created_success'),
            'data' => $doctor
        ], 201);
    }

    public function show(string $id)
    {
        $doctor = Doctor::with('department')->find($id);

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.doctor_not_found'),
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => __('messages.doctor_fetched_success'),
            'data'   => $doctor
        ], 200);
    }

    public function update(UpdateDoctorRequest $request, string $id)
    { {
            $doctor = Doctor::findOrFail($id);
            $data = $request->validated();

            if ($request->hasFile('profile_picture')) {
                $data['profile_picture'] = $request->file('profile_picture')->store('doctors/profiles', 'public');
            }

            if ($request->hasFile('cv')) {
                $data['cv'] = $request->file('cv')->store('doctors/cvs', 'public');
            }

            $doctor->update($data);

            return response()->json([
                'status'  => 'success',
                'message' => __('messages.doctor_updated_success'),
                'data'    => $doctor
            ], 200);
        }
    }
    public function destroy(string $id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.doctor_not_found')
            ], 404);
        }

        if ($doctor->profile_picture) {
            Storage::disk('public')->delete($doctor->profile_picture);
        }

        if ($doctor->cv) {
            Storage::disk('public')->delete($doctor->cv);
        }

        $doctor->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.doctor_deleted_success')
        ], 200);
    }
    public function addAdditions(Request $request, $appointment_id)
    {
        $request->validate([
            'additions' => 'required|array',
            'additions.*.item_name' => 'required|string',
            'additions.*.price' => 'required|numeric|min:0',
        ]);

        $appointment = Appointment::with('doctor')->findOrFail($appointment_id);


        foreach ($request->additions as $addition) {
            $appointment->additions()->create([
                'item_name' => $addition['item_name'],
                'price' => $addition['price']
            ]);
        }

        $totalAdditions = collect($request->additions)->sum('price');


        $doctorCommission = ($appointment->price * $appointment->doctor->commission_percentage) / 100;


        $appointment->update([
            'status' => 'completed',
            'doctor_earnings' => $doctorCommission,
            'payment_status' => $totalAdditions > 0 ? 'partially_paid' : 'fully_paid'
        ]);

        return response()->json(['message' => __('messages.additions_recorded_success')]);
    }

    public function toggleFavorite($doctorId)
    {
        $parent = auth()->user();

        $doctor = Doctor::find($doctorId);

        if (!$doctor) {
            return response()->json([
                'message' => __('messages.doctor_not_found')
            ], 404);
        }

        $isFavorite = $parent->doctors()
            ->where('doctor_id', $doctorId)
            ->exists();

        if ($isFavorite) {

            $parent->doctors()->detach($doctorId);

            return response()->json([
                'message' => __('messages.favorite_removed'),
                'is_favorite' => false
            ]);
        }

        $parent->doctors()->attach($doctorId);

        return response()->json([
            'message' => __('messages.favorite_added'),
            'is_favorite' => true
        ]);
    }

    public function getFavorites()
    {
        $favorites = auth()->user()
            ->doctors()
            ->select(
                'doctors.id',
                'doctors.first_name',
                'doctors.last_name',
                'doctors.profile_picture',
                'doctors.department_id'
            )
            ->with('department:id,name')
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'first_name' => $doctor->first_name,
                    'last_name' => $doctor->last_name,
                    'image' => $doctor->profile_picture,
                    'department' => $doctor->department?->name,
                    'is_favorite' => true,
                ];
            });

        return response()->json([
            'status'    => 'success',
            'message'   => __('messages.favorites_fetched_success'),
            'favorites' => $favorites
        ]);
    }

    public function home()
    {
        $doctor = auth()->user();

        return response()->json([
            'id' => $doctor->id,
            'name' => $doctor->first_name . ' ' . $doctor->last_name,
            'specialization' => $doctor->department?->name,
            'image' => $doctor->profile_picture,
        ]);
    }

    public function todayAppointmentsCount()
    {
        $doctor = auth()->user();

        $count = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('date', today())
            ->where('status', 'confirmed')
            ->count();

        return response()->json([
            'count' => $count
        ]);
    }

    public function nextPatient()
    {
        $doctor = auth()->user();

        $appointment = Appointment::with('child')
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', today())
            ->where('status', 'confirmed')
            ->whereTime('time', '>=', now()->format('H:i:s'))
            ->orderBy('time')
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'No upcoming patients'
            ]);
        }

        return response()->json([
            'id' => $appointment->child->id,
            'name' => $appointment->child->first_name . ' ' . $appointment->child->last_name,
            'age' => Carbon::parse($appointment->child->birth_date)->age,
            'gender' => $appointment->child->gender,
            'image' => $appointment->child->image,
            'appointment_time' => $appointment->time,
        ]);
    }

    public function remainingPatients()
    {
        $doctor = auth()->user();

        $appointments = Appointment::with('child')
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', today())
            ->where('status', 'confirmed')
            ->whereTime('time', '>=', now()->format('H:i:s'))
            ->orderBy('time')
            ->get();

        return response()->json(
            $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->child->id,
                    'name' => $appointment->child->first_name . ' ' . $appointment->child->last_name,
                    'age' => Carbon::parse($appointment->child->birth_date)->age,
                    'gender' => $appointment->child->gender,
                    'image' => $appointment->child->image,
                    'appointment_time' => $appointment->time,
                ];
            })
        );
    }

    public function completedAppointmentsToday()
    {
        $doctor = auth()->user();

        $count = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereDate('date', today())
            ->count();

        return response()->json([
            'completed_appointments' => $count
        ]);
    }

    public function completeAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);

        $appointment->status = 'completed';

        $doctor = Doctor::find($appointment->doctor_id);

        $appointment->doctor_earnings =
            $appointment->price * ($doctor->commission_percentage / 100);

        $appointment->save();

        return response()->json([
            'message' => 'Appointment completed'
        ]);
    }

    public function monthlyRevenue()
    {
        $doctor = auth()->user();

        $revenue = Appointment::where('doctor_id', $doctor->id)
            ->where('status', 'completed')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('doctor_earnings');

        return response()->json([
            'monthly_revenue' => $revenue
        ]);
    }

    public function addDiagnosis(Request $request, $appointmentId)
 {
    $request->validate([
        'diagnosis' => 'required|string',
        'doctor_notes' => 'nullable|string',
    ]);

     $doctor = auth()->user();

    $appointment = Appointment::where('id', $appointmentId)
        ->where('doctor_id', $doctor->id)
        ->firstOrFail();

    $record = MedicalRecord::updateOrCreate(
        [
            'appointment_id' => $appointment->id
        ],
        [
            'diagnosis' => $request->diagnosis,
            'doctor_notes' => $request->doctor_notes
        ]
    );

    return response()->json([
        'message' => __('messages.Diagnosis_add_success'),
        'record' => $record
    ]);
 }

 public function addMedication(Request $request, $recordId)
{
    $request->validate([
        'name' => 'required|string',
        'dosage' => 'required|string',
        'frequency' => 'required|string',
        'timing' => 'required|string',
        'duration' => 'required|string',
    ]);

     $doctor = auth()->user();

    $record = MedicalRecord::whereHas('appointment', function ($q) use ($doctor) {
        $q->where('doctor_id', $doctor->id);
    })->findOrFail($recordId);

    $medication = $record->medications()->create([
        'name' => $request->name,
        'dosage' => $request->dosage,
        'frequency' => $request->frequency,
        'timing' => $request->timing,
        'duration' => $request->duration,
    ]);

    return response()->json([
        'message' =>  __('messages.Medication_add_success'),
        'medication' => $medication
    ]);
}

public function addGrowthRecord(Request $request, $appointmentId)
{
    $request->validate([
        'height' => 'nullable|numeric|min:10|max:250|required_with:weight',
        'weight' => 'nullable|numeric|min:1|max:150|required_with:height',
    ]);

    $doctor = auth()->user();

    $appointment = Appointment::where('id', $appointmentId)
        ->where('doctor_id', $doctor->id)
        ->firstOrFail();

    $growth = Growth::create([
        'child_id' => $appointment->child_id,
        'height'   => $request->height,
        'weight'   => $request->weight,
        'date'     => now()->toDateString(),
    ]);

    return response()->json([
        'message' => __('messages.Growth_add_success'),
        'data' => $growth
    ]);
}

public function upcomingWorkingDays()
{
    $doctor = auth()->user();

    $workingDays = DoctorAvailability::where('doctor_id', $doctor->id)
        ->pluck('day_of_week')
        ->map(fn($day) => strtolower($day))
        ->toArray();

    $dates = [];

    $currentDate = Carbon::today();

    while (count($dates) < 6) {

        $dayName = strtolower($currentDate->format('l'));

        if (in_array($dayName, $workingDays)) {

            $dates[] = [
                'date' => $currentDate->toDateString(),
                'day_name' => __("messages.days.$dayName"),
            ];
        }

        $currentDate->addDay();
    }

    return response()->json([
        'status' => 'success',
        'days' => $dates
    ]);
}

public function appointmentsByDate(Request $request)
{
   
    $request->validate([
        'date' => 'required|date',
    ]);

    $doctor = auth()->user();

    $date = $request->date;


    $appointments = Appointment::with('child')
        ->where('doctor_id', $doctor->id)
        ->whereDate('date', $date)
        ->orderBy('time')
        ->get();

    return response()->json([
        'status' => 'success',
        'data' => [
            'total_appointments' => $appointments->count(),
            'appointments' => $appointments->map(function ($appointment) {

                $child = $appointment->child;

                $age = Carbon::parse($child->birth_date)->age;

                return [
                    'id' => $appointment->id,
                    'patient_name' => $child->first_name . ' ' . $child->last_name,
                    'age' => $age,
                    'gender' => $child->gender,
                    'image' => $child->image,
                    'time' => Carbon::parse($appointment->time)->format('H:i'),
                    'status' => $appointment->status,
                ];
            })
        ]
    ]);
}

public function allPatients(Request $request)
{
    $doctor = auth()->user();

    $patients = Child::with('parent')
        ->whereHas('appointments', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })
        ->when($request->search, function ($q) use ($request) {
            $search = $request->search;

            $q->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        })
        ->get()
        ->unique('id')
        ->values();

    return response()->json([
        'status' => true,
        'patients' => $patients->map(function ($child) {
            return [
                'id' => $child->id,
                'name' => $child->first_name . ' ' . $child->last_name,
                'age' => Carbon::parse($child->birth_date)->age,
                'gender' => $child->gender,
                'image' => $child->image,
                'parent_phone' => $child->parent->phone_number,
            ];
        }),
    ]);
}
    
public function monthlyIncome()
{
    $doctor = auth()->user();

    $income = Appointment::where('doctor_id', $doctor->id)
        ->whereMonth('date', now()->month)
        ->whereYear('date', now()->year)
       // ->where('status', 'completed')
        ->whereIn('payment_status', ['paid_online', 'fully_paid'])
        ->sum('doctor_earnings');

    return response()->json([
        'monthly_income' => $income
    ]);
}
    
}

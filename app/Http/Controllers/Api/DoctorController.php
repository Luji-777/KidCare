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
use App\Models\Child;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

    //----------------------Dashboard--------------------------------

    //--------doctors--------
    public function getDoctorsCount()
    {

        $count = Doctor::count();

        return response()->json([
            'status' => 'success',
            'doctors_count' => $count
        ], 200);
    }

    public function getDoctorsCountByDepartment($department_id)
    {

        $count = Doctor::where('department_id', $department_id)->count();

        return response()->json([
            'status' => 'success',
            'department_id' => (int) $department_id,
            'doctors_count' => $count
        ], 200);
    }

    public function getTopDoctorThisWeek()
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek == Carbon::SATURDAY) {
            $startOfWeek = $today->copy()->format('Y-m-d');
            $endOfWeek   = $today->copy()->addDays(5)->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::THURSDAY) {
            $startOfWeek = $today->copy()->subDays(5)->format('Y-m-d');
            $endOfWeek   = $today->copy()->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::FRIDAY) {
            $startOfWeek = $today->copy()->subDays(6)->format('Y-m-d');
            $endOfWeek   = $today->copy()->subDay()->format('Y-m-d');
        } else {
            $startOfWeek = $today->copy()->previous(Carbon::SATURDAY)->format('Y-m-d');
            $endOfWeek   = $today->copy()->next(Carbon::THURSDAY)->format('Y-m-d');
        }

        $topDoctor = Doctor::select(
            'doctors.id',
            DB::raw("CONCAT(doctors.first_name, ' ', doctors.last_name) as full_name"),
            'departments.name as department_name',
            DB::raw('COUNT(appointments.id) as appointments_count')
        )
            ->join('appointments', 'doctors.id', '=', 'appointments.doctor_id')
            ->join('departments', 'doctors.department_id', '=', 'departments.id')
            ->whereBetween('appointments.date', [$startOfWeek, $endOfWeek])
            ->whereIn('appointments.status', ['confirmed', 'completed'])
            ->groupBy('doctors.id', 'doctors.first_name', 'doctors.last_name', 'departments.name')
            ->orderBy('appointments_count', 'desc')
            ->first();

        if (!$topDoctor) {
            return response()->json([
                'status' => 'success',
                'message' => 'No confirmed or completed appointments booked for any doctor during this period.',
                'doctor' => null,
                'week_range' => [
                    'start_saturday' => $startOfWeek,
                    'end_thursday'   => $endOfWeek
                ]
            ], 200);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'doctor_id' => $topDoctor->id,
                'doctor_name' => $topDoctor->full_name,
                'department_name'    => $topDoctor->department_name,
                'appointments_count' => $topDoctor->appointments_count,
                'week_range' => [
                    'start_saturday' => $startOfWeek,
                    'end_thursday'   => $endOfWeek
                ]
            ]
        ], 200);
    }

    public function index()
    {
        $now = Carbon::now();
        $todayName = Carbon::now()->locale('en')->dayName;
        $todayDate = Carbon::now()->format('Y-m-d');
        $currentTime = $now->format('H:i:s');


        $doctorsPaginator = Doctor::with([
            'department',
            'availabilities' => function ($query) use ($todayName) {
                $query->where('day_of_week', $todayName);
            }
        ])
            ->withCount([

                'appointments as unique_patients_count' => function ($query) {
                    $query->whereIn('status', ['confirmed', 'completed'])
                        ->select(DB::raw('count(distinct(child_id))'));
                },

                'availabilities as is_working_today' => function ($query) use ($todayName) {
                    $query->where('day_of_week', $todayName);
                },

                'appointments as today_appointments_count' => function ($query) use ($todayDate) {
                    $query->where('date', $todayDate)->where('status', 'confirmed');
                }
            ])
            ->orderBy('id', 'asc')
            ->paginate(10);


        $transformedDoctors = $doctorsPaginator->getCollection()->map(function ($doctor) use ($currentTime) {
            $todayAvailability = $doctor->availabilities->first();
            if (!$todayAvailability) {

                $statusText = 'Out of Schedule';
            } else {
                $startTime = $todayAvailability->start_time;
                $endTime   = $todayAvailability->end_time;


                if ($currentTime >= $startTime && $currentTime <= $endTime) {

                    $statusText = $doctor->today_appointments_count > 0 ? 'Busy' : 'Available';
                } else {

                    $statusText = 'Out of Schedule';
                }
            }

            return [
                'id'               => $doctor->id,
                'image'            => $doctor->profile_picture,
                'full_name'        => $doctor->first_name . ' ' . $doctor->last_name,
                'department'       => $doctor->department ? $doctor->department->name : null,
                'phone'            => $doctor->phone_number,
                'experience_years' => $doctor->experience_years,
                'patients_count'   => $doctor->unique_patients_count,
                'current_status'   => $statusText
            ];
        });


        return response()->json([
            'status'  => 'success',
            'message' => __('messages.doctors_fetched_success'),
            'data'    => $transformedDoctors,
            'pagination' => [
                'current_page' => $doctorsPaginator->currentPage(),
                'last_page'    => $doctorsPaginator->lastPage(),
                'per_page'     => $doctorsPaginator->perPage(),
                'total'        => $doctorsPaginator->total(),
            ]
        ], 200);
    }
    public function show(string $id)
    {

        $doctor = Doctor::with(['department', 'availabilities'])->find($id);

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.doctor_not_found'),
            ], 404);
        }

        $availabilities = $doctor->availabilities->map(function ($slot) {
            return [
                'day_of_week' => $slot->day_of_week,
                'start_time'  => $slot->start_time,
                'end_time'    => $slot->end_time,
            ];
        });


        $customData = [
            'id'                    => $doctor->id,
            'first_name'            => $doctor->first_name,
            'last_name'             => $doctor->last_name,
            'full_name'             => $doctor->first_name . ' ' . $doctor->last_name,
            'address'               => $doctor->address,
            'email'                 => $doctor->email,
            'phone_number'          => $doctor->phone_number,
            'experience_years'      => $doctor->experience_years,
            'education'             => $doctor->education,
            'profile_picture'       => $doctor->profile_picture,
            'cv'                    => $doctor->cv,
            'fee'                   => $doctor->fee,
            'commission_percentage' => $doctor->commission_percentage,
            'gender'                => $doctor->gender,
            'department' => $doctor->department ? [
                'id'   => $doctor->department->id,
                'name' => $doctor->department->name,
            ] : null,
            'availabilities'        => $availabilities
        ];

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.doctor_fetched_success'),
            'data'    => $customData
        ], 200);
    }
    public function getActiveDoctorsCountThisWeek()
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek == Carbon::SATURDAY) {
            $startOfWeek = $today->copy()->format('Y-m-d');
            $endOfWeek   = $today->copy()->addDays(5)->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::THURSDAY) {
            $startOfWeek = $today->copy()->subDays(5)->format('Y-m-d');
            $endOfWeek   = $today->copy()->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::FRIDAY) {
            $startOfWeek = $today->copy()->subDays(6)->format('Y-m-d');
            $endOfWeek   = $today->copy()->subDay()->format('Y-m-d');
        } else {
            $startOfWeek = $today->copy()->previous(Carbon::SATURDAY)->format('Y-m-d');
            $endOfWeek   = $today->copy()->next(Carbon::THURSDAY)->format('Y-m-d');
        }

        $activeDoctorsCount = Doctor::whereHas('appointments', function ($query) use ($startOfWeek, $endOfWeek) {
            $query->whereBetween('date', [$startOfWeek, $endOfWeek])
                ->whereIn('status', ['confirmed', 'completed']);
        })->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'active_doctors_count' => $activeDoctorsCount,
                'week_range' => [
                    'start_saturday' => $startOfWeek,
                    'end_thursday'   => $endOfWeek
                ]
            ]
        ], 200);
    }
    public function getDoctorWeeklyStats(string $id)
    {

        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.doctor_not_found'),
            ], 404);
        }
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek == Carbon::SATURDAY) {
            $startOfWeek = $today->copy()->format('Y-m-d');
            $endOfWeek   = $today->copy()->addDays(5)->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::THURSDAY) {
            $startOfWeek = $today->copy()->subDays(5)->format('Y-m-d');
            $endOfWeek   = $today->copy()->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::FRIDAY) {
            $startOfWeek = $today->copy()->subDays(6)->format('Y-m-d');
            $endOfWeek   = $today->copy()->subDay()->format('Y-m-d');
        } else {
            $startOfWeek = $today->copy()->previous(Carbon::SATURDAY)->format('Y-m-d');
            $endOfWeek   = $today->copy()->next(Carbon::THURSDAY)->format('Y-m-d');
        }

        $appointmentsQuery = $doctor->appointments()
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', ['confirmed', 'completed']);

        $appointmentsCount = $appointmentsQuery->count();

        $patientsCount = $appointmentsQuery->clone()
            ->distinct('child_id')
            ->count('child_id');

        $availabilities = $doctor->availabilities;
        $totalWorkingHours = 0;

        foreach ($availabilities as $slot) {
            $startTime = Carbon::parse($slot->start_time);
            $endTime = Carbon::parse($slot->end_time);

            $durationInHours = $startTime->diffInMinutes($endTime) / 60;

            $totalWorkingHours += $durationInHours;
        }


        return response()->json([
            'status' => 'success',
            'data' => [
                'doctor_id'            => $doctor->id,
                'department_id' => $doctor->department_id,
                'doctor_name'          => $doctor->first_name . ' ' . $doctor->last_name,
                'experience_years'     => $doctor->experience_years,
                'weekly_appointments'  => $appointmentsCount,
                'weekly_patients'      => $patientsCount,
                'weekly_working_hours' => round($totalWorkingHours, 2),
                'week_range' => [
                    'start_saturday' => $startOfWeek,
                    'end_thursday'   => $endOfWeek
                ]
            ]
        ], 200);
    }
}

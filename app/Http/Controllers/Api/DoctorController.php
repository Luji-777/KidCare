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
use Carbon\Carbon;

class DoctorController extends Controller
{
    public function sendOtpDoctor(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15'
        ]);

        $doctor = Doctor::where('phone_number', $request->phone_number)->firstOrFail();

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' =>
                'This phone number is not registered in our records. Please check the number or create a new account.'
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
            'message' => 'A new verification code has been sent to your phone.',
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
                'message' => 'The provided OTP is invalid or has expired.',
                'otp' => $doctor->otp_code
            ], 422);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Phone number verified successfully.'
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
                    'message' => 'User not found.'
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
            'message' => 'Password updated successfully.',
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
                'message' => 'Invalid phone number or password.'
            ], 401);
        }

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful. Welcome back!',
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
        // الترتيب الأبجدي حسب الاسم الأول ثم جلب 10 بكل صفحة
        $doctors = Doctor::with('department')
            ->orderBy('first_name', 'asc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
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
            'message' => 'Doctor profile created successfully',
            'data' => $doctor
        ], 201);
    }


    public function show(string $id)
    {
        $doctor = Doctor::with('department')->find($id);

        if (!$doctor) {
            return response()->json([
                'status' => 'error',
                'message' => 'Doctor not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
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
                'message' => 'Doctor profile updated successfully.',
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
                'message' => 'Doctor not found.'
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
            'message' => 'Doctor and their related files have been deleted successfully.'
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

        return response()->json(['message' => 'The appointment was completed and the additional costs were successfully recorded.']);
    }

    public function toggleFavorite($doctorId)
    {
        $parent = auth()->user();

        $doctor = Doctor::find($doctorId);

        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor not found'
            ], 404);
        }

        $isFavorite = $parent->favoriteDoctors()
            ->where('doctor_id', $doctorId)
            ->exists();

        if ($isFavorite) {

            $parent->favoriteDoctors()->detach($doctorId);

            return response()->json([
                'message' => 'Removed from favorites',
                'is_favorite' => false
            ]);
        }

        $parent->favoriteDoctors()->attach($doctorId);

        return response()->json([
            'message' => 'Added to favorites',
            'is_favorite' => true
        ]);
    }

    public function getFavorites()
    {
        $favorites = auth()->user()
            ->favoriteDoctors()
            ->with('department')
            ->get();

        return response()->json([
            'favorites' => $favorites
        ]);
    }
}

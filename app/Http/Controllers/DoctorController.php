<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class DoctorController extends Controller
{
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
            'message' => 'Login successful. Welcome back to your medical portal',
            'user'    => [
                'id'           => $doctor->id,
                'phone_number' => $doctor->phone_number,
                'first_name'   => $doctor->first_name,
                'last_name'    => $doctor->last_name,
            ],
            'Token'   => $token,
        ], 200);
    }
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
            'message' => 'Verification code has been sent to your phone.',
            'otp'     => $otp
        ]);
    }

    public function verifyOtpAndSetPasswordDoctor(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'otp'          => 'required|digits:4',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);

        $doctor = Doctor::where('phone_number', $request->phone_number)
            ->where('otp_code', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$doctor) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Verification failed. Invalid or expired OTP.'
                ],
                400
            );
        }

        $doctor->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        $token = $doctor->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Password created successfully. You can now log in with your new credentials.',
            'token'   => $token
        ], 200);
    }
}

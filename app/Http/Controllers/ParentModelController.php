<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use Illuminate\Http\Request;

class ParentModelController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone_number' => 'required|digits_between:9,15|unique:parent_models,phone_number',
            'email' => 'required|string|max:255',
            'password' => 'required|string|min:6|max:255|confirmed',

        ]);

        $otp = rand(1000, 9999); // توليد رمز تحقق عشوائي من 4 أرقام

        session(['otp' => $otp]);
        session(['otp_phone' => $request->phone_number]);

        // أرسل عبر واتساب
        sendWhatsAppMessage(
            $request->phone_number,
            "Your confirmation code is: {$otp}. Do not share it with anyone."
        );


        // $birthDate = Carbon::createFromFormat('d-m-Y', $request->birth_date)->format('Y-m-d'); // شكل التاريخ


        // إنشاء مستخدم جديد
        $parent = ParentModel::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'address' => $request->address,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'otp_code' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10), // صلاحية الرمز 10 دقايق
        ]);

        // إنشاء توكن للمستخدم
        $token = $parent->createToken('auth_token')->plainTextToken;

        // إعادة استجابة بنجاح التسجيل
        return response()->json([
            'message' => 'the account created successfully, Please verify your phone number.',
            'otp'     => $otp,
            'phone_number'   => $request->phone_number,
            'next_step' => 'verify-otp',
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'otp'   => 'required|string|max:255'
        ]);

        $parent = ParentModel::where('phone_number', $request->phone_number)->firstOrFail();
        if ($parent->otp_code !== $request->otp || Carbon::now()->gt($parent->otp_expires_at)) {
            return response()->json([
                'status' => 'error',
                'message' => 'The provided OTP is invalid or has expired.',
                'otp' => $parent->otp_code
            ], 422);
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Phone number verified successfully.'
        ]);
    }
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15'
        ]);

        $parent = ParentModel::where('phone_number', $request->phone_number)->firstOrFail();

        if (!$parent) {
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

        $parent->otp_code = $otp;
        $parent->otp_expires_at = Carbon::now()->addMinutes(10);
        $parent->save();

        return response()->json([
            'status' => 'success',
            'message' => 'A new verification code has been sent to your phone.',
            'otp'     => $otp
        ]);
    }
    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'password'     => 'required|string|min:6|max:255'
        ]);

        $parent = ParentModel::where('phone_number', $request->phone_number)->first();

        if (!$parent || !Hash::check($request->password, $parent->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid phone number or password.'
            ], 401);
        }

        $token = $parent->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful. Welcome back!',
            'user'    => [
                'id'           => $parent->id,
                'phone_number' => $parent->phone_number,
                'first_name'   => $parent->first_name,
                'last_name'    => $parent->last_name,
            ],
            'Token'   => $token,
        ], 200);
    }

    public function verifyOtpAndSetPassword(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'otp'          => 'required|digits:4',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);

        $parent = ParentModel::where('phone_number', $request->phone_number)
            ->where('otp_code', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$parent) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Verification failed. Invalid or expired OTP.'
                ],
                400
            );
        }

        $parent->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        $token = $parent->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully. You can now log in with your new credentials.',
            'token'   => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully. Your session has been terminated.'
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No active session found.'
        ], 401);
    }
}

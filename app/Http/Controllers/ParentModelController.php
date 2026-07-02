<?php

namespace App\Http\Controllers;

use App\Models\ParentModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ParentModelController extends Controller
{
    public function register(Request $request)
    {

        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'address'      => 'required|string|max:255',
            'phone_number' => 'required|digits_between:9,15|unique:parent_models,phone_number',
            'email'        => 'required|string|max:255',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);
        $otp = rand(1000, 9999); // توليد رمز تحقق عشوائي من 4 أرقام


        $pendingUserData = [
            'otp'          => $otp,
            'first_name'   => $request->first_name,
            'last_name'    => $request->last_name,
            'address'      => $request->address,
            'email'        => $request->email,
            'phone_number' => $request->phone_number,
            'password'     => Hash::make($request->password),
        ];

        Cache::put('pending_user_' . $request->phone_number, $pendingUserData, now()->addMinutes(10));


        sendWhatsAppMessage(
            $request->phone_number,
            "Your confirmation code is: {$otp}. Do not share it with anyone."
        );

        return response()->json([
            'status' => 'success',
            'message'      => __('messages.otp_sent_success'),
            'otp'          => $otp,
            'phone_number' => $request->phone_number,
            'next_step'    => 'verify-otp',
        ]);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'otp'          => 'required|string|max:255'
        ]);

        $parent = ParentModel::where('phone_number', $request->phone_number)->first();

        if ($parent) {

            if ($parent->otp_code !== $request->otp || now()->gt($parent->otp_expires_at)) {
                return response()->json([
                    'status'  => 'error',
                    'message' =>  __('messages.otp_invalid_expired'),
                ], 422);
            }

            $token = $parent->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => __('messages.phone_verified_success'),
                'access_token' => $token,
                'token_type'   => 'Bearer',
                'user'         => $parent
            ], 200);
        }


        $pendingUser = Cache::get('pending_user_' . $request->phone_number);

        if (!$pendingUser || $pendingUser['otp'] != $request->otp) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The provided OTP is invalid, expired, or no pending registration found.',
            ], 422);
        }

        $newParent = ParentModel::create([
            'first_name'     => $pendingUser['first_name'],
            'last_name'      => $pendingUser['last_name'],
            'address'        => $pendingUser['address'],
            'email'          => $pendingUser['email'],
            'phone_number'   => $pendingUser['phone_number'],
            'password'       => $pendingUser['password'],
            'otp_code'       => $pendingUser['otp'],
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $token = $newParent->createToken('auth_token')->plainTextToken;

        Cache::forget('pending_user_' . $request->phone_number);

        return response()->json([
            'status'       => 'success',
            'message'      => 'Phone number verified and account created successfully.',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $newParent
        ], 201);
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
                __('messages.phone_not_registered')
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
            'message' =>  __('messages.otp_sent_success'),
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
                'message' => __('messages.invalid_credentials')
            ], 401);
        }

        $token = $parent->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('messages.login_welcome_back'),
            'user'    => [
                'id'           => $parent->id,
                'phone_number' => $parent->phone_number,
                'first_name'   => $parent->first_name,
                'last_name'    => $parent->last_name,
            ],
            'Token'   => $token,
        ], 200);
    }
    public function SetPassword(Request $request)
    {

        $request->validate([
            'phone_number' => 'required',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);


        $parent = ParentModel::where('phone_number', $request->phone_number)->first();

        if (!$parent) {
            return response()->json(
                [
                    'status' => 'error',
                    'message' =>  __('messages.user_not_found'),
                ],
                404
            );
        }

        $parent->update([
            'password'       => Hash::make($request->password)
        ]);

        $token = $parent->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' =>  __('messages.password_updated_success'),
            'token'   => $token
        ], 200);
    }
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => __('messages.logout_succssfuly')
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => __('messages.no_active_session')
        ], 401);
    }
    public function showProfile(Request $request)
    {
        $parent = $request->user();

        if (!$parent) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthorized')
            ], 401);
        }

        $children = $parent->children()->select('image', 'first_name')->get();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.parent_fetched_success'),
            'user' => [
                'id'           => $parent->id,
                'first_name'   => $parent->first_name,
                'last_name'    => $parent->last_name,
                'email'        => $parent->email,
                'phone_number' => $parent->phone_number,
                'address'      => $parent->address,
                'children'     => $children
            ]
        ], 200);
    }
    public function parentName(Request $request)
    {
        $parent = $request->user();

        if (!$parent) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthorized')
            ], 401);
        }
        return response()->json([
            'user' => [
                'first_name'   => $parent->first_name,
                'last_name'    => $parent->last_name,
            ]
        ], 200);
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required'
        ]);

        auth()->user()->update([
            'fcm_token' => $request->fcm_token
        ]);

        return response()->json([
            'message' => __('messages.token_saved_successfully')
        ]);
    }

    public function updateProfile(Request $request)
    {
        $parent = $request->user();

        if (!$parent) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.unauthorized')
            ], 401);
        }

        $request->validate([

            'email'        => 'sometimes|email|unique:users,email,' . $parent->id,
            'phone_number' => 'sometimes|string|max:20|unique:users,phone_number,' . $parent->id,
            'address'      => 'sometimes|string|max:255',
        ]);

        $parent->update($request->only([

            'email',
            'phone_number',
            'address'

        ]));

        $children = $parent->children()->select('image', 'first_name')->get();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.profile_updated_successfully'),
            'user' => [
                'id'           => $parent->id,
                'first_name'   => $parent->first_name,
                'last_name'    => $parent->last_name,
                'email'        => $parent->email,
                'phone_number' => $parent->phone_number,
                'address'      => $parent->address,
                'children'     => $children
            ]
        ], 200);
    }
    public function destroyAccount(Request $request)
{
  
    $parent = $request->user();

    if (!$parent) {
        return response()->json([
            'status' => 'error',
            'message' => __('messages.unauthorized')
        ], 401);
    }

    
    $parent->tokens()->delete(); 
    $parent->forceDelete(); 
    return response()->json([
        'status'  => 'success',
        'message' => __('messages.account_permanently_deleted')
    ], 200);
}
}

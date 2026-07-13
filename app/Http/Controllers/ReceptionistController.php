<?php

namespace App\Http\Controllers;

use App\Models\Receptionist;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\Child;

use Illuminate\Http\Request;

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
}

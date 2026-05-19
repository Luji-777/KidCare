<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'password'     => 'required|string|min:6|max:255'
        ]);

        $admin = Admin::where('phone_number', $request->phone_number)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid phone number or password.'
            ], 401);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful. Welcome back!',
            'user'    => [
                'id'           => $admin->id,
                'phone_number' => $admin->phone_number,
                'name'   => $admin->name,
            ],
            'Token'   => $token,
        ], 200);
    }
}

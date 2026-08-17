<?php

namespace App\Http\Controllers;

use App\Models\DoctorNotification;
use Illuminate\Http\Request;

class DoctorNotificationController extends Controller
{
    public function getDoctorNotifications()
    {
        $doctor = auth()->user();

        $notifications = DoctorNotification::where('doctor_id', $doctor->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }
}

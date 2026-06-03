<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
{
    $notifications = Notification::where(
        'parent_id',
        auth()->id()
    )
    ->latest()
    ->get();

    return response()->json([
        'notifications' => $notifications
    ]);
}
}

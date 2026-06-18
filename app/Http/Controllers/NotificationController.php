<?php

namespace App\Http\Controllers;
use Kreait\Firebase\Messaging\CloudMessage;
//use Kreait\Firebase\Messaging\Notification;
use App\Models\Notification;

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

public function test()
{
    $messaging = app('firebase.messaging');

    $message = CloudMessage::withTarget(
        'token',
        'dimX37JeRQ253l4KOrSSfn:APA91bHX0Wuf-sLeHYZpvuDYFcfxpv607udagruWWXTz_cH26M5Z19g1k6kcLLynHLatUqTNPBHMxacC-93R6XU6SaSm8fS6aZgnP9cniJ61442-ANXNi70'
    )->withNotification(
        Notification::create(
            'Test Notification',
            'Hello from Laravel'
        )
    );

    $messaging->send($message);

    return response()->json([
        'message' => 'Notification sent'
    ]);
}
}

<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase-service-account.json'));

        $this->messaging = $factory->createMessaging();
    }

    public function send($token, $title, $body)
{
    $message = CloudMessage::withTarget('token', $token)
        ->withNotification(Notification::create($title, $body))
        ->withData([
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK', 
            'title' => $title,
            'body' => $body,
            'type' => 'appointment_cancelled' 
        ]);

    try {
        $result = $this->messaging->send($message);
        \Log::info("FCM Sent: " . json_encode($result));
        return $result;
    } catch (\Throwable $e) {
        \Log::error("FCM Error: " . $e->getMessage());
        return null;
    }
}
}

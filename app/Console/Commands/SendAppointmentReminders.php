<?php

namespace App\Console\Commands;
use App\Models\Appointment;
use Carbon\Carbon;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-appointment-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
       $appointments = Appointment::with('child.parent')
        ->whereIn('status', ['pending', 'confirmed'])
        ->get();

    foreach ($appointments as $appointment) {

        $parent = $appointment->child->parent;

        if (!$parent || !$parent->fcm_token) {
            continue;
        }

        $appointmentDateTime = Carbon::parse(
            $appointment->date . ' ' . $appointment->time
        );

        $minutesLeft = now()->diffInMinutes(
            $appointmentDateTime,
            false
        );

        // قبل الموعد ب 24 ساعة
        if (
            !$appointment->reminder_24_sent &&
            $minutesLeft <= 1440 &&
            $minutesLeft > 1380
        ) {

            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget(
                'token',
                $parent->fcm_token
            )
            ->withNotification(
                Notification::create(
                    'Appointment Reminder',
                    'Your appointment is tomorrow.'
                )
            );

            $messaging->send($message);

            $appointment->update([
                'reminder_24_sent' => true
            ]);
        }

       //تذكير قبل الموعد بساعتين
        if (
            !$appointment->reminder_2h_sent &&
            $minutesLeft <= 120 &&
            $minutesLeft > 60
        ) {

            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget(
                'token',
                $parent->fcm_token
            )
            ->withNotification(
                Notification::create(
                    'Appointment Reminder',
                    'Your appointment is in 2 hours.'
                )
            );

            $messaging->send($message);

            $appointment->update([
                'reminder_2h_sent' => true
            ]);
        }
    }
    $appointments = Appointment::with('child.parent')
        ->where('status', 'confirmed')
        ->where('test_reminder_sent', false)
        ->get();

    foreach ($appointments as $appointment) {

        $parent = $appointment->child->parent;

        if (!$parent || !$parent->fcm_token) {
            continue;
        }

        // وقت الموعد
        $appointmentDateTime = Carbon::parse($appointment->created_at)->addMinutes(3);

        $minutesLeft = now()->diffInMinutes($appointmentDateTime, false);

        // 🔔 قبل 3 دقائق من وقت الاختبار (يعني بعد إنشاء الموعد بـ 3 دقائق)
        if ($minutesLeft <= 3 && $minutesLeft >= 0) {

            $messaging = app('firebase.messaging');

            $message = CloudMessage::withTarget(
                'token',
                $parent->fcm_token
            )
            ->withNotification(
                Notification::create(
                    'TEST Reminder',
                    'Your appointment was just created (3 min test reminder)'
                )
            )
            ->withData([
                'appointment_id' => (string) $appointment->id,
                'sound' => 'default'
            ]);

            $messaging->send($message);

            $appointment->update([
                'test_reminder_sent' => true
            ]);
        }
    }
    }

    
}

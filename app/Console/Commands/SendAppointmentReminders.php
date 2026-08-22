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

        $messaging = app('firebase.messaging');

        if (
            !$appointment->reminder_24_sent &&
            $minutesLeft <= 1440 &&
            $minutesLeft > 1380
        ) {

            $message = CloudMessage::withTarget(
                'token',
                $parent->fcm_token
            )
            ->withNotification(
                Notification::create(
                    __('notifications.appointment_reminder_title'),
                    __('notifications.appointment_reminder_24h')
                )
            );

            $messaging->send($message);

            $appointment->update([
                'reminder_24_sent' => true
            ]);
        }

        if (
            !$appointment->reminder_2h_sent &&
            $minutesLeft <= 120 &&
            $minutesLeft > 60
        ) {

            $message = CloudMessage::withTarget(
                'token',
                $parent->fcm_token
            )
            ->withNotification(
                Notification::create(
                    __('notifications.appointment_reminder_title'),
                    __('notifications.appointment_reminder_2h')
                )
            );

            $messaging->send($message);

            $appointment->update([
                'reminder_2h_sent' => true
            ]);
        }
    }
}
    
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorAvailabilityRequest;
use App\Http\Requests\UpdateDoctorAvailabilityRequest;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateDoctorRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\DoctorAvailability;
use App\Models\Appointment;
use App\Models\Doctor;



class DoctorAvailabilityController extends Controller
{
    public function availableWorkingPeriods()
{
    $doctor = auth()->user();

    // جلب جميع أطباء نفس القسم، بما فيهم الطبيب الحالي
    $departmentDoctorIds = Doctor::where(
        'department_id',
        $doctor->department_id
    )->pluck('id');

    $days = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    $result = [];

    foreach ($days as $day) {

        // جلب جميع أوقات دوام أطباء القسم في هذا اليوم
        $availabilities = DoctorAvailability::whereIn(
                'doctor_id',
                $departmentDoctorIds
            )
            ->whereRaw('LOWER(day_of_week) = ?', [$day])
            ->orderBy('start_time')
            ->get();

        $freePeriods = [];

        // بداية ونهاية ساعات العمل العامة
        $startOfDay = Carbon::createFromTime(9, 0, 0);
        $endOfDay = Carbon::createFromTime(18, 0, 0);

        // إذا لم يوجد أي دوام لأطباء القسم بهذا اليوم
        if ($availabilities->isEmpty()) {

            $freePeriods[] = [
                'start_time' => '09:00',
                'end_time'   => '18:00',
            ];

        } else {

            $currentTime = $startOfDay->copy();

            foreach ($availabilities as $availability) {

                $start = Carbon::parse($availability->start_time);
                $end = Carbon::parse($availability->end_time);

                // تجاهل أي وقت خارج ساعات العمل العامة
                if ($end->lte($startOfDay)) {
                    continue;
                }

                if ($start->gte($endOfDay)) {
                    break;
                }

                // ضبط بداية الفترة ضمن 09:00 - 18:00
                if ($start->lt($startOfDay)) {
                    $start = $startOfDay->copy();
                }

                // ضبط نهاية الفترة ضمن 09:00 - 18:00
                if ($end->gt($endOfDay)) {
                    $end = $endOfDay->copy();
                }

                // يوجد وقت فارغ قبل بداية الدوام الحالي
                if ($currentTime->lt($start)) {

                    $freePeriods[] = [
                        'start_time' => $currentTime->format('H:i'),
                        'end_time'   => $start->format('H:i'),
                    ];
                }

                // تحريك currentTime إلى نهاية الدوام المشغول
                if ($end->gt($currentTime)) {
                    $currentTime = $end->copy();
                }
            }

            // يوجد وقت فارغ بعد آخر دوام
            if ($currentTime->lt($endOfDay)) {

                $freePeriods[] = [
                    'start_time' => $currentTime->format('H:i'),
                    'end_time'   => $endOfDay->format('H:i'),
                ];
            }
        }

        $result[] = [
            'day'          => $day,
            'day_name'     => __("messages.days.$day"),
            'free_periods' => $freePeriods,
        ];
    }

    return response()->json([
        'status'            => 'success',
        'available_periods' => $result,
    ]);
}

    public function availability(StoreDoctorAvailabilityRequest $request)
{
    $doctor = auth()->user();

    // جلب جميع أطباء نفس القسم
    $doctorIds = Doctor::where('department_id', $doctor->department_id)
        ->pluck('id');

    // التحقق من وجود تداخل فعلي في أوقات الدوام
    $conflict = DoctorAvailability::whereIn('doctor_id', $doctorIds)
        ->whereRaw('LOWER(day_of_week) = ?', [
            strtolower($request->day_of_week)
        ])
        ->where('start_time', '<', $request->end_time)
        ->where('end_time', '>', $request->start_time)
        ->exists();

    if ($conflict) {
        return response()->json([
            'message' => __('messages.doctor_time_conflict')
        ], 422);
    }

    // إنشاء الدوام
    $availability = DoctorAvailability::create([
        'doctor_id'   => $doctor->id,
        'day_of_week' => $request->day_of_week,
        'start_time'  => $request->start_time,
        'end_time'    => $request->end_time,
    ]);

    return response()->json([
        'status'       => 'success',
        'message'      => __('messages.availability_added_success'),
        'availability' => $availability
    ]);
}
    public function deleteAvailability($id)
    {
        $doctor = auth()->user();


        $availability = DoctorAvailability::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (!$availability) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.availability_not_found')
            ], 404);
        }

        $day = strtolower($availability->day_of_week);

        $startTime = $availability->start_time;

        $endTime = Carbon::parse($availability->end_time)
            ->format('H:i:s');



        $appointments = Appointment::with('child.parent')
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereRaw('LOWER(DAYNAME(date)) = ?', [$day])
            ->whereTime('time', '>=', $startTime)
            ->whereTime('time', '<', $endTime)
            ->whereNotIn('status', ['cancelled_by_clinic', 'cancelled_by_patient', 'completed','finished'])
            ->get();



        foreach ($appointments as $appointment) {


            $appointment->update([
                'status' => 'cancelled_by_clinic'
            ]);



            $parent = $appointment->child->parent;



            if ($parent && $parent->fcm_token) {

                $messaging = app('firebase.messaging');

                $message = CloudMessage::withTarget(
                    'token',
                    $parent->fcm_token
                )->withNotification(
                    Notification::create(
                        'Appointment Cancelled',
                        'Your appointment has been cancelled because the doctor is no longer available at this time.'
                    )
                )->withData([
                    'appointment_id' => (string) $appointment->id,
                    'type' => 'appointment_cancelled',
                    'sound' => 'default'
                ]);

                $messaging->send($message);
            }
        }

        $availability->delete();


        return response()->json([
            'status' => 'success',
            'message' => __('messages.availability_deleted_success'),
            'cancelled_appointments_count' => $appointments->count()
        ], 200);
    }

    public function availableTimes($doctorId, Request $request)
    {
        $date = $request->date;

        if (Carbon::parse($date)->isPast() && !Carbon::parse($date)->isToday()) {
            return response()->json([
                'status'   => 'success',
                'times'    => [],
                'day_name' => __("messages.days." . strtolower(Carbon::parse($date)->format('l'))),
                'message'  => __('messages.no_available_times'),
            ]);
        }

        $day = strtolower(Carbon::parse($date)->format('l'));
        $translatedDay = __("messages.days.{$day}");

        $availability = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('day_of_week', $day)
            ->first();

        if (!$availability) {
            return response()->json([
                'status'  => 'success',
                'times' => [],
                'day_name'      => $translatedDay,
                'message' => __('messages.no_available_times'),
            ]);
        }

        $start = Carbon::parse($availability->start_time);
        $end = Carbon::parse($availability->end_time);

        $times = [];

        $isToday = Carbon::parse($date)->isToday();

        while ($start < $end) {
            $slotDateTime = Carbon::parse("$date " . $start->format('H:i'));


            if ($isToday && $slotDateTime->isPast()) {
                $start->addMinutes(30);
                continue;
            }
            $formatted = $start->format('H:i');

            $isBooked = Appointment::where('doctor_id', $doctorId)
                ->where('date', $date)
                ->where('time', $formatted)
                ->where('status', '!=', 'cancelled_by_patient')
                ->where('status', '!=', 'cancelled_by_clinic')
                ->exists();

            if (!$isBooked) {
                $times[] = $formatted;
            }

            $start->addMinutes(30);
        }

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.available_times_fetched_success'),
            'day_name'      => $translatedDay,
            'times'   => $times
        ], 200);
    }

    public function index($doctorId)
    {
        $availabilities = DoctorAvailability::where('doctor_id', $doctorId)->get();

        $availabilities->map(function ($item) {
            $item->day_name_translated = ("messages.days." . strtolower($item->day_of_week));
            return $item;
        });

        return response()->json([
            'status'         => 'success',
            'message'        => ('messages.availabilities_fetched_success'),
            'availabilities' => $availabilities
        ], 200);
    }
}

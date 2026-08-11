<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorAvailabilityRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use App\Models\DoctorAvailability;
use App\Models\Appointment;
use App\Models\Doctor;



class DoctorAvailabilityController extends Controller
{

    public function availability(StoreDoctorAvailabilityRequest $request)
    {
        $doctor = auth()->user();
        $doctorIds = Doctor::where('department_id', $doctor->department_id)
            ->pluck('id');

        $conflict = DoctorAvailability::whereIn('doctor_id', $doctorIds)
            ->where('day_of_week', $request->day_of_week)
            ->where(function ($query) use ($request) {

                $query->whereBetween('start_time', [
                    $request->start_time,
                    $request->end_time
                ])
                    ->orWhereBetween('end_time', [
                        $request->start_time,
                        $request->end_time
                    ])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('start_time', '<=', $request->start_time)
                            ->where('end_time', '>=', $request->end_time);
                    });
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => __('messages.doctor_time_conflict')
            ], 422);
        }

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
                'message' => ('messages.availability_not_found')
            ], 404);
        }


        $day = strtolower($availability->day_of_week);


        $hasAppointments = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->whereRaw('LOWER(DAYNAME(date)) = ?', [$day])
            ->whereBetween('time', [
                $availability->start_time,
                Carbon::parse($availability->end_time)->subMinute()->format('H:i:s')
            ])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($hasAppointments) {
            return response()->json([
                'status' => 'error',
                'message' => ('messages.cannot_delete_availability_with_appointments')
            ], 422);
        }


        $availability->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('messages.availability_deleted_success')
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

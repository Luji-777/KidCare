<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorAvailabilityRequest;
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

    public function index($doctorId)
    {
        $availabilities = DoctorAvailability::where('doctor_id', $doctorId)->get();

        $availabilities->map(function ($item) {
            $item->day_name_translated = __("messages.days." . strtolower($item->day_of_week));
            return $item;
        });

        return response()->json([
            'status'         => 'success',
            'message'        => __('messages.availabilities_fetched_success'),
            'availabilities' => $availabilities
        ], 200);
    }

    public function availableTimes($doctorId, Request $request)
    {
        $date = $request->date;

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

        while ($start < $end) {

            $formatted = $start->format('H:i');

            $isBooked = Appointment::where('doctor_id', $doctorId)
                ->where('date', $date)
                ->where('time', $formatted)
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
}

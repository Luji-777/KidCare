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


class DoctorAvailabilityController extends Controller
{

    public function availability(StoreDoctorAvailabilityRequest $request)
    {
        $availability = DoctorAvailability::create([
            'doctor_id'   => auth()->id(),
            'day_of_week' => $request->day_of_week,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
        ]);

        return response()->json([
            'message'      => 'Added successfully',
            'availability' => $availability
        ]);
    }

    public function index($doctorId)
    {
        $availabilities = DoctorAvailability::where('doctor_id', $doctorId)->get();

        return response()->json($availabilities);
    }

    public function availableTimes($doctorId, Request $request)
    {
        $date = $request->date;

        $day = strtolower(Carbon::parse($date)->format('l'));

        $availability = DoctorAvailability::where('doctor_id', $doctorId)
            ->where('day_of_week', $day)
            ->first();

        if (!$availability) {
            return response()->json([
                'times' => [],
                'message' => 'No times available'
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
            'times' => $times
        ]);
    }

}
<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\DoctorAvailability;
use App\Models\Appointment;

class AppointmentController extends Controller
{
    public function store(StoreAppointmentRequest $request)
{
    $child = auth()->user()
        ->children()
        ->where('id', $request->child_id)
        ->first();

    if (!$child) {
        return response()->json(['message' => 'Child not found'], 404);
    }

    $date = Carbon::parse($request->date)->format('Y-m-d');
    $time = Carbon::parse($request->time)->format('H:i');

    
    $day = strtolower(Carbon::parse($date)->format('l'));

    $availability = DoctorAvailability::where('doctor_id', $request->doctor_id)
        ->where('day_of_week', $day)
        ->first();

    if (!$availability) {
        return response()->json([
            'message' => 'Doctor is not available on this day'
        ], 400);
    }

    
    $start = Carbon::parse($availability->start_time)->format('H:i');
    $end = Carbon::parse($availability->end_time)->format('H:i');

    if ($time < $start || $time >= $end) {
        return response()->json([
            'message' => 'Time is outside doctor working hours'
        ], 400);
    }

    
    $isBooked = Appointment::where('doctor_id', $request->doctor_id)
        ->where('date', $date)
        ->where('time', $time)
        ->exists();

    if ($isBooked) {
        return response()->json([
            'message' => 'Time already booked'
        ], 400);
    }

   
    $appointment = Appointment::create([
        'child_id' => $request->child_id,
        'doctor_id' => $request->doctor_id,
        'date' => $date,
        'time' => $time,
        'status' => 'pending',
        'price' => $request->price ?? null
    ]);

    return response()->json([
        'message' => 'Appointment booked successfully',
        'appointment' => $appointment
    ], 201);
}


}
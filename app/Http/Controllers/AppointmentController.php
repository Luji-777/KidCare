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
        $doctor = \App\Models\Doctor::findOrFail($request->doctor_id);


        $appointment = Appointment::create([
            'child_id' => $request->child_id,
            'doctor_id' => $request->doctor_id,
            'date' => $date,
            'time' => $time,
            'status' => 'pending',
            'price'     => $doctor->fee,
        ]);

        return response()->json([
            'message' => 'Appointment booked successfully',
            'appointment' => $appointment
        ], 201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment)
    {
        $doctorId = $request->doctor_id ?? $appointment->doctor_id;

        $childId = $request->child_id ?? $appointment->child_id;

        $date = $request->date
            ? Carbon::parse($request->date)->format('Y-m-d')
            : $appointment->date;

        $time = $request->time
            ? Carbon::parse($request->time)->format('H:i')
            : $appointment->time;

        $day = strtolower(Carbon::parse($date)->format('l'));

        $availability = DoctorAvailability::where('doctor_id', $doctorId)
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

        $isBooked = Appointment::where('doctor_id', $doctorId)
            ->where('date', $date)
            ->where('time', $time)
            ->where('id', '!=', $appointment->id)
            ->exists();

        if ($isBooked) {
            return response()->json([
                'message' => 'Time already booked'
            ], 400);
        }
        $doctor = \App\Models\Doctor::findOrFail($request->doctor_id);


        $appointment->update([
            'child_id' => $childId,
            'doctor_id' => $doctorId,
            'date' => $date,
            'time' => $time,
            'price' => $request->price ?? $appointment->price,
        ]);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment
        ]);
    }

    public function index()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->latest()
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price',
                'created_at'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }

    public function show(Appointment $appointment)
    {
        $isOwner = auth()->user()
            ->children()
            ->where('id', $appointment->child_id)
            ->exists();

        if (!$isOwner) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }


        return response()->json([
            'appointment' => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'child_id' => $appointment->child_id,
                'date' => $appointment->date,
                'time' => $appointment->time,
                'status' => $appointment->status,
                'price' => $appointment->price,
            ]
        ]);
    }

    public function destroy(Appointment $appointment)
    {
        $isOwner = auth()->user()
            ->children()
            ->where('id', $appointment->child_id)
            ->exists();

        if (!$isOwner) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $appointment->delete();


        return response()->json([
            'message' => 'Appointment deleted successfully'
        ]);
    }

    public function upcoming()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }

    public function past()
    {
        $appointments = Appointment::whereHas('child', function ($query) {
            $query->where('parent_id', auth()->id());
        })
            ->whereDate('date', '<', now()->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get([
                'id',
                'doctor_id',
                'child_id',
                'date',
                'time',
                'status',
                'price'
            ]);

        return response()->json([
            'appointments' => $appointments
        ]);
    }
}

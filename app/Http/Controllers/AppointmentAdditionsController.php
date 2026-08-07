<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment_additions;
use App\Models\Appointment;

class AppointmentAdditionsController extends Controller
{
    public function store(Request $request, $appointmentId)
    {
        $request->validate([
            'item_name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $doctor = auth()->user();

        $appointment = Appointment::where('id', $appointmentId)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        // إنشاء الإضافة
        Appointment_additions::create([
            'appointment_id' => $appointment->id,
            'item_name'      => $request->item_name,
            'price'          => $request->price,
        ]);

        // جلب جميع الإضافات الخاصة بالموعد
        $additions = Appointment_additions::where('appointment_id', $appointment->id)->get();

        // حساب مجموع الإضافات
        $totalAdditions = $additions->sum('price');

        // تحديث السعر النهائي
        $appointment->update([
            'price' => $appointment->base_price + $totalAdditions,
        ]);

        $appointment->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Addition added successfully',

            'appointment' => [
                'appointment_id'  => $appointment->id,
                'base_price'      => $appointment->base_price,

                // جميع الإضافات
                'additions' => $additions,

                'total_additions' => $totalAdditions,

                'final_price' => $appointment->price,
            ]
        ]);
    }
    public function destroy($additionId)

    {
        $doctor = auth()->user();

        $addition = Appointment_additions::findOrFail($additionId);

        $appointment = Appointment::where('id', $addition->appointment_id)
            ->where('doctor_id', $doctor->id)
            ->firstOrFail();

        $addition->delete();

        // إعادة حساب مجموع الإضافات
        $totalAdditions = Appointment_additions::where('appointment_id', $appointment->id)
            ->sum('price');

        // تحديث السعر النهائي
        $appointment->update([
            'price' => $appointment->base_price + $totalAdditions,
        ]);

        $appointment->refresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Addition deleted successfully',

            'appointment' => [
                'appointment_id' => $appointment->id,

                'base_price' => $appointment->base_price,

                'total_additions' => $totalAdditions,

                'final_price' => $appointment->price,
            ]
        ]);
    }
}

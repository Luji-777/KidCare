<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Appointment_additions;
use App\Models\Appointment;

class AppointmentAdditionsController extends Controller
{
   public function store(Request $request, $appointmentId)
{
    // Validation
    $request->validate([
        'item_name' => 'required|string|max:255',
        'price'     => 'required|numeric|min:0',
    ]);

    $doctor = auth()->user();

    // التأكد أن الموعد تابع لهذا الدكتور
    $appointment = Appointment::where('id', $appointmentId)
        ->where('doctor_id', $doctor->id)
        ->firstOrFail();

    // إنشاء الإضافة
    Appointment_additions::create([
        'appointment_id' => $appointment->id,
        'item_name'      => $request->item_name,
        'price'          => $request->price,
    ]);

    // جلب جميع إضافات الموعد
    $additions = Appointment_additions::where(
        'appointment_id',
        $appointment->id
    )->get();

    // مجموع أسعار الإضافات
    $totalAdditions = $additions->sum('price');

    // سعر الكشفية الأساسي
    $appointmentPrice = $appointment->price;

    // السعر النهائي
    $finalPrice = $appointmentPrice + $totalAdditions;

    return response()->json([
        'status'  => 'success',
        'message' => 'Addition added successfully',

        'appointment' => [
            'appointment_id' => $appointment->id,

            // سعر الكشفية الأساسي
            'appointment_price' => $appointmentPrice,

            // جميع الإضافات
            'additions' => $additions,

            // مجموع الإضافات
            'total_additions' => $totalAdditions,

            // الكشفية + الإضافات
            'final_price' => $finalPrice,
        ]
    ], 201);
}
    public function destroy($additionId)
{
    $doctor = auth()->user();

    
    $addition = Appointment_additions::findOrFail($additionId);

    
    $appointment = Appointment::where('id', $addition->appointment_id)
        ->where('doctor_id', $doctor->id)
        ->firstOrFail();

    
    $addition->delete();

    
    $additions = Appointment_additions::where(
        'appointment_id',
        $appointment->id
    )->get();

    
    $totalAdditions = $additions->sum('price');

    
    $appointmentPrice = $appointment->price;

   
    $finalPrice = $appointmentPrice + $totalAdditions;

    return response()->json([
        'status'  => 'success',
        'message' => 'Addition deleted successfully',

        'appointment' => [
            'appointment_id' => $appointment->id,

            
            'appointment_price' => $appointmentPrice,

           
            'additions' => $additions,

            
            'total_additions' => $totalAdditions,

            
            'final_price' => $finalPrice,
        ]
    ], 200);
}
}

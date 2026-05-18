<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Carbon\Carbon;
use Exception;

class PaymentController extends Controller
{

    public function getSummary($appointment_id)
    {

        $appointment = Appointment::with(['child', 'doctor.department'])
            ->where('id', $appointment_id)
            ->first();

        if (!$appointment) {
            return response()->json(['message' => 'Appointment is not found'], 404);
        }


        if ($appointment->child->parent_id !== auth()->id()) {
            return response()->json(['message' => 'unauthorized'], 403);
        }

        $patient_full_name = trim($appointment->child->first_name . ' ' . $appointment->child->last_name);
        $doctor_full_name  = trim($appointment->doctor->first_name . ' ' . $appointment->doctor->last_name);


        $patient_age = Carbon::parse($appointment->child->birth_date)->age;

        return response()->json([
            "patient_name"      => $patient_full_name,
            "patient_age"       => (string)$patient_age, // تحويله لنص بناءً على التوثيق المرسل
            "patient_image_url" => $appointment->child->image ? url('storage/' . $appointment->child->image) : '',
            "doctor_name"       => $doctor_full_name,
            "department_name"   => $appointment->doctor->department->name ?? 'عيادة الأطفال',
            "date_time"         => $appointment->date . ' ' . $appointment->time, // دمج التاريخ والوقت
            "price"             => (string)$appointment->price,
            "currency"          => $appointment->currency,
        ], 200);
    }


    public function checkout(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'amount'         => 'required|numeric',
            'currency'       => 'required|string|max:3',
        ]);

        $appointment = Appointment::with('child')->find($request->appointment_id);

        // حماية المعاملة المالية من التلاعب
        if ($appointment->child->parent_id !== auth()->id()) {
            return response()->json(['message' => 'غير مصرح لك لإتمام هذه العملية المالية'], 403);
        }

        // التأكد أن الموعد غير مدفوع مسبقاً لحظر الدفع المزدوج
        if ($appointment->status === 'confirmed') {
            return response()->json(['message' => 'هذا الموعد مؤكد ومدفوع مسبقاً'], 400);
        }

        // إعداد مفتاح Stripe السري من ملف .env
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // تحويل المبلغ لأصغر وحدة نقدية (سنتات) لأن Stripe لا يقبل الكسور في العملات الأساسية
            $amountInCents = round($request->amount * 100);

            // إنشاء الـ Payment Intent في Stripe
            $intent = PaymentIntent::create([
                'amount'   => $amountInCents,
                'currency' => strtolower($request->currency),
                'metadata' => [
                    'appointment_id' => $appointment->id,
                    'parent_id'      => auth()->id()
                ]
            ]);

            // تسجيل المعاملة في جدول الـ transactions الخاص بك بوضع الانتظار الحالي
            $transaction = Transaction::create([
                'appointment_id'           => $appointment->id,
                'stripe_payment_intent_id' => $intent->id,
                'amount'                   => $request->amount,
                'currency'                 => $request->currency,
                'status'                   => $intent->status,
            ]);

            // الرد المتوافق حرفياً مع متطلبات الفرونت-إند
            return response()->json([
                "status"         => $intent->status,
                "client_secret"  => $intent->client_secret, // التوكن المهم جداً للموبايل
                "transaction_id" => (string)$transaction->id
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'فشلت عملية الدفع في نظام سترايب: ' . $e->getMessage()], 500);
        }
    }
}

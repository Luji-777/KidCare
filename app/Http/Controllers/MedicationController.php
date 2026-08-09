<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MedicalRecord;

class MedicationController extends Controller
{
    public function showPrescription($recordId)
{
    $parent = auth()->user();

    $record = MedicalRecord::where('id', $recordId)
        ->whereHas('appointment.child', function ($q) use ($parent) {
            $q->where('parent_id', $parent->id);
        })
        ->with([
            'medications',
            'appointment.doctor'
        ])
        ->firstOrFail();

    return response()->json([
        'status' => 'success',

        'prescription' => [
            'record_id' => $record->id,

            'appointment_id' => $record->appointment_id,

            'doctor' => [
                'id' => $record->appointment->doctor->id,
                'name' =>
                    $record->appointment->doctor->first_name .
                    ' ' .
                    $record->appointment->doctor->last_name,
            ],

            'medications' => $record->medications,
        ]
    ]);
}
}

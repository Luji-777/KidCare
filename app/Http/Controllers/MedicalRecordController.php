<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Growth;
use App\Models\Child;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Models\MedicalRecord;



class MedicalRecordController extends Controller
{
    private $growthChart = [
        0 => ['weight' => [3, 11],  'height' => [50, 80]],
        1 => ['weight' => [9, 14],  'height' => [75, 90]],
        2 => ['weight' => [11, 16], 'height' => [85, 100]],
        3 => ['weight' => [13, 18], 'height' => [95, 108]],
        4 => ['weight' => [15, 21], 'height' => [100, 115]],
        5 => ['weight' => [17, 24], 'height' => [105, 122]],
        6 => ['weight' => [19, 28], 'height' => [110, 128]],
        7 => ['weight' => [19, 28], 'height' => [110, 128]],
    ];

    public function medicalRecord($childId)
    {
        $child = Child::find($childId);

        if (!$child) {
            return response()->json([
                'status' => 'error',
                'message' => __('messages.child_not_found')
            ], 404);
        }


        $growth = Growth::where('child_id', $child->id)
            ->latest('date')
            ->first();

        $weightStatus = null;
        $heightStatus = null;

        if ($growth) {

            $age = Carbon::parse($child->birth_date)->age;

            $weightStatus = $this->getWeightStatus($growth->weight, $age);
            $heightStatus = $this->getHeightStatus($growth->height, $age);
        }

        $doctor = auth()->user();
        $lastAppointment = Appointment::where('child_id', $child->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed', 'finished')
            ->latest('date')
            ->with(['doctor', 'record'])
            ->first();


        $previousVisits = Appointment::where('child_id', $child->id)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'completed', 'finished')
            ->when($lastAppointment, function ($q) use ($lastAppointment) {
                $q->where('id', '!=', $lastAppointment->id);
            })
            ->latest('date')
            ->with(['doctor', 'record'])
            ->get();

        return response()->json([
            'status' => 'success',
            'summary' => [
                'weight' => $growth?->weight,
                'weight_status' => $weightStatus,

                'height' => $growth?->height,
                'height_status' => $heightStatus,

                'blood_type' => $child->blood_type,
                'allergies' => $child->allergies,

                'last_visit' => $lastAppointment ? [
                    'date' => $lastAppointment->date,
                    'doctor_name' => $lastAppointment->doctor->first_name . ' ' . $lastAppointment->doctor->last_name,
                    'diagnosis' => $lastAppointment->record->diagnosis ?? null,
                ] : null,

                'previous_visits' => $previousVisits->map(function ($visit) {
                    return [
                        'date' => $visit->date,
                        'doctor_name' => $visit->doctor->first_name . ' ' . $visit->doctor->last_name,
                        'diagnosis' => $visit->record->diagnosis ?? null,
                    ];
                }),
            ]
        ]);
    }
    private function getWeightStatus($weight, $age)
    {
        $range = $this->growthChart[$age]['weight'];

        return ($weight >= $range[0] && $weight <= $range[1])
            ? 'normal'
            : 'abnormal';
    }

    private function getHeightStatus($height, $age)
    {
        $range = $this->growthChart[$age]['height'];

        return ($height >= $range[0] && $height <= $range[1])
            ? 'normal'
            : 'abnormal';
    }
    public function showMedicalRecord($appointmentId)
    {
        $parent = auth()->user();

        $record = MedicalRecord::with('medications')
            ->whereHas('appointment', function ($query) use ($appointmentId, $parent) {
                $query->where('id', $appointmentId)
                    ->whereHas('child', function ($q) use ($parent) {
                        $q->where('parent_id', $parent->id);
                    });
            })
            ->first();

        if (!$record) {
            return response()->json([
                'status' => 'error',
                'message' => 'Medical record not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'medical_record' => [
                'id' => $record->id,
                'appointment_id' => $record->appointment_id,
                'diagnosis' => $record->diagnosis,
                'doctor_notes' => $record->doctor_notes,
                //'medications' => $record->medications,
                //'created_at' => $record->created_at,
            ]
        ]);
    }
}

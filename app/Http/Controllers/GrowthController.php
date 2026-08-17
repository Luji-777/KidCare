<?php

namespace App\Http\Controllers;

use App\Models\Growth;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GrowthController extends Controller
{
    public function index($child_id)
    {
        $child = auth()->user()->children()->where('id', $child_id)->first();

        if (!$child) {
            return response()->json(['message' => __('messages.child_not_found')], 404);
        }

        $growthRecords = Growth::where('child_id', $child_id)
            ->orderBy('date', 'asc')
            ->get();

        $birthDate = Carbon::parse($child->birth_date);
        $currentAgeInMonths = $birthDate->diffInMonths(Carbon::now());
        $gender = $child->gender;


        $formattedHistory = $growthRecords->map(function ($record) use ($birthDate, $gender) {
            $recordDate = Carbon::parse($record->date);
            $ageInMonths = $birthDate->diffInMonths($recordDate);

            $healthStatus = $this->getChildHealthStatus($record->weight, $record->height, $ageInMonths, $gender);

            return [
                'id'            => $record->id,
                'height'        => $record->height,
                'weight'        => $record->weight,
                'date'          => $record->date,
                'age_in_months' => $ageInMonths,
                'bmi'           => $healthStatus['bmi'],
                'status_text'   => $healthStatus['text'],
                'status_color'  => $healthStatus['color'],
            ];
        });

        $whoStandards = [];

        for ($month = 0; $month <= $currentAgeInMonths; $month++) {
            $whoStandards[] = $this->generateWhoWeightStandards($month, $gender);
        }

        return response()->json([
            'child_name'        => $child->first_name,
            'child_gender'      => $gender,
            'current_age_months' => $currentAgeInMonths,
            'growth_history'    => $formattedHistory,
            'who_standards'     => $whoStandards
        ], 200);
    }

    private function generateWhoWeightStandards($month, $gender)
    {
        $baseWeight = ($gender === 'male') ? 3.3 : 3.2;

        if ($month <= 12) {

            $idealWeight = $baseWeight + ($month * 0.55) - ($month * $month * 0.008);
        } elseif ($month > 12 && $month <= 24) {

            $idealWeight = $baseWeight + 6.0 + (($month - 12) * 0.22);
        } else {

            $idealWeight = $baseWeight + 8.6 + (($month - 24) * 0.15);
        }

        $percentageSpread = 0.15 + ($month * 0.001);

        $minWeight = $idealWeight * (1 - $percentageSpread);
        $maxWeight = $idealWeight * (1 + $percentageSpread);

        return [
            'age_in_months' => $month,
            'child_gender'       => $gender === 'male' ? __('messages.male') : __('messages.female'),
            'who_min_weight' => round($minWeight, 1),
            'who_ideal'     => round($idealWeight, 1),
            'who_max_weight' => round($maxWeight, 1),
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'child_id' => 'required|exists:children,id',
            'height'   => 'required|numeric|min:10|max:250',
            'weight'   => 'required|numeric|min:1|max:150',
            'date'     => 'required|string',
        ]);

        $child = auth()->user()->children()->where('id', $request->child_id)->first();

        if (!$child) {
            return response()->json(['message' => __('messages.child_not_found')], 403);
        }
        try {
            $carbonDate = Carbon::createFromFormat('d-m-Y', $request->date);
        } catch (\Exception $e) {
            try {
                $carbonDate = Carbon::parse($request->date);
            } catch (\Exception $ex) {
                return response()->json(['message' => __('messages.invalid_date_format')], 400);
            }
        }

        if ($carbonDate->isAfter(Carbon::today())) {
            return response()->json(['message' => __('messages.future_date_error')], 422);
        }

        $birthDate = Carbon::parse($child->birth_date);
        $ageInMonths = $birthDate->diffInMonths($carbonDate);

        $growth = Growth::create([
            'child_id' => $request->child_id,
            'height'   => $request->height,
            'weight'   => $request->weight,
            'date'     => $carbonDate->format('Y-m-d'),
        ]);
        $healthStatus = $this->getChildHealthStatus(
            $growth->weight,
            $growth->height,
            $ageInMonths,
            $child->gender
        );

        return response()->json([
            'message' => __('messages.growth_record_added'),
            'data'    => [
                'id'            => $growth->id,
                'child_name'    => $child->first_name,
                'height'        => $growth->height,
                'weight'        => $growth->weight,
                'date'          => $growth->date,
                'age_in_months' => $ageInMonths,
                'bmi'           => $healthStatus['bmi'],
                'status_text'   => $healthStatus['text'],
                'status_color'  => $healthStatus['color']
            ]
        ], 201);
    }

    private function getChildHealthStatus($weight, $height, $ageInMonths, $gender)
    {
        $heightInMeters = $height / 100;
        $bmi = $heightInMeters > 0 ? round($weight / ($heightInMeters * $heightInMeters), 1) : 0;

        if ($ageInMonths <= 24) {
            $minHealthy = ($gender === 'male') ? 14.5 : 14.0;
            $maxHealthy = ($gender === 'male') ? 18.5 : 18.0;
        } elseif ($ageInMonths > 24 && $ageInMonths <= 60) { // من سنتين إلى 5 سنوات
            $minHealthy = ($gender === 'male') ? 13.8 : 13.5;
            $maxHealthy = ($gender === 'male') ? 16.8 : 16.5;
        } else { // من 5 سنوات إلى 7 سنوات
            $minHealthy = ($gender === 'male') ? 13.5 : 13.0;
            $maxHealthy = ($gender === 'male') ? 17.0 : 16.8;
        }

        if ($bmi < $minHealthy) {
            return [
                'bmi'   => $bmi,
                'text'  => __('messages.bmi_underweight'),
                'color' => '#FF3B30'
            ];
        } elseif ($bmi >= $minHealthy && $bmi <= $maxHealthy) {
            return [
                'bmi'   => $bmi,
                'text'  => __('messages.bmi_healthy'),
                'color' => '#34C759'
            ];
        } else {
            return [
                'bmi'   => $bmi,
                'text'  => __('messages.bmi_overweight'),
                'color' => '#FFCC00'
            ];
        }
    }

    public function destroy($id)
    {
        $growth = Growth::find($id);

        if (!$growth) {
            return response()->json(['message' => __('messages.record_not_found')], 404);
        }
        $child = auth()->user()->children()->where('id', $growth->child_id)->exists();

        if (!$child) {
            return response()->json(['message' => __('messages.unauthorized_role')], 403);
        }

        $growth->delete();

        return response()->json(['message' => __('messages.growth_record_deleted_successfully')], 200);
    }
}

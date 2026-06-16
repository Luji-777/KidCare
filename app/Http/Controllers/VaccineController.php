<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vaccine;


class VaccineController extends Controller
{
    public function getChildVaccines($childId)
    {
        // 1. نتأكد أن الطفل تابع للمستخدم الحالي
        $child = auth()->user()
            ->children()
            ->where('id', $childId)
            ->first();

        if (!$child) {
            return response()->json([
                'message' => 'Child not found'
            ], 404);
        }

        // 2. نحسب عمر الطفل (بالأشهر)
        $ageMonths = \Carbon\Carbon::parse($child->birth_date)
            ->diffInMonths(now());

        // 3. نجيب اللقاحات المناسبة فقط
        $vaccines = Vaccine::where('age_months', '<=', $ageMonths)
            ->with(['children' => function ($query) use ($childId) {
                $query->where('child_id', $childId);
            }])
            ->get();

        // 4. نرتب البيانات
        $result = $vaccines->map(function ($vaccine) {
            $pivot = $vaccine->children->first()?->pivot;

            return [
                'id' => $vaccine->id,
                'name' => $vaccine->name,
                'description' => $vaccine->description,
                'age_months' => $vaccine->age_months,

                // 5. أهم جزء: الحالة
                'status' => $pivot ? 'taken' : 'not_taken',

                'taken_date' => $pivot->taken_date ?? null,
                'notes' => $pivot->notes ?? null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'child_age_months' => $ageMonths,
            'vaccines' => $result
        ]);
    } 
}

<?php

namespace App\Http\Controllers;

use App\Models\Vaccine;
use App\Models\VaccineSchedule;
use App\Models\ChildVaccination;
use App\Models\Child;
use App\Models\Receptionist;
use App\Models\ParentModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class VaccineController extends Controller
{

    public function storeVaccine(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|unique:vaccines,name',
            'min_age_months' => 'required|integer|min:0',
            'max_age_months' => 'required|integer|gte:min_age_months',
            'description'    => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $vaccine = Vaccine::create([
            'name'           => $request->name,
            'min_age_months' => $request->min_age_months,
            'max_age_months' => $request->max_age_months,
            'description'    => $request->description,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.vaccine_created_successfully'),
            'vaccine' => $vaccine
        ], 201);
    }

    public function getAllVaccines()
    {
        $vaccines = Vaccine::orderBy('min_age_months', 'asc')->get();

        return response()->json([
            'status'   => 'success',
            'count'    => $vaccines->count(),
            'vaccines' => $vaccines
        ], 200);
    }
    public function createSchedule(Request $request)
{
    $currentUser = auth()->user();

    if (!$currentUser || !($currentUser instanceof Receptionist)) {
        return response()->json([
            'status'  => 'error',
            'message' => __('messages.unauthorized')
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'vaccine_id' => 'required|exists:vaccines,id',
        'date'       => 'required|date|date_format:Y-m-d|after_or_equal:today',
        'start_time' => 'required|date_format:H:i',
        'end_time'   => 'required|date_format:H:i|after:start_time',
        'notes'      => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $alreadyExists = VaccineSchedule::where('vaccine_id', $request->vaccine_id)
        ->where('date', $request->date)
        ->where('status', '!=', 'cancelled')
        ->exists();

    if ($alreadyExists) {
        return response()->json([
            'status'  => 'error',
            'message' => __('messages.vaccine_schedule_already_exists')
        ], 400);
    }

    // إنشاء جدول اللقاح
    $schedule = VaccineSchedule::create([
        'vaccine_id' => $request->vaccine_id,
        'date'       => $request->date,
        'start_time' => $request->start_time,
        'end_time'   => $request->end_time,
        'status'     => 'available',
        'notes'      => $request->notes,
    ]);

    // جلب اللقاح مع معلومات العمر
    $vaccine = $schedule->load('vaccine')->vaccine;

    /*
    |--------------------------------------------------------------------------
    | تحديد الأطفال المناسبين للعمر
    |--------------------------------------------------------------------------
    */

    $scheduleDate = Carbon::parse($schedule->date);

    // أصغر عمر مسموح
    $minimumBirthDate = $scheduleDate->copy()
        ->subMonths($vaccine->min_age_months);

    // أكبر عمر مسموح
    $maximumBirthDate = $scheduleDate->copy()
        ->subMonths($vaccine->max_age_months);

    $children = Child::with('parent')
        ->whereDate('birth_date', '<=', $minimumBirthDate->toDateString())
        ->whereDate('birth_date', '>=', $maximumBirthDate->toDateString())
        ->get();

    /*
    |--------------------------------------------------------------------------
    | إرسال الإشعارات للأهل
    |--------------------------------------------------------------------------
    */

    $messaging = app('firebase.messaging');

    // حتى إذا كان عند الأب أكثر من طفل مناسب، يصله إشعار واحد فقط
    $parents = $children
        ->filter(function ($child) {
            return $child->parent && $child->parent->fcm_token;
        })
        ->map(function ($child) {
            return $child->parent;
        })
        ->unique('id');

    foreach ($parents as $parent) {

        $message = CloudMessage::withTarget(
            'token',
            $parent->fcm_token
        )->withNotification(
            Notification::create(
                'New Vaccine Available',
                $vaccine->name . ' is available for children of the appropriate age.'
            )
        )->withData([
            'type'        => 'vaccine_schedule_created',
            'schedule_id' => (string) $schedule->id,
            'vaccine_id'  => (string) $vaccine->id,
            'vaccine_name'=> $vaccine->name,
            'date'        => $schedule->date->toDateString(),
            'start_time'  => $schedule->start_time,
            'end_time'    => $schedule->end_time,
        ]);

        $messaging->send($message);
    }

    return response()->json([
        'status'   => 'success',
        'message'  => __('messages.vaccine_schedule_created_successfully'),
        'schedule' => $schedule->load('vaccine'),
        'notified_parents_count' => $parents->count(),
    ], 201);
}
   public function updateScheduleStatus(Request $request, $scheduleId)
{
    $currentUser = auth()->user();

    // التأكد أن المستخدم Receptionist
    if (!$currentUser || !($currentUser instanceof Receptionist)) {
        return response()->json([
            'status'  => 'error',
            'message' => __('messages.unauthorized')
        ], 403);
    }

    // جلب جدول اللقاح مع اللقاح
    $schedule = VaccineSchedule::with('vaccine')->find($scheduleId);

    if (!$schedule) {
        return response()->json([
            'status'  => 'error',
            'message' => __('messages.schedule_not_found')
        ], 404);
    }

    // التحقق من الحالة
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:available,finished,cancelled',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    // تحديث حالة الجدول
    $schedule->update([
        'status' => $request->status
    ]);

    /*
    |--------------------------------------------------------------------------
    | إرسال الإشعار عند انتهاء أو إلغاء جدول اللقاح
    |--------------------------------------------------------------------------
    */

    if (in_array($request->status, ['finished', 'cancelled'])) {

        $vaccine = $schedule->vaccine;

        // جلب الأطفال الذين لديهم أب
        $children = Child::with('parent')->get();

        $messaging = app('firebase.messaging');

        foreach ($children as $child) {

            $parent = $child->parent;

            // إذا ما عند الأب FCM token
            if (!$parent || !$parent->fcm_token) {
                continue;
            }

            // حساب عمر الطفل بالأشهر
            $ageInMonths = Carbon::parse($child->birth_date)
                ->diffInMonths(Carbon::today());

            // التأكد أن عمر الطفل مناسب للقاح
            if (
                $ageInMonths >= $vaccine->min_age_months &&
                $ageInMonths <= $vaccine->max_age_months
            ) {

                if ($request->status === 'finished') {

                    $title = 'Vaccination Schedule Finished';

                    $body = "The {$vaccine->name} vaccination schedule has been finished.";

                } else {

                    $title = 'Vaccination Schedule Cancelled';

                    $body = "The {$vaccine->name} vaccination schedule has been cancelled.";
                }

                $message = CloudMessage::withTarget(
                    'token',
                    $parent->fcm_token
                )
                ->withNotification(
                    Notification::create(
                        $title,
                        $body
                    )
                )
                ->withData([
                    'type' => 'vaccine_schedule_status',
                    'schedule_id' => (string) $schedule->id,
                    'vaccine_id' => (string) $vaccine->id,
                    'vaccine_name' => $vaccine->name,
                    'status' => $request->status,
                ]);

                $messaging->send($message);
            }
        }
    }

    return response()->json([
        'status'  => 'success',
        'message' => __('messages.schedule_status_updated'),
        'data'    => $schedule->load('vaccine')
    ], 200);
}

    public function recordChildVaccination(Request $request)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'child_id'   => 'required|exists:children,id',
            'vaccine_id' => 'required|exists:vaccines,id',
            'given_date' => 'required|date|date_format:Y-m-d',
            'notes'      => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // منع توثيق نفس اللقاح لنفس الطفل أكثر من مرة
        $alreadyGiven = ChildVaccination::where('child_id', $request->child_id)
            ->where('vaccine_id', $request->vaccine_id)
            ->exists();

        if ($alreadyGiven) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.vaccine_already_given_to_child')
            ], 400);
        }

        $record = ChildVaccination::create([
            'child_id'        => $request->child_id,
            'vaccine_id'      => $request->vaccine_id,
            'given_date'      => $request->given_date,
            'notes'           => $request->notes,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.vaccination_recorded_successfully'),
            'record'  => $record->load(['child:id,first_name', 'vaccine:id,name'])
        ], 201);
    }


    public function getAvailableSchedules(Request $request)
    {
        $currentUser = auth()->user();

        $query = VaccineSchedule::with('vaccine')
            ->where('date', '>=', now()->toDateString())
            ->where('status', 'available');

        if ($currentUser instanceof ParentModel) {

            if ($request->has('child_id')) {
                $child = Child::where('id', $request->child_id)
                    ->where('parent_id', $currentUser->id)
                    ->first();

                if (!$child) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => __('messages.unauthorized')
                    ], 403);
                }

                $childAgeInMonths = $child->age_in_months;

                $query->whereHas('vaccine', function ($q) use ($childAgeInMonths) {
                    $q->where('min_age_months', '<=', $childAgeInMonths)
                        ->where('max_age_months', '>=', $childAgeInMonths);
                });
            } else {
                $children = Child::where('parent_id', $currentUser->id)->get();

                if ($children->isEmpty()) {
                    return response()->json([
                        'status'    => 'success',
                        'message'   => 'No children registered for this parent.',
                        'schedules' => []
                    ], 200);
                }

                $childrenAges = $children->map(function ($child) {
                    return $child->age_in_months;
                })->toArray();

                $query->whereHas('vaccine', function ($q) use ($childrenAges) {
                    $q->where(function ($subQuery) use ($childrenAges) {
                        foreach ($childrenAges as $age) {
                            $subQuery->orWhere(function ($q2) use ($age) {
                                $q2->where('min_age_months', '<=', $age)
                                    ->where('max_age_months', '>=', $age);
                            });
                        }
                    });
                });
            }
        }

        $schedules = $query->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        $formattedSchedules = $schedules->map(function ($s) {
            return [
                'id'                => $s->id,
                'vaccine_name'      => $s->vaccine?->name,
                'min_age_months'    => $s->vaccine?->min_age_months,
                'max_age_months'    => $s->vaccine?->max_age_months,
                'date'              => $s->date,
                'start_time'        => Carbon::parse($s->start_time)->format('H:i'),
                'end_time'          => Carbon::parse($s->end_time)->format('H:i'),
                'notes'             => $s->notes,
            ];
        });

        return response()->json([
            'status'    => 'success',
            'schedules' => $formattedSchedules
        ], 200);
    }


    public function getChildVaccinationHistory($childId)
    {
        $currentUser = auth()->user();

        // فحص صلاحية الوصول (الأب يرى أطفاله فقط، والرسبشن يرى الجميع)
        if ($currentUser instanceof ParentModel) {
            $childExists = Child::where('id', $childId)
                ->where('parent_id', $currentUser->id)
                ->exists();

            if (!$childExists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => __('messages.unauthorized')
                ], 403);
            }
        } elseif (!($currentUser instanceof Receptionist)) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $records = ChildVaccination::with('vaccine')
            ->where('child_id', $childId)
            ->orderByDesc('given_date')
            ->get();

        $formattedRecords = $records->map(function ($r) {
            return [
                'id'           => $r->id,
                'vaccine_name' => $r->vaccine?->name,
                'given_date'   => $r->given_date,
                'notes'        => $r->notes,
            ];
        });

        return response()->json([
            'status'  => 'success',
            'records' => $formattedRecords
        ], 200);
    }
}

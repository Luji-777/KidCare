<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreChildRequest;
use App\Http\Requests\UpdateChildRequest;
use App\Models\Child;
use Carbon\Carbon;
use App\Models\ParentModel;
use App\Models\Receptionist;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    public function store(StoreChildRequest $request)
    {
        $user = auth()->user();

        if ($user && $user->is_blocked) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.account_blocked')
            ], 403);
        }

        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 401);
        }

        $data = $request->validated();

        if ($currentUser instanceof ParentModel) {
            $parentId = $currentUser->id;
        } elseif ($currentUser instanceof Receptionist) {
            $validator = Validator::make($request->all(), [
                'parent_id' => 'required|integer|exists:parent_models,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => __('messages.error'),
                    'message' => __('messages.parent_id_required'),
                    'errors'  => $validator->errors()
                ], 422);
            }

            $parentId = $request->parent_id;
        } else {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized_role')
            ], 403);
        }
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->extension();

            $basePath = file_exists(base_path('../public_html'))
                ? base_path('../public_html/uploads/children')
                : public_path('uploads/children');

            // move تقوم بإنشاء المجلد تلقائياً إن لم يكن موجوداً
            $image->move($basePath, $imageName);

            $data['image'] = 'uploads/children/' . $imageName;
        } else {
            $data['image'] = null;
        }

        $child = Child::create(array_merge($data, [
            'parent_id' => $parentId
        ]));

        return response()->json([
            'message' => __('messages.child_added_successfully'),
            'child'   => $child
        ], 201);
    }

    public function update(UpdateChildRequest $request, $id)
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 401);
        }

        if ($currentUser instanceof ParentModel) {
            $child = $currentUser->children()->where('id', $id)->first();
        } elseif ($currentUser instanceof Receptionist) {
            $child = Child::find($id);

            if ($child && $request->has('parent_id')) {
                if ($child->parent_id != $request->parent_id) {
                    return response()->json([
                        'status' => __('messages.error'),
                        'message' => __('messages.child_parent_mismatch')
                    ], 422);
                }
            }
        } else {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized_role')
            ], 403);
        }

        if (!$child) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.child_not_found')
            ], 404);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $oldImagePath = $child->getRawOriginal('image');
            if ($oldImagePath && file_exists(public_path($oldImagePath))) {
                unlink(public_path($oldImagePath));
            }

            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/children'), $imageName);

            $data['image'] = 'uploads/children/' . $imageName;
        }

        $child->update($data);

        $changes = $child->getChanges();

        return response()->json([
            'message' => __('messages.child_updated_successfully'),
            'updated_fields' => $changes
        ]);
    }
    public function index()
    {
        $user = auth()->user();

        if ($user && $user->is_blocked) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.account_blocked')
            ], 403);
        }
        $children = optional($user)->children ?? collect();

        $filteredChildren = $children
            ->filter(function ($child) {
                return $child->birth_date && \Carbon\Carbon::parse($child->birth_date)->age <= 7;
            })
            ->values();

        return response()->json([
            'children' => $filteredChildren
        ]);
    }
    public function dashboardIndex(Request $request)
    {
        $currentUser = $request->user();

        if (!$currentUser || !($currentUser instanceof Receptionist)) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $children = Child::select([
            'id',
            'parent_id',
            'first_name',
            'last_name',
            'gender',
            'birth_date',
            'blood_type',
            'image'
        ])
            ->with(['parent' => function ($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->get();
        $formattedChildren = $children->map(function ($child) {
            return [
                'id'          => $child->id,
                'first_name'  => $child->first_name,
                'last_name'   => $child->last_name,
                'gender'      => $child->gender,
                'birth_date'  => $child->birth_date,
                'blood_type'  => $child->blood_type,
                'image'       => $child->image,
                'parent_name' => $child->parent
                    ? $child->parent->first_name . ' ' . $child->parent->last_name
                    : null
            ];
        });

        return response()->json([
            'status'   => 'success',
            'message' => __('messages.all_children_fetched_successfully'),
            'children' => $formattedChildren
        ]);
    }

    public function show($id)
    {
        $currentUser = request()->user();

        if (!$currentUser) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.unauthorized')
            ], 401);
        }
        if ($currentUser instanceof ParentModel) {

            $child = $currentUser->children()->find($id);
        } elseif ($currentUser instanceof Receptionist) {

            $child = Child::find($id);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.unauthorized_role')
            ], 403);
        }

        if (!$child) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.child_not_found')
            ], 404);
        }
        return response()->json([
            'status'  => 'success',
            'message' => __('messages.child_fetched_success'),
            'data'    => $child
        ], 200);
    }
    public function destroy($id)
    {
        $currentUser = request()->user();

        if (!$currentUser) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 401);
        }

        if ($currentUser instanceof ParentModel) {
            $child = $currentUser->children()->where('id', $id)->first();
        } elseif ($currentUser instanceof Receptionist) {
            $child = Child::find($id);
        } else {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized_role')
            ], 403);
        }

        if (!$child) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.child_not_found')
            ], 404);
        }

        $child->delete();

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.child_deleted_successfully')
        ]);
    }
    public function homeChildren()
    {
        $children = auth()->user()->children->map(function ($child) {
            $birthDate = Carbon::parse($child->birth_date);
            $now = Carbon::now();

            $years = (int) $birthDate->diffInYears($now);
            $months = (int) $birthDate->diffInMonths($now);
            $days = (int) $birthDate->diffInDays($now);

            if ($years >= 1) {
                $age = $years;
                $ageType = 'year'; // أو 'سنة'
            } elseif ($months >= 1) {
                $age = $months;
                $ageType = 'month'; // أو 'شهر'
            } else {
                $age = $days;
                $ageType = 'day'; // أو 'يوم'
            }

            return [
                'id'       => $child->id,
                'name'     => $child->first_name . ' ' . $child->last_name,
                'age'      => (int) $age,
                'age_type' => $ageType,
                'image'    => $child->image
            ];
        });

        return response()->json([
            'children' => $children
        ]);
    }


    /*  public function childAllergies($id)
    {
        $child = auth()->user()
            ->children()
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'child_id' => $child->id,
            'name' => $child->first_name . ' ' . $child->last_name,
            'allergies' => $child->allergies
        ]);
    }

    public function childProfile($id)
    {
        $child = auth()->user()
            ->children()
            ->with('growth')
            ->where('id', $id)
            ->firstOrFail();

        $latestGrowth = $child->growth()
            ->latest('date')
            ->first();

        return response()->json([

            'id' => $child->id,
            'name' => $child->first_name . ' ' . $child->last_name,
            'age' => Carbon::parse($child->birth_date)->age,
            'gender' => $child->gender,
            'blood_type' => $child->blood_type,
            'image' => $child->image,
            'height' => $latestGrowth?->height,
            'weight' => $latestGrowth?->weight,
        ]);
    }*/
}

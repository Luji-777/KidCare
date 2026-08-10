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
        $currentUser = $request->user();

        if (!$currentUser) {
            return response()->json([
                'status' => __('messages.error'),
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
                    'status' => __('messages.error'),
                    'message' => __('messages.parent_id_required'),
                    'errors' => $validator->errors()
                ], 422);
            }

            $parentId = $request->parent_id;
        } else {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.unauthorized_role')
            ], 403);
        }
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/children'), $imageName);
            $data['image'] = 'uploads/children/' . $imageName;
        } else {
            $data['image'] = null;
        }

        $child = Child::create(array_merge($data, [
            'parent_id' => $parentId
        ]));

        return response()->json([
            'message' => 'Child added successfully.',
            'child' => $child
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
            'message' => 'Child updated successfully.',
            'updated_fields' => $changes
        ]);
    }

    public function index(Request $request)
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
            'message'  => 'All children fetched successfully.',
            'children' => $formattedChildren
        ]);
    }

    public function show($id)
    {
        $currentUser = request()->user();

        if (!$currentUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized.'
            ], 401);
        }

        // 1. جلب الطفل بناءً على الصلاحيات
        if ($currentUser instanceof ParentModel) {
            // الأب يبحث في أطفاله فقط
            $child = $currentUser->children()->find($id);
        } elseif ($currentUser instanceof Receptionist) {
            // الرسبشن يبحث في كل الأطفال
            $child = Child::find($id);
        } else {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized role.'
            ], 403);
        }

        // 2. التحقق من وجود الطفل
        if (!$child) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.child_not_found')
            ], 404);
        }
        return response()->json([
            'status'  => 'success',
            'message' => 'Child fetched successfully.',
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
            'message' => 'Child deleted successfully.'
        ]);
    }
    public function homeChildren()
    {
        $children = auth()->user()->children->map(function ($child) {
            return [
                'id' => $child->id,
                'name' => $child->first_name . ' ' . $child->last_name,
                'age' => Carbon::parse($child->birth_date)->age,
                'image' => $child->image
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

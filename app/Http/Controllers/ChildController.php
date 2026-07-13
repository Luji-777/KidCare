<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreChildRequest;
use App\Http\Requests\UpdateChildRequest;
use App\Models\Child;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    public function store(StoreChildRequest $request)
    {

        $data = $request->validated();


        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            $image->move(public_path('uploads/children'), $imageName);


            $data['image'] = 'uploads/children/' . $imageName;
        } else {

            $data['image'] = null;
        }


        $child = Child::create([
            'parent_id' => auth()->user()->id,
            ...$data
        ]);

        return response()->json([
            'message' => __('messages.child_added_success'),
            'child' => $child
        ], 201);
    }

    public function update(UpdateChildRequest $request, $id)
    {
        $child = auth()->user()->children()->where('id', $id)->firstOrFail();

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
            'message' => __('messages.child_updated_success'),
            'updated_fields' => $changes
        ]);
    }

    public function index()
    {
        $children = auth()->user()->children;
        return response()->json([
            'status'   => 'success',
            'message'  => __('messages.children_fetched_success'),
            'children' => $children
        ]);
    }

    public function show($id)
    {

        $child = auth()->user()->children()->find($id);

        if (!$child) {
            return response()->json([
                'status'  => 'error',
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
        $child = auth()->user()->children()->where('id', $id)->firstOrFail();
        $child->delete();

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.child_deleted_success')
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

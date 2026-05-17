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
            'message' => 'Child added successfully',
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

        return response()->json([
            'message' => 'Child info updated successfully',
            'child' => $child
        ]);
    }

    public function index()
    {
        $children = auth()->user()->children;
        return response()->json([
            'children' => $children
        ]);
    }

    public function show($id)
    {
        $child = Child::find($id);

        if (!$child) {
            return response()->json([
                'message' => 'not found'
            ], 404);
        }

        return response()->json($child);
    }

    public function destroy($id)
    {
        $child = auth()->user()->children()->where('id', $id)->firstOrFail();
        $child->delete();

        return response()->json([
            'message' => 'Child deleted successfully'
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
}

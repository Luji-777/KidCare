<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreChildRequest;
use App\Http\Requests\UpdateChildRequest;
use App\Models\Child;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    public function store(StoreChildRequest $request){
       $child=Child::create([
       'parent_id'=>Auth::guard()->user()->id,
        ...$request->validated() ]);

        return response()->json([
            'message'=>'Child added successfully',
            'child'=>$child
        ],201);
    }

    public function update(UpdateChildRequest $request,$id){

        $child=auth()->user()->children()->where('id',$id)->firstOrFail();
        $child->update($request->validated());

        return response()->json([
            'message'=>'Child info updated successfully',
            'child'=>$child
        ]);
    }

    public function index(){
        $children=auth()->user()->children;
        return response()->json([
        'children' => $children
        ]);
    }

    public function show($id){
        $child=auth()->user()->children()->where('id',$id)->firstOrFail();

        return response()->json([
        'child' => $child
        ]);
    }

    public function destroy($id){
        $child=auth()->user()->children()->where('id',$id)->firstOrFail();
        $child->delete();

        return response()->json([
            'message'=>'Child deleted successfully'
        ]);
    }
}

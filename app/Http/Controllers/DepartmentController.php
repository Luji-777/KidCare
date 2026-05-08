<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index(){
        $departments=Department::select('id','name','description')->get();

        return response()->json($departments);
      
        }

    public function doctors($id){
        $department=Department::with('doctors')->findOrFail($id);

            return response()->json($department->doctors);
            
        }
}

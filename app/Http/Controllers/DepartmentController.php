<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::select('id', 'name', 'description')->get();

        return response()->json($departments);
    }

    public function doctors($id)
    {
        $doctors = \App\Models\Doctor::where('department_id', $id)
            ->select('id', 'department_id', 'first_name', 'last_name', 'email', 'address')
            ->get();

        return response()->json($doctors);
    }
}

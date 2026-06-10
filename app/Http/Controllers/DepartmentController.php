<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::select('id', 'name', 'description')->get();

        return response()->json([
            'status'      => 'success',
            'message'     => __('messages.departments_fetched_success'),
            'departments' => $departments
        ], 200);
    }

    public function doctors($id)
    {
        $departmentExists = Department::where('id', $id)->exists();

        if (!$departmentExists) {
            return response()->json([
                'status'  => 'error',
                'message' => __('messages.department_not_found')
            ], 404);
        }

        $doctors = \App\Models\Doctor::where('department_id', $id)
            ->select('id', 'department_id', 'first_name', 'last_name', 'email', 'address')
            ->get();

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.department_doctors_fetched_success'),
            'doctors' => $doctors
        ], 200);
    }
}

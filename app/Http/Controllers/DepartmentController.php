<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::select('id', 'name', 'description')->get();

        $formattedDepartments = $departments->map(function ($dept) {
            return [
                'id'          => $dept->id,
                'name'        => __("messages.departments_names.{$dept->name}"),
            ];
        });
        return response()->json([
            'status'      => 'success',
            'message'     => __('messages.departments_fetched_success'),
            'departments' => $formattedDepartments
        ], 200);
    }

    public function doctors($id)
    {
        $department = Department::select('id', 'name')->find($id);

        if (!$department) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.department_not_found')
            ], 404);
        }

        $doctors = \App\Models\Doctor::where('department_id', $id)
            ->select('id', 'department_id', 'first_name', 'last_name', 'email', 'address', 'gender', 'profile_picture')
            ->get();

        return response()->json([
            'status'          => 'success',
            'message'         => __('messages.department_doctors_fetched_success'),
            'department_name' => __("messages.departments_names.{$department->name}"),
            'doctors'         => $doctors
        ], 200);
    }
}

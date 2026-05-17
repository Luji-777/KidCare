<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;

class DoctorController extends Controller
{

    public function index()
    {
        // الترتيب الأبجدي حسب الاسم الأول ثم جلب 10 بكل صفحة
    $doctors = Doctor::with('department')
        ->orderBy('first_name', 'asc')
        ->paginate(10);

    return response()->json([
        'status' => 'success',
        'data'   => $doctors
    ], 200);
    }

    public function store(StoreDoctorRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
        $data['profile_picture'] = $request->file('profile_picture')->store('doctors/photos', 'public');
        }

        if ($request->hasFile('cv')) {
        $data['cv'] = $request->file('cv')->store('doctors/cvs', 'public');
        }

        $doctor = Doctor::create($data);

        return response()->json([
        'status' => 'success',
        'message' => 'Doctor profile created successfully',
        'data' => $doctor
            ], 201);
    }


    public function show(string $id)
    {
        $doctor = Doctor::with('department')->find($id);

    if (!$doctor) {
        return response()->json([
            'status' => 'error',
            'message' => 'Doctor not found.'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'data'   => $doctor
    ], 200);
    }


    public function update(UpdateDoctorRequest $request, string $id)
    {

        {
         $doctor = Doctor::findOrFail($id);
         $data = $request->validated();

    if ($request->hasFile('profile_picture')) {
        $data['profile_picture'] = $request->file('profile_picture')->store('doctors/profiles', 'public');
    }

    if ($request->hasFile('cv')) {
        $data['cv'] = $request->file('cv')->store('doctors/cvs', 'public');
    }

    $doctor->update($data);

    return response()->json([
        'status'  => 'success',
        'message' => 'Doctor profile updated successfully.',
        'data'    => $doctor
    ], 200);
    }}


    public function destroy(string $id)
    {
        $doctor = Doctor::find($id);

      if (!$doctor) {
        return response()->json([
            'status' => 'error',
            'message' => 'Doctor not found.'
        ], 404);
            }

    if ($doctor->profile_picture) {
         Storage::disk('public')->delete($doctor->profile_picture);
    }

    if ($doctor->cv) {
        Storage::disk('public')->delete($doctor->cv);
    }

    $doctor->delete();

    return response()->json([
        'status' => 'success',
        'message' => 'Doctor and their related files have been deleted successfully.'
    ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;
use App\Http\Requests\StoreDoctorRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Doctor;

class DoctorController extends Controller
{

    public function index()
    {
        //
    }

    public function store(StoreDoctorRequest $request){

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
        //
    }


    public function update(Request $request, string $id)
    {
        //
    }


    public function destroy(string $id)
    {
        //
    }
}

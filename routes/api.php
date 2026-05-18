<?php

use App\Http\Controllers\ParentModelController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [ParentModelController::class, 'register']);
Route::post('/verifyOtp', [ParentModelController::class, 'verifyOtp']);
Route::post('/sendOtp', [ParentModelController::class, 'sendOtp']);
Route::post('/login', [ParentModelController::class, 'login']);
Route::post('/SetPassword', [ParentModelController::class, 'SetPassword']);
Route::post('/logout', [ParentModelController::class, 'logout'])->middleware('auth:sanctum');
Route::get('profile', [ParentModelController::class, 'showProfile'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {

Route::get('children', [ChildController::class, 'index']);
Route::post('children', [ChildController::class, 'store']);
Route::get('children/{id}', [ChildController::class, 'show']);
Route::put('children/{id}', [ChildController::class, 'update']);
Route::delete('children/{id}', [ChildController::class, 'destroy']);
Route::get('/home-children', [ChildController::class, 'homeChildren']);


Route::get('departments', [DepartmentController::class, 'index']);
Route::get('departments/{id}/doctors', [DepartmentController::class, 'doctors']);

Route::get('doctors/{id}/available-times',[DoctorAvailabilityController::class, 'availableTimes']);
Route::post('/doctor-availabilities',[DoctorAvailabilityController::class, 'store']);
Route::get('/doctors/{id}/availabilities',[DoctorAvailabilityController::class, 'index']);
});

Route::apiResource('doctors', DoctorController::class);


//Route::post('/loginDoctor', [DoctorController::class, 'loginDoctor']);
//Route::post('/sendOtpDoctor', [DoctorController::class, 'sendOtpDoctor']);
//Route::post('/verifyOtpAndSetPasswordDoctor', [DoctorController::class, 'verifyOtpAndSetPasswordDoctor']);

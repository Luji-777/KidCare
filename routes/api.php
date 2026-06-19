<?php

use App\Http\Controllers\ParentModelController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GrowthController;
use App\Http\Controllers\VaccineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('set.locale')->group(function () {

    Route::post('/register', [ParentModelController::class, 'register']);
    Route::post('/verifyOtp', [ParentModelController::class, 'verifyOtp']);
    Route::post('/sendOtp', [ParentModelController::class, 'sendOtp']);
    Route::post('/login', [ParentModelController::class, 'login']);
    Route::post('/SetPassword', [ParentModelController::class, 'SetPassword']);
    Route::post('/logout', [ParentModelController::class, 'logout'])->middleware('auth:sanctum');


    Route::post('/loginDoctor', [DoctorController::class, 'loginDoctor']);
    Route::post('/sendOtpDoctor', [DoctorController::class, 'sendOtpDoctor']);
    Route::post('/verifyOtpDoctor', [DoctorController::class, 'verifyOtpDoctor']);
    Route::post('/SetPasswordDoctor', [DoctorController::class, 'setPasswordDoctor']);
});


Route::apiResource('doctors', DoctorController::class);


Route::post('/loginDoctor', [DoctorController::class, 'loginDoctor']);
Route::post('/sendOtpDoctor', [DoctorController::class, 'sendOtpDoctor']);
Route::post('/verifyOtpDoctor', [DoctorController::class, 'verifyOtpDoctor']);
Route::post('/SetPasswordDoctor', [DoctorController::class, 'setPasswordDoctor']);
Route::post('/loginAdmin', [AdminController::class, 'loginAdmin']);


Route::get(
    '/favorite-doctors',
    [DoctorController::class, 'getFavorites']
);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/children', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
    Route::get('/children/{id}', [ChildController::class, 'show']);
    Route::put('/children/{id}', [ChildController::class, 'update']);
    Route::delete('/children/{id}', [ChildController::class, 'destroy']);
    Route::get('/home-children', [ChildController::class, 'homeChildren']);

    // Route::get('/childProfile/{id}', [ChildController::class, 'childProfile']);
    // Route::get('/childAllergies/{id}', [ChildController::class, 'childAllergies']);

    Route::get('/children/{child_id}/growth', [GrowthController::class, 'index']);
    Route::post('/growth', [GrowthController::class, 'store']);
    Route::delete('/growth/{id}', [GrowthController::class, 'destroy']);

    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{id}/doctors', [DepartmentController::class, 'doctors']);

    Route::post('/doctors/{id}/available-times', [DoctorAvailabilityController::class, 'availableTimes']);
    Route::post('/doctor-availabilities', [DoctorAvailabilityController::class, 'availability']);
    Route::get('/doctors/{id}/availabilities', [DoctorAvailabilityController::class, 'index']);

    Route::post('/doctors/{doctorId}/favorite', [DoctorController::class, 'toggleFavorite']);
    Route::get('/favorite-doctors', [DoctorController::class, 'getFavorites']);
    Route::get('parentProfile', [ParentModelController::class, 'showProfile']);
    Route::get('parentName', [ParentModelController::class, 'parentName']);
    Route::post('/parent/save-fcm-token', [ParentModelController::class, 'saveFcmToken']);
    Route::put('updateparentProfile', [ParentModelController::class, 'updateProfile']);

    Route::get('/doctor/home', [DoctorController::class, 'home']); //home page
    Route::get('/doctor/today-appointments-count', [DoctorController::class, 'todayAppointmentsCount']); //home page
    Route::get('/doctor/next-patient', [DoctorController::class, 'nextPatient']); //home page
    Route::get('/doctor/remaining-patients', [DoctorController::class, 'remainingPatients']); //home page
    Route::get('/doctor/completed-appointments-today', [DoctorController::class, 'completedAppointmentsToday']); //home page
    Route::get('/doctor/monthlyRevenue', [DoctorController::class, 'monthlyRevenue']); //home page
    Route::get('/doctors/{id}/completeAppointment', [DoctorController::class, 'completeAppointment']);


    Route::post('/appointment', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/past', [AppointmentController::class, 'past']);
    Route::get('/appointments/upcoming/{childId}', [AppointmentController::class, 'upcomingByChild']);
    Route::get('/appointments/past/{childId}', [AppointmentController::class, 'pastByChild']);
    Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);
    Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{appointment}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy']);

    Route::get('departments/{department_id}/closest-appointments', [AppointmentController::class, 'getClosestAppointmentPerDoctor']);

    Route::post('/test-appointment', [PaymentController::class, 'testAppointment']);
    Route::get('/appointments/{appointment_id}/summary', [PaymentController::class, 'getSummary']);
    Route::post('/payment/checkout', [PaymentController::class, 'checkout']);
    Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);
    Route::get('/test-fcm', [PaymentController::class, 'testFcm']);


    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/test-notification', [NotificationController::class, 'test']);

    //  Route::get('/child/{id}/vaccines', [VaccineController::class, 'getChildVaccines']);
});

//Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);

Route::apiResource('doctors', DoctorController::class);

Route::post('/loginDoctor', [DoctorController::class, 'loginDoctor']);
Route::post('/sendOtpDoctor', [DoctorController::class, 'sendOtpDoctor']);
Route::post('/verifyOtpDoctor', [DoctorController::class, 'verifyOtpDoctor']);
Route::post('/SetPasswordDoctor', [DoctorController::class, 'setPasswordDoctor']);

Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);

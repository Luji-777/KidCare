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


Route::middleware('set.locale')->group(function () {

    // Auth parent
    Route::post('/register', [ParentModelController::class, 'register']);
    Route::post('/sendOtp', [ParentModelController::class, 'sendOtp']);
    Route::post('/verifyOtp', [ParentModelController::class, 'verifyOtp']);
    Route::post('/login', [ParentModelController::class, 'login']);
    Route::post('/SetPassword', [ParentModelController::class, 'SetPassword']);

    // Auth doctor
    Route::post('/loginDoctor', [DoctorController::class, 'loginDoctor']);
    Route::post('/sendOtpDoctor', [DoctorController::class, 'sendOtpDoctor']);
    Route::post('/verifyOtpDoctor', [DoctorController::class, 'verifyOtpDoctor']);
    Route::post('/SetPasswordDoctor', [DoctorController::class, 'setPasswordDoctor']);

    //Auth dashboard
    Route::post('/loginAdmin', [AdminController::class, 'loginAdmin']);

    // Open
    Route::apiResource('doctors', DoctorController::class)->only(['index', 'show']);
    Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);

    //Sanctum
    Route::middleware('auth:sanctum')->group(function () {


        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [ParentModelController::class, 'logout']);

        // Parent
        Route::get('parentProfile', [ParentModelController::class, 'showProfile']);
        Route::put('updateparentProfile', [ParentModelController::class, 'updateProfile']);
        Route::get('parentName', [ParentModelController::class, 'parentName']);
        Route::post('/parent/save-fcm-token', [ParentModelController::class, 'saveFcmToken']);

        // Children
        Route::get('/home-children', [ChildController::class, 'homeChildren']);
        Route::apiResource('children', ChildController::class);

        // Growth
        Route::get('/children/{child_id}/growth', [GrowthController::class, 'index']);
        Route::post('/growth', [GrowthController::class, 'store']);
        Route::delete('/growth/{id}', [GrowthController::class, 'destroy']);

        // Vaccines & Departments

        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/departments/{id}/doctors', [DepartmentController::class, 'doctors']);
        //Route::get('/child/{id}/vaccines', [VaccineController::class, 'getChildVaccines']);


        // Favorites & doctor availability
        Route::post('/doctors/{id}/available-times', [DoctorAvailabilityController::class, 'availableTimes']);
        Route::post('/doctor-availabilities', [DoctorAvailabilityController::class, 'availability']);
        Route::get('/doctors/{id}/availabilities', [DoctorAvailabilityController::class, 'index']);
        Route::post('/doctors/{doctorId}/favorite', [DoctorController::class, 'toggleFavorite']);
        Route::get('/favorite-doctors', [DoctorController::class, 'getFavorites']);

        // Appointments
        Route::get('/appointments/past', [AppointmentController::class, 'past']);
        Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);
        Route::get('/appointments/past/{childId}', [AppointmentController::class, 'pastByChild']);
        Route::get('/appointments/upcoming/{childId}', [AppointmentController::class, 'upcomingByChild']);
        Route::get('departments/{department_id}/closest-appointments', [AppointmentController::class, 'getClosestAppointmentPerDoctor']);
        Route::apiResource('appointment', AppointmentController::class)->except(['index']);

        // Doctor app
        Route::prefix('doctor')->group(function () {
            Route::get('/home', [DoctorController::class, 'home']);
            Route::get('/today-appointments-count', [DoctorController::class, 'todayAppointmentsCount']);
            Route::get('/next-patient', [DoctorController::class, 'nextPatient']);
            Route::get('/remaining-patients', [DoctorController::class, 'remainingPatients']);
            Route::get('/completed-appointments-today', [DoctorController::class, 'completedAppointmentsToday']);
            Route::get('/monthlyRevenue', [DoctorController::class, 'monthlyRevenue']);

            Route::post('/{appointmentId}/diagnosis',[DoctorController::class, 'addDiagnosis']);
            Route::post('/{recordId}/medications',[DoctorController::class, 'addMedication']);
            Route::post('/{appointmentId}/growth',[DoctorController::class, 'addGrowthRecord']);

            Route::get('/upcomingWorkingDays',[DoctorController::class, 'upcomingWorkingDays']);
            Route::get('/appointmentsByDate',[DoctorController::class, 'appointmentsByDate']);
            Route::get('patients', [DoctorController::class, 'Allpatients']);


            Route::get('/appointments/{appointment}', [AppointmentController::class, 'appointmentDetails']);
        });
        Route::get('/doctors/{id}/completeAppointment', [DoctorController::class, 'completeAppointment']);

        // Payments & Notifications
        Route::post('/payment/checkout', [PaymentController::class, 'checkout']);
        Route::get('/appointments/{appointment_id}/summary', [PaymentController::class, 'getSummary']);
        Route::post('/test-appointment', [PaymentController::class, 'testAppointment']);
        Route::get('/test-fcm', [PaymentController::class, 'testFcm']);
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/test-notification', [NotificationController::class, 'test']);
    });
});

<?php

use App\Http\Controllers\ParentModelController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AppointmentAdditionsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DoctorNotificationController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\GrowthController;
use App\Http\Controllers\ReceptionistController;
use App\Http\Controllers\VaccineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('set.locale')->group(function () {

    \Log::info('Incoming Request:', ['path' => request()->path(), 'method' => request()->method(), 'token' => request()->bearerToken()]);
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
    Route::post('/SetAdminPassword', [AdminController::class, 'SetAdminPassword']);
    Route::post('/loginReceptionist', [ReceptionistController::class, 'loginReceptionist']);
    Route::post('/SetReceptionistPassword', [ReceptionistController::class, 'SetReceptionistPassword']);

    //Sanctum
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('/logout', [ParentModelController::class, 'logout']);
        Route::post('/logoutReception', [ReceptionistController::class, 'logout']);


        // Parent
        Route::get('parentProfile', [ParentModelController::class, 'showProfile']);
        Route::put('updateparentProfile', [ParentModelController::class, 'updateProfile']);
        Route::get('parentName', [ParentModelController::class, 'parentName']);
        Route::post('/parent/save-fcm-token', [ParentModelController::class, 'saveFcmToken']);
        Route::delete('parent/account/terminate', [ParentModelController::class, 'destroyAccount']);


        // Children
        Route::get('/home-children', [ChildController::class, 'homeChildren']);
        Route::apiResource('children', ChildController::class);

        // Growth
        Route::get('/children/{child_id}/growth', [GrowthController::class, 'index']);
        Route::post('/growth', [GrowthController::class, 'store']);
        Route::delete('/growth/{id}', [GrowthController::class, 'destroy']);

        // Departments

        Route::get('/departments', [DepartmentController::class, 'index']);
        Route::get('/departments/{id}/doctors', [DepartmentController::class, 'doctors']);


        // Favorites & doctor availability
        Route::post('/doctors/{id}/available-times', [DoctorAvailabilityController::class, 'availableTimes']);
        Route::post('/doctor-availabilities', [DoctorAvailabilityController::class, 'availability']);
        Route::delete('/doctor/availability/{id}', [DoctorAvailabilityController::class, 'deleteAvailability']);
        Route::get('/doctors/{id}/availabilities', [DoctorAvailabilityController::class, 'index']);
        Route::get('/doctors/availableWorkingPeriods', [DoctorAvailabilityController::class, 'availableWorkingPeriods']);
        Route::post('/doctors/{doctorId}/favorite', [DoctorController::class, 'toggleFavorite']);
        Route::get('/favorite-doctors', [DoctorController::class, 'getFavorites']);

        // Appointments
        Route::get('/appointments/past', [AppointmentController::class, 'past']);
        Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);
        Route::get('/appointments/cancelled', [AppointmentController::class, 'cancelledAppointment']);
        Route::get('/appointments/past/{childId}', [AppointmentController::class, 'pastByChild']);
        Route::get('/appointments/upcoming/{childId}', [AppointmentController::class, 'upcomingByChild']);
        Route::get('departments/{department_id}/closest-appointments', [AppointmentController::class, 'getClosestAppointmentPerDoctor']);
        Route::apiResource('appointments', AppointmentController::class)->except(['index']);

        Route::get('/prescription/{recordId}', [MedicationController::class, 'showPrescription']);
        Route::get('/medical-record/{appointmentId}', [MedicalRecordController::class, 'showMedicalRecord']);


        // Doctor app
        Route::prefix('doctor')->group(function () {
            Route::get('/home', [DoctorController::class, 'home']);
            Route::get('/today-appointments-count', [DoctorController::class, 'todayAppointmentsCount']);
            Route::get('/next-patient', [DoctorController::class, 'nextPatient']);
            Route::get('/remaining-patients', [DoctorController::class, 'remainingPatients']);
            Route::get('/completed-appointments-today', [DoctorController::class, 'completedAppointmentsToday']);
            Route::get('/monthlyRevenue', [DoctorController::class, 'monthlyRevenue']);
            Route::delete('/account/terminate', [DoctorController::class, 'destroyAccount']);
            Route::put('appointments/cancelAppointments', [DoctorController::class, 'cancelAppointmentsByDate']);
            Route::put('appointments/{appointmentId}/cancel', [DoctorController::class, 'cancelAppointment']);
            Route::get('/patient-visits/{childId}', [DoctorController::class, 'patientVisitsSummary']);

            Route::get('/notification', [DoctorNotificationController::class, 'getDoctorNotifications']);


            Route::post('/{appointmentId}/diagnosis', [DoctorController::class, 'addDiagnosis']);
            Route::post('/{recordId}/medications', [DoctorController::class, 'addMedication']);
            Route::post('/{appointmentId}/growth', [DoctorController::class, 'addGrowthRecord']);

            Route::get('/profile', [DoctorController::class, 'showProfile']);
            Route::put('/updateProfile', [DoctorController::class, 'updateProfile']);

            Route::post('/{appointment}/medicalRequests', [AppointmentController::class, 'addMedicalRequests']);
            Route::post('/{appointment}/additions', [AppointmentAdditionsController::class, 'store']);
            Route::delete('/{additionId}/additions', [AppointmentAdditionsController::class, 'destroy']);



            Route::get('/upcomingWorkingDays', [DoctorController::class, 'upcomingWorkingDays']);
            Route::get('/appointmentsByDate', [DoctorController::class, 'appointmentsByDate']);

            Route::get('/patients', [DoctorController::class, 'Allpatients']);

            Route::get('/income', [DoctorController::class, 'monthlyIncome']);
            Route::get('/yearlyIncome', [DoctorController::class, 'yearlyIncome']);

            Route::get('/{childId}/medicalRecord', [MedicalRecordController::class, 'medicalRecord']);


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

        //Doctor dashboard
        Route::get('/doctors/count', [DoctorController::class, 'getDoctorsCount']);
        Route::get('/doctors/count/department/{department_id}', [DoctorController::class, 'getDoctorsCountByDepartment']);
        Route::get('/doctors/top-this-week', [DoctorController::class, 'getTopDoctorThisWeek']);
        Route::get('/doctors/active-count', [DoctorController::class, 'getActiveDoctorsCountThisWeek']);
        Route::apiResource('doctors', DoctorController::class)->only(['index', 'show', 'store', 'destroy']);
        Route::put('doctors/{doctor}/update', [DoctorController::class, 'update']);

        // Home dashboard
        Route::get('/home/patients-count', [AdminController::class, 'getPatientsCount']);
        Route::get('/home/present-doctors-count', [AdminController::class, 'getPresentDoctorsCount']);
        Route::get('/home/appointments-count', [AdminController::class, 'getAppointmentsCount']);
        Route::get('/home/clinic-occupancy', [AdminController::class, 'getDailyClinicOccupancy']);
        Route::get('/monthly-revenue', [AdminController::class, 'getMonthlyRevenueReport']);
        Route::get('/daily-revenue', [AdminController::class, 'getDailyRevenueReport']);
        Route::get('/home/top-department', [AdminController::class, 'getTopDepartmentThisWeek']);
        Route::put('/admin/receptionists/{receptionist}/change-password', [AdminController::class, 'changeReceptionistPassword']);
        Route::post('/admin/logout', [AdminController::class, 'logoutAdmin']);

        // Departments dashboard
        Route::get('/departments/daily-report', [AdminController::class, 'getDepartmentsDashboardReport']);

        // Reports
        Route::get('/reports/children-age-distribution', [AdminController::class, 'getChildrenAgeDistribution']);
        Route::get('/reports/{id}/weekly-stats', [DoctorController::class, 'getDoctorWeeklyStats']);
        Route::get('/reports/appointments-per-weekday', [AdminController::class, 'getAppointmentsCountPerDayOfWeek']);
        Route::get('/reports/top-three-departments-share', [AdminController::class, 'getTopThreeDepartmentsShare']);
        Route::get('/reports/weekly-summary', [AdminController::class, 'getWeeklyClinicSummary']);
        Route::get('/reports/monthly-budget', [AdminController::class, 'getMonthlyBudgetReport']);

        //reseption
        Route::post('/appointments/{appointment_id}/complete-payment', [PaymentController::class, 'completePayment']);
        Route::get('/appointments/{appointment_id}/payment-summary-reception', [PaymentController::class, 'getSummaryForReception']);
        Route::get('/home/today-children-count', [ReceptionistController::class, 'getTodayAddedChildrenCount']);
        Route::get('/dashboard/children', [ChildController::class, 'dashboardIndex']);
        Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']);


        // Vaccine
        Route::post('/reception/vaccine-schedules', [VaccineController::class, 'createSchedule']);
        Route::put('/reception/vaccine-schedules/{schedule}/status', [VaccineController::class, 'updateScheduleStatus']);
        Route::post('/reception/child-vaccinations', [VaccineController::class, 'recordChildVaccination']);
        Route::post('/reception/vaccines', [VaccineController::class, 'storeVaccine']);
        Route::get('/vaccines', [VaccineController::class, 'getAllVaccines']);

        Route::get('/vaccines/available-schedules', [VaccineController::class, 'getAvailableSchedules']);
        Route::get('/vaccines/child-history/{childId}', [VaccineController::class, 'getChildVaccinationHistory']);

        Route::post('reception/parents/add', [ReceptionistController::class, 'addParent']);
        Route::post('/reception/appointments', [ReceptionistController::class, 'store']);
        Route::put('/reception/appointments/{appointment}', [ReceptionistController::class, 'updateReception']);
        Route::get('/reception/appointments', [ReceptionistController::class, 'indexReception']);
        Route::delete('/reception/appointments/{appointment}', [ReceptionistController::class, 'destroy']);
        Route::get('/appointments/doctor/{doctor}/past', [ReceptionistController::class, 'pastByDoctor']);
        Route::get('/appointments/doctor/{doctor}/upcoming', [ReceptionistController::class, 'upcomingByDoctor']);
        Route::get('/reception/appointments/date/{date}', [ReceptionistController::class, 'getByDateForReception']);
        Route::get('/reception/children', [ChildController::class, 'dashboardIndex']);
        Route::post('/reception/parents/{parent}/block', [ReceptionistController::class, 'blockParent']);
        Route::post('/reception/{parent}/revoke-tokens', [ReceptionistController::class, 'revokeTokens']);
        Route::post('/reception/parents/{parent}/hard-delete', [ReceptionistController::class, 'hardDelete']);
        Route::get('/parents/profiles', [ReceptionistController::class, 'showAllProfiles']);
    });

    // Open routes

    Route::post('/stripe/webhook', [PaymentController::class, 'handleWebhook']);
});

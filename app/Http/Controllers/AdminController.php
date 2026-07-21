<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Child;
use App\Models\Doctor;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Models\Transaction;
use App\Models\Department;
use App\Models\DoctorAvailability;
use App\Models\Receptionist;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function loginAdmin(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|digits_between:9,15',
            'password'     => 'required|string|min:6|max:255'
        ]);

        $admin = Admin::where('phone_number', $request->phone_number)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'status' => __('messages.error'),
                'message' => __('messages.invalid_credentials')
            ], 401);
        }

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => __('messages.login_welcome_back'),
            'user'    => [
                'id'           => $admin->id,
                'phone_number' => $admin->phone_number,
                'name'   => $admin->name,
            ],
            'Token'   => $token,
        ], 200);
    }
    public function SetAdminPassword(Request $request)
    {

        $request->validate([
            'phone_number' => 'required',
            'password'     => 'required|string|min:6|max:255|confirmed',
        ]);


        $admin = Admin::where('phone_number', $request->phone_number)->first();

        if (!$admin) {
            return response()->json(
                [
                    'status' => __('messages.error'),
                    'message' =>  __('messages.user_not_found'),
                ],
                404
            );
        }

        $admin->update([
            'password'       => Hash::make($request->password)
        ]);

        $token = $admin->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' =>  __('messages.password_updated_success'),
            'token'   => $token
        ], 200);
    }

    //----------------Home------------------
    public function getPatientsCount()
    {
        $count = Child::count();

        return response()->json([
            'status' => 'success',
            'patients_count' => $count
        ], 200);
    }

    public function getPresentDoctorsCount()
    {
        $now = Carbon::now();
        $todayName = $now->locale('en')->dayName;
        $currentTime = $now->format('H:i:s');

        $presentDoctorsCount = Doctor::whereHas('availabilities', function ($query) use ($todayName, $currentTime) {
            $query->where('day_of_week', $todayName)
                ->where('start_time', '<=', $currentTime)
                ->where('end_time', '>=', $currentTime);
        })->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'present_doctors_count' => $presentDoctorsCount,
                'checked_at' => [
                    'day'  => $todayName,
                    'time' => $now->format('g:i A')
                ]
            ]
        ], 200);
    }

    public function getAppointmentsCount()
    {
        $todayDate = Carbon::now()->format('Y-m-d');

        $todayAppointmentsCount = Appointment::where('date', $todayDate)
            ->whereIn('status', ['confirmed', 'completed'])
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'today_appointments_count' => $todayAppointmentsCount,
                'date'                     => $todayDate
            ]
        ], 200);
    }

    public function getMonthlyRevenueReport()
    {
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d H:i:s');
        $endOfMonth   = Carbon::now()->endOfMonth()->format('Y-m-d H:i:s');
        $monthlyTransactions = Transaction::where('status', 'succeeded')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $totalRevenue    = $monthlyTransactions->sum('amount');
        $stripeRevenue   = $monthlyTransactions->where('payment_method', 'stripe')->sum('amount');
        $cashRevenue     = $monthlyTransactions->where('payment_method', 'cash')->sum('amount');
        $fixedTypeTotal  = $monthlyTransactions->where('type', 'fixed')->sum('amount');
        $additionsTotal  = $monthlyTransactions->where('type', 'additions')->sum('amount');

        $doctorsCommission = DB::table('transactions')
            ->join('appointments', 'transactions.appointment_id', '=', 'appointments.id')
            ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->where('transactions.status', 'succeeded')
            ->where('transactions.type', 'fixed')
            ->whereBetween('transactions.created_at', [$startOfMonth, $endOfMonth])
            ->sum(DB::raw('transactions.amount * (doctors.commission_percentage / 100)'));

        $clinicNetProfit = $fixedTypeTotal - $doctorsCommission;

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => Carbon::now()->format('F Y'),
                'financials' => [
                    'total_revenue'         => round($totalRevenue, 2),
                    'clinic_net_profit'     => round($clinicNetProfit, 2),
                    'clinic_additions_profit'   => round($additionsTotal, 2),
                    'doctors_total_payout'  => round($doctorsCommission, 2),
                ],
                'breakdown_by_method' => [
                    'online_stripe' => round($stripeRevenue, 2),
                    'cash_reception' => round($cashRevenue, 2),
                ],
                'breakdown_by_type' => [
                    'fixed_appointments' => round($fixedTypeTotal, 2),
                    'additions_total'    => round($additionsTotal, 2),
                ]
            ]
        ], 200);
    }

    public function getDailyRevenueReport()
    {
        $startOfToday = Carbon::today()->startOfDay()->format('Y-m-d H:i:s');
        $endOfToday   = Carbon::today()->endOfDay()->format('Y-m-d H:i:s');


        $todayTransactions = Transaction::where('status', 'succeeded')
            ->whereBetween('created_at', [$startOfToday, $endOfToday])
            ->get();

        $totalRevenue    = $todayTransactions->sum('amount');
        $stripeRevenue   = $todayTransactions->where('payment_method', 'stripe')->sum('amount');
        $cashRevenue     = $todayTransactions->where('payment_method', 'cash')->sum('amount');
        $fixedTypeTotal  = $todayTransactions->where('type', 'fixed')->sum('amount');
        $additionsTotal  = $todayTransactions->where('type', 'additions')->sum('amount');

        $doctorsCommission = DB::table('transactions')
            ->join('appointments', 'transactions.appointment_id', '=', 'appointments.id')
            ->join('doctors', 'appointments.doctor_id', '=', 'doctors.id')
            ->where('transactions.status', 'succeeded')
            ->where('transactions.type', 'fixed')
            ->whereBetween('transactions.created_at', [$startOfToday, $endOfToday])
            ->sum(DB::raw('transactions.amount * (doctors.commission_percentage / 100)'));

        $clinicNetProfit = $fixedTypeTotal - $doctorsCommission;

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => Carbon::today()->format('Y-m-d'),
                'financials' => [
                    'total_revenue'         => round($totalRevenue, 2),
                    'clinic_net_profit'     => round($clinicNetProfit, 2),
                    'clinic_additions_profit'   => round($additionsTotal, 2),
                    'doctors_total_payout'  => round($doctorsCommission, 2),
                ],
                'breakdown_by_method' => [
                    'online_stripe' => round($stripeRevenue, 2),
                    'cash_reception' => round($cashRevenue, 2),
                ],
                'breakdown_by_type' => [
                    'fixed_appointments' => round($fixedTypeTotal, 2),
                    'additions_total'    => round($additionsTotal, 2),
                ]
            ]
        ], 200);
    }
    public function getDailyClinicOccupancy()
    {
        $today = Carbon::today();
        $startOfToday = $today->copy()->startOfDay()->format('Y-m-d H:i:s');
        $endOfToday   = $today->copy()->endOfDay()->format('Y-m-d H:i:s');
        $dayOfWeek    = $today->format('l');

        $excludedStatuses = ['cancelled', 'pending'];
        $totalTodayAppointments = Appointment::whereBetween('date', [$startOfToday, $endOfToday])
            ->whereNotIn('status', $excludedStatuses)
            ->count();

        $appointmentDurationMinutes = 30;
        $availabilities = DoctorAvailability::where('day_of_week', $dayOfWeek)->get();

        $maxClinicCapacity = 0;

        foreach ($availabilities as $availability) {

            $startTime = Carbon::parse($availability->start_time);
            $endTime   = Carbon::parse($availability->end_time);
            $totalMinutesInSlot = $startTime->diffInMinutes($endTime);

            if ($appointmentDurationMinutes > 0) {
                $slotsCount = floor($totalMinutesInSlot / $appointmentDurationMinutes);
                $maxClinicCapacity += $slotsCount;
            }
        }

        if ($maxClinicCapacity <= 0) {
            return response()->json([
                'status' => 'success',
                'message' => 'No available slots calculated for today.',
                'data' => [
                    'date' => $today->format('Y-m-d'),
                    'day' => $dayOfWeek,
                    'clinic_occupancy_percentage' => '0%'
                ]
            ]);
        }

        $occupancyPercentage = ($totalTodayAppointments / $maxClinicCapacity) * 100;
        $occupancyPercentage = min(round($occupancyPercentage, 1), 100);

        return response()->json([
            'status' => 'success',
            'data' => [
                'date'                        => $today->format('Y-m-d'),
                'day'                         => $dayOfWeek,
                'booked_appointments'         => $totalTodayAppointments,
                'max_available_slots'         => $maxClinicCapacity,
                'clinic_occupancy_percentage' => $occupancyPercentage . '%'
            ]
        ], 200);
    }

    public function getTopDepartmentThisWeek()
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek == Carbon::SATURDAY) {
            $startOfWeek = $today->copy()->format('Y-m-d');
            $endOfWeek   = $today->copy()->addDays(5)->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::THURSDAY) {
            $startOfWeek = $today->copy()->subDays(5)->format('Y-m-d');
            $endOfWeek   = $today->copy()->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::FRIDAY) {
            $startOfWeek = $today->copy()->subDays(6)->format('Y-m-d');
            $endOfWeek   = $today->copy()->subDay()->format('Y-m-d');
        } else {
            $startOfWeek = $today->copy()->previous(Carbon::SATURDAY)->format('Y-m-d');
            $endOfWeek   = $today->copy()->next(Carbon::THURSDAY)->format('Y-m-d');
        }

        $topDepartment = Department::select(
            'departments.id',
            'departments.name as department_name',
            DB::raw('COUNT(appointments.id) as appointments_count')
        )

            ->join('doctors', 'departments.id', '=', 'doctors.department_id')
            ->join('appointments', 'doctors.id', '=', 'appointments.doctor_id')
            ->whereBetween('appointments.date', [$startOfWeek, $endOfWeek])
            ->whereIn('appointments.status', ['confirmed', 'completed'])
            ->groupBy('departments.id', 'departments.name')
            ->orderBy('appointments_count', 'desc')
            ->first();

        if (!$topDepartment) {
            return response()->json([
                'status' => 'success',
                'message' => 'No confirmed or completed appointments booked for any department during this period.',
                'department' => null,
                'week_range' => [
                    'start_saturday' => $startOfWeek,
                    'end_thursday'   => $endOfWeek
                ]
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'department_id'      => $topDepartment->id,
                'department_name'    => $topDepartment->department_name,
                'appointments_count' => $topDepartment->appointments_count,
                'week_range' => [
                    'start_saturday' => $startOfWeek,
                    'end_thursday'   => $endOfWeek
                ]
            ]
        ], 200);
    }

    //----------------Departments------------------
    public function getDepartmentsDashboardReport()
    {

        $today = Carbon::today();
        $startOfToday = $today->copy()->startOfDay()->format('Y-m-d H:i:s');
        $endOfToday   = $today->copy()->endOfDay()->format('Y-m-d H:i:s');
        $dayOfWeek    = $today->format('l');
        $excludedStatuses = ['cancelled'];
        $appointmentDurationMinutes = 30;

        $departments = Department::with('doctors')->get();
        $report = [];

        foreach ($departments as $department) {

            $doctorsCount = $department->doctors->count();
            $appointmentsCount = Appointment::whereHas('doctor', function ($query) use ($department) {
                $query->where('department_id', $department->id);
            })
                ->whereBetween('date', [$startOfToday, $endOfToday])
                ->whereNotIn('status', $excludedStatuses)
                ->count();

            $totalPatientsCount = Appointment::whereHas('doctor', function ($query) use ($department) {
                $query->where('department_id', $department->id);
            })
                ->distinct('child_id')
                ->count('child_id');

            $maxDepartmentCapacity = 0;
            $availabilities = DoctorAvailability::where('day_of_week', $dayOfWeek)
                ->whereIn('doctor_id', $department->doctors->pluck('id'))
                ->get();

            foreach ($availabilities as $availability) {
                $startTime = Carbon::parse($availability->start_time);
                $endTime   = Carbon::parse($availability->end_time);
                $totalMinutes = $startTime->diffInMinutes($endTime);

                if ($appointmentDurationMinutes > 0) {
                    $maxDepartmentCapacity += floor($totalMinutes / $appointmentDurationMinutes);
                }
            }

            $occupancyPercentage = 0;
            if ($maxDepartmentCapacity > 0) {
                $percentage = ($appointmentsCount / $maxDepartmentCapacity) * 100;
                $occupancyPercentage = min(round($percentage, 1), 100);
            }
            $report[] = [
                'department_id'            => $department->id,
                'department_name'          => $department->name,
                'doctors'            => $doctorsCount,
                'patients'     => $totalPatientsCount,
                'today_appointments' => $appointmentsCount,
                'max_available_slots'      => $maxDepartmentCapacity,
                'occupancy_percentage'     => $occupancyPercentage . '%'
            ];
        }
        return response()->json([
            'status' => 'success',
            'date'   => $today->format('Y-m-d'),
            'day'    => $dayOfWeek,
            'data'   => $report
        ], 200);
    }

    //----------------Statistics-----------------

    public function getWeeklyClinicSummary()
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek == Carbon::SATURDAY) {
            $startOfWeek = $today->copy()->format('Y-m-d');
            $endOfWeek   = $today->copy()->addDays(5)->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::THURSDAY) {
            $startOfWeek = $today->copy()->subDays(5)->format('Y-m-d');
            $endOfWeek   = $today->copy()->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::FRIDAY) {
            $startOfWeek = $today->copy()->subDays(6)->format('Y-m-d');
            $endOfWeek   = $today->copy()->subDay()->format('Y-m-d');
        } else {
            $startOfWeek = $today->copy()->previous(Carbon::SATURDAY)->format('Y-m-d');
            $endOfWeek   = $today->copy()->next(Carbon::THURSDAY)->format('Y-m-d');
        }

        $allowedStatuses = ['confirmed', 'completed'];
        $appointmentDurationMinutes = 30;

        $totalDoctorsCount = Doctor::count();

        $activeDoctorsThisWeekCount = Appointment::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', $allowedStatuses)
            ->distinct('doctor_id')
            ->count('doctor_id');

        $bookedAppointmentsCount = Appointment::whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', $allowedStatuses)
            ->count();

        $availabilities = DoctorAvailability::get();
        $maxWeeklyCapacity = 0;

        $startDate = Carbon::parse($startOfWeek);
        for ($i = 0; $i <= 5; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $currentDayName = $currentDate->format('l');

            $dayAvailabilities = $availabilities->where('day_of_week', $currentDayName);

            foreach ($dayAvailabilities as $availability) {
                $startTime = Carbon::parse($availability->start_time);
                $endTime   = Carbon::parse($availability->end_time);
                $totalMinutes = $startTime->diffInMinutes($endTime);

                if ($appointmentDurationMinutes > 0) {
                    $maxWeeklyCapacity += floor($totalMinutes / $appointmentDurationMinutes);
                }
            }
        }
        $availableSlotsRemaining = max(0, $maxWeeklyCapacity - $bookedAppointmentsCount);
        $busiestDayQuery = Appointment::select(
            DB::raw('DAYNAME(date) as day_name'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', $allowedStatuses)
            ->groupBy('day_name')
            ->orderBy('count', 'desc')
            ->first();

        $busiestDay = $busiestDayQuery ? $busiestDayQuery->day_name : 'No appointments this week';
        $busiestDayCount = $busiestDayQuery ? $busiestDayQuery->count : 0;

        return response()->json([
            'status' => 'success',
            'week_range' => [
                'start_date' => $startOfWeek,
                'end_date'   => $endOfWeek
            ],
            'data' => [
                'total_doctors'               => $totalDoctorsCount,
                'active_doctors_this_week'    => $activeDoctorsThisWeekCount,
                'available_appointments_left' => $availableSlotsRemaining,
                'busiest_day_of_week'         => [
                    'day_name'           => $busiestDay,
                    'appointments_count' => $busiestDayCount
                ]
            ]
        ], 200);
    }
    public function getChildrenAgeDistribution()
    {
        $ageCounts = DB::table('children')
            ->select(DB::raw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) as age, COUNT(*) as count'))
            ->whereRaw('TIMESTAMPDIFF(YEAR, birth_date, CURDATE()) <= 6')
            ->groupBy('age')
            ->pluck('count', 'age')
            ->toArray();

        $report = [
            [
                'age_range' => '0 - 1',
                'children_count' => $ageCounts[0] ?? 0
            ],
            [
                'age_range' => '1 - 2',
                'children_count' => $ageCounts[1] ?? 0
            ],
            [
                'age_range' => '2 - 3',
                'children_count' => $ageCounts[2] ?? 0
            ],
            [
                'age_range' => '3 - 4',
                'children_count' => $ageCounts[3] ?? 0
            ],
            [
                'age_range' => '4 - 5',
                'children_count' => $ageCounts[4] ?? 0
            ],
            [
                'age_range' => '5 - 6',
                'children_count' => $ageCounts[5] ?? 0
            ],
        ];

        $totalChildrenInRanges = array_sum(array_column($report, 'children_count'));

        return response()->json([
            'status' => 'success',
            'total_monitored_children' => $totalChildrenInRanges,
            'data' => $report
        ], 200);
    }

    public function getAppointmentsCountPerDayOfWeek()
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        if ($dayOfWeek == Carbon::SATURDAY) {
            $startOfWeek = $today->copy()->format('Y-m-d');
            $endOfWeek   = $today->copy()->addDays(5)->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::THURSDAY) {
            $startOfWeek = $today->copy()->subDays(5)->format('Y-m-d');
            $endOfWeek   = $today->copy()->format('Y-m-d');
        } elseif ($dayOfWeek == Carbon::FRIDAY) {
            $startOfWeek = $today->copy()->subDays(6)->format('Y-m-d');
            $endOfWeek   = $today->copy()->subDay()->format('Y-m-d');
        } else {
            $startOfWeek = $today->copy()->previous(Carbon::SATURDAY)->format('Y-m-d');
            $endOfWeek   = $today->copy()->next(Carbon::THURSDAY)->format('Y-m-d');
        }

        $allowedStatuses = ['confirmed', 'completed'];

        $appointmentsPerDay = Appointment::select(
            DB::raw('DAYNAME(date) as day_name'),
            DB::raw('COUNT(*) as count')
        )
            ->whereBetween('date', [$startOfWeek, $endOfWeek])
            ->whereIn('status', $allowedStatuses)
            ->groupBy('day_name')
            ->pluck('count', 'day_name')
            ->toArray();

        $weekDays = [
            'Saturday',
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday'
        ];

        $report = [];
        $totalWeeklyAppointments = 0;

        foreach ($weekDays as $day) {
            $count = $appointmentsPerDay[$day] ?? 0;
            $totalWeeklyAppointments += $count;

            $report[] = [
                'day_name'           => $day,
                'appointments_count' => $count
            ];
        }

        return response()->json([
            'status' => 'success',
            'week_range' => [
                'start_saturday' => $startOfWeek,
                'end_thursday'   => $endOfWeek
            ],
            'total_confirmed_and_completed' => $totalWeeklyAppointments,
            'data' => $report
        ], 200);
    }
    public function getTopThreeDepartmentsShare()
    {
        $allowedStatuses = ['confirmed', 'completed'];
        $departments = Department::take(3)->get();

        $totalAppointmentsForTopThree = Appointment::whereHas('doctor', function ($query) use ($departments) {
            $query->whereIn('department_id', $departments->pluck('id'));
        })
            ->whereIn('status', $allowedStatuses)
            ->count();

        if ($totalAppointmentsForTopThree == 0) {
            $report = $departments->map(function ($department) {
                return [
                    'department_id'   => $department->id,
                    'department_name' => $department->name,
                    'appointments_count' => 0,
                    'share_percentage'   => '0%'
                ];
            });

            return response()->json([
                'status' => 'success',
                'total_appointments' => 0,
                'data' => $report
            ], 200);
        }

        $report = [];

        foreach ($departments as $department) {
            $departmentAppointmentsCount = Appointment::whereHas('doctor', function ($query) use ($department) {
                $query->where('department_id', $department->id);
            })
                ->whereIn('status', $allowedStatuses)
                ->count();

            $sharePercentage = ($departmentAppointmentsCount / $totalAppointmentsForTopThree) * 100;

            $report[] = [
                'department_id'      => $department->id,
                'department_name'    => $department->name,
                'appointments_count' => $departmentAppointmentsCount,
                'share_percentage'   => round($sharePercentage, 1) . '%'
            ];
        }

        return response()->json([
            'status' => 'success',
            'total_appointments' => $totalAppointmentsForTopThree,
            'data' => $report
        ], 200);
    }

    public function getMonthlyBudgetReport()
    {
        $startOfYear = Carbon::now()->startOfYear();
        $currentDate = Carbon::now()->endOfDay();
        $allowedStatuses = ['completed'];

        $appointments = Appointment::with('additions')
            ->whereIn('status', $allowedStatuses)
            ->whereBetween('date', [$startOfYear->format('Y-m-d'), $currentDate->format('Y-m-d')])
            ->get();

        $monthlyReport = [];
        $startMonth = $startOfYear->copy();
        while ($startMonth->lte($currentDate)) {
            $monthName = $startMonth->format('F');
            $year = $startMonth->year;

            $currentMonthAppointments = $appointments->filter(function ($appointment) use ($startMonth) {
                $appointmentDate = Carbon::parse($appointment->date);
                return $appointmentDate->month === $startMonth->month && $appointmentDate->year === $startMonth->year;
            });

            $appointmentsRevenue = $currentMonthAppointments->sum('price');

            $additionsRevenue = 0;
            foreach ($currentMonthAppointments as $appointment) {
                if ($appointment->additions) {
                    $additionsRevenue += $appointment->additions->sum('price');
                }
            }

            $doctorEarningsExpense = $currentMonthAppointments->sum('doctor_earnings');
            $materialsCostExpense = $additionsRevenue;

            $totalIncome = $appointmentsRevenue + $additionsRevenue;
            $totalExpense = $doctorEarningsExpense + $materialsCostExpense;
            $netProfit = $totalIncome - $totalExpense;

            $monthlyReport[] = [

                'month_number' => $startMonth->month,
                'month_name' => $monthName,
                'income_details' => [
                    'appointments_revenue' => round($appointmentsRevenue, 2),
                    'additions_revenue'    => round($additionsRevenue, 2),
                    'total_income'         => round($totalIncome, 2)
                ],
                'expense_details' => [
                    'doctor_earnings'  => round($doctorEarningsExpense, 2),
                    'materials_cost'   => round($materialsCostExpense, 2),
                    'total_expense'    => round($totalExpense, 2)
                ],
                'net_profit' => round($netProfit, 2)
            ];

            $startMonth->addMonth();
        }

        return response()->json([
            'status' => 'success',
            'currency' => 'USD',
            'year' => $year,
            'data' => $monthlyReport
        ], 200);
    }

    public function changeReceptionistPassword(Request $request, $receptionistId)
    {
        $currentUser = auth()->user();

        if (!$currentUser || !($currentUser instanceof Admin)) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.unauthorized')
            ], 403);
        }

        $receptionist = Receptionist::find($receptionistId);
        if (!$receptionist) {
            return response()->json([
                'status'  => __('messages.error'),
                'message' => __('messages.receptionist_not_found')
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => __('messages.error'),
                'errors' => $validator->errors()
            ], 422);
        }

        $receptionist->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => __('messages.password_updated_successfully')
        ], 200);
    }
}

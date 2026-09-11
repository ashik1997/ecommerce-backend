<?php

use App\Http\Controllers\Hrat\AttendanceAdjustmentController;
use App\Http\Controllers\Hrat\AttendanceConfigController;
use App\Http\Controllers\Hrat\AttendanceDashboardController;
use App\Http\Controllers\Hrat\AttendanceReportController;
use App\Http\Controllers\Hrat\CsvImportController;
use App\Http\Controllers\Hrat\DepartmentController;
use App\Http\Controllers\Hrat\DesignationController;
use App\Http\Controllers\Hrat\BranchController;
use App\Http\Controllers\Hrat\EmployeeProfileController;
use App\Http\Controllers\Hrat\EmployeeSalaryAssignmentController;
use App\Http\Controllers\Hrat\EmployeeScheduleController;
use App\Http\Controllers\Hrat\HolidayController;
use App\Http\Controllers\Hrat\HrmManualController;
use App\Http\Controllers\Hrat\LeaveApplicationController;
use App\Http\Controllers\Hrat\LeaveTypeController;
use App\Http\Controllers\Hrat\ManualAttendanceController;
use App\Http\Controllers\Hrat\SalaryGradeController;
use App\Http\Controllers\Hrat\SalaryComponentController;
use App\Http\Controllers\Hrat\PayrollController;
use App\Http\Controllers\Hrat\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('hrat')->name('hrat.')->middleware(['auth', 'CheckUserType', 'DemoMode'])->group(function () {
    Route::get('/dashboard', [AttendanceDashboardController::class, 'index'])->name('dashboard');
    Route::get('/manual', [HrmManualController::class, 'bn'])->name('manual.index');
    Route::get('/manual/bn', [HrmManualController::class, 'bn'])->name('manual.bn');
    Route::get('/manual/en', [HrmManualController::class, 'en'])->name('manual.en');
    Route::get('/payroll-manual', [HrmManualController::class, 'payrollBn'])->name('payroll-manual.index');
    Route::get('/payroll-manual/bn', [HrmManualController::class, 'payrollBn'])->name('payroll-manual.bn');
    Route::get('/payroll-manual/en', [HrmManualController::class, 'payrollEn'])->name('payroll-manual.en');
    Route::resource('/departments', DepartmentController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/designations', DesignationController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/branches', BranchController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/shifts', ShiftController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/holidays', HolidayController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/leave-types', LeaveTypeController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::resource('/leave-applications', LeaveApplicationController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['leave-applications' => 'leaveApplication']);
    Route::post('/leave-applications/{leaveApplication}/approve', [LeaveApplicationController::class, 'approve'])->name('leave-applications.approve');
    Route::post('/leave-applications/{leaveApplication}/reject', [LeaveApplicationController::class, 'reject'])->name('leave-applications.reject');
    Route::post('/leave-applications/{leaveApplication}/cancel', [LeaveApplicationController::class, 'cancel'])->name('leave-applications.cancel');
    Route::resource('/employee-profiles', EmployeeProfileController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['employee-profiles' => 'employeeProfile']);
    Route::get('/configs', [AttendanceConfigController::class, 'index'])->name('configs.index');
    Route::post('/configs', [AttendanceConfigController::class, 'store'])->name('configs.store');
    Route::resource('/employee-schedules', EmployeeScheduleController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['employee-schedules' => 'employeeSchedule']);
    Route::get('/manual-entry', [ManualAttendanceController::class, 'index'])->name('manual-entry.index');
    Route::post('/manual-entry', [ManualAttendanceController::class, 'store'])->name('manual-entry.store');
    Route::get('/csv-import', [CsvImportController::class, 'index'])->name('csv-import.index');
    Route::post('/csv-import/preview', [CsvImportController::class, 'preview'])->name('csv-import.preview');
    Route::post('/csv-import/store', [CsvImportController::class, 'store'])->name('csv-import.store');
    Route::get('/import-batches', [CsvImportController::class, 'batches'])->name('import-batches.index');
    Route::get('/import-batches/{batch}/failed-rows', [CsvImportController::class, 'failedRows'])->name('import-batches.failed-rows');
    Route::get('/reports/daily', [AttendanceReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/monthly', [AttendanceReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/absent', [AttendanceReportController::class, 'absent'])->name('reports.absent');
    Route::get('/reports/late', [AttendanceReportController::class, 'late'])->name('reports.late');
    Route::get('/reports/overtime', [AttendanceReportController::class, 'overtime'])->name('reports.overtime');
    Route::get('/reports/leave', [AttendanceReportController::class, 'leave'])->name('reports.leave');
    Route::get('/reports/employee-master', [AttendanceReportController::class, 'employeeMaster'])->name('reports.employee-master');
    Route::get('/reports/department-branch', [AttendanceReportController::class, 'departmentBranch'])->name('reports.department-branch');
    Route::get('/reports/export/csv', [AttendanceReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::resource('/salary-grades', SalaryGradeController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['salary-grades' => 'salaryGrade']);
    Route::resource('/salary-components', SalaryComponentController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['salary-components' => 'salaryComponent']);
    Route::resource('/employee-salary-assignments', EmployeeSalaryAssignmentController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->parameters(['employee-salary-assignments' => 'employeeSalaryAssignment']);
    Route::post('/employee-salary-assignments/{employeeSalaryAssignment}/components', [EmployeeSalaryAssignmentController::class, 'storeComponent'])->name('employee-salary-assignments.components.store');
    Route::delete('/employee-salary-assignments/{employeeSalaryAssignment}/components/{component}', [EmployeeSalaryAssignmentController::class, 'destroyComponent'])->name('employee-salary-assignments.components.destroy');
    Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');
    Route::get('/payroll-reports', [PayrollController::class, 'report'])->name('payroll-reports.index');
    Route::get('/payroll-reports/export/csv', [PayrollController::class, 'exportCsv'])->name('payroll-reports.export.csv');
    Route::post('/payrolls/generate', [PayrollController::class, 'generate'])->name('payrolls.generate');
    Route::get('/payrolls/{payroll}/payslips/{line}', [PayrollController::class, 'payslip'])->name('payrolls.payslip');
    Route::post('/payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve');
    Route::post('/payrolls/{payroll}/finalize', [PayrollController::class, 'finalize'])->name('payrolls.finalize');
    Route::post('/payrolls/{payroll}/mark-paid', [PayrollController::class, 'markPaid'])->name('payrolls.mark-paid');
    Route::post('/payrolls/{payroll}/reverse-payment', [PayrollController::class, 'reversePayment'])->name('payrolls.reverse-payment');
    Route::post('/payrolls/{payroll}/void-finalization', [PayrollController::class, 'voidFinalization'])->name('payrolls.void-finalization');
    Route::get('/adjustments', [AttendanceAdjustmentController::class, 'index'])->name('adjustments.index');
    Route::post('/adjustments', [AttendanceAdjustmentController::class, 'store'])->name('adjustments.store');
    Route::put('/adjustments/{attendanceAdjustment}', [AttendanceAdjustmentController::class, 'update'])->name('adjustments.update');
    Route::delete('/adjustments/{attendanceAdjustment}', [AttendanceAdjustmentController::class, 'destroy'])->name('adjustments.destroy');
    Route::post('/adjustments/{attendanceAdjustment}/approve', [AttendanceAdjustmentController::class, 'approve'])->name('adjustments.approve');
    Route::post('/adjustments/{attendanceAdjustment}/reject', [AttendanceAdjustmentController::class, 'reject'])->name('adjustments.reject');
});

<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitProblemController;
use App\Http\Controllers\KilometerReportController;
use App\Http\Controllers\MaintenanceLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Authentication routes are handled by Laravel Breeze

Route::middleware('auth')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Profile routes (provided by Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/settings', [ProfileController::class, 'settings'])->name('profile.settings');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Future admin-specific routes can go here
    });

    // User Management Routes (Superadmin only)
    Route::resource('users', UserController::class);

    // Driver routes
    Route::get('/drivers/import', [DriverController::class, 'importForm'])->name('drivers.import');
    Route::post('/drivers/import', [DriverController::class, 'import'])->name('drivers.import.process');
    Route::get('/drivers/get-units-for-route', [DriverController::class, 'getUnitsForRoute'])->name('drivers.get-units-for-route');
    Route::resource('drivers', DriverController::class);

    // Unit routes
    Route::resource('units', UnitController::class);

    // Unit Problem Routes
    Route::resource('unit-problems', UnitProblemController::class);
    Route::get('unit-problems/drivers-for-unit/{unitId}', [UnitProblemController::class, 'getDriversForUnit']);
    Route::post('unit-problems/driver-from-schedule', [UnitProblemController::class, 'getDriverFromSchedule']);
    Route::delete('unit-problems/photos/{id}', [UnitProblemController::class, 'deletePhoto']);
    
    // Maintenance Log Routes
    Route::resource('maintenance-logs', MaintenanceLogController::class);
    Route::get('maintenance-logs/drivers-for-unit/{unitId}', [MaintenanceLogController::class, 'getDriversForUnit']);
    Route::get('maintenance-logs/routes-for-unit/{unitId}', [MaintenanceLogController::class, 'getRoutesForUnit']);
    Route::post('maintenance-logs/driver-from-schedule', [MaintenanceLogController::class, 'getDriverFromSchedule']);
    Route::delete('maintenance-logs/photos/{id}', [MaintenanceLogController::class, 'deletePhoto']);
    
    // Kilometer Report Routes
    Route::get('kilometer-reports', [KilometerReportController::class, 'index'])->name('kilometer-reports.index');
    Route::get('kilometer-reports/export/excel', [KilometerReportController::class, 'exportExcel'])->name('kilometer-reports.export.excel');
    Route::get('kilometer-reports/export/pdf', [KilometerReportController::class, 'exportPdf'])->name('kilometer-reports.export.pdf');
    Route::get('kilometer-reports/{unit}', [KilometerReportController::class, 'show'])->name('kilometer-reports.show');
    Route::post('kilometer-reports', [KilometerReportController::class, 'store'])->name('kilometer-reports.store');

    // Route routes
    Route::resource('routes', RouteController::class);

    // Schedule routes
    Route::get('/schedules/weekly', [ScheduleController::class, 'weekly'])->name('schedules.weekly');
    Route::get('/schedules/calendar', [ScheduleController::class, 'calendar'])->name('schedules.calendar');
    Route::get('/schedules/generate', [ScheduleController::class, 'showGenerateForm'])->name('schedules.generate.form');
    Route::post('/schedules/generate', [ScheduleController::class, 'generateSchedules'])->name('schedules.generate');
    Route::get('/schedules/date/{date}', [ScheduleController::class, 'getSchedulesByDate'])->name('schedules.by.date');
    Route::get('/schedules/{schedule}/unavailable', [ScheduleController::class, 'markUnavailable'])->name('schedules.unavailable');
    Route::post('/schedules/{schedule}/backup', [ScheduleController::class, 'assignBackup'])->name('schedules.backup');
    Route::get('/schedules/export/excel', [ScheduleController::class, 'exportExcel'])->name('schedules.export.excel');
    Route::get('/schedules/export/pdf', [ScheduleController::class, 'exportPdf'])->name('schedules.export.pdf');
    Route::resource('schedules', ScheduleController::class);

    // Leave Request routes
    Route::resource('leave-requests', LeaveRequestController::class);
    Route::post('/leave-requests/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('/leave-requests/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::get('/leave-requests/{leaveRequest}/check-available-drivers', [LeaveRequestController::class, 'checkAvailableDrivers'])->name('leave-requests.check-available-drivers');
    Route::post('/leave-requests/{leaveRequest}/assign-backup-drivers', [LeaveRequestController::class, 'assignBackupDrivers'])->name('leave-requests.assign-backup-drivers');

    // Report routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
});

require __DIR__.'/auth.php';

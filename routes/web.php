<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UnitImportController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitProblemController;
use App\Http\Controllers\KilometerReportController;
use App\Http\Controllers\MaintenanceLogController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\UnitRenopsController;
use App\Http\Controllers\DriverScheduleSettingsController;
use App\Http\Controllers\GlobalKilometerReportController;
use App\Http\Controllers\GlobalKilometerReportGeneratorController;
use App\Http\Controllers\GlobalSearchController;
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
    Route::get('/driver/schedule/settings', [DriverScheduleSettingsController::class, 'index'])->name('driver.schedule.settings');
    Route::post('/driver/schedule/settings/update', [DriverScheduleSettingsController::class, 'update'])->name('driver.schedule.settings.update');

    // Unit routes
    Route::resource('units', UnitController::class);

    // Unit Import Routes
    Route::get('units-import', [UnitImportController::class, 'showImportForm'])->name('units.import.form');
    Route::post('units-import/pool', [UnitImportController::class, 'importPoolUnits'])->name('units.import.pool');
    Route::post('units-import/non-pool', [UnitImportController::class, 'importNonPoolUnits'])->name('units.import.non-pool');
    Route::get('units-import/pool/template', [UnitImportController::class, 'downloadPoolTemplate'])->name('units.import.pool.template');
    Route::get('units-import/non-pool/template', [UnitImportController::class, 'downloadNonPoolTemplate'])->name('units.import.non-pool.template');
    Route::patch('/units/{unit}/toggle-renops', [UnitController::class, 'toggleRenops'])->name('units.toggle-renops');
    Route::post('/units/bulk-renops', [UnitController::class, 'bulkRenops'])->name('units.bulk-renops');

    // Unit Problem Routes
    Route::resource('unit-problems', UnitProblemController::class);
    Route::get('unit-problems/drivers-for-unit/{unitId}', [UnitProblemController::class, 'getDriversForUnit'])->name('unit-problems.drivers-for-unit');
    Route::post('unit-problems/driver-from-schedule', [UnitProblemController::class, 'getDriverFromSchedule'])->name('unit-problems.get-driver-from-schedule');
    Route::delete('unit-problems/photos/{id}', [UnitProblemController::class, 'deletePhoto'])->name('unit-problems.delete-photo');
    Route::get('unit-problems/{unit_problem}/convert-to-maintenance', [UnitProblemController::class, 'convertToMaintenance'])
        ->name('unit-problems.convert-to-maintenance');

    // Maintenance Log Routes
    Route::resource('maintenance-logs', MaintenanceLogController::class);
    Route::get('maintenance-logs/drivers-for-unit/{unitId}', [MaintenanceLogController::class, 'getDriversForUnit']);
    Route::get('maintenance-logs/routes-for-unit/{unitId}', [MaintenanceLogController::class, 'getRoutesForUnit']);
    Route::post('maintenance-logs/driver-from-schedule', [MaintenanceLogController::class, 'getDriverFromSchedule']);
    Route::delete('maintenance-logs/photos/{id}', [MaintenanceLogController::class, 'deletePhoto']);
    Route::patch('maintenance-logs/{maintenanceLog}/update-status', [MaintenanceLogController::class, 'updateStatus'])
        ->name('maintenance-logs.update-status');

    // Kilometer Report Routes
    Route::get('kilometer-reports', [KilometerReportController::class, 'index'])->name('kilometer-reports.index');
    Route::post('kilometer-reports', [KilometerReportController::class, 'store'])->name('kilometer-reports.store');
    Route::get('kilometer-reports/export/excel', [KilometerReportController::class, 'exportExcel'])->name('kilometer-reports.export.excel');
    Route::get('kilometer-reports/export/pdf', [KilometerReportController::class, 'exportPdf'])->name('kilometer-reports.export.pdf');
    Route::get('kilometer-reports/template', [KilometerReportController::class, 'downloadTemplate'])->name('kilometer-reports.template');
    Route::post('kilometer-reports/import', [KilometerReportController::class, 'import'])->name('kilometer-reports.import');
    Route::get('kilometer-reports/{unit}', [KilometerReportController::class, 'show'])->name('kilometer-reports.show');

    // Global Kilometer Report Routes
    Route::get('global-kilometer-reports', [GlobalKilometerReportController::class, 'index'])->name('global-kilometer-reports.index');
    Route::get('global-kilometer-reports/export/excel', [GlobalKilometerReportController::class, 'exportExcel'])->name('global-kilometer-reports.export.excel');
    Route::get('global-kilometer-reports/export/pdf', [GlobalKilometerReportController::class, 'exportPdf'])->name('global-kilometer-reports.export.pdf');
    Route::post('global-kilometer-reports/generate', [GlobalKilometerReportGeneratorController::class, 'generate'])->name('global-kilometer-reports.generate');
    Route::post('global-kilometer-reports/reset', [GlobalKilometerReportController::class, 'reset'])->name('global-kilometer-reports.reset');

    // Holiday Routes
    Route::resource('holidays', HolidayController::class);
    Route::get('holidays/check-date', [HolidayController::class, 'checkDateStatus'])->name('holidays.check-date');
    Route::get('holidays/get-holidays-and-weekends', [HolidayController::class, 'getHolidaysAndWeekends'])->name('holidays.get-holidays-and-weekends');

    // Unit Renops Routes
    Route::get('renops', [UnitRenopsController::class, 'index'])->name('renops.index');
    Route::get('renops/create', [UnitRenopsController::class, 'create'])->name('renops.create');
    Route::post('renops', [UnitRenopsController::class, 'store'])->name('renops.store');
    Route::put('renops/{date}', [UnitRenopsController::class, 'update'])->name('renops.update');
    Route::delete('renops/{date}', [UnitRenopsController::class, 'destroy'])->name('renops.destroy');
    Route::post('renops/toggle-unit', [UnitRenopsController::class, 'toggleUnit'])->name('renops.toggle-unit');
    Route::get('renops/date-range', [UnitRenopsController::class, 'getByDateRange'])->name('renops.date-range');
    Route::get('renops/settings', [UnitRenopsController::class, 'showSettings'])->name('renops.settings');
    Route::post('renops/settings', [UnitRenopsController::class, 'updateSettings'])->name('renops.settings.update');
    Route::post('renops/generate-automatic', [UnitRenopsController::class, 'generateAutomatic'])->name('renops.generate-automatic');

    // Route routes
    Route::resource('routes', RouteController::class);
    Route::post('/routes/{route}/units', [RouteController::class, 'addUnit'])->name('routes.units.add');
    Route::delete('/routes/{route}/units/{unit}', [RouteController::class, 'removeUnit'])->name('routes.units.remove');

    // Schedule routes
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::get('/export/excel', [ScheduleController::class, 'exportExcel'])->name('export.excel');
        Route::get('/export/pdf', [ScheduleController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/matrix-pdf', [ScheduleController::class, 'exportMatrixPdf'])->name('export.matrix-pdf');
        Route::get('/generate', [ScheduleController::class, 'generateForm'])->name('generate.form');
        Route::post('/generate', [ScheduleController::class, 'generate'])->name('generate');
        Route::post('/reset-all', [ScheduleController::class, 'resetAll'])->name('reset-all');
        Route::post('/update', [ScheduleController::class, 'update'])->name('update');
    });

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
    
    // Global Search
    Route::get('/global-search', [GlobalSearchController::class, 'search'])->name('global.search');
});

require __DIR__.'/auth.php';

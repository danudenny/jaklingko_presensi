<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\DriverHistory;
use App\Models\DriverScheduleHistory;
use App\Models\Route;
use App\Mail\LeaveRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Unit;

class LeaveRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $drivers = Driver::orderBy('name')->get();
        $routes = Route::active()->orderBy('route_number')->get();
        $route = $request->input('route');
        $units = Unit::active()->orderBy('unit_number')->get();
        $unit = $request->input('unit');
        $query = LeaveRequest::with('driver');
        
        // Filter by driver if selected
        if ($request->has('driver') && $request->driver) {
            $query->where('driver_id', $request->driver);
        }
        
        $pendingRequests = $query->clone()->pending()->orderBy('start_date')->get();
        $approvedRequests = $query->clone()->approved()->where('end_date', '>=', Carbon::today())->orderBy('start_date')->get();
        $rejectedRequests = $query->clone()->rejected()->orderBy('created_at', 'desc')->get();

        return view('modules.admin.leave-requests.index', compact(
            'pendingRequests', 
            'approvedRequests', 
            'rejectedRequests',
            'drivers',
            'routes',
            'route',
            'units',
            'unit'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $drivers = Driver::active()->get();
        return view('modules.admin.leave-requests.create', compact('drivers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:terencana,sakit,darurat,lainnya',
            'reason' => 'nullable|string',
            'documentation' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $leaveRequest = new LeaveRequest([
            'driver_id' => $validated['driver_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'type' => $validated['type'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'requested',
        ]);

        // Handle documentation image upload
        if ($request->hasFile('documentation')) {
            $image = $request->file('documentation');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/leave-requests', $imageName);
            $leaveRequest->documentation = 'leave-requests/' . $imageName;
        }

        $leaveRequest->save();

        // Send email notification
        $recipients = ['danudenny@gmail.com', 'denny.danuwijaya@gmail.com'];
        Mail::to($recipients)->send(new LeaveRequestNotification($leaveRequest));

        return redirect()->route('leave-requests.index')
            ->with('success', 'Leave request created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load('driver');

        // Get affected schedules
        $affectedSchedules = Schedule::with(['route', 'unit', 'backupDriver'])
            ->where('driver_id', $leaveRequest->driver_id)
            ->whereBetween('schedule_date', [$leaveRequest->start_date, $leaveRequest->end_date])
            ->get();

        // Check if there are available backup drivers for each schedule
        $availableBackupDrivers = [];
        $hasAllBackups = true;
        $schedulesWithoutBackup = [];

        foreach ($affectedSchedules as $schedule) {
            $backups = $schedule->findAvailableBackupDrivers();
            $availableBackupDrivers[$schedule->id] = $backups;

            if ($backups->isEmpty()) {
                $hasAllBackups = false;
                $schedulesWithoutBackup[] = [
                    'date' => $schedule->schedule_date->format('Y-m-d'),
                    'unit' => $schedule->unit->unit_number,
                    'shift' => ucfirst($schedule->shift),
                    'route' => $schedule->route->route_number . ' - ' . $schedule->route->name
                ];
            }
        }

        return view('modules.admin.leave-requests.show', compact(
            'leaveRequest',
            'affectedSchedules',
            'availableBackupDrivers',
            'hasAllBackups',
            'schedulesWithoutBackup'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leaveRequest)
    {
        $drivers = Driver::active()->get();
        return view('modules.admin.leave-requests.edit', compact('leaveRequest', 'drivers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'driver_id' => 'required|exists:drivers,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:terencana,sakit,darurat,lainnya',
            'reason' => 'nullable|string',
            'status' => 'required|in:requested,approved,rejected',
            'admin_notes' => 'nullable|string',
            'documentation' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Check if user is trying to approve and is not a superadmin
        if ($validated['status'] === 'approved' && $leaveRequest->status !== 'approved' && !Auth::user()->isSuperAdmin()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['status' => 'Hanya superadmin yang dapat menyetujui permohonan cuti.']);
        }

        // If trying to approve, check for available backup drivers
        if ($validated['status'] === 'approved' && $leaveRequest->status !== 'approved') {
            $leaveRequest->driver_id = $validated['driver_id'];
            $leaveRequest->start_date = $validated['start_date'];
            $leaveRequest->end_date = $validated['end_date'];

            // Check for available backups and get details of schedules without backups
            $checkResult = $leaveRequest->checkAvailableBackupsWithDetails();
            
            if (!$checkResult['hasAllBackups']) {
                $errorMessage = 'Tidak dapat menyetujui permohonan cuti. Tidak ada backup driver yang tersedia untuk jadwal berikut: ';
                
                foreach ($checkResult['schedulesWithoutBackup'] as $index => $schedule) {
                    $errorMessage .= ($index > 0 ? ', ' : '') . 
                        $schedule['date'] . ' (' . $schedule['unit'] . ' - ' . $schedule['shift'] . ')';
                }
                
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['status' => $errorMessage]);
            }
        }

        $validated['approved_by'] = ($validated['status'] === 'approved') ? Auth::id() : null;

        // Store original values before update
        $originalDriverId = $leaveRequest->driver_id;
        $originalStartDate = $leaveRequest->start_date;
        $originalEndDate = $leaveRequest->end_date;
        $originalStatus = $leaveRequest->status;

        // Handle documentation image upload
        if ($request->hasFile('documentation')) {
            // Delete old image if exists
            if ($leaveRequest->documentation) {
                Storage::delete('public/' . $leaveRequest->documentation);
            }

            $image = $request->file('documentation');
            $imageName = time() . '_' . $image->getClientOriginalName();
            $image->storeAs('public/leave-requests', $imageName);
            $validated['documentation'] = 'leave-requests/' . $imageName;
        }

        $leaveRequest->update($validated);

        // If approved, update affected schedules to mark driver as on leave
        if ($validated['status'] === 'approved') {
            // If this is a newly approved request or the date range/driver has changed
            if ($originalStatus !== 'approved' ||
                $originalDriverId != $validated['driver_id'] ||
                $originalStartDate != $validated['start_date'] ||
                $originalEndDate != $validated['end_date']) {

                // If previously approved with different parameters, clean up old records
                if ($originalStatus === 'approved') {
                    // Get previously affected schedules
                    $oldSchedules = Schedule::where('driver_id', $originalDriverId)
                        ->whereBetween('schedule_date', [$originalStartDate, $originalEndDate])
                        ->where('status', 'on_leave')
                        ->get();

                    // Reset schedules and remove driver history records
                    foreach ($oldSchedules as $schedule) {
                        // Delete driver history records for this schedule
                        DriverHistory::where('driver_id', $originalDriverId)
                            ->where('unit_id', $schedule->unit_id)
                            ->where('shift', $schedule->shift)
                            ->where('start_date', $schedule->schedule_date)
                            ->where('on_leave', true)
                            ->delete();

                        // Delete backup driver history records
                        if ($schedule->backup_driver_id) {
                            DriverHistory::where('driver_id', $schedule->backup_driver_id)
                                ->where('unit_id', $schedule->unit_id)
                                ->where('shift', $schedule->shift)
                                ->where('start_date', $schedule->schedule_date)
                                ->where('as_backup', true)
                                ->delete();

                            // Decrement the backup driver's schedule count
                            $periodStart = Carbon::parse($schedule->schedule_date)->startOfMonth()->format('Y-m-d');
                            $periodEnd = Carbon::parse($schedule->schedule_date)->endOfMonth()->format('Y-m-d');

                            $history = DriverScheduleHistory::where('driver_id', $schedule->backup_driver_id)
                                ->where('period_start_date', $periodStart)
                                ->where('period_end_date', $periodEnd)
                                ->first();

                            if ($history && $history->total_schedules > 0) {
                                $history->total_schedules -= 1;
                                $history->target_met = $history->total_schedules >= $history->target_count;
                                $history->save();
                            }
                        }

                        // Reset the schedule
                        $schedule->update([
                            'status' => 'active',
                            'backup_driver_id' => null,
                            'notes' => 'Reset due to leave request update',
                        ]);
                    }
                }

                // Get newly affected schedules
                $affectedSchedules = Schedule::where('driver_id', $validated['driver_id'])
                    ->whereBetween('schedule_date', [$validated['start_date'], $validated['end_date']])
                    ->get();

                foreach ($affectedSchedules as $schedule) {
                    $backupDrivers = $schedule->findAvailableBackupDrivers();

                    if ($backupDrivers->isNotEmpty()) {
                        $backupDriver = $backupDrivers->first();

                        // Update the schedule with backup driver
                        $schedule->update([
                            'status' => 'on_leave',
                            'backup_driver_id' => $backupDriver->id,
                            'notes' => 'Automatically assigned backup driver due to approved leave request #' . $leaveRequest->id,
                        ]);

                        // Create driver history record for the original driver (on leave)
                        DriverHistory::create([
                            'driver_id' => $validated['driver_id'],
                            'unit_id' => $schedule->unit_id,
                            'shift' => $schedule->shift,
                            'as_backup' => false,
                            'start_date' => $schedule->schedule_date,
                            'end_date' => $schedule->schedule_date,
                            'as_renops' => false,
                            'on_leave' => true,
                            'on_duty' => false,
                        ]);

                        // Create driver history record for the backup driver
                        DriverHistory::create([
                            'driver_id' => $backupDriver->id,
                            'unit_id' => $schedule->unit_id,
                            'shift' => $schedule->shift,
                            'as_backup' => true,
                            'start_date' => $schedule->schedule_date,
                            'end_date' => $schedule->schedule_date,
                            'as_renops' => false,
                            'on_leave' => false,
                            'on_duty' => true,
                        ]);

                        // Update driver schedule history for the backup driver
                        DriverScheduleHistory::incrementScheduleCount(
                            $backupDriver->id,
                            Carbon::parse($schedule->schedule_date)->startOfMonth()->format('Y-m-d'),
                            Carbon::parse($schedule->schedule_date)->endOfMonth()->format('Y-m-d')
                        );
                    }
                }
            }
        }
        // If the request was previously approved but now rejected or back to requested
        else if ($originalStatus === 'approved' && $validated['status'] !== 'approved') {
            // Get affected schedules
            $affectedSchedules = Schedule::where('driver_id', $originalDriverId)
                ->whereBetween('schedule_date', [$originalStartDate, $originalEndDate])
                ->where('status', 'on_leave')
                ->get();

            // Reset schedules and remove driver history records
            foreach ($affectedSchedules as $schedule) {
                // Delete driver history records for this schedule
                DriverHistory::where('driver_id', $originalDriverId)
                    ->where('unit_id', $schedule->unit_id)
                    ->where('shift', $schedule->shift)
                    ->where('start_date', $schedule->schedule_date)
                    ->where('on_leave', true)
                    ->delete();

                // Delete backup driver history records
                if ($schedule->backup_driver_id) {
                    DriverHistory::where('driver_id', $schedule->backup_driver_id)
                        ->where('unit_id', $schedule->unit_id)
                        ->where('shift', $schedule->shift)
                        ->where('start_date', $schedule->schedule_date)
                        ->where('as_backup', true)
                        ->delete();

                    // Decrement the backup driver's schedule count
                    $periodStart = Carbon::parse($schedule->schedule_date)->startOfMonth()->format('Y-m-d');
                    $periodEnd = Carbon::parse($schedule->schedule_date)->endOfMonth()->format('Y-m-d');

                    $history = DriverScheduleHistory::where('driver_id', $schedule->backup_driver_id)
                        ->where('period_start_date', $periodStart)
                        ->where('period_end_date', $periodEnd)
                        ->first();

                    if ($history && $history->total_schedules > 0) {
                        $history->total_schedules -= 1;
                        $history->target_met = $history->total_schedules >= $history->target_count;
                        $history->save();
                    }
                }

                // Reset the schedule
                $schedule->update([
                    'status' => 'active',
                    'backup_driver_id' => null,
                    'notes' => 'Reset due to leave request status change',
                ]);
            }
        }

        return redirect()->route('leave-requests.index')
            ->with('success', 'Leave request updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        // Only allow deletion of pending requests
        if ($leaveRequest->status !== 'requested') {
            return redirect()->route('leave-requests.index')
                ->with('error', 'Hanya permohonan cuti yang belum disetujui yang dapat dihapus.');
        }

        $leaveRequest->delete();

        return redirect()->route('leave-requests.index')
            ->with('success', 'Permohonan cuti berhasil dihapus.');
    }

    /**
     * Approve a leave request.
     */
    public function approve(LeaveRequest $leaveRequest)
    {
        // Check if user is a superadmin
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('leave-requests.show', $leaveRequest)
                ->with('error', 'Hanya superadmin yang dapat menyetujui permohonan cuti.');
        }

        // Check if there are available backup drivers with detailed information
        $checkResult = $leaveRequest->checkAvailableBackupsWithDetails();
        
        if (!$checkResult['hasAllBackups']) {
            $errorMessage = 'Tidak dapat menyetujui permohonan cuti. Tidak ada backup driver yang tersedia untuk jadwal berikut: ';
            
            foreach ($checkResult['schedulesWithoutBackup'] as $index => $schedule) {
                $errorMessage .= ($index > 0 ? ', ' : '') . 
                    $schedule['date'] . ' (' . $schedule['unit'] . ' - ' . $schedule['shift'] . ')';
            }
            
            return redirect()->route('leave-requests.show', $leaveRequest)
                ->with('error', $errorMessage);
        }

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        // Update affected schedules to mark driver as on leave
        $affectedSchedules = Schedule::where('driver_id', $leaveRequest->driver_id)
            ->whereBetween('schedule_date', [$leaveRequest->start_date, $leaveRequest->end_date])
            ->get();

        foreach ($affectedSchedules as $schedule) {
            $backupDrivers = $schedule->findAvailableBackupDrivers();

            if ($backupDrivers->isNotEmpty()) {
                $backupDriver = $backupDrivers->first();

                // Update the schedule with backup driver
                $schedule->update([
                    'status' => 'on_leave',
                    'backup_driver_id' => $backupDriver->id,
                    'notes' => 'Automatically assigned backup driver due to approved leave request #' . $leaveRequest->id,
                ]);

                // Create driver history record for the original driver (on leave)
                DriverHistory::create([
                    'driver_id' => $leaveRequest->driver_id,
                    'unit_id' => $schedule->unit_id,
                    'shift' => $schedule->shift,
                    'as_backup' => false,
                    'start_date' => $schedule->schedule_date,
                    'end_date' => $schedule->schedule_date,
                    'as_renops' => false,
                    'on_leave' => true,
                    'on_duty' => false,
                ]);

                // Create driver history record for the backup driver
                DriverHistory::create([
                    'driver_id' => $backupDriver->id,
                    'unit_id' => $schedule->unit_id,
                    'shift' => $schedule->shift,
                    'as_backup' => true,
                    'start_date' => $schedule->schedule_date,
                    'end_date' => $schedule->schedule_date,
                    'as_renops' => false,
                    'on_leave' => false,
                    'on_duty' => true,
                ]);

                // Update driver schedule history for the backup driver
                DriverScheduleHistory::incrementScheduleCount(
                    $backupDriver->id,
                    Carbon::parse($schedule->schedule_date)->startOfMonth()->format('Y-m-d'),
                    Carbon::parse($schedule->schedule_date)->endOfMonth()->format('Y-m-d')
                );
            }
        }

        return redirect()->route('leave-requests.index')
            ->with('success', 'Leave request approved successfully.');
    }

    /**
     * Reject a leave request.
     */
    public function reject(Request $request, LeaveRequest $leaveRequest)
    {
        $validated = $request->validate([
            'admin_notes' => 'nullable|string',
        ]);

        $leaveRequest->update([
            'status' => 'rejected',
            'admin_notes' => $validated['admin_notes'],
        ]);

        return redirect()->route('leave-requests.index')
            ->with('success', 'Leave request rejected successfully.');
    }

    /**
     * Check available backup drivers for a leave request.
     */
    public function checkAvailableDrivers(LeaveRequest $leaveRequest)
    {
        // Get affected schedules
        $affectedSchedules = Schedule::with(['route', 'unit'])
            ->where('driver_id', $leaveRequest->driver_id)
            ->whereBetween('schedule_date', [$leaveRequest->start_date, $leaveRequest->end_date])
            ->get();

        // Check if there are available backup drivers for each schedule
        $availableBackupDrivers = [];
        $hasAllBackups = true;
        $schedulesWithoutBackup = [];

        foreach ($affectedSchedules as $schedule) {
            // This method already prioritizes batangan drivers over cadangan drivers
            $backups = $schedule->findAvailableBackupDrivers();
            $availableBackupDrivers[$schedule->id] = $backups;

            if ($backups->isEmpty()) {
                $hasAllBackups = false;
                $schedulesWithoutBackup[] = [
                    'date' => $schedule->schedule_date->format('Y-m-d'),
                    'unit' => $schedule->unit->unit_number,
                    'shift' => ucfirst($schedule->shift)
                ];
            }
        }

        return response()->json([
            'has_all_backups' => $hasAllBackups,
            'affected_schedules_count' => $affectedSchedules->count(),
            'available_backups' => $availableBackupDrivers,
        ]);
    }

    /**
     * Assign backup drivers to schedules affected by a leave request.
     */
    public function assignBackupDrivers(Request $request, LeaveRequest $leaveRequest)
    {
        // Check if user is a superadmin
        if (!Auth::user()->isSuperAdmin()) {
            return redirect()->route('leave-requests.show', $leaveRequest)
                ->with('error', 'Hanya superadmin yang dapat menyetujui permohonan cuti.');
        }

        $validated = $request->validate([
            'backup_assignments' => 'required|array',
            'backup_assignments.*' => 'required|exists:drivers,id',
        ]);

        // Get affected schedules
        $affectedSchedules = Schedule::where('driver_id', $leaveRequest->driver_id)
            ->whereBetween('schedule_date', [$leaveRequest->start_date, $leaveRequest->end_date])
            ->get();

        // Assign backup drivers
        foreach ($affectedSchedules as $schedule) {
            if (isset($validated['backup_assignments'][$schedule->id])) {
                $backupDriverId = $validated['backup_assignments'][$schedule->id];

                // Update the schedule with backup driver
                $schedule->update([
                    'status' => 'on_leave',
                    'backup_driver_id' => $backupDriverId,
                    'notes' => 'Manually assigned backup driver due to approved leave request #' . $leaveRequest->id,
                ]);

                // Create driver history record for the original driver (on leave)
                DriverHistory::create([
                    'driver_id' => $leaveRequest->driver_id,
                    'unit_id' => $schedule->unit_id,
                    'shift' => $schedule->shift,
                    'as_backup' => false,
                    'start_date' => $schedule->schedule_date,
                    'end_date' => $schedule->schedule_date,
                    'as_renops' => false,
                    'on_leave' => true,
                    'on_duty' => false,
                ]);

                // Create driver history record for the backup driver
                DriverHistory::create([
                    'driver_id' => $backupDriverId,
                    'unit_id' => $schedule->unit_id,
                    'shift' => $schedule->shift,
                    'as_backup' => true,
                    'start_date' => $schedule->schedule_date,
                    'end_date' => $schedule->schedule_date,
                    'as_renops' => false,
                    'on_leave' => false,
                    'on_duty' => true,
                ]);

                // Update driver schedule history for the backup driver
                DriverScheduleHistory::incrementScheduleCount(
                    $backupDriverId,
                    Carbon::parse($schedule->schedule_date)->startOfMonth()->format('Y-m-d'),
                    Carbon::parse($schedule->schedule_date)->endOfMonth()->format('Y-m-d')
                );
            }
        }

        // Approve the leave request
        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        return redirect()->route('leave-requests.index')
            ->with('success', 'Leave request approved and backup drivers assigned successfully.');
    }
}

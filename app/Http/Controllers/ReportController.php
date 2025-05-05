<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Schedule;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ReportController extends Controller
{
    /**
     * Display the attendance report form.
     */
    public function index()
    {
        $drivers = Driver::all();
        return view('modules.admin.reports.index', compact('drivers'));
    }

    /**
     * Generate attendance report based on date range.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        // Get all days in the period
        $period = CarbonPeriod::create($startDate, $endDate);
        $totalDays = $period->count();
        
        // Filter drivers if specified
        $driversQuery = Driver::query();
        if (isset($validated['driver_id'])) {
            $driversQuery->where('id', $validated['driver_id']);
        }
        $drivers = $driversQuery->get();
        
        $reportData = [];
        
        foreach ($drivers as $driver) {
            // Get all schedules for this driver in the date range
            $schedules = Schedule::where('driver_id', $driver->id)
                ->whereBetween('schedule_date', [$startDate, $endDate])
                ->get();
                
            // Get all backup schedules for this driver in the date range
            $backupSchedules = Schedule::where('backup_driver_id', $driver->id)
                ->whereBetween('schedule_date', [$startDate, $endDate])
                ->get();
                
            // Get all leave requests for this driver in the date range
            $leaveRequests = LeaveRequest::where('driver_id', $driver->id)
                ->where('status', 'approved')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($query) use ($startDate, $endDate) {
                            $query->where('start_date', '<', $startDate)
                                ->where('end_date', '>', $endDate);
                        });
                })
                ->get();
                
            // Calculate leave days within the date range
            $leaveDays = 0;
            foreach ($leaveRequests as $leave) {
                $leaveStart = max($startDate, Carbon::parse($leave->start_date));
                $leaveEnd = min($endDate, Carbon::parse($leave->end_date));
                $leavePeriod = CarbonPeriod::create($leaveStart, $leaveEnd);
                $leaveDays += $leavePeriod->count();
            }
            
            // Count schedules by status
            $scheduledCount = $schedules->where('status', 'scheduled')->count();
            $completedCount = $schedules->where('status', 'completed')->count();
            $absentCount = $schedules->where('status', 'absent')->count();
            $onLeaveCount = $schedules->where('status', 'on_leave')->count();
            
            // Count backup schedules
            $backupCount = $backupSchedules->count();
            
            $reportData[] = [
                'driver' => $driver,
                'scheduled' => $scheduledCount,
                'completed' => $completedCount,
                'absent' => $absentCount,
                'on_leave' => $onLeaveCount,
                'backup' => $backupCount,
                'leave_days' => $leaveDays,
                'total_shifts' => $scheduledCount + $completedCount + $absentCount + $onLeaveCount,
            ];
        }
        
        return view('modules.admin.reports.result', compact('reportData', 'startDate', 'endDate', 'totalDays'));
    }

    /**
     * Export attendance report to CSV.
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'driver_id' => 'nullable|exists:drivers,id',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        
        // Filter drivers if specified
        $driversQuery = Driver::query();
        if (isset($validated['driver_id'])) {
            $driversQuery->where('id', $validated['driver_id']);
        }
        $drivers = $driversQuery->get();
        
        $filename = 'attendance_report_' . $startDate->format('Y-m-d') . '_to_' . $endDate->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function() use ($drivers, $startDate, $endDate) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'Driver ID',
                'Driver Name',
                'Driver Type',
                'Scheduled Shifts',
                'Completed Shifts',
                'Absent Shifts',
                'On Leave Shifts',
                'Backup Shifts',
                'Leave Days',
                'Total Shifts',
            ]);
            
            foreach ($drivers as $driver) {
                // Get all schedules for this driver in the date range
                $schedules = Schedule::where('driver_id', $driver->id)
                    ->whereBetween('schedule_date', [$startDate, $endDate])
                    ->get();
                    
                // Get all backup schedules for this driver in the date range
                $backupSchedules = Schedule::where('backup_driver_id', $driver->id)
                    ->whereBetween('schedule_date', [$startDate, $endDate])
                    ->get();
                    
                // Get all leave requests for this driver in the date range
                $leaveRequests = LeaveRequest::where('driver_id', $driver->id)
                    ->where('status', 'approved')
                    ->where(function ($query) use ($startDate, $endDate) {
                        $query->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($query) use ($startDate, $endDate) {
                                $query->where('start_date', '<', $startDate)
                                    ->where('end_date', '>', $endDate);
                            });
                    })
                    ->get();
                    
                // Calculate leave days within the date range
                $leaveDays = 0;
                foreach ($leaveRequests as $leave) {
                    $leaveStart = max($startDate, Carbon::parse($leave->start_date));
                    $leaveEnd = min($endDate, Carbon::parse($leave->end_date));
                    $leavePeriod = CarbonPeriod::create($leaveStart, $leaveEnd);
                    $leaveDays += $leavePeriod->count();
                }
                
                // Count schedules by status
                $scheduledCount = $schedules->where('status', 'scheduled')->count();
                $completedCount = $schedules->where('status', 'completed')->count();
                $absentCount = $schedules->where('status', 'absent')->count();
                $onLeaveCount = $schedules->where('status', 'on_leave')->count();
                
                // Count backup schedules
                $backupCount = $backupSchedules->count();
                
                // Add row to CSV
                fputcsv($file, [
                    $driver->unique_id,
                    $driver->name,
                    $driver->type,
                    $scheduledCount,
                    $completedCount,
                    $absentCount,
                    $onLeaveCount,
                    $backupCount,
                    $leaveDays,
                    $scheduledCount + $completedCount + $absentCount + $onLeaveCount,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}

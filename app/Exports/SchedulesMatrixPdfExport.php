<?php

namespace App\Exports;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Unit;
use App\Models\Schedule;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SchedulesMatrixPdfExport
{
    protected $month;
    protected $year;
    protected $period;
    protected $schedules;
    protected $startDate;
    protected $endDate;
    protected $dateRange;
    protected $routeUnitDrivers;
    protected $allDrivers;
    protected $unassignedDrivers;

    /**
     * Constructor
     * 
     * @param int $month Month number (1-12)
     * @param int $year Year (e.g., 2025)
     * @param int $period Period (1 or 2) - 1 = days 1-15, 2 = days 16-end of month
     * @param Collection|null $filteredSchedules Pre-filtered schedules (optional)
     */
    public function __construct($month, $year, $period = 1, $filteredSchedules = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->period = $period;
        
        // Calculate date range based on period
        $this->calculateDateRange();
        
        // Use pre-filtered schedules if provided, otherwise fetch all schedules for the date range
        if ($filteredSchedules !== null) {
            $this->schedules = $filteredSchedules;
        } else {
            // Fetch schedules for the date range (fallback)
            $this->fetchSchedules();
        }
        
        // Get all active drivers
        $this->fetchAllDrivers();
        
        // Build the route-unit-driver matrix
        $this->buildRouteUnitDriverMatrix();
        
        // Find unassigned drivers
        $this->findUnassignedDrivers();
    }

    /**
     * Calculate the date range based on period
     */
    protected function calculateDateRange()
    {
        // For period 1: days 1-15
        if ($this->period == 1) {
            $this->startDate = Carbon::createFromDate($this->year, $this->month, 1);
            $this->endDate = Carbon::createFromDate($this->year, $this->month, 15);
        } 
        // For period 2: days 16-end of month
        else {
            $this->startDate = Carbon::createFromDate($this->year, $this->month, 16);
            $this->endDate = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();
        }
        
        // Create array of dates in the range
        $this->dateRange = [];
        $currentDate = clone $this->startDate;
        
        while ($currentDate->lte($this->endDate)) {
            $this->dateRange[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
    }

    /**
     * Fetch schedules for the date range
     */
    protected function fetchSchedules()
    {
        $this->schedules = Schedule::with(['driver', 'backupDriver', 'route', 'unit'])
            ->whereBetween('schedule_date', [
                $this->startDate->format('Y-m-d'), 
                $this->endDate->format('Y-m-d')
            ])
            ->where('status', 'scheduled') // Only include schedules with status = 'scheduled'
            ->get();
    }
    
    /**
     * Fetch all active drivers
     */
    protected function fetchAllDrivers()
    {
        $this->allDrivers = Driver::where('status', 'aktif')
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Build the route-unit-driver matrix
     */
    protected function buildRouteUnitDriverMatrix()
    {
        $this->routeUnitDrivers = [];
        
        // Get all units and routes that have schedules in this period
        $unitIds = $this->schedules->pluck('unit_id')->unique()->toArray();
        $routeIds = $this->schedules->pluck('route_id')->unique()->toArray();
        
        $units = Unit::whereIn('id', $unitIds)->orderBy('unit_number')->get();
        $routes = Route::whereIn('id', $routeIds)->orderBy('route_number')->get();
        
        // Group schedules by unit, route, driver and shift
        foreach ($units as $unit) {
            foreach ($routes as $route) {
                // Check if this unit-route combination has any schedules
                $unitRouteSchedules = $this->schedules->filter(function ($schedule) use ($unit, $route) {
                    return $schedule->unit_id == $unit->id && $schedule->route_id == $route->id;
                });
                
                if ($unitRouteSchedules->isEmpty()) {
                    continue;
                }
                
                // Group by driver and shift
                $driverShifts = [];
                
                foreach ($unitRouteSchedules as $schedule) {
                    $driverId = $schedule->driver_id;
                    $shift = $schedule->shift;
                    $date = $schedule->schedule_date->format('Y-m-d');
                    
                    // Initialize if not exists
                    if (!isset($driverShifts[$driverId][$shift])) {
                        $driverShifts[$driverId][$shift] = [
                            'driver' => $schedule->driver,
                            'dates' => []
                        ];
                    }
                    
                    // Add date to the driver-shift combination
                    $driverShifts[$driverId][$shift]['dates'][] = $date;
                    
                    // Also handle backup driver if present
                    if ($schedule->backup_driver_id) {
                        $backupDriverId = $schedule->backup_driver_id;
                        
                        // Initialize if not exists
                        if (!isset($driverShifts[$backupDriverId][$shift])) {
                            $driverShifts[$backupDriverId][$shift] = [
                                'driver' => $schedule->backupDriver,
                                'dates' => [],
                                'backup_dates' => []
                            ];
                        } else if (!isset($driverShifts[$backupDriverId][$shift]['backup_dates'])) {
                            $driverShifts[$backupDriverId][$shift]['backup_dates'] = [];
                        }
                        
                        // Add date to the backup driver-shift combination
                        $driverShifts[$backupDriverId][$shift]['backup_dates'][] = $date;
                    }
                }
                
                // Add to the matrix
                $this->routeUnitDrivers[] = [
                    'unit' => $unit,
                    'route' => $route,
                    'driver_shifts' => $driverShifts
                ];
            }
        }
    }
    
    /**
     * Find drivers that are not assigned to any schedule in this period
     */
    protected function findUnassignedDrivers()
    {
        // Get all driver IDs that have schedules in this period
        $assignedDriverIds = collect();
        
        foreach ($this->routeUnitDrivers as $routeUnitDriver) {
            foreach ($routeUnitDriver['driver_shifts'] as $driverId => $shifts) {
                $assignedDriverIds->push($driverId);
            }
        }
        
        $assignedDriverIds = $assignedDriverIds->unique();
        
        // Find unassigned drivers
        $this->unassignedDrivers = $this->allDrivers->whereNotIn('id', $assignedDriverIds->toArray());
    }

    /**
     * Get the date headers for the matrix
     */
    protected function getDateHeaders()
    {
        $headers = [];
        foreach ($this->dateRange as $date) {
            $headers[$date] = Carbon::parse($date)->format('d');
        }
        return $headers;
    }

    /**
     * Download the PDF
     */
    public function download()
    {
        $dateHeaders = $this->getDateHeaders();
        
        $monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('F');
        $periodText = 'Periode ' . $this->period . ' (' . $this->startDate->format('d') . '-' . $this->endDate->format('d') . ' ' . $monthName . ' ' . $this->year . ')';
        
        $data = [
            'month' => $this->month,
            'year' => $this->year,
            'period' => $this->period,
            'periodText' => $periodText,
            'monthName' => $monthName,
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
            'dateRange' => $this->dateRange,
            'dateHeaders' => $dateHeaders,
            'routeUnitDrivers' => $this->routeUnitDrivers,
            'unassignedDrivers' => $this->unassignedDrivers,
            'totalPages' => 1, // We're not paginating anymore
            'totalRouteUnitDriverPages' => 1, // For backward compatibility
        ];

        $pdf = PDF::loadView('exports.schedules-matrix-pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        $filename = 'jadwal-matrix-' . $monthName . '-' . $this->year . '-periode-' . $this->period;
        
        return $pdf->download($filename . '.pdf');
    }
}

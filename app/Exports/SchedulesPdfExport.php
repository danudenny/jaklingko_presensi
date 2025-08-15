<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Barryvdh\DomPDF\Facade\Pdf;

class SchedulesPdfExport
{
    protected $schedules;
    protected $startDate;
    protected $endDate;
    protected $filters;

    public function __construct($schedules, $startDate, $endDate, $filters = [])
    {
        $this->schedules = $schedules;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->filters = $filters;
    }

    public function download()
    {
        // Group schedules by unit for better organization and performance
        // Also include route information for header format
        $schedulesByUnit = $this->schedules->groupBy(function($schedule) {
            return $schedule->unit->unit_number;
        })->sortKeys();
        
        // Calculate counts more efficiently in single pass
        $morningCount = 0;
        $eveningCount = 0;
        $batanganCount = 0;
        $cadanganCount = 0;
        
        foreach ($this->schedules as $schedule) {
            // Count shifts
            if ($schedule->shift == 'pagi' || $schedule->shift == 'morning') {
                $morningCount++;
            } else {
                $eveningCount++;
            }
            
            // Count driver types (with null check)
            if ($schedule->driver) {
                if ($schedule->driver->type == 'batangan') {
                    $batanganCount++;
                } elseif ($schedule->driver->type == 'cadangan') {
                    $cadanganCount++;
                }
            }
        }
        
        $data = [
            'schedulesByUnit' => $schedulesByUnit,
            'totalSchedules' => $this->schedules->count(),
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'filters' => $this->filters,
            'morningCount' => $morningCount,
            'eveningCount' => $eveningCount,
            'batanganCount' => $batanganCount,
            'cadanganCount' => $cadanganCount,
        ];

        $pdf = PDF::loadView('exports.schedules-pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('jadwal-' . $this->startDate . '-' . $this->endDate . '.pdf');
    }
}

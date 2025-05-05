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
        $data = [
            'schedules' => $this->schedules,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'filters' => $this->filters,
            'morningCount' => $this->schedules->where('shift', 'pagi')->count() + $this->schedules->where('shift', 'morning')->count(),
            'eveningCount' => $this->schedules->where('shift', 'siang')->count() + $this->schedules->where('shift', 'evening')->count(),
            'batanganCount' => $this->schedules->filter(function($schedule) {
                return $schedule->driver->type == 'batangan';
            })->count(),
            'cadanganCount' => $this->schedules->filter(function($schedule) {
                return $schedule->driver->type == 'cadangan';
            })->count(),
        ];

        $pdf = PDF::loadView('exports.schedules-pdf', $data);
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('jadwal-' . $this->startDate . '-' . $this->endDate . '.pdf');
    }
}

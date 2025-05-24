<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\GlobalKilometerReport;
use App\Models\KilometerReport;
use App\Models\Route;
use App\Models\Schedule;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalKilometerReportGeneratorController extends Controller
{
    /**
     * Show the form for generating global kilometer reports.
     *
     * @return \Illuminate\View\View
     */
    public function showGenerateForm()
    {
        $currentYear = Carbon::now()->year;
        $years = range($currentYear - 2, $currentYear + 1);
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        
        return view('modules.admin.global-kilometer-reports.generate', compact('years', 'months'));
    }
    
    /**
     * Generate global kilometer reports based on provided parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2050',
            'month' => 'required|integer|min:1|max:12',
            'period' => 'required|integer|in:1,2',
        ]);
        
        $year = (int)$validated['year'];
        $month = (int)$validated['month'];
        $period = (int)$validated['period'];
        
        // Determine date range based on period
        $startDate = Carbon::createFromDate($year, $month, $period == 1 ? 1 : 16);
        $endDate = $period == 1 
            ? Carbon::createFromDate($year, $month, 15)
            : Carbon::createFromDate($year, $month)->endOfMonth();
            
        try {
            // Begin transaction
            DB::beginTransaction();
            
            // Delete any existing global kilometer reports for this period
            GlobalKilometerReport::where([
                'year' => $year,
                'month' => $month,
                'period' => $period,
            ])->delete();
            
            // Generate new reports
            $this->generateReports($startDate, $endDate, $period, $month, $year);
            
            // Commit transaction
            DB::commit();
            
            return redirect()->route('global-kilometer-reports.index', ['period' => $period, 'group' => 'all'])
                ->with('success', 'Laporan kilometer global berhasil dibuat untuk periode ' . 
                    ($period == 1 ? '1 (1-15)' : '2 (16-' . $endDate->day . ')') . 
                    ' ' . Carbon::create()->month($month)->translatedFormat('F') . ' ' . $year);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat laporan kilometer global. Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate reports for the given date range.
     *
     * @param  \Carbon\Carbon  $startDate
     * @param  \Carbon\Carbon  $endDate
     * @param  int  $period
     * @param  int  $month
     * @param  int  $year
     * @return void
     */
    private function generateReports(Carbon $startDate, Carbon $endDate, $period, $month, $year)
    {
        $dates = [];
        $currentDate = $startDate->copy();
        
        // Prepare all dates in the range
        while ($currentDate->lte($endDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all kilometer reports for the date range
        $reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        // Get all schedules for the date range
        $schedules = Schedule::with(['driver', 'unit', 'route'])
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->get();
        
        // Group schedules by unit and date
        $schedulesByUnitDate = [];
        foreach ($schedules as $schedule) {
            $unitId = $schedule->unit_id;
            $date = $schedule->schedule_date->format('Y-m-d');
            
            if (!isset($schedulesByUnitDate[$unitId])) {
                $schedulesByUnitDate[$unitId] = [];
            }
            if (!isset($schedulesByUnitDate[$unitId][$date])) {
                $schedulesByUnitDate[$unitId][$date] = [];
            }
            
            $schedulesByUnitDate[$unitId][$date][] = $schedule;
        }
        
        // Process each kilometer report
        foreach ($reports as $report) {
            $unitId = $report->unit_id;
            $routeId = $report->route_id;
            $date = $report->date->format('Y-m-d');
            $kilometers = $report->kilometers;
            
            // Get schedules for this unit and date
            $unitSchedules = $schedulesByUnitDate[$unitId][$date] ?? [];
            $driverCount = count($unitSchedules);
            
            // If no drivers scheduled, continue to next report
            if ($driverCount == 0) {
                continue;
            }
            
            // Calculate kilometers per driver
            $kilometersPerDriver = $driverCount > 0 ? $kilometers / $driverCount : 0;
            
            // For each driver, create a global kilometer report
            foreach ($unitSchedules as $schedule) {
                $driverId = $schedule->driver_id;
                
                GlobalKilometerReport::create([
                    'driver_id' => $driverId,
                    'unit_id' => $unitId,
                    'route_id' => $routeId,
                    'report_date' => $date,
                    'kilometers' => $kilometersPerDriver,
                    'period' => $period,
                    'month' => $month,
                    'year' => $year,
                    'driver_count' => $driverCount,
                    'notes' => $report->notes,
                ]);
            }
        }
    }
}

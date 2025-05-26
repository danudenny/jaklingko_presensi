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
    
    private function generateReports(Carbon $startDate, Carbon $endDate, $period, $month, $year)
    {
        // Delete existing reports for this period to avoid conflicts
        GlobalKilometerReport::where([
            'period' => $period,
            'month' => $month,
            'year' => $year
        ])->delete();

        // Get all kilometer reports for the date range, making sure to include full days
        $kilometerReports = KilometerReport::with(['unit', 'route'])
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->get();

        // Get all schedules for the date range
        $schedules = Schedule::with(['driver', 'unit', 'route'])
            ->whereDate('schedule_date', '>=', $startDate)
            ->whereDate('schedule_date', '<=', $endDate)
            ->get();

        if ($kilometerReports->isEmpty() || $schedules->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'message' => 'No reports or schedules found'];
        }

        // Group kilometer reports by unit_id and date for easy lookup
        $kilometerByUnitDate = [];
        foreach ($kilometerReports as $kilometerReport) {
            $unitId = $kilometerReport->unit_id;
            $date = $kilometerReport->date->format('Y-m-d');
            $kilometerByUnitDate[$unitId][$date] = $kilometerReport;
        }

        // Group schedules by unit_id and date for easy lookup
        $schedulesByUnitDate = [];
        foreach ($schedules as $schedule) {
            $unitId = $schedule->unit_id;
            $date = $schedule->schedule_date->format('Y-m-d');
            $shift = $schedule->shift;
            $schedulesByUnitDate[$unitId][$date][$shift] = $schedule;
        }

        $reportCount = 0;
        $skippedCount = 0;

        // Process each unit's kilometer reports
        foreach ($kilometerByUnitDate as $unitId => $unitReports) {
            foreach ($unitReports as $date => $kilometerReport) {
                // Skip if no schedules for this unit and date
                if (!isset($schedulesByUnitDate[$unitId][$date])) {
                    $skippedCount++;
                    continue;
                }

                $daySchedules = $schedulesByUnitDate[$unitId][$date];
                $totalKilometers = $kilometerReport->kilometers;
                $routeId = $kilometerReport->route_id;
                $notes = $kilometerReport->notes ?? '';

                // Divide kilometers by 2 since we always have 2 shifts
                $kilometerPerShift = $totalKilometers / 2;

                // Create reports for both shifts if they exist
                foreach (['pagi', 'siang'] as $shift) {
                    if (!isset($daySchedules[$shift])) {
                        continue;
                    }

                    $schedule = $daySchedules[$shift];
                    $driverId = $schedule->driver_id;

                    if (!$driverId) {
                        continue;
                    }

                    try {
                        // Create new global kilometer report
                        GlobalKilometerReport::create([
                            'driver_id' => $driverId,
                            'unit_id' => $unitId,
                            'route_id' => $routeId,
                            'report_date' => $date,
                            'shift' => $shift,
                            'period' => $period,
                            'month' => $month,
                            'year' => $year,
                            'kilometers' => $kilometerPerShift,
                            'driver_count' => $shift === 'pagi' ? 1 : 2, // First driver gets 1, second gets 2
                            'notes' => $notes,
                        ]);

                        $reportCount++;

                    } catch (\Exception $e) {
                        throw new \Exception('Error creating GlobalKilometerReport: ' . $e->getMessage());
                    }
                }
            }
        }

        return [
            'created' => $reportCount,
            'skipped' => $skippedCount
        ];
    }
}

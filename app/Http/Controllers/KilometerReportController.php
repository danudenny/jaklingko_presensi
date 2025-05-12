<?php

namespace App\Http\Controllers;

use App\Models\KilometerReport;
use App\Models\Route;
use App\Models\Unit;
use App\Exports\KilometerReportsExport;
use App\Exports\KilometerReportsPdfExport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class KilometerReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $period = $request->input('period', 1); // Default to period 1
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all routes with their assigned units
        $allRoutes = Route::with(['units' => function($query) {
            $query->with('drivers');
        }])->orderBy('route_number')->get();
        
        // Group routes by their exact route_number
        $routeGroups = [];
        $routesByGroup = [];
        
        foreach ($allRoutes as $route) {
            $routeNumber = $route->route_number;
            
            // Skip empty route numbers
            if (empty($routeNumber)) {
                continue;
            }
            
            if (!in_array($routeNumber, $routeGroups)) {
                $routeGroups[] = $routeNumber;
            }
            
            if (!isset($routesByGroup[$routeNumber])) {
                $routesByGroup[$routeNumber] = [];
            }
            
            $routesByGroup[$routeNumber][] = $route;
        }
        
        // Sort route groups alphabetically
        sort($routeGroups);
        
        // Default to first route group if available, otherwise use 'all'
        $defaultGroup = !empty($routeGroups) ? $routeGroups[0] : 'all';
        $activeRouteGroup = $request->input('group', $defaultGroup);
        
        // Move 'all' tab to the end
        $routeGroups = array_merge(array_filter($routeGroups, function($group) {
            return $group !== 'all';
        }), ['all']);
        
        // Filter routes based on the active group
        if ($activeRouteGroup !== 'all') {
            $routes = collect($routesByGroup[$activeRouteGroup] ?? []);
        } else {
            $routes = $allRoutes;
        }
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get holidays for the date range
        $holidays = \App\Models\Holiday::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(function($holiday) {
                return $holiday->date->format('Y-m-d');
            });
            
        // Get maintenance units for the date range using MaintenanceLog
        $maintenanceUnitsByDate = [];
        $maintenanceLogs = \App\Models\MaintenanceLog::whereBetween('date_reported', [$startDate, $endDate])
            ->where('status', 'ongoing')
            ->get();
            
        foreach ($maintenanceLogs as $maintenance) {
            $date = $maintenance->date_reported->format('Y-m-d');
            if (!isset($maintenanceUnitsByDate[$date])) {
                $maintenanceUnitsByDate[$date] = [];
            }
            $maintenanceUnitsByDate[$date][] = $maintenance->unit_id;
        }
        
        // Also check for unit problems that might indicate maintenance
        $unitProblems = \App\Models\UnitProblem::whereBetween('date_reported', [$startDate, $endDate])
            ->get();
            
        foreach ($unitProblems as $problem) {
            $date = $problem->date_reported->format('Y-m-d');
            if (!isset($maintenanceUnitsByDate[$date])) {
                $maintenanceUnitsByDate[$date] = [];
            }
            if (!in_array($problem->unit_id, $maintenanceUnitsByDate[$date])) {
                $maintenanceUnitsByDate[$date][] = $problem->unit_id;
            }
        }
        
        // Get all kilometer reports for the date range
        $reports = KilometerReport::with(['unit', 'route'])
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        // Group reports by route, unit, and date
        $reportsByRouteUnitDate = [];
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $unitId = $report->unit_id;
            $date = $report->date->format('Y-m-d');
            
            if (!isset($reportsByRouteUnitDate[$routeId])) {
                $reportsByRouteUnitDate[$routeId] = [];
            }
            
            if (!isset($reportsByRouteUnitDate[$routeId][$unitId])) {
                $reportsByRouteUnitDate[$routeId][$unitId] = [];
            }
            
            $reportsByRouteUnitDate[$routeId][$unitId][$date] = $report;
        }
        
        // Calculate totals
        $routeTotals = [];
        $unitTotals = [];
        $dateTotals = [];
        $routeUnitTotals = [];
        $grandTotal = 0;
        
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $unitId = $report->unit_id;
            $date = $report->date->format('Y-m-d');
            $kilometers = $report->kilometers;
            
            // Route totals
            if (!isset($routeTotals[$routeId])) {
                $routeTotals[$routeId] = 0;
            }
            $routeTotals[$routeId] += $kilometers;
            
            // Unit totals
            if (!isset($unitTotals[$unitId])) {
                $unitTotals[$unitId] = 0;
            }
            $unitTotals[$unitId] += $kilometers;
            
            // Date totals
            if (!isset($dateTotals[$date])) {
                $dateTotals[$date] = 0;
            }
            $dateTotals[$date] += $kilometers;
            
            // Route-Unit totals
            if (!isset($routeUnitTotals[$routeId])) {
                $routeUnitTotals[$routeId] = [];
            }
            if (!isset($routeUnitTotals[$routeId][$unitId])) {
                $routeUnitTotals[$routeId][$unitId] = 0;
            }
            $routeUnitTotals[$routeId][$unitId] += $kilometers;
            
            // Grand total
            $grandTotal += $kilometers;
        }
        
        return view('modules.admin.kilometer-reports.index', compact(
            'routes',
            'dates', 
            'reportsByRouteUnitDate', 
            'routeTotals', 
            'unitTotals', 
            'dateTotals',
            'routeUnitTotals',
            'grandTotal',
            'startDate',
            'endDate',
            'period',
            'routeGroups',
            'activeRouteGroup',
            'holidays',
            'maintenanceUnitsByDate'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'route_id' => 'required|exists:routes,id',
            'date' => 'required|date',
            'kilometers' => 'required|numeric|min:0|max:999.9',
        ]);
        
        // Check if record already exists
        $existingReport = KilometerReport::where('unit_id', $request->unit_id)
            ->where('route_id', $request->route_id)
            ->where('date', $request->date)
            ->first();
            
        if ($existingReport) {
            // Update existing record
            $existingReport->update([
                'kilometers' => $request->kilometers,
                'notes' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Data kilometer berhasil diperbarui',
                'report' => $existingReport
            ]);
        } else {
            // Create new record
            $report = KilometerReport::create([
                'unit_id' => $request->unit_id,
                'route_id' => $request->route_id,
                'date' => $request->date,
                'kilometers' => $request->kilometers,
                'notes' => $request->notes,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Data kilometer berhasil disimpan',
                'report' => $report
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, $unitId)
    {
        $unit = Unit::findOrFail($unitId);
        $period = $request->input('period', 1); // Default to period 1
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Get all routes for this unit
        $routes = $unit->routes;
        
        // Get all kilometer reports for this unit and date range
        $reports = KilometerReport::with(['route'])
            ->where('unit_id', $unitId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();
        
        // Group reports by route and date
        $reportsByRouteAndDate = [];
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $date = $report->date->format('Y-m-d');
            
            if (!isset($reportsByRouteAndDate[$routeId])) {
                $reportsByRouteAndDate[$routeId] = [];
            }
            
            $reportsByRouteAndDate[$routeId][$date] = $report;
        }
        
        // Calculate totals
        $routeTotals = [];
        $dateTotals = [];
        $grandTotal = 0;
        
        foreach ($reports as $report) {
            $routeId = $report->route_id;
            $date = $report->date->format('Y-m-d');
            $kilometers = $report->kilometers;
            
            // Route totals
            if (!isset($routeTotals[$routeId])) {
                $routeTotals[$routeId] = 0;
            }
            $routeTotals[$routeId] += $kilometers;
            
            // Date totals
            if (!isset($dateTotals[$date])) {
                $dateTotals[$date] = 0;
            }
            $dateTotals[$date] += $kilometers;
            
            // Grand total
            $grandTotal += $kilometers;
        }
        
        // Get related unit problems
        $unitProblems = $unit->unitProblems()
            ->whereBetween('date_reported', [$startDate, $endDate])
            ->orderBy('date_reported')
            ->get();
        
        // Get related schedules
        $schedules = $unit->schedules()
            ->whereBetween('schedule_date', [$startDate, $endDate])
            ->orderBy('schedule_date')
            ->get();
        
        return view('modules.admin.kilometer-reports.show', compact(
            'unit', 
            'routes', 
            'dates', 
            'reportsByRouteAndDate', 
            'routeTotals', 
            'dateTotals', 
            'grandTotal',
            'startDate',
            'endDate',
            'unitProblems',
            'schedules',
            'period'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Export kilometer reports to Excel
     */
    public function exportExcel(Request $request)
    {
        $period = $request->input('period', 1);
        $group = $request->input('group', 'all');
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        $title = "Laporan Kilometer - Periode " . ($period == 1 ? "1 (1-15)" : "2 (16-" . $currentMonth->copy()->endOfMonth()->format('d') . ")") . " " . $today->format('F Y');
        
        if ($group !== 'all') {
            $title .= " - Rute " . $group;
        }
        
        return Excel::download(new KilometerReportsExport($startDate, $endDate, $group), $title . '.xlsx');
    }
    
    /**
     * Export kilometer reports to PDF
     */
    public function exportPdf(Request $request)
    {
        $period = $request->input('period', 1);
        $group = $request->input('group', 'all');
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        $title = "Laporan Kilometer - Periode " . ($period == 1 ? "1 (1-15)" : "2 (16-" . $currentMonth->copy()->endOfMonth()->format('d') . ")") . " " . $today->format('F Y');
        
        if ($group !== 'all') {
            $title .= " - Rute " . $group;
        }
        
        return (new KilometerReportsPdfExport($startDate, $endDate, $group))->download($title . '.pdf');
    }

    /**
     * Download Excel template for kilometer report import.
     */
    public function downloadTemplate(Request $request)
    {
        $period = $request->input('period', 1); // Default to period 1
        $activeRouteGroup = $request->input('group', 'all'); // Default to all groups
        
        // Determine date ranges based on period
        $today = Carbon::now();
        $currentMonth = $today->copy()->startOfMonth();
        
        if ($period == 1) {
            // Period 1: 1st to 15th of the month
            $startDate = $currentMonth->copy()->format('Y-m-d');
            $endDate = $currentMonth->copy()->addDays(14)->format('Y-m-d');
        } else {
            // Period 2: 16th to end of month
            $startDate = $currentMonth->copy()->addDays(15)->format('Y-m-d');
            $endDate = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
        }
        
        // Get all routes with their assigned units
        if ($activeRouteGroup !== 'all') {
            $routes = Route::where('route_number', $activeRouteGroup)
                ->with('units')
                ->orderBy('route_number')
                ->get();
        } else {
            $routes = Route::with('units')
                ->orderBy('route_number')
                ->get();
        }
        
        // Get all dates in the range
        $dates = [];
        $currentDate = Carbon::parse($startDate);
        $lastDate = Carbon::parse($endDate);
        
        while ($currentDate->lte($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }
        
        // Create a new spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kilometer Reports Template');
        
        // Set headers
        $sheet->setCellValue('A1', 'Route ID');
        $sheet->setCellValue('B1', 'Route Number');
        $sheet->setCellValue('C1', 'Unit ID');
        $sheet->setCellValue('D1', 'Unit Number');
        $sheet->setCellValue('E1', 'Date');
        $sheet->setCellValue('F1', 'Kilometers');
        $sheet->setCellValue('G1', 'Notes');
        
        // Style the header row
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
        
        // Populate data rows
        $row = 2;
        foreach ($routes as $route) {
            foreach ($route->units as $unit) {
                foreach ($dates as $date) {
                    $sheet->setCellValue('A' . $row, $route->id);
                    $sheet->setCellValue('B' . $row, $route->route_number . ' - ' . $route->name);
                    $sheet->setCellValue('C' . $row, $unit->id);
                    $sheet->setCellValue('D' . $row, $unit->unit_number . ' - ' . $unit->plate_number);
                    $sheet->setCellValue('E' . $row, $date);
                    $sheet->setCellValue('F' . $row, ''); // Empty for user to fill
                    $sheet->setCellValue('G' . $row, ''); // Empty for user to fill
                    $row++;
                }
            }
        }
        
        // Auto-size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Create a writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Set the filename
        $periodText = $period == 1 ? '1-15' : '16-' . $today->copy()->endOfMonth()->format('d');
        $filename = 'kilometer_report_template_' . $today->format('Y_m') . '_period_' . $periodText . '.xlsx';
        
        // Create response with headers
        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function() use ($writer) {
            $writer->save('php://output');
        });
        
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');
        
        return $response;
    }
    
    /**
     * Import kilometer reports from Excel file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:xlsx,xls',
            'period' => 'required|in:1,2',
            'group' => 'required',
        ]);
        
        try {
            // Load the Excel file
            $file = $request->file('import_file');
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // Remove header row
            array_shift($rows);
            
            // Start transaction
            DB::beginTransaction();
            
            $importCount = 0;
            $updateCount = 0;
            $errorCount = 0;
            
            foreach ($rows as $row) {
                // Skip empty rows
                if (empty($row[0]) || empty($row[2]) || empty($row[4]) || $row[5] === '') {
                    continue;
                }
                
                $routeId = $row[0];
                $unitId = $row[2];
                $date = $row[4];
                $kilometers = $row[5];
                $notes = $row[6] ?? null;
                
                // Validate data
                if (!is_numeric($kilometers) || $kilometers < 0 || $kilometers > 999.9) {
                    $errorCount++;
                    continue;
                }
                
                try {
                    // Check if record already exists
                    $existingReport = KilometerReport::where('unit_id', $unitId)
                        ->where('route_id', $routeId)
                        ->where('date', $date)
                        ->first();
                        
                    if ($existingReport) {
                        // Update existing record
                        $existingReport->update([
                            'kilometers' => $kilometers,
                            'notes' => $notes,
                        ]);
                        $updateCount++;
                    } else {
                        // Create new record
                        KilometerReport::create([
                            'unit_id' => $unitId,
                            'route_id' => $routeId,
                            'date' => $date,
                            'kilometers' => $kilometers,
                            'notes' => $notes,
                        ]);
                        $importCount++;
                    }
                } catch (\Exception $e) {
                    $errorCount++;
                }
            }
            
            DB::commit();
            
            $message = "Import berhasil: {$importCount} data baru, {$updateCount} data diperbarui";
            if ($errorCount > 0) {
                $message .= ", {$errorCount} data gagal diimpor";
            }
            
            return redirect()->route('kilometer-reports.index', [
                'period' => $request->period,
                'group' => $request->group
            ])->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->route('kilometer-reports.index', [
                'period' => $request->period,
                'group' => $request->group
            ])->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }
}

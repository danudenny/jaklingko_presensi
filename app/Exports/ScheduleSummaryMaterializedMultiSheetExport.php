<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Services\ScheduleSummaryMaterializedService;
use Illuminate\Support\Facades\Log;

class ScheduleSummaryMaterializedMultiSheetExport implements WithMultipleSheets
{
    protected $startDate;
    protected $endDate;
    protected $routeId;
    protected $unitIds;
    protected $driverType;
    protected $materializedService;

    public function __construct($startDate = null, $endDate = null, $routeId = null, $unitIds = null, $driverType = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->routeId = $routeId;
        $this->unitIds = $unitIds;
        $this->driverType = $driverType;
        $this->materializedService = new ScheduleSummaryMaterializedService();
    }

    public function sheets(): array
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);
        
        Log::info('Starting multi-sheet materialized export', [
            'memory_start' => $memoryStart,
            'filters' => [
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'route_id' => $this->routeId,
                'unit_ids' => $this->unitIds,
                'driver_type' => $this->driverType
            ]
        ]);

        // Check if materialized data is fresh
        if (!$this->materializedService->isMaterializedDataFresh()) {
            Log::warning('Materialized data is stale, consider refreshing');
        }

        // Get data grouped by month
        $monthlyData = $this->materializedService->getSummaryDataGroupedByMonth(
            $this->startDate,
            $this->endDate,
            $this->routeId,
            $this->unitIds,
            $this->driverType
        );

        $sheets = [];
        $totalRecords = 0;
        
        foreach ($monthlyData as $monthKey => $monthInfo) {
            $sheets[] = new ScheduleSummaryMonthlySheet(
                $monthInfo['month_name'],
                $monthInfo['data']
            );
            $totalRecords += count($monthInfo['data']);
        }

        // If no data found, create a single empty sheet
        if (empty($sheets)) {
            $sheets[] = new ScheduleSummaryMonthlySheet('No Data', []);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        $memoryUsed = memory_get_usage(true) - $memoryStart;

        Log::info('Multi-sheet materialized export completed', [
            'sheets_created' => count($sheets),
            'total_records' => $totalRecords,
            'execution_time_ms' => round($executionTime, 2),
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ]);

        return $sheets;
    }
}

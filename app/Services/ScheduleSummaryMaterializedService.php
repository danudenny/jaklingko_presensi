<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleSummaryMaterializedService
{
    /**
     * Refresh the materialized summary table with latest data
     * This should be called periodically (daily/hourly) or on-demand
     */
    public function refreshMaterializedData()
    {
        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);
        
        Log::info('Starting materialized summary refresh');

        try {
            // Clear existing materialized data
            DB::table('schedule_summary_materialized')->truncate();
            
            // Insert fresh aggregated data (monthly aggregation)
            DB::statement("
                INSERT INTO schedule_summary_materialized 
                (driver_id, driver_name, driver_type, driver_rekening, route_id, route_name, unit_id, unit_number, year, month, total_days, created_at, updated_at)
                SELECT 
                    s.driver_id,
                    d.name as driver_name,
                    d.type as driver_type,
                    d.rekening as driver_rekening,
                    s.route_id,
                    r.name as route_name,
                    s.unit_id,
                    u.unit_number,
                    EXTRACT(YEAR FROM s.schedule_date) as year,
                    EXTRACT(MONTH FROM s.schedule_date) as month,
                    COUNT(*) as total_days,
                    NOW() as created_at,
                    NOW() as updated_at
                FROM schedules s
                JOIN drivers d ON s.driver_id = d.id
                JOIN routes r ON s.route_id = r.id
                JOIN units u ON s.unit_id = u.id
                WHERE s.status = 'scheduled'
                GROUP BY s.driver_id, d.name, d.type, d.rekening, s.route_id, r.name, s.unit_id, u.unit_number, EXTRACT(YEAR FROM s.schedule_date), EXTRACT(MONTH FROM s.schedule_date)
                ORDER BY year DESC, month DESC, d.name, u.unit_number
            ");
            
            $endTime = microtime(true);
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsed = memory_get_usage(true) - $memoryStart;
            $recordCount = DB::table('schedule_summary_materialized')->count();
            
            Log::info('Materialized summary refresh completed', [
                'execution_time_ms' => round($executionTime, 2),
                'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
                'records_processed' => $recordCount
            ]);
            
            return [
                'success' => true,
                'message' => "Materialized data refreshed successfully. Processed {$recordCount} records in " . round($executionTime, 2) . "ms",
                'records' => $recordCount,
                'execution_time_ms' => round($executionTime, 2)
            ];
            
        } catch (\Exception $e) {
            Log::error('Materialized summary refresh failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            throw $e; // Re-throw the exception for proper error handling
        }
    }
    
    /**
     * Get summary data from materialized table (super fast)
     * Returns data grouped by month for multi-sheet Excel export
     */
    public function getSummaryData($startDate = null, $endDate = null, $routeId = null, $unitIds = null, $driverType = null)
    {
        $query = DB::table('schedule_summary_materialized as sm')
            ->select([
                'sm.driver_id',
                'sm.driver_name',
                'sm.unit_number',
                'sm.route_name',
                'sm.driver_rekening',
                'sm.year',
                'sm.month',
                'sm.total_days'
            ]);

        // Apply date filters
        if ($startDate && $endDate) {
            $startYear = date('Y', strtotime($startDate));
            $startMonth = date('n', strtotime($startDate));
            $endYear = date('Y', strtotime($endDate));
            $endMonth = date('n', strtotime($endDate));
            
            $query->where(function($q) use ($startYear, $startMonth, $endYear, $endMonth) {
                $q->where(function($subQ) use ($startYear, $startMonth) {
                    $subQ->where('sm.year', '>', $startYear)
                         ->orWhere(function($innerQ) use ($startYear, $startMonth) {
                             $innerQ->where('sm.year', $startYear)
                                    ->where('sm.month', '>=', $startMonth);
                         });
                })->where(function($subQ) use ($endYear, $endMonth) {
                    $subQ->where('sm.year', '<', $endYear)
                         ->orWhere(function($innerQ) use ($endYear, $endMonth) {
                             $innerQ->where('sm.year', $endYear)
                                    ->where('sm.month', '<=', $endMonth);
                         });
                });
            });
        } elseif ($startDate) {
            $startYear = date('Y', strtotime($startDate));
            $startMonth = date('n', strtotime($startDate));
            $query->where(function($q) use ($startYear, $startMonth) {
                $q->where('sm.year', '>', $startYear)
                  ->orWhere(function($subQ) use ($startYear, $startMonth) {
                      $subQ->where('sm.year', $startYear)
                           ->where('sm.month', '>=', $startMonth);
                  });
            });
        } elseif ($endDate) {
            $endYear = date('Y', strtotime($endDate));
            $endMonth = date('n', strtotime($endDate));
            $query->where(function($q) use ($endYear, $endMonth) {
                $q->where('sm.year', '<', $endYear)
                  ->orWhere(function($subQ) use ($endYear, $endMonth) {
                      $subQ->where('sm.year', $endYear)
                           ->where('sm.month', '<=', $endMonth);
                  });
            });
        }

        // Apply other filters
        if ($routeId && $routeId !== 'all') {
            $query->where('sm.route_id', $routeId);
        }

        if ($unitIds && $unitIds !== 'all') {
            $unitIdsArray = is_array($unitIds) ? $unitIds : explode(',', $unitIds);
            $query->whereIn('sm.unit_id', $unitIdsArray);
        }

        if ($driverType) {
            $query->where('sm.driver_type', $driverType);
        }

        return $query->orderBy('sm.year', 'desc')
                    ->orderBy('sm.month', 'desc')
                    ->orderBy('sm.driver_name')
                    ->orderBy('sm.unit_number')
                    ->get();
    }
    
    /**
     * Get summary data grouped by month for multi-sheet export
     */
    public function getSummaryDataGroupedByMonth($startDate = null, $endDate = null, $routeId = null, $unitIds = null, $driverType = null)
    {
        $data = $this->getSummaryData($startDate, $endDate, $routeId, $unitIds, $driverType);
        
        $groupedData = [];
        foreach ($data as $row) {
            $monthKey = $row->year . '-' . str_pad($row->month, 2, '0', STR_PAD_LEFT);
            $monthName = date('F Y', mktime(0, 0, 0, $row->month, 1, $row->year));
            
            if (!isset($groupedData[$monthKey])) {
                $groupedData[$monthKey] = [
                    'month_name' => $monthName,
                    'year' => $row->year,
                    'month' => $row->month,
                    'data' => []
                ];
            }
            
            $groupedData[$monthKey]['data'][] = [
                'driver_id' => $row->driver_id,
                'driver_name' => $row->driver_name,
                'unit_number' => $row->unit_number,
                'route_name' => $row->route_name,
                'driver_rekening' => $row->driver_rekening ?? '',
                'total_days' => (int)$row->total_days
            ];
        }
        
        // Sort by year-month descending
        krsort($groupedData);
        
        return $groupedData;
    }
    
    /**
     * Check if materialized data exists and is recent
     */
    public function isMaterializedDataFresh($maxAgeHours = 24)
    {
        $latestRecord = DB::table('schedule_summary_materialized')
            ->orderBy('updated_at', 'desc')
            ->first();
            
        if (!$latestRecord) {
            return false;
        }
        
        $ageInHours = now()->diffInHours($latestRecord->updated_at);
        return $ageInHours <= $maxAgeHours;
    }
    
    /**
     * Get materialized data statistics
     */
    public function getDataStats()
    {
        $stats = DB::table('schedule_summary_materialized')
            ->selectRaw("
                COUNT(*) as total_records,
                MIN(year || '-' || LPAD(month::text, 2, '0') || '-01') as earliest_date,
                MAX(year || '-' || LPAD(month::text, 2, '0') || '-01') as latest_date,
                MAX(updated_at) as last_updated
            ")
            ->first();
            
        return [
            'total_records' => $stats->total_records ?? 0,
            'earliest_date' => $stats->earliest_date ? date('Y-m', strtotime($stats->earliest_date)) : null,
            'latest_date' => $stats->latest_date ? date('Y-m', strtotime($stats->latest_date)) : null,
            'last_updated' => $stats->last_updated,
            'is_fresh' => $this->isMaterializedDataFresh()
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\GlobalKilometerReport;
use App\Models\KilometerReport;
use App\Models\Route;
use App\Models\MaintenanceLog;
use App\Models\Schedule;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalKilometerReportGeneratorController extends Controller
{
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
        
        $startDate = Carbon::createFromDate($year, $month, $period == 1 ? 1 : 16);
        $endDate = $period == 1 
            ? Carbon::createFromDate($year, $month, 15)
            : Carbon::createFromDate($year, $month)->endOfMonth();
            
        try {
            DB::beginTransaction();
            
            $result = $this->generateReports($startDate, $endDate, $period, $month, $year);
            
            DB::commit();
            
            return redirect()->route('global-kilometer-reports.index', ['period' => $period, 'group' => 'all'])
                ->with('success', sprintf(
                    'Laporan kilometer global berhasil dibuat untuk periode %s %s %d. Created: %d, Skipped: %d',
                    ($period == 1 ? '1 (1-15)' : '2 (16-' . $endDate->day . ')'),
                    Carbon::create()->month($month)->translatedFormat('F'),
                    $year,
                    $result['created'],
                    $result['skipped']
                ));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Gagal membuat laporan kilometer global. Error: ' . $e->getMessage());
        }
    }
    
    private function generateReports(Carbon $startDate, Carbon $endDate, int $period, int $month, int $year): array
    {
        GlobalKilometerReport::where([
            'period' => $period,
            'month' => $month,
            'year' => $year
        ])->delete();

        $data = $this->fetchReportData($startDate, $endDate);
        
        if ($data['kilometerReports']->isEmpty() || $data['schedules']->isEmpty()) {
            return ['created' => 0, 'skipped' => 0, 'message' => 'No reports or schedules found'];
        }

        $organized = $this->organizeData($data);
        return $this->createGlobalReports(
            $organized,
            $period,
            $month,
            $year
        );
    }
    
    private function fetchReportData(Carbon $startDate, Carbon $endDate): array
    {
        return [
            'kilometerReports' => KilometerReport::with(['unit', 'route'])
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->get(),
                
            'schedules' => Schedule::with(['driver', 'unit', 'route'])
                ->whereDate('schedule_date', '>=', $startDate)
                ->whereDate('schedule_date', '<=', $endDate)
                ->whereIn('status', ['active', 'confirmed', 'scheduled'])
                ->get(),
                
            'maintenanceLogs' => MaintenanceLog::with(['driver', 'unit'])
                ->whereDate('date_reported', '>=', $startDate)
                ->whereDate('date_reported', '<=', $endDate)
                ->get()
        ];
    }
    
    private function organizeData(array $data): array
    {
        $organized = [
            'kilometerByUnitDate' => [],
            'maintenanceByDriverDate' => [],
            'schedulesByUnitDate' => [],
            'driversByUnitDate' => []
        ];
        
        foreach ($data['kilometerReports'] as $report) {
            $unitId = $report->unit_id;
            $date = $report->date->format('Y-m-d');
            $organized['kilometerByUnitDate'][$unitId][$date] = $report;
        }
        
        foreach ($data['maintenanceLogs'] as $log) {
            if ($log->driver_id) {
                $date = $log->date_reported->format('Y-m-d');
                $organized['maintenanceByDriverDate'][$log->driver_id][$date] = true;
            }
        }
        
        foreach ($data['schedules'] as $schedule) {
            if (!$schedule->driver_id) {
                continue;
            }
            
            $unitId = $schedule->unit_id;
            $date = $schedule->schedule_date->format('Y-m-d');
            $shift = $schedule->shift;
            $driverId = $schedule->driver_id;
            
            $organized['schedulesByUnitDate'][$unitId][$date][$shift] = $schedule;
            
            if (!isset($organized['driversByUnitDate'][$unitId][$date])) {
                $organized['driversByUnitDate'][$unitId][$date] = [
                    'all' => [],
                    'maintenance' => [],
                    'regular' => []
                ];
            }
            
            if (!in_array($driverId, $organized['driversByUnitDate'][$unitId][$date]['all'])) {
                $organized['driversByUnitDate'][$unitId][$date]['all'][] = $driverId;
                
                $isInMaintenance = isset($organized['maintenanceByDriverDate'][$driverId][$date]);
                if ($isInMaintenance) {
                    $organized['driversByUnitDate'][$unitId][$date]['maintenance'][] = $driverId;
                } else {
                    $organized['driversByUnitDate'][$unitId][$date]['regular'][] = $driverId;
                }
            }
        }
        
        return $organized;
    }
    
    private function createGlobalReports(array $organized, int $period, int $month, int $year): array
    {
        $reportCount = 0;
        $skippedCount = 0;
        
        foreach ($organized['kilometerByUnitDate'] as $unitId => $unitReports) {
            foreach ($unitReports as $date => $kilometerReport) {
                if (!isset($organized['schedulesByUnitDate'][$unitId][$date])) {
                    $skippedCount++;
                    continue;
                }
                
                $drivers = $organized['driversByUnitDate'][$unitId][$date] ?? null;
                if (!$drivers || empty($drivers['all'])) {
                    $skippedCount++;
                    continue;
                }
                
                $distribution = $this->calculateKilometerDistribution(
                    $kilometerReport->kilometers,
                    $drivers
                );
                
                foreach ($organized['schedulesByUnitDate'][$unitId][$date] as $shift => $schedule) {
                    if (!$schedule->driver_id) {
                        continue;
                    }
                    
                    $driverId = $schedule->driver_id;
                    $driverKilometers = $distribution[$driverId] ?? 0;
                    $isInMaintenance = in_array($driverId, $drivers['maintenance']);
                    
                    try {
                        GlobalKilometerReport::create([
                            'driver_id' => $driverId,
                            'unit_id' => $unitId,
                            'route_id' => $kilometerReport->route_id,
                            'report_date' => $date,
                            'shift' => $shift,
                            'period' => $period,
                            'month' => $month,
                            'year' => $year,
                            'kilometers' => $driverKilometers,
                            'driver_count' => count($drivers['all']),
                            'notes' => $this->generateNotes(
                                $isInMaintenance, 
                                $kilometerReport->notes,
                                $kilometerReport->kilometers,
                                count($drivers['maintenance']),  
                                count($drivers['regular'])
                            ),
                        ]);
                        
                        $reportCount++;
                        
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }
        
        return [
            'created' => $reportCount,
            'skipped' => $skippedCount
        ];
    }
    
    private function calculateKilometerDistribution(int $totalKilometers, array $drivers): array
    {
        $distribution = [];
        $allDrivers = $drivers['all'];
        $maintenanceDrivers = $drivers['maintenance'];
        $regularDrivers = $drivers['regular'];
        
        $totalDrivers = count($allDrivers);
        $maintenanceCount = count($maintenanceDrivers);
        $regularCount = count($regularDrivers);
        
        foreach ($allDrivers as $driverId) {
            $distribution[$driverId] = 0;
        }
        
        if ($totalKilometers <= 170 && $maintenanceCount > 0 && $regularCount > 0) {
            $kmForRegularDrivers = min($totalKilometers, $regularCount * 100);
            $kmPerRegularDriver = intval($kmForRegularDrivers / $regularCount);
            
            $kmForMaintenanceDrivers = $totalKilometers - $kmForRegularDrivers;
            $kmPerMaintenanceDriver = $maintenanceCount > 0 
                ? intval($kmForMaintenanceDrivers / $maintenanceCount) 
                : 0;
            
            foreach ($regularDrivers as $driverId) {
                $distribution[$driverId] = $kmPerRegularDriver;
            }
            
            foreach ($maintenanceDrivers as $driverId) {
                $distribution[$driverId] = $kmPerMaintenanceDriver;
            }
            
            $distributed = ($kmPerRegularDriver * $regularCount) + ($kmPerMaintenanceDriver * $maintenanceCount);
            $remainder = $totalKilometers - $distributed;
            
            if ($remainder > 0) {
                foreach ($regularDrivers as $driverId) {
                    if ($remainder <= 0) break;
                    $distribution[$driverId]++;
                    $remainder--;
                }
                
                foreach ($maintenanceDrivers as $driverId) {
                    if ($remainder <= 0) break;
                    $distribution[$driverId]++;
                    $remainder--;
                }
            }
            
        } else {
            $kmPerDriver = intval($totalKilometers / $totalDrivers);
            
            foreach ($allDrivers as $driverId) {
                $distribution[$driverId] = $kmPerDriver;
            }
            
            $remainder = $totalKilometers - ($kmPerDriver * $totalDrivers);
            foreach ($allDrivers as $driverId) {
                if ($remainder <= 0) break;
                $distribution[$driverId]++;
                $remainder--;
            }
        }
        
        return $distribution;
    }
    
    private function generateNotes(
        bool $isInMaintenance, 
        ?string $originalNotes, 
        int $totalKm, 
        int $maintenanceCount,
        int $regularCount
    ): string {
        $notes = [];
        
        if ($isInMaintenance) {
            $notes[] = 'Driver in maintenance';
        }
        
        if ($totalKm <= 170 && $maintenanceCount > 0 && $regularCount > 0) {
            $notes[] = 'Special distribution applied (≤170km)';
        }
        
        if ($originalNotes) {
            $notes[] = $originalNotes;
        }
        
        return implode('. ', $notes);
    }
}

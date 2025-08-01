<?php

namespace App\Imports;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DriversImport implements ToCollection, WithHeadingRow, WithValidation
{
    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            Log::info('Row data:', $row->toArray());
            
            $driverName = $this->getValueFromPossibleKeys($row, [
                'nama_pramudi', 'nama pramudi', 'namapramudi', 'nama', 'name', 'driver_name', 'driver name'
            ]);
            
            $routeNumber = $this->getValueFromPossibleKeys($row, [
                'route', 'rute', 'route_number', 'route number', 'rute_number', 'rute number'
            ]);
            
            $unitNumber = $this->getValueFromPossibleKeys($row, [
                'unit', 'unit_number', 'unit number', 'no_unit', 'no unit'
            ]);
            
            $driverType = $this->getValueFromPossibleKeys($row, [
                'type', 'tipe', 'driver_type', 'driver type', 'tipe_driver', 'tipe driver'
            ]);
            
            $ktpNumber = $this->getValueFromPossibleKeys($row, [
                'no_ktp', 'no ktp', 'ktp', 'noktp', 'ktp_number', 'ktp number'
            ]);
            
            $kppNumber = $this->getValueFromPossibleKeys($row, [
                'no_kpp', 'no kpp', 'kpp', 'nokpp', 'kpp_number', 'kpp number'
            ]);
            
            $kkNumber = $this->getValueFromPossibleKeys($row, [
                'no_kk', 'no kk', 'kk', 'nokk', 'kk_number', 'kk number'
            ]);
            
            $rekeningNumber = $this->getValueFromPossibleKeys($row, [
                'no_rekening', 'no rekening', 'rekening', 'norekening', 'rekening_number', 'rekening number'
            ]);
            
            $phoneNumber = $this->getValueFromPossibleKeys($row, [
                'telepon', 'phone', 'no_telepon', 'no telepon', 'phone_number', 'phone number', 'telepon_number', 'telepon number'
            ]);
            
            $email = $this->getValueFromPossibleKeys($row, [
                'email', 'email_address', 'email address'
            ]);
            
            $status = $this->getValueFromPossibleKeys($row, [
                'status', 'status_driver', 'status driver'
            ]);
            
            if (!$driverName) {
                Log::warning('Skipping row due to missing driver name', $row->toArray());
                continue;
            }
            
            // Process routes (handle comma-separated values)
            $routeIds = [];
            if ($routeNumber) {
                // Split by comma and trim whitespace
                $routeNumberList = array_map('trim', explode(',', $routeNumber));
                
                foreach ($routeNumberList as $singleRouteNumber) {
                    $possibleRouteNumbers = [
                        $singleRouteNumber,
                    ];
                    
                    if (!str_starts_with(strtoupper($singleRouteNumber), 'JAK')) {
                        $possibleRouteNumbers[] = 'JAK' . $singleRouteNumber;
                    }
                    
                    if (str_starts_with(strtoupper($singleRouteNumber), 'JAK')) {
                        $possibleRouteNumbers[] = substr($singleRouteNumber, 3);
                    }
                    
                    if (is_numeric($singleRouteNumber) && intval($singleRouteNumber) < 10) {
                        $possibleRouteNumbers[] = 'JAK0' . intval($singleRouteNumber);
                    }
                    
                    if (str_starts_with(strtoupper($singleRouteNumber), 'JAK') && strlen($singleRouteNumber) == 4 && is_numeric(substr($singleRouteNumber, 3))) {
                        $digit = substr($singleRouteNumber, 3);
                        if (intval($digit) < 10) {
                            $possibleRouteNumbers[] = 'JAK0' . intval($digit);
                        }
                    }
                    
                    if (is_numeric($singleRouteNumber) && intval($singleRouteNumber) >= 10) {
                        $possibleRouteNumbers[] = 'JAK' . $singleRouteNumber;
                    }
                    
                    Log::info("Trying route number formats for {$singleRouteNumber}:", $possibleRouteNumbers);
                    
                    $routeFound = false;
                    foreach ($possibleRouteNumbers as $format) {
                        $route = Route::where('route_number', $format)->first();
                        if ($route) {
                            $routeIds[] = $route->id;
                            Log::info("Found route with format: {$format}");
                            $routeFound = true;
                            break;
                        }
                    }
                    
                    if (!$routeFound) {
                        Log::warning("Route not found after trying all formats: {$singleRouteNumber}");
                    }
                }
            }
            
            // Process units (handle comma-separated values)
            $unitIds = [];
            if ($unitNumber) {
                // Split by comma and trim whitespace
                $unitNumberList = array_map('trim', explode(',', $unitNumber));
                
                foreach ($unitNumberList as $singleUnitNumber) {
                    $unit = Unit::where('unit_number', $singleUnitNumber)->first();
                    if ($unit) {
                        $unitIds[] = $unit->id;
                        Log::info("Found unit: {$singleUnitNumber}");
                    } else {
                        Log::warning("Unit not found: {$singleUnitNumber}");
                    }
                }
            }
            
            $driver = Driver::where('name', $driverName)->first();
            
            $driverData = [
                'name' => $driverName,
                'type' => strtolower($driverType ?: 'batangan'),
                'ktp' => $ktpNumber ?: '',
                'kpp' => $kppNumber,
                'kk' => $kkNumber,
                'rekening' => $rekeningNumber,
                'phone' => $phoneNumber,
                'email' => $email,
                'status' => strtolower($status ?: 'aktif'),
            ];
            
            Log::info('Processing driver:', $driverData);
            
            if ($driver) {
                $driver->update($driverData);
                Log::info("Updated driver: {$driverName}");
                
                // Update route relationships if any routes were found
                if (!empty($routeIds)) {
                    $driver->routes()->sync($routeIds);
                    Log::info("Synced routes for driver: {$driverName}", ['route_ids' => $routeIds]);
                }
                
                // Update unit relationships if any units were found
                if (!empty($unitIds)) {
                    $driver->units()->sync($unitIds);
                    Log::info("Synced units for driver: {$driverName}", ['unit_ids' => $unitIds]);
                }
            } else {
                $driver = Driver::create($driverData);
                Log::info("Created new driver: {$driverName}");
                
                // Attach route relationships if any routes were found
                if (!empty($routeIds)) {
                    $driver->routes()->attach($routeIds);
                    Log::info("Attached routes to new driver: {$driverName}", ['route_ids' => $routeIds]);
                }
                
                // Attach unit relationships if any units were found
                if (!empty($unitIds)) {
                    $driver->units()->attach($unitIds);
                    Log::info("Attached units to new driver: {$driverName}", ['unit_ids' => $unitIds]);
                }
            }
        }
    }
    
    private function getValueFromPossibleKeys($row, $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && !empty($row[$key])) {
                return $row[$key];
            }
            
            $lowerKey = strtolower($key);
            if (isset($row[$lowerKey]) && !empty($row[$lowerKey])) {
                return $row[$lowerKey];
            }
            
            $upperKey = strtoupper($key);
            if (isset($row[$upperKey]) && !empty($row[$upperKey])) {
                return $row[$upperKey];
            }
        }
        
        return null;
    }
    
    public function rules(): array
    {
        return [
            '*.nama_pramudi' => 'sometimes',
            '*.nama pramudi' => 'sometimes',
            '*.namapramudi' => 'sometimes',
            '*.nama' => 'sometimes',
            '*.name' => 'sometimes',
            '*.driver_name' => 'sometimes',
            '*.driver name' => 'sometimes',
            '*.NAMA_PRAMUDI' => 'sometimes',
            '*.NAMA PRAMUDI' => 'sometimes',
        ];
    }
    
    public function customValidationMessages()
    {
        return [
            '*.nama_pramudi.required' => 'Nama pengemudi harus diisi',
            '*.nama pramudi.required' => 'Nama pengemudi harus diisi',
            '*.namapramudi.required' => 'Nama pengemudi harus diisi',
            '*.nama.required' => 'Nama pengemudi harus diisi',
            '*.name.required' => 'Nama pengemudi harus diisi',
            '*.driver_name.required' => 'Nama pengemudi harus diisi',
            '*.driver name.required' => 'Nama pengemudi harus diisi',
            '*.NAMA_PRAMUDI.required' => 'Nama pengemudi harus diisi',
            '*.NAMA PRAMUDI.required' => 'Nama pengemudi harus diisi',
        ];
    }
}

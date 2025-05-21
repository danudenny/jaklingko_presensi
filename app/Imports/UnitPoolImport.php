<?php

namespace App\Imports;

use App\Models\Unit;
use App\Models\Route;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class UnitPoolImport extends DefaultValueBinder implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithCustomValueBinder
{
    use SkipsFailures;
    
    /**
     * Force all values to be treated as strings
     */
    public function bindValue(Cell $cell, $value)
    {
        // Convert all numeric values to strings for specific columns
        if (in_array($cell->getColumn(), ['A', 'B', 'C', 'D', 'E'])) { // A-E = text columns
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }
        
        // Use default handling for other columns
        return parent::bindValue($cell, $value);    
    }
    
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Begin transaction to ensure unit and route associations are saved together
        DB::beginTransaction();
        
        try {
            // Convert numeric values to strings
            $unitNumber = $this->convertToString($row['unit_number']);
            $plateNumber = $this->convertToString($row['plate_number']);
            $unitReg = $this->convertToString($row['unit_reg']);
            $serialNumber = $this->convertToString($row['serial_number']);
            $kir = $this->convertToString($row['kir']);
            
            // Create or update the unit
            $unit = Unit::updateOrCreate(
                ['unit_number' => $unitNumber],
                [
                    'plate_number' => $plateNumber,
                    'unit_reg' => $unitReg,
                    'serial_number' => $serialNumber,
                    'kir' => $kir,
                    'expired_stnk' => $this->transformDate($row['expired_stnk']),
                    'expired_kir' => $this->transformDate($row['expired_kir']),
                    'expired_kp' => $this->transformDate($row['expired_kp']),
                    'status' => $row['status'] ?? 'aktif',
                    'is_pool' => true,
                ]
            );
            
            // Handle route associations if route_codes column exists
            if (isset($row['route_codes']) && !empty($row['route_codes'])) {
                $routeCodes = explode(',', $row['route_codes']);
                $routeIds = Route::whereIn('route_number', $routeCodes)->pluck('id')->toArray();
                
                if (!empty($routeIds)) {
                    $unit->routes()->sync($routeIds);
                }
            }
            
            DB::commit();
            return $unit;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Transform a date value from Excel
     */
    private function transformDate($value)
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            // Try to parse the date
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Convert any value to string, handling numeric values properly
     */
    private function convertToString($value)
    {
        if (is_null($value)) {
            return '';
        }
        
        // Convert numeric values to strings with proper formatting
        if (is_numeric($value)) {
            // If it's an integer, convert directly to string to avoid scientific notation
            if (is_int($value) || (is_float($value) && $value == (int)$value)) {
                return (string)(int)$value;
            }
            
            // For floats, format to avoid scientific notation
            return number_format($value, 0, '', '');
        }
        
        // Already a string or other type
        return (string)$value;
    }
    
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'unit_number' => 'required',  // Accept any type
            'plate_number' => 'required',  // Accept any type
            'unit_reg' => 'required',      // Accept any type
            'serial_number' => 'required', // Accept any type
            'kir' => 'required',          // Accept any type
            'expired_stnk' => 'required',
            'expired_kir' => 'required',
            'expired_kp' => 'required',
            'status' => 'nullable|in:aktif,nonaktif,maintenance',
        ];
    }
    
    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'unit_number.required' => 'Nomor unit wajib diisi',
            'plate_number.required' => 'Nomor plat wajib diisi',
            'unit_reg.required' => 'Nomor registrasi wajib diisi',
            'serial_number.required' => 'Nomor seri wajib diisi',
            'kir.required' => 'KIR wajib diisi',
            'expired_stnk.required' => 'Tanggal expired STNK wajib diisi',
            'expired_kir.required' => 'Tanggal expired KIR wajib diisi',
            'expired_kp.required' => 'Tanggal expired KP wajib diisi',
        ];
    }
}

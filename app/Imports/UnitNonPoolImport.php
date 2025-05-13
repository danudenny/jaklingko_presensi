<?php

namespace App\Imports;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class UnitNonPoolImport extends DefaultValueBinder implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithCustomValueBinder
{
    use SkipsFailures;
    
    /**
     * Force all values to be treated as strings
     */
    public function bindValue(Cell $cell, $value)
    {
        // Convert all numeric values to strings for specific columns
        if ($cell->getColumn() === 'A' || $cell->getColumn() === 'B') { // A = unit_number, B = plate_number
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
        // Convert numeric values to strings
        $unitNumber = $this->convertToString($row['unit_number']);
        $plateNumber = $this->convertToString($row['plate_number']);
        
        return Unit::updateOrCreate(
            ['unit_number' => $unitNumber],
            [
                'plate_number' => $plateNumber,
                'status' => $row['status'] ?? 'aktif',
                'is_pool' => false,
            ]
        );
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
            'unit_number' => 'required',  // Remove string validation to accept any type
            'plate_number' => 'required', // Remove string validation to accept any type
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
        ];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\UnitPoolImport;
use App\Imports\UnitNonPoolImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class UnitImportController extends Controller
{
    /**
     * Show the import form
     */
    public function showImportForm()
    {
        return view('modules.admin.units.import');
    }
    
    /**
     * Import pool units from Excel
     */
    public function importPoolUnits(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);
        
        try {
            $import = new UnitPoolImport();
            Excel::import($import, $request->file('file'));
            
            $failures = $import->failures();
            
            if ($failures->count() > 0) {
                return redirect()->route('units.import.form')
                    ->with('warning', 'Import berhasil dengan ' . $failures->count() . ' error. Lihat detail error di bawah.')
                    ->with('failures', $failures);
            }
            
            return redirect()->route('units.index')
                ->with('success', 'Import unit pool berhasil.');
        } catch (\Exception $e) {
            Log::error('Unit pool import error: ' . $e->getMessage());
            return redirect()->route('units.import.form')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Import non-pool units from Excel
     */
    public function importNonPoolUnits(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);
        
        try {
            $import = new UnitNonPoolImport();
            Excel::import($import, $request->file('file'));
            
            $failures = $import->failures();
            
            if ($failures->count() > 0) {
                return redirect()->route('units.import.form')
                    ->with('warning', 'Import berhasil dengan ' . $failures->count() . ' error. Lihat detail error di bawah.')
                    ->with('failures', $failures);
            }
            
            return redirect()->route('units.index')
                ->with('success', 'Import unit non-pool berhasil.');
        } catch (\Exception $e) {
            Log::error('Unit non-pool import error: ' . $e->getMessage());
            return redirect()->route('units.import.form')
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Download sample Excel template for pool units
     */
    public function downloadPoolTemplate()
    {
        return Excel::download(new \App\Exports\UnitPoolTemplateExport, 'unit_pool_template.xlsx');
    }
    
    /**
     * Download sample Excel template for non-pool units
     */
    public function downloadNonPoolTemplate()
    {
        return Excel::download(new \App\Exports\UnitNonPoolTemplateExport, 'unit_non_pool_template.xlsx');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Unit;
use App\Imports\DriversImport;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|Application|Factory|JsonResponse|string
    {
        $query = Driver::with(['units', 'routes']);

        // Filter by name
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by KTP
        if ($request->has('ktp') && !empty($request->ktp)) {
            $query->where('ktp', 'like', '%' . $request->ktp . '%');
        }

        // Filter by type
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Filter by unit
        if ($request->has('unit') && !empty($request->unit)) {
            $query->whereHas('units', function ($q) use ($request) {
                $q->where('units.id', $request->unit);
            });
        }

        // Filter by route
        if ($request->has('route') && !empty($request->route)) {
            $query->whereHas('routes', function ($q) use ($request) {
                $q->where('routes.id', $request->route);
            });
        }

        $drivers = $query->orderBy('name')->paginate(10)->withQueryString();
        $units = Unit::orderBy('unit_number')->get();
        $routes = Route::orderBy('route_number')->get();

        if ($request->ajax()) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return view('modules.admin.drivers.index', compact('drivers', 'units', 'routes'))->render();
            }

            return response()->json([
                'success' => true,
                'data' => $drivers
            ]);
        }

        return view('modules.admin.drivers.index', compact('drivers', 'units', 'routes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $routes = Route::active()->get();
        return view('modules.admin.drivers.create', compact('routes'));
    }

    /**
     * Get units for a specific route (AJAX endpoint)
     */
    public function getUnitsForRoute(Request $request)
    {
        try {
            $routeId = $request->input('route_id');
            $units = [];
            
            if ($routeId) {
                try {
                    $route = Route::findOrFail($routeId);
                    
                    // Get units for the route
                    $routeUnits = $route->units()->get();
                    
                    if ($routeUnits->isEmpty()) {
                        // If no units found for this route, get all active units
                        $units = Unit::where('status', 'aktif')->get();
                    } else {
                        $units = $routeUnits;
                    }
                } catch (\Exception $e) {
                    // Return empty units array if route not found
                    $units = [];
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching units: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get all units (AJAX endpoint)
     */
    public function getAllUnits(Request $request)
    {
        try {
            // Get all active units
            $units = Unit::where('status', 'aktif')->get();
            
            return response()->json([
                'success' => true,
                'data' => $units
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching units: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ktp' => 'required|string|max:255|unique:drivers',
            'kpp' => 'nullable|string|max:255',
            'kk' => 'nullable|string|max:16',
            'rekening' => 'nullable|string|max:20',
            'type' => 'required|string|in:batangan,cadangan',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|string|in:aktif,nonaktif',
            'units' => 'required|array',
            'units.*' => 'exists:units,id',
            'routes' => 'nullable|array',
            'routes.*' => 'exists:routes,id',
        ]);

        $driver = Driver::create([
            'name' => $validated['name'],
            'ktp' => $validated['ktp'],
            'kpp' => $validated['kpp'] ?? null,
            'kk' => $validated['kk'] ?? null,
            'rekening' => $validated['rekening'] ?? null,
            'type' => $validated['type'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ]);
        
        // Attach units
        $driver->units()->attach($validated['units']);
        
        // Use routes directly selected from the form if available, otherwise get from units
        $routeIds = [];
        
        if ($request->has('routes') && is_array($request->routes)) {
            $routeIds = $request->routes;
        } else {
            // Get routes from units if no routes were directly selected
            $units = Unit::whereIn('id', $validated['units'])->get();
            
            foreach ($units as $unit) {
                $unitRoutes = $unit->routes()->pluck('routes.id')->toArray();
                $routeIds = array_merge($routeIds, $unitRoutes);
            }
            
            // Remove duplicates
            $routeIds = array_unique($routeIds);
        }
        
        // Check if driver type is 'batangan' and trying to assign more than 1 route
        if ($validated['type'] === 'batangan' && count($routeIds) > 1) {
            // For batangan drivers, only use the first route
            $routeIds = [reset($routeIds)];
        }
        
        // Attach routes
        if (!empty($routeIds)) {
            $driver->routes()->attach($routeIds);
        }

        return redirect()->route('drivers.index')
            ->with('success', 'Driver created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Driver $driver)
    {
        $driver->load(['units', 'routes', 'schedules', 'leaveRequests']);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $driver
            ]);
        }

        return view('modules.admin.drivers.show', compact('driver'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        $routes = Route::active()->get();
        $driver->load(['routes', 'units']);
        
        // Get all units for the driver's routes
        $routeUnits = collect();
        foreach ($driver->routes as $route) {
            $routeUnits = $routeUnits->merge($route->units()->active()->get());
        }
        $routeUnits = $routeUnits->unique('id');
        
        return view('modules.admin.drivers.edit', compact('driver', 'routes', 'routeUnits'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ktp' => 'required|string|max:255|unique:drivers,ktp,' . $driver->id,
            'kpp' => 'nullable|string|max:255',
            'kk' => 'nullable|string|max:16',
            'rekening' => 'nullable|string|max:20',
            'type' => 'required|string|in:batangan,cadangan',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'status' => 'required|string|in:aktif,nonaktif',
            'routes' => 'required|array',
            'routes.*' => 'exists:routes,id',
            'units' => 'required|array',
            'units.*' => 'exists:units,id',
        ]);

        // Check if driver type is 'batangan' and trying to assign more than 1 route
        if ($validated['type'] === 'batangan' && count($validated['routes']) > 1) {
            return redirect()->back()
                ->with('error', 'Driver batangan hanya dapat ditugaskan ke 1 rute.')
                ->withInput();
        }

        $driver->update([
            'name' => $validated['name'],
            'ktp' => $validated['ktp'],
            'kpp' => $validated['kpp'] ?? null,
            'kk' => $validated['kk'] ?? null,
            'rekening' => $validated['rekening'] ?? null,
            'type' => $validated['type'],
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ]);
        
        // Sync routes
        $driver->routes()->sync($validated['routes']);
        
        // Sync units
        $driver->units()->sync($validated['units']);

        return redirect()->route('drivers.index')
            ->with('success', 'Driver updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Driver $driver)
    {
        try {
            $driver->delete();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Driver deleted successfully.'
                ]);
            }

            return redirect()->route('drivers.index')
                ->with('success', 'Driver deleted successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete driver.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to delete driver: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function importForm()
    {
        return view('modules.admin.drivers.import');
    }

    /**
     * Import drivers from Excel file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        try {
            Excel::import(new DriversImport, $request->file('file'));

            return redirect()->route('drivers.index')
                ->with('success', 'Drivers imported successfully.');
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = 'Row ' . $failure->row() . ': ' . implode(', ', $failure->errors());
            }

            return redirect()->back()
                ->with('error', 'Import failed: ' . implode('<br>', $errors))
                ->withInput();
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Generate import template with existing driver data
     */
    public function generateImportTemplate()
    {
        try {
            // Create a new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = [
                'Route', 'Unit', 'NAMA PRAMUDI', 'Type', 'No KTP', 'No KPP', 'No KK', 
                'No Rekening', 'Telepon', 'Email', 'Status'
            ];
            
            // Apply header styling
            $sheet->getStyle('A1:K1')->getFont()->setBold(true);
            $sheet->getStyle('A1:K1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A1:K1')->getFill()->getStartColor()->setRGB('DDEBF7');
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(10);
            $sheet->getColumnDimension('C')->setWidth(25);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(15);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(20);
            $sheet->getColumnDimension('I')->setWidth(15);
            $sheet->getColumnDimension('J')->setWidth(25);
            $sheet->getColumnDimension('K')->setWidth(10);
            
            // Set headers
            for ($i = 0; $i < count($headers); $i++) {
                $sheet->setCellValue(chr(65 + $i) . '1', $headers[$i]);
            }
            
            // Get existing drivers with their relationships
            $drivers = Driver::with(['routes', 'units'])->orderBy('name')->get();
            
            // Add driver data
            $row = 2;
            foreach ($drivers as $driver) {
                // Get all routes and units for this driver as comma-separated strings
                $routeNumbers = $driver->routes->pluck('route_number')->implode(', ');
                $unitNumbers = $driver->units->pluck('unit_number')->implode(', ');
                
                $sheet->setCellValue('A' . $row, $routeNumbers);
                $sheet->setCellValue('B' . $row, $unitNumbers);
                $sheet->setCellValue('C' . $row, $driver->name);
                $sheet->setCellValue('D' . $row, ucfirst($driver->type));
                $sheet->setCellValue('E' . $row, $driver->ktp);
                $sheet->setCellValue('F' . $row, $driver->kpp);
                $sheet->setCellValue('G' . $row, $driver->kk);
                $sheet->setCellValue('H' . $row, $driver->rekening);
                $sheet->setCellValue('I' . $row, $driver->phone);
                $sheet->setCellValue('J' . $row, $driver->email);
                $sheet->setCellValue('K' . $row, ucfirst($driver->status));
                
                $row++;
            }
            
            // Add an empty row for new driver template
            $sheet->setCellValue('C' . $row, '(New Driver Name)');
            $sheet->setCellValue('D' . $row, 'Batangan');
            $sheet->setCellValue('K' . $row, 'Aktif');
            
            // Apply styling to the table
            $lastRow = $row;
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:K' . $lastRow)->applyFromArray($styleArray);
            
            // Create writer
            $writer = new Xlsx($spreadsheet);
            
            // Set the appropriate headers for download
            $filename = 'driver_import_template_' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Save to output
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to generate template: ' . $e->getMessage());
        }
    }
    
    /**
     * Toggle driver status between aktif and nonaktif
     */
    public function toggleStatus(Request $request, Driver $driver): JsonResponse
    {
        try {
            // Toggle between aktif and nonaktif
            $newStatus = $driver->status === 'aktif' ? 'nonaktif' : 'aktif';
            
            // Update the driver status
            $driver->status = $newStatus;
            $driver->save();
            
            return response()->json([
                'success' => true,
                'message' => "Status pengemudi berhasil diubah menjadi " . ucfirst($newStatus),
                'status' => $newStatus,
                'statusLabel' => ucfirst($newStatus === 'aktif' ? 'Aktif' : 'Non Aktif')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export drivers data to Excel
     */
    public function exportToExcel(Request $request)
    {
        try {
            // Create new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Define headers
            $headers = [
                'No', 'Nama', 'Tipe', 'Rute', 'Unit', 'No KTP', 'No KPP', 'No KK',
                'No Rekening', 'Telepon', 'Email', 'Status', 'Tanggal Dibuat'
            ];
            
            // Apply header styling
            $sheet->getStyle('A1:M1')->getFont()->setBold(true);
            $sheet->getStyle('A1:M1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A1:M1')->getFill()->getStartColor()->setRGB('DDEBF7');
            
            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(5);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(15);
            $sheet->getColumnDimension('H')->setWidth(15);
            $sheet->getColumnDimension('I')->setWidth(20);
            $sheet->getColumnDimension('J')->setWidth(15);
            $sheet->getColumnDimension('K')->setWidth(25);
            $sheet->getColumnDimension('L')->setWidth(15);
            $sheet->getColumnDimension('M')->setWidth(20);
            
            // Set headers
            for ($i = 0; $i < count($headers); $i++) {
                $sheet->setCellValue(chr(65 + $i) . '1', $headers[$i]);
            }
            
            // Build query with filters from request
            $query = Driver::with(['units', 'routes']);

            // Filter by name
            if ($request->has('name') && !empty($request->name)) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filter by KTP
            if ($request->has('ktp') && !empty($request->ktp)) {
                $query->where('ktp', 'like', '%' . $request->ktp . '%');
            }

            // Filter by type
            if ($request->has('type') && !empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by unit
            if ($request->has('unit') && !empty($request->unit)) {
                $query->whereHas('units', function ($q) use ($request) {
                    $q->where('units.id', $request->unit);
                });
            }

            // Filter by route
            if ($request->has('route') && !empty($request->route)) {
                $query->whereHas('routes', function ($q) use ($request) {
                    $q->where('routes.id', $request->route);
                });
            }
            
            // Get drivers data
            $drivers = $query->orderBy('name')->get();
            
            // Add driver data
            $row = 2;
            foreach ($drivers as $index => $driver) {
                // Get all routes and units for this driver
                $routeNumbers = $driver->routes->pluck('route_number')->implode(', ');
                $unitNumbers = $driver->units->pluck('unit_number')->implode(', ');
                
                $sheet->setCellValue('A' . $row, $index + 1);
                $sheet->setCellValue('B' . $row, $driver->name);
                $sheet->setCellValue('C' . $row, ucfirst($driver->type));
                $sheet->setCellValue('D' . $row, $routeNumbers);
                $sheet->setCellValue('E' . $row, $unitNumbers);
                $sheet->setCellValue('F' . $row, $driver->ktp);
                $sheet->setCellValue('G' . $row, $driver->kpp);
                $sheet->setCellValue('H' . $row, $driver->kk);
                $sheet->setCellValue('I' . $row, $driver->rekening);
                $sheet->setCellValue('J' . $row, $driver->phone);
                $sheet->setCellValue('K' . $row, $driver->email);
                $sheet->setCellValue('L' . $row, ucfirst($driver->status));
                $sheet->setCellValue('M' . $row, $driver->created_at ? $driver->created_at->format('Y-m-d H:i:s') : '');
                
                $row++;
            }
            
            // Apply styling to the table
            $lastRow = $row - 1;
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:M' . $lastRow)->applyFromArray($styleArray);
            
            // Create writer
            $writer = new Xlsx($spreadsheet);
            
            // Set the appropriate headers for download
            $filename = 'daftar_pengemudi_' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            // Save to output
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to export data: ' . $e->getMessage());
        }
    }
}

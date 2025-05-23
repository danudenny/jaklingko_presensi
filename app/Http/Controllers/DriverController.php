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

        $drivers = $query->paginate(10)->withQueryString();
        $units = \App\Models\Unit::orderBy('unit_number')->get();
        $routes = \App\Models\Route::orderBy('route_number')->get();

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
        
        // Attach routes
        $driver->routes()->attach($validated['routes']);
        
        // Attach units
        $driver->units()->attach($validated['units']);

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
}

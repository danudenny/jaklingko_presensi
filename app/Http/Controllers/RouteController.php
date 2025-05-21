<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Unit;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $routes = Route::with('units')->get();

        if ($request->ajax()) {
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return view('modules.admin.routes.index', compact('routes'))->render();
            }

            return response()->json([
                'success' => true,
                'data' => $routes
            ]);
        }

        return view('modules.admin.routes.index', compact('routes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('modules.admin.routes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_number' => 'required|string|max:255|unique:routes',
            'name' => 'required|string|max:255',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $route = Route::create($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rute berhasil ditambahkan.',
                'data' => $route
            ]);
        }

        return redirect()->route('routes.index')
            ->with('success', 'Rute berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Route $route)
    {
        // Load units relationship
        $route->load(['units']);
        
        // Paginate schedules - 10 per page
        $schedules = $route->schedules()->orderBy('schedule_date', 'desc')->paginate(10);

        if ($request->ajax()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $route
                ]);
            }
            
            // For AJAX pagination requests
            if ($request->has('page')) {
                return view('modules.admin.routes.partials.schedules-table', compact('schedules', 'route'))->render();
            }
        }

        return view('modules.admin.routes.show', compact('route', 'schedules'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Route $route)
    {
        return view('modules.admin.routes.edit', compact('route'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Route $route)
    {
        $validated = $request->validate([
            'route_number' => 'required|string|max:255|unique:routes,route_number,' . $route->id,
            'name' => 'required|string|max:255',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        $route->update($validated);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rute berhasil diperbarui.',
                'data' => $route
            ]);
        }

        return redirect()->route('routes.index')
            ->with('success', 'Rute berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Route $route)
    {
        $route->delete();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Route deleted successfully.'
            ]);
        }

        return redirect()->route('routes.index')
            ->with('success', 'Route deleted successfully.');
    }
    
    /**
     * Add a unit to a route.
     */
    public function addUnit(Request $request, Route $route)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:units,id'
        ]);
        
        $unit = Unit::findOrFail($validated['unit_id']);
        
        // Check if the unit is already associated with this route
        if (!$route->units()->where('unit_id', $unit->id)->exists()) {
            $route->units()->attach($unit->id);
            return redirect()->back()->with('success', 'Unit berhasil ditambahkan ke rute.');
        }
        
        return redirect()->back()->with('error', 'Unit sudah terkait dengan rute ini.');
    }
    
    /**
     * Remove a unit from a route.
     */
    public function removeUnit(Route $route, Unit $unit)
    {
        $route->units()->detach($unit->id);
        return redirect()->back()->with('success', 'Unit berhasil dihapus dari rute.');
    }
}

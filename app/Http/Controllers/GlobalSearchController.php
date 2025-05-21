<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Unit;
use App\Models\Route;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Search for drivers, units, or routes based on the query.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'drivers' => [],
                'units' => [],
                'routes' => [],
            ]);
        }
        
        // Search for drivers
        $drivers = Driver::where('name', 'like', "%{$query}%")
            ->orWhere('ktp', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'type' => $driver->type,
                    'identifier' => $driver->ktp, // Using KTP as identifier
                    'url' => route('drivers.show', $driver->id),
                ];
            });
        
        // Search for units
        $units = Unit::where('unit_number', 'like', "%{$query}%")
            ->orWhere('plate_number', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($unit) {
                return [
                    'id' => $unit->id,
                    'unit_number' => $unit->unit_number,
                    'plate_number' => $unit->plate_number,
                    'is_pool' => $unit->is_pool,
                    'url' => route('units.show', $unit->id),
                ];
            });
        
        // Search for routes
        $routes = Route::where('route_number', 'like', "%{$query}%")
            ->orWhere('name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($route) {
                return [
                    'id' => $route->id,
                    'route_number' => $route->route_number,
                    'name' => $route->name,
                    'url' => route('routes.show', $route->id),
                ];
            });
        
        return response()->json([
            'drivers' => $drivers,
            'units' => $units,
            'routes' => $routes,
        ]);
    }
}

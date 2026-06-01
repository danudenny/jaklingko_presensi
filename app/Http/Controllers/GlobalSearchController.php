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
        
        $lowerQuery = strtolower($query);

        // Search for drivers (case-insensitive for PostgreSQL)
        $drivers = Driver::whereRaw("LOWER(name) LIKE ?", ["%{$lowerQuery}%"])
            ->orWhereRaw("LOWER(ktp) LIKE ?", ["%{$lowerQuery}%"])
            ->orWhereRaw("LOWER(phone) LIKE ?", ["%{$lowerQuery}%"])
            ->limit(5)
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'type' => $driver->type,
                    'identifier' => $driver->ktp,
                    'url' => route('drivers.show', $driver->id),
                ];
            });

        // Search for units
        $units = Unit::whereRaw("LOWER(unit_number) LIKE ?", ["%{$lowerQuery}%"])
            ->orWhereRaw("LOWER(plate_number) LIKE ?", ["%{$lowerQuery}%"])
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
        $routes = Route::whereRaw("LOWER(route_number) LIKE ?", ["%{$lowerQuery}%"])
            ->orWhereRaw("LOWER(name) LIKE ?", ["%{$lowerQuery}%"])
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

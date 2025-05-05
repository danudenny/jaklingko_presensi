<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $routes = Route::all();

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
        $route->load(['schedules']);

        if ($request->ajax()) {
            $mode = $request->query('mode', 'view');

            if ($mode === 'view') {
                $html = view('modules.admin.routes.partials.view', compact('route'))->render();
            } else {
                $html = view('modules.admin.routes.partials.edit', compact('route'))->render();
            }

            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $route
            ]);
        }

        return view('modules.admin.routes.show', compact('route'));
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
}

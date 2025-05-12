<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Unit;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Factory|View|Application|JsonResponse
     */
    public function index(Request $request): Factory|View|Application|JsonResponse
    {
        $query = Unit::with(['drivers', 'routes']);

        // Filter by unit number
        if ($request->has('unit_number') && !empty($request->unit_number)) {
            $query->where('unit_number', 'like', '%' . $request->unit_number . '%');
        }

        // Filter by plate number
        if ($request->has('plate_number') && !empty($request->plate_number)) {
            $query->where('plate_number', 'like', '%' . $request->plate_number . '%');
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status) && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by expired STNK date range
        if ($request->has('expired_stnk_from') && !empty($request->expired_stnk_from)) {
            $query->whereDate('expired_stnk', '>=', $request->expired_stnk_from);
        }
        if ($request->has('expired_stnk_to') && !empty($request->expired_stnk_to)) {
            $query->whereDate('expired_stnk', '<=', $request->expired_stnk_to);
        }

        // Filter by expired KIR date range
        if ($request->has('expired_kir_from') && !empty($request->expired_kir_from)) {
            $query->whereDate('expired_kir', '>=', $request->expired_kir_from);
        }
        if ($request->has('expired_kir_to') && !empty($request->expired_kir_to)) {
            $query->whereDate('expired_kir', '<=', $request->expired_kir_to);
        }

        // Filter by expired KP date range
        if ($request->has('expired_kp_from') && !empty($request->expired_kp_from)) {
            $query->whereDate('expired_kp', '>=', $request->expired_kp_from);
        }
        if ($request->has('expired_kp_to') && !empty($request->expired_kp_to)) {
            $query->whereDate('expired_kp', '<=', $request->expired_kp_to);
        }

        // Filter by route
        if ($request->has('route_id') && !empty($request->route_id)) {
            $query->whereHas('routes', function($q) use ($request) {
                $q->where('routes.id', $request->route_id);
            });
        }

        // Get routes for filter dropdown
        $routes = Route::active()->get();

        // Paginate results
        $units = $query->orderBy('unit_number')->paginate(10);

        if ($request->ajax() || $request->has('format') && $request->format === 'json') {
            return response()->json($units);
        }

        return view('modules.admin.units.index', compact('units', 'routes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create(): Application|Factory|View
    {
        $routes = Route::active()->get();
        return view('modules.admin.units.create', compact('routes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        // Debug: Log the request data to see what's being received
        Log::info('Unit store request data:', $request->all());
        
        $validator = Validator::make($request->all(), [
            'unit_number' => 'required|string|max:255|unique:units',
            'plate_number' => 'required|nullable|string|max:255',
            'unit_reg' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'kir' => 'nullable|string|max:255',
            'expired_stnk' => 'required|nullable|date',
            'expired_kir' => 'required|nullable|date',
            'expired_kp' => 'required|nullable|date',
            'status' => 'required|in:aktif,nonaktif,maintenance',
            'route_ids' => 'nullable|array',
            'route_ids.*' => 'exists:routes,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Start a database transaction
            \DB::beginTransaction();
            
            // Create the unit
            $unit = Unit::create($request->except('route_ids'));
            
            // Debug: Log the unit that was created
            Log::info('Unit created:', ['id' => $unit->id, 'unit_number' => $unit->unit_number]);

            // Attach routes if provided
            if ($request->has('route_ids') && is_array($request->route_ids)) {
                // Debug: Log the route IDs that are being attached
                Log::info('Attaching routes to unit:', ['unit_id' => $unit->id, 'route_ids' => $request->route_ids]);
                
                try {
                    // Try to attach each route individually and log any errors
                    foreach ($request->route_ids as $routeId) {
                        try {
                            Log::info('Attaching route to unit:', ['unit_id' => $unit->id, 'route_id' => $routeId]);
                            \DB::table('unit_routes')->insert([
                                'unit_id' => $unit->id,
                                'route_id' => $routeId,
                            ]);
                            Log::info('Successfully attached route to unit:', ['unit_id' => $unit->id, 'route_id' => $routeId]);
                        } catch (\Exception $e) {
                            Log::error('Failed to attach route to unit:', [
                                'unit_id' => $unit->id, 
                                'route_id' => $routeId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to process route attachments:', ['error' => $e->getMessage()]);
                }
                
                // Check if routes were attached successfully
                $attachedRoutes = $unit->routes()->pluck('id')->toArray();
                Log::info('Attached routes:', ['attached_routes' => $attachedRoutes]);
            } else {
                Log::info('No route_ids provided or not an array');
            }
            
            // Commit the transaction
            \DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Unit berhasil ditambahkan',
                    'unit' => $unit->load('routes')
                ]);
            }

            return redirect()->route('units.index')
                ->with('success', 'Unit berhasil ditambahkan.');
                
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            \DB::rollBack();
            
            Log::error('Failed to create unit:', ['error' => $e->getMessage()]);
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create unit: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to create unit: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  \App\Models\Unit  $unit
     * @return Response
     */
    public function show(Request $request, Unit $unit)
    {
        $unit->load(['drivers', 'schedules', 'routes']);

        if ($request->has('mode') && $request->mode === 'view') {
            $html = view('modules.admin.units.partials.view', compact('unit'))->render();
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        }

        if ($request->has('mode') && $request->mode === 'edit') {
            $routes = Route::active()->get();
            $html = view('modules.admin.units.partials.edit', compact('unit', 'routes'))->render();
            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        }

        if ($request->ajax() || $request->has('format') && $request->format === 'json') {
            return response()->json($unit);
        }

        return view('modules.admin.units.show', compact('unit'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Unit  $unit
     * @return Response
     */
    public function edit(Unit $unit)
    {
        $routes = Route::active()->get();
        $unit->load('routes');
        return view('modules.admin.units.edit', compact('unit', 'routes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Unit $unit
     * @return RedirectResponse|JsonResponse
     */
    public function update(Request $request, Unit $unit): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'unit_number' => 'required|string|max:255|unique:units,unit_number,' . $unit->id,
            'plate_number' => 'required|nullable|string|max:255',
            'unit_reg' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'kir' => 'nullable|string|max:255',
            'expired_stnk' => 'required|nullable|date',
            'expired_kir' => 'required|nullable|date',
            'expired_kp' => 'required|nullable|date',
            'status' => 'required|in:aktif,nonaktif,maintenance',
            'route_ids' => 'nullable|array',
            'route_ids.*' => 'exists:routes,id',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Update the unit
        $unit->update($request->except('route_ids'));

        // Sync routes if provided
        if ($request->has('route_ids')) {
            $unit->routes()->sync($request->route_ids);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unit berhasil diperbarui',
                'unit' => $unit->load('routes')
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', 'Unit berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Unit  $unit
     * @return Response
     */
    public function destroy(Request $request, Unit $unit)
    {
        $unit->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unit deleted successfully'
            ]);
        }

        return redirect()->route('units.index')
            ->with('success', 'Unit deleted successfully.');
    }

    /**
     * Toggle the is_renops status for a specific unit
     *
     * @param Unit $unit
     * @return JsonResponse
     */
    public function toggleRenops(Unit $unit): JsonResponse
    {
        try {
            $unit->is_renops = !$unit->is_renops;
            $unit->save();

            return response()->json([
                'success' => true,
                'message' => 'Renops status updated successfully',
                'is_renops' => $unit->is_renops
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling renops status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update renops status'
            ], 500);
        }
    }

    /**
     * Update is_renops status for multiple units
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkRenops(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'unit_ids' => 'required|array',
            'unit_ids.*' => 'exists:units,id',
            'is_renops' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $unitIds = $request->input('unit_ids');
            $isRenops = $request->input('is_renops');

            Unit::whereIn('id', $unitIds)->update(['is_renops' => $isRenops]);

            return response()->json([
                'success' => true,
                'message' => count($unitIds) . ' units updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating bulk renops status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update units'
            ], 500);
        }
    }
}

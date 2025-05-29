<?php

namespace App\Http\Controllers;

use App\Models\SchedulePattern;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SchedulePatternController extends Controller
{
    /**
     * Display a listing of the schedule patterns.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'valid');
        $driverType = $request->query('driver_type', 'all');
        $days = $request->query('days');
        
        $query = SchedulePattern::query();
        
        // Apply filters
        if ($type) {
            $query->where('type', $type);
        }
        
        if ($driverType !== 'all') {
            $query->forDriverType($driverType);
        }
        
        if ($days) {
            $query->where('days', $days);
        }
        
        // Get active patterns by default
        $patterns = $query->orderBy('days')->orderBy('name')->paginate(10);
        
        return view('modules.admin.schedule-patterns.index', [
            'patterns' => $patterns,
            'type' => $type,
            'driverType' => $driverType,
            'days' => $days
        ]);
    }

    /**
     * Show the form for creating a new schedule pattern.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('modules.admin.schedule-patterns.create');
    }

    /**
     * Store a newly created schedule pattern in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:valid,invalid',
            'driver_type' => 'required|in:batangan,cadangan,all',
            'days' => 'required|integer|min:1|max:15',
            'pattern' => 'required|array|min:1',
            'pattern.*' => 'required|in:P,S,N',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'create_complement' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // Validate pattern length matches days
            $pattern = $request->input('pattern');
            if (count($pattern) != $request->input('days')) {
                return back()->withErrors(['pattern' => 'Pattern length must match the number of days'])->withInput();
            }
            
            // Check if the pattern has at least one Pagi or Siang shift
            $hasPagi = in_array('P', $pattern);
            $hasSiang = in_array('S', $pattern);
            
            if (!$hasPagi && !$hasSiang) {
                return back()->withErrors(['pattern' => 'Pattern must have at least one Pagi or Siang shift'])->withInput();
            }
            
            // Create the primary schedule pattern
            $primaryPattern = SchedulePattern::create([
                'name' => $request->input('name'),
                'type' => $request->input('type'),
                'driver_type' => $request->input('driver_type'),
                'days' => $request->input('days'),
                'pattern' => $pattern,
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true)
            ]);
            
            // Create the complementary pattern to ensure each day has both Pagi and Siang shifts covered
            $complementPattern = [];
            $hasComplement = false;
            
            foreach ($pattern as $day => $shift) {
                if ($shift === 'P') {
                    $complementPattern[$day] = 'S'; // Complement Pagi with Siang
                    $hasComplement = true;
                } elseif ($shift === 'S') {
                    $complementPattern[$day] = 'P'; // Complement Siang with Pagi
                    $hasComplement = true;
                } else {
                    $complementPattern[$day] = 'N'; // Keep No Schedule as is
                }
            }
            
            // Only create complement if it has at least one shift and user requested it
            if ($hasComplement && $request->input('create_complement', true)) {
                SchedulePattern::create([
                    'name' => $request->input('name') . ' (Complement)',
                    'type' => $request->input('type'),
                    'driver_type' => $request->input('driver_type'),
                    'days' => $request->input('days'),
                    'pattern' => $complementPattern,
                    'description' => 'Complementary pattern for ' . $request->input('name') . '. Created automatically to ensure each day has both Pagi and Siang shifts covered.',
                    'is_active' => $request->input('is_active', true)
                ]);
                
                return redirect()->route('schedule-patterns.index')
                    ->with('success_message', 'Schedule pattern pair created successfully');
            }
            
            return redirect()->route('schedule-patterns.index')
                ->with('success_message', 'Schedule pattern created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating schedule pattern: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create schedule pattern'])->withInput();
        }
    }

    /**
     * Display the specified schedule pattern.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $pattern = SchedulePattern::findOrFail($id);
        
        return view('modules.admin.schedule-patterns.show', [
            'pattern' => $pattern
        ]);
    }

    /**
     * Show the form for editing the specified schedule pattern.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $pattern = SchedulePattern::findOrFail($id);
        
        return view('modules.admin.schedule-patterns.edit', [
            'pattern' => $pattern
        ]);
    }

    /**
     * Update the specified schedule pattern in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:valid,invalid',
            'driver_type' => 'required|in:batangan,cadangan,all',
            'days' => 'required|integer|min:1|max:15',
            'pattern' => 'required|array|min:1',
            'pattern.*' => 'required|in:P,S,N',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            $pattern = SchedulePattern::findOrFail($id);
            
            // Validate pattern length matches days
            $patternArray = $request->input('pattern');
            if (count($patternArray) != $request->input('days')) {
                return back()->withErrors(['pattern' => 'Pattern length must match the number of days'])->withInput();
            }
            
            // Update the schedule pattern
            $pattern->update([
                'name' => $request->input('name'),
                'type' => $request->input('type'),
                'driver_type' => $request->input('driver_type'),
                'days' => $request->input('days'),
                'pattern' => $patternArray,
                'description' => $request->input('description'),
                'is_active' => $request->input('is_active', true)
            ]);
            
            return redirect()->route('schedule-patterns.index')
                ->with('success_message', 'Schedule pattern updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating schedule pattern: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update schedule pattern'])->withInput();
        }
    }

    /**
     * Remove the specified schedule pattern from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $pattern = SchedulePattern::findOrFail($id);
            $pattern->delete();
            
            return redirect()->route('schedule-patterns.index')
                ->with('success_message', 'Schedule pattern deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting schedule pattern: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete schedule pattern']);
        }
    }
    
    /**
     * Toggle the active status of a schedule pattern.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleActive($id)
    {
        try {
            $pattern = SchedulePattern::findOrFail($id);
            $pattern->is_active = !$pattern->is_active;
            $pattern->save();
            
            $status = $pattern->is_active ? 'activated' : 'deactivated';
            
            return redirect()->route('schedule-patterns.index')
                ->with('success_message', "Schedule pattern {$status} successfully");
        } catch (\Exception $e) {
            Log::error('Error toggling schedule pattern status: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to update schedule pattern status']);
        }
    }
}

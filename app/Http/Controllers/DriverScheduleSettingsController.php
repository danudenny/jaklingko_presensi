<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\DriverScheduleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DriverScheduleSettingsController extends Controller
{
    /**
     * Show the driver schedule settings page.
     */
    public function index(): View
    {
        // Get all settings or create defaults if none exist
        $settings = DriverScheduleSettings::getAllSettings();
        
        // Get counts of each driver type for reference
        $driverCounts = [
            'batangan' => Driver::batangan()->active()->count(),
            'cadangan' => Driver::cadangan()->active()->count(),
            'total' => Driver::active()->count(),
        ];
        
        return view('modules.admin.schedules.settings', [
            'settings' => $settings,
            'driverCounts' => $driverCounts,
        ]);
    }
    
    /**
     * Update the driver schedule settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.id' => 'required|exists:driver_schedule_settings,id',
            'settings.*.min_schedules' => 'required|integer|min:1',
            'settings.*.max_schedules' => 'required|integer|min:1|gte:settings.*.min_schedules',
            'settings.*.period_days' => 'required|integer|min:1',
            'settings.*.notes' => 'nullable|string',
        ]);
        
        foreach ($validated['settings'] as $settingData) {
            $setting = DriverScheduleSettings::findOrFail($settingData['id']);
            $setting->update([
                'min_schedules' => $settingData['min_schedules'],
                'max_schedules' => $settingData['max_schedules'],
                'period_days' => $settingData['period_days'],
                'notes' => $settingData['notes'] ?? null,
            ]);
        }
        
        return redirect()->route('drivers.index')
            ->with('success', 'Pengaturan jadwal pengemudi berhasil diperbarui.');
    }
}

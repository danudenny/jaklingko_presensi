<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HolidayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $holidays = Holiday::orderBy('date', 'desc')->paginate(10);
        
        return view('modules.admin.holidays.index', compact('holidays'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('modules.admin.holidays.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:cuti_bersama,libur_nasional',
            'description' => 'nullable|string',
        ]);

        try {
            Holiday::create($validated);
            
            return redirect()->route('holidays.index')
                ->with('success', 'Hari libur berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menambahkan hari libur: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Holiday $holiday)
    {
        return view('modules.admin.holidays.show', compact('holiday'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Holiday $holiday)
    {
        return view('modules.admin.holidays.edit', compact('holiday'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Holiday $holiday)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'type' => 'required|in:cuti_bersama,libur_nasional',
            'description' => 'nullable|string',
        ]);

        try {
            $holiday->update($validated);
            
            return redirect()->route('holidays.index')
                ->with('success', 'Hari libur berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal memperbarui hari libur: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Holiday $holiday)
    {
        try {
            $holiday->delete();
            
            return redirect()->route('holidays.index')
                ->with('success', 'Hari libur berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus hari libur: ' . $e->getMessage());
        }
    }
}

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Unit;
use App\Models\Driver;
use App\Models\Route;
use Illuminate\Support\Facades\DB;

echo "===== DATABASE DEBUGGING =====\n\n";

// Check if units table exists and has data
echo "UNITS TABLE:\n";
try {
    $unitsCount = DB::table('units')->count();
    echo "- Total units: {$unitsCount}\n";
    
    $unitStatuses = DB::table('units')->select('status')->distinct()->get();
    echo "- Unit statuses: " . json_encode($unitStatuses) . "\n";
    
    if ($unitsCount > 0) {
        $firstUnit = DB::table('units')->first();
        echo "- First unit: " . json_encode($firstUnit) . "\n";
    }
} catch (\Exception $e) {
    echo "- Error checking units: " . $e->getMessage() . "\n";
}

// Check if drivers table exists and has data
echo "\nDRIVERS TABLE:\n";
try {
    $driversCount = DB::table('drivers')->count();
    echo "- Total drivers: {$driversCount}\n";
    
    $driverTypes = DB::table('drivers')->select('type')->distinct()->get();
    echo "- Driver types: " . json_encode($driverTypes) . "\n";
    
    $driverStatuses = DB::table('drivers')->select('status')->distinct()->get();
    echo "- Driver statuses: " . json_encode($driverStatuses) . "\n";
    
    if ($driversCount > 0) {
        $firstDriver = DB::table('drivers')->first();
        echo "- First driver: " . json_encode($firstDriver) . "\n";
    }
} catch (\Exception $e) {
    echo "- Error checking drivers: " . $e->getMessage() . "\n";
}

// Check if routes table exists and has data
echo "\nROUTES TABLE:\n";
try {
    $routesCount = DB::table('routes')->count();
    echo "- Total routes: {$routesCount}\n";
    
    if ($routesCount > 0) {
        $firstRoute = DB::table('routes')->first();
        echo "- First route: " . json_encode($firstRoute) . "\n";
    }
} catch (\Exception $e) {
    echo "- Error checking routes: " . $e->getMessage() . "\n";
}

// Check driver-unit relationships
echo "\nDRIVER-UNIT RELATIONSHIPS:\n";
try {
    $driverUnitsCount = DB::table('driver_units')->count();
    echo "- Total driver-unit relationships: {$driverUnitsCount}\n";
    
    if ($driverUnitsCount > 0) {
        $firstRelationship = DB::table('driver_units')->first();
        echo "- First relationship: " . json_encode($firstRelationship) . "\n";
    }
} catch (\Exception $e) {
    echo "- Error checking driver-unit relationships: " . $e->getMessage() . "\n";
}

echo "\n===== MODEL DEBUGGING =====\n\n";

// Check Unit model
echo "UNIT MODEL:\n";
try {
    $units = Unit::all();
    echo "- Total units from model: " . $units->count() . "\n";
    
    $activeUnits = Unit::active()->get();
    echo "- Active units from model: " . $activeUnits->count() . "\n";
    
    if ($units->isNotEmpty()) {
        $firstUnitStatus = $units->first()->status;
        echo "- First unit status: {$firstUnitStatus}\n";
        
        // Try with actual status
        $unitsWithStatus = Unit::where('status', $firstUnitStatus)->get();
        echo "- Units with status '{$firstUnitStatus}': " . $unitsWithStatus->count() . "\n";
    }
} catch (\Exception $e) {
    echo "- Error with Unit model: " . $e->getMessage() . "\n";
}

// Check Driver model
echo "\nDRIVER MODEL:\n";
try {
    $drivers = Driver::all();
    echo "- Total drivers from model: " . $drivers->count() . "\n";
    
    $batanganDrivers = Driver::batangan()->get();
    echo "- Batangan drivers: " . $batanganDrivers->count() . "\n";
    
    $cadanganDrivers = Driver::cadangan()->get();
    echo "- Cadangan drivers: " . $cadanganDrivers->count() . "\n";
    
    $activeDrivers = Driver::active()->get();
    echo "- Active drivers: " . $activeDrivers->count() . "\n";
    
    if ($drivers->isNotEmpty()) {
        $firstDriver = $drivers->first();
        echo "- First driver type: {$firstDriver->type}, status: {$firstDriver->status}\n";
        
        // Check if driver has units
        $driverUnits = $firstDriver->units;
        echo "- First driver units count: " . $driverUnits->count() . "\n";
    }
} catch (\Exception $e) {
    echo "- Error with Driver model: " . $e->getMessage() . "\n";
}

echo "\n===== END OF DEBUGGING =====\n";

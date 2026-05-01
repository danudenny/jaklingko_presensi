<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Route;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoogleSheetSeeder extends Seeder
{
    /**
     * Path to the CSV file containing driver data from Google Sheets.
     */
    private const CSV_PATH = '/Users/denny/.local/share/opencode/tool-output/tool_de22def0e001nzx1TvSn99fJSh';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Clearing existing data...');
        $this->clearData();

        $this->command->info('Parsing CSV data...');
        $rows = $this->parseCSV();

        $this->command->info('Creating routes...');
        $routeMap = $this->createRoutes($rows);

        $this->command->info('Creating units...');
        $unitMap = $this->createUnits($rows, $routeMap);

        $this->command->info('Creating drivers...');
        $driverMap = $this->createDrivers($rows);

        $this->command->info('Linking drivers to routes and units...');
        $this->linkDrivers($rows, $routeMap, $unitMap, $driverMap);

        $this->printSummary();
    }

    /**
     * Clear existing data from tables.
     */
    private function clearData(): void
    {
        // Truncate tables to bypass soft deletes and foreign keys
        DB::statement('TRUNCATE TABLE driver_routes, driver_units, unit_routes, drivers, units, routes RESTART IDENTITY CASCADE');
    }

    /**
     * Parse CSV file into array of row data.
     */
    private function parseCSV(): array
    {
        if (!file_exists(self::CSV_PATH)) {
            throw new \RuntimeException('CSV file not found: ' . self::CSV_PATH);
        }

        $handle = fopen(self::CSV_PATH, 'r');
        if (!$handle) {
            throw new \RuntimeException('Cannot open CSV file: ' . self::CSV_PATH);
        }

        $header = fgetcsv($handle);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) < 30) continue;

            $rows[] = [
                'route' => trim($data[0] ?? ''),
                'unit' => trim($data[1] ?? ''),
                'name' => trim($data[2] ?? ''),
                'type' => trim($data[3] ?? ''),
                'ktp' => trim($data[14] ?? ''),
                'kpp' => trim($data[26] ?? ''),
                'kk' => trim($data[15] ?? ''),
                'phone' => trim($data[10] ?? ''),
                'nopol' => trim($data[29] ?? ''),
                'no_body' => trim($data[28] ?? ''),
                'mikrotrans' => trim($data[27] ?? ''),
            ];
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Create routes and return map of route_number => id.
     */
    private function createRoutes(array $rows): array
    {
        $routeNumbers = [];
        foreach ($rows as $row) {
            if ($row['route']) {
                $routeNumbers[$this->formatRouteNumber($row['route'])] = true;
            }
        }

        $map = [];
        foreach (array_keys($routeNumbers) as $routeNumber) {
            $route = Route::create([
                'route_number' => $routeNumber,
                'name' => 'Trayek ' . $routeNumber,
                'status' => 'active',
            ]);
            $map[$routeNumber] = $route->id;
        }

        return $map;
    }

    /**
     * Create units and return map of unit_number => id.
     */
    private function createUnits(array $rows, array $routeMap): array
    {
        $units = [];
        foreach ($rows as $row) {
            $unitNum = $row['unit'];
            $routeNum = $this->formatRouteNumber($row['route']);

            if ($unitNum && !isset($units[$unitNum])) {
                $units[$unitNum] = [
                    'unit_number' => $unitNum,
                    'plate_number' => $row['nopol'] ?: null,
                    'serial_number' => $row['no_body'] ?: null,
                    'unit_reg' => $row['mikrotrans'] ?: null,
                    'route_id' => $routeMap[$routeNum] ?? null,
                ];
            }
        }

        $map = [];
        foreach ($units as $unitNum => $data) {
            $unit = Unit::create([
                'unit_number' => $data['unit_number'],
                'plate_number' => $data['plate_number'],
                'serial_number' => $data['serial_number'],
                'unit_reg' => $data['unit_reg'],
                'status' => 'active',
            ]);

            if ($data['route_id']) {
                $unit->routes()->attach($data['route_id']);
            }

            $map[$unitNum] = $unit->id;
        }

        return $map;
    }

    /**
     * Create drivers and return map of normalized_name => id.
     */
    private function createDrivers(array $rows): array
    {
        $seenNames = [];
        $seenKtp = [];
        $drivers = [];

        foreach ($rows as $row) {
            $name = $row['name'];
            if (!$name || $name === '#N/A') continue;

            $key = strtolower(preg_replace('/\s+/', ' ', $name));
            if (isset($seenNames[$key])) continue;
            $seenNames[$key] = true;

            $type = $this->mapDriverType($row['type']);
            if (!$type) continue;

            $phone = $row['phone'];
            if ($phone && !preg_match('/^\d+$/', $phone)) {
                $phone = '';
            }

            $ktp = $row['ktp'];
            if ($ktp === '#N/A' || strlen($ktp) > 20) {
                $ktp = null;
            }
            // Skip duplicate KTPs
            if ($ktp && isset($seenKtp[$ktp])) {
                $ktp = null;
            }
            if ($ktp) {
                $seenKtp[$ktp] = true;
            }

            $kpp = $row['kpp'];
            if ($kpp === '#N/A') {
                $kpp = null;
            }

            $kk = $row['kk'];
            if ($kk === '#N/A' || strlen($kk) > 16) {
                $kk = null;
            }

            $driver = Driver::create([
                'name' => $name,
                'type' => $type,
                'ktp' => $ktp ?: null,
                'kpp' => $kpp ?: null,
                'kk' => $kk ?: null,
                'phone' => $phone ?: null,
                'status' => 'active',
            ]);

            $drivers[$key] = $driver->id;
        }

        return $drivers;
    }

    /**
     * Link drivers to routes and units via pivot tables.
     */
    private function linkDrivers(array $rows, array $routeMap, array $unitMap, array $driverMap): void
    {
        $seenDrivers = [];
        $routeLinks = [];
        $unitLinks = [];

        foreach ($rows as $row) {
            $name = $row['name'];
            if (!$name || $name === '#N/A') continue;

            $driverKey = strtolower(preg_replace('/\s+/', ' ', $name));
            if (!isset($driverMap[$driverKey])) continue;

            $driverId = $driverMap[$driverKey];
            $routeNum = $this->formatRouteNumber($row['route']);
            $unitNum = $row['unit'];

            if (isset($routeMap[$routeNum])) {
                $routeId = $routeMap[$routeNum];
                $routeKey = $driverId . '-' . $routeId;
                if (!isset($routeLinks[$routeKey])) {
                    $routeLinks[$routeKey] = [
                        'driver_id' => $driverId,
                        'route_id' => $routeId,
                    ];
                }
            }

            if ($unitNum && isset($unitMap[$unitNum])) {
                $unitId = $unitMap[$unitNum];
                $unitKey = $driverId . '-' . $unitId;
                if (!isset($unitLinks[$unitKey])) {
                    $unitLinks[$unitKey] = [
                        'driver_id' => $driverId,
                        'unit_id' => $unitId,
                    ];
                }
            }
        }

        if (!empty($routeLinks)) {
            DB::table('driver_routes')->insert(array_values($routeLinks));
        }

        if (!empty($unitLinks)) {
            DB::table('driver_units')->insert(array_values($unitLinks));
        }
    }

    /**
     * Format route number with zero-padding for single digits.
     */
    private function formatRouteNumber(string $route): string
    {
        $num = (int) $route;
        if ($num < 10) {
            return '0' . $num;
        }
        return $route;
    }

    /**
     * Map driver type from sheet to Laravel enum values.
     */
    private function mapDriverType(string $type): ?string
    {
        $upper = strtoupper($type);
        if (str_contains($upper, 'BATANGAN')) {
            return 'batangan';
        }
        if (str_contains($upper, 'CADANGAN')) {
            return 'cadangan';
        }
        return null;
    }

    /**
     * Print summary of seeded data.
     */
    private function printSummary(): void
    {
        $counts = [
            'Routes' => Route::count(),
            'Units' => Unit::count(),
            'Drivers' => Driver::count(),
            'Driver-Route links' => DB::table('driver_routes')->count(),
            'Driver-Unit links' => DB::table('driver_units')->count(),
            'Unit-Route links' => DB::table('unit_routes')->count(),
        ];

        $this->command->newLine();
        $this->command->info('Seeding complete!');
        foreach ($counts as $label => $count) {
            $this->command->line("  {$label}: {$count}");
        }
    }
}

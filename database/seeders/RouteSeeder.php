<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = [
            [
                'route_number' => 'R001',
                'name' => 'Blok M - Kota',
                'status' => 'aktif',
            ],
            [
                'route_number' => 'R002',
                'name' => 'Pulogadung - Ancol',
                'status' => 'aktif',
            ],
            [
                'route_number' => 'R003',
                'name' => 'Kalideres - Pasar Minggu',
                'status' => 'aktif',
            ],
            [
                'route_number' => 'R004',
                'name' => 'Grogol - Kemayoran',
                'status' => 'aktif',
            ],
            [
                'route_number' => 'R005',
                'name' => 'Ciledug - Blok M',
                'status' => 'aktif',
            ],
        ];

        foreach ($routes as $route) {
            Route::updateOrCreate(
                ['route_number' => $route['route_number']],
                $route
            );
        }
    }
}

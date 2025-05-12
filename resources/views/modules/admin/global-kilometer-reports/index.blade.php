@extends('modules.admin.layouts.main')

@section('title', 'Laporan Kilometer Global')

@section('content')
<div class="container mx-auto" x-data="globalKilometerReport()">
    <x-page-title>
        <x-slot name="title">Laporan Kilometer Global</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />
    <x-toast id="km-toast" />

    <!-- Period Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
                <a href="{{ route('global-kilometer-reports.index', ['period' => 1, 'group' => $activeRouteGroup]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 1 (1-15)
                </a>
                <a href="{{ route('global-kilometer-reports.index', ['period' => 2, 'group' => $activeRouteGroup]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 2 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 2 (16-{{ Carbon\Carbon::now()->endOfMonth()->format('d') }})
                </a>
            </nav>
        </div>
    </div>

    <!-- Route Group Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200 overflow-x-auto">
            <nav class="-mb-px flex space-x-2" aria-label="Route Groups">
                @foreach($routeGroups as $group)
                    @if($group !== 'all')
                    <a href="{{ route('global-kilometer-reports.index', ['period' => $period, 'group' => $group]) }}" 
                       class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == $group ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $group }}
                    </a>
                    @endif
                @endforeach
                <a href="{{ route('global-kilometer-reports.index', ['period' => $period, 'group' => 'all']) }}" 
                   class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Semua Rute
                </a>
            </nav>
        </div>
    </div>

    <!-- Global KM Report Table -->
    <div class="overflow-x-auto">
        <x-card>
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Periode: {{ $period == 1 ? '1-15' : '16-' . Carbon\Carbon::now()->endOfMonth()->format('d') }} {{ Carbon\Carbon::now()->format('F Y') }}
                    @if($activeRouteGroup != 'all')
                        - Rute {{ $activeRouteGroup }}
                    @endif
                </h3>
                <div class="flex space-x-2">
                    <a href="{{ route('global-kilometer-reports.export.excel', ['period' => $period, 'group' => $activeRouteGroup]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-excel mr-2"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('global-kilometer-reports.export.pdf', ['period' => $period, 'group' => $activeRouteGroup]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto" id="global-km-report-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="pl-6 pr-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-100 z-10">
                                Unit
                            </th>
                            <th scope="col" class="pl-6 pr-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-24 bg-gray-100 z-10">
                                Driver
                            </th>
                            @foreach($dates as $date)
                                @php
                                    $dateObj = \Carbon\Carbon::parse($date);
                                    $isWeekend = $dateObj->isWeekend();
                                    $isHoliday = isset($holidays[$date]);
                                    $cellClass = $isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : '');
                                @endphp
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider {{ $cellClass }}">
                                    {{ $dateObj->format('d M') }}<br>
                                    <span class="text-xs">{{ $dateObj->format('D') }}</span>
                                    @if($isHoliday)
                                        <span class="ml-1 text-yellow-600 cursor-help" title="{{ $holidays[$date]->name }}">
                                            <i class="fas fa-question-circle"></i>
                                        </span>
                                    @endif
                                </th>
                            @endforeach
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-100">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($routes as $route)
                            <tr class="bg-gray-100">
                                <td colspan="{{ count($dates) + 3 }}" class="px-6 py-2 whitespace-nowrap text-sm font-bold text-gray-900 sticky left-0 bg-gray-100 z-10">
                                    {{ $route->route_number }} - {{ $route->name }}
                                </td>
                            </tr>
                            
                            @foreach($route->units as $unit)
                                @php
                                    // Count how many drivers are assigned to this unit for rowspan
                                    $driversForUnit = isset($reportsByRouteUnitDriverDate[$route->id][$unit->id]) ? 
                                        count($reportsByRouteUnitDriverDate[$route->id][$unit->id]) : 0;
                                    $rowspan = max(1, $driversForUnit);
                                @endphp
                                
                                @if($driversForUnit == 0)
                                <tr class="bg-gray-50">
                                    <td class="pl-8 pr-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-gray-50 z-10">
                                        {{ $unit->unit_number }} - {{ $unit->plate_number }}
                                    </td>
                                    <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                        -
                                    </td>
                                    @foreach($dates as $date)
                                        @php
                                            $dateObj = \Carbon\Carbon::parse($date);
                                            $isWeekend = $dateObj->isWeekend();
                                            $isHoliday = isset($holidays[$date]);
                                            $isUnitMaintenance = in_array($unit->id, $maintenanceUnitsByDate[$date] ?? []);
                                            $cellClass = $isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : '');
                                        @endphp
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-center text-gray-500 {{ $cellClass }}">
                                            -
                                        </td>
                                    @endforeach
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-center font-bold text-gray-900 bg-gray-100">
                                        0.0
                                    </td>
                                </tr>
                                @else
                                    @php $driverCounter = 0; @endphp
                                    @foreach($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates)
                                        <tr class="{{ $driverCounter == 0 ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                                            @if($driverCounter == 0)
                                            <td class="pl-8 pr-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-gray-50 z-10" rowspan="{{ $rowspan }}">
                                                {{ $unit->unit_number }} - {{ $unit->plate_number }}
                                            </td>
                                            @endif
                                            
                                            <td class="px-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                                {{ $drivers[$driverId]->name ?? 'Unknown Driver' }}
                                                <span class="text-xs text-gray-500 block">{{ $driverDates[array_key_first($driverDates)]->shift ?? 'No Shift' }}</span>
                                            </td>
                                            
                                            @foreach($dates as $date)
                                                @php
                                                    $dateObj = \Carbon\Carbon::parse($date);
                                                    $isWeekend = $dateObj->isWeekend();
                                                    $isHoliday = isset($holidays[$date]);
                                                    $isUnitMaintenance = in_array($unit->id, $maintenanceUnitsByDate[$date] ?? []);
                                                    $cellClass = $isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : '');
                                                    
                                                    $km = isset($driverDates[$date]) ? $driverDates[$date]->kilometers : 0;
                                                    $originalKm = isset($driverDates[$date]) ? $driverDates[$date]->original_kilometers : 0;
                                                    $driverCount = isset($driverDates[$date]) ? $driverDates[$date]->driver_count : 0;
                                                    $shift = isset($driverDates[$date]) ? $driverDates[$date]->shift : '';
                                                    
                                                    $kmBelowThreshold = $originalKm > 0 && $originalKm < 150;
                                                    $cellBgClass = $kmBelowThreshold ? 'bg-red-100' : ($isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : ''));
                                                @endphp
                                                <td class="px-6 py-2 whitespace-nowrap text-sm text-center text-gray-500 {{ $cellBgClass }}">
                                                    @if($km > 0)
                                                        {{ number_format($km, 1) }}
                                                        <span class="text-xs text-gray-400">({{ $driverCount }})</span>
                                                        @if($isUnitMaintenance)
                                                            <i class="fas fa-exclamation-triangle text-yellow-500" title="Unit dalam maintenance"></i>
                                                        @endif
                                                        @if($unit->status !== 'aktif')
                                                            <i class="fas fa-exclamation-circle text-red-500" title="Unit tidak aktif"></i>
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-6 py-2 whitespace-nowrap text-sm text-center font-bold text-gray-900 bg-gray-100">
                                                {{ isset($routeDriverTotals[$route->id][$driverId]) ? number_format($routeDriverTotals[$route->id][$driverId], 1) : '0.0' }}
                                            </td>
                                        </tr>
                                        @php $driverCounter++; @endphp
                                    @endforeach
                                @endif
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-200 font-bold">
                        <tr>
                            <th scope="row" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-200 z-10">
                                Total
                            </th>
                            <th scope="row" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider bg-gray-200 z-10">
                                -
                            </th>
                            @foreach($dates as $date)
                                <th scope="row" class="px-6 py-3 text-center text-xs font-medium text-gray-700">
                                    {{ isset($dateTotals[$date]) ? number_format($dateTotals[$date], 1) : '0.0' }}
                                </th>
                            @endforeach
                            <th scope="row" class="px-6 py-3 text-center text-sm font-bold text-gray-900 bg-gray-300">
                                {{ number_format($grandTotal, 1) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>
    
    <div class="mt-6">
        <x-card>
            <h3 class="text-lg font-medium text-gray-900 mb-4">Keterangan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="flex items-center mb-2">
                        <div class="w-4 h-4 bg-yellow-100 mr-2"></div>
                        <span class="text-sm text-gray-700">Hari Libur</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <div class="w-4 h-4 bg-orange-100 mr-2"></div>
                        <span class="text-sm text-gray-700">Hari Sabtu/Minggu</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <div class="w-4 h-4 bg-red-100 mr-2"></div>
                        <span class="text-sm text-gray-700">Kilometer di bawah 150</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                        <span class="text-sm text-gray-700">Unit dalam maintenance</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span class="text-sm text-gray-700">Unit tidak aktif</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <span class="text-xs text-gray-400 mr-2">(2)</span>
                        <span class="text-sm text-gray-700">Jumlah driver yang berbagi kilometer</span>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</div>

@push('scripts')
<script>
    function globalKilometerReport() {
        return {
            // Alpine.js component properties and methods can be added here if needed
        }
    }
</script>
@endpush
@endsection

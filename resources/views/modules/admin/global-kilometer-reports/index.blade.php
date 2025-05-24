@extends('modules.admin.layouts.main')

@section('title', 'Laporan Kilometer Global')

@section('content')
<div class="container mx-auto" x-data="globalKilometerReport()">
    <x-page-title>
        <x-slot name="title">Laporan Kilometer Global</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-arrow-left"></i>
                    Kembali
                </a>
                <a href="{{ route('global-kilometer-reports.generate.form') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-md hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-sync-alt"></i>
                    Generate Report
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />
    <x-toast id="km-toast" />
    
    <!-- Month/Year Filter -->
    <div class="mb-6">
        <x-card>
            <form id="filter-form" method="GET" action="{{ route('global-kilometer-reports.index') }}">
                <div class="flex space-x-4">
                    <div>
                        <x-input-label for="month" value="Bulan" class="font-medium" />
                        <select id="month" name="month" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(range(1, 12) as $m)
                                @php
                                    $monthName = \Carbon\Carbon::create()->month($m)->translatedFormat('F');
                                @endphp
                                <option value="{{ $m }}" {{ $m == request('month', Carbon\Carbon::now()->month) ? 'selected' : '' }}>
                                    {{ $monthName }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="year" value="Tahun" class="font-medium" />
                        <select id="year" name="year" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach(range(Carbon\Carbon::now()->year - 2, Carbon\Carbon::now()->year + 1) as $y)
                                <option value="{{ $y }}" {{ $y == request('year', Carbon\Carbon::now()->year) ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="period" value="{{ $period }}" />
                    <input type="hidden" name="group" value="{{ $activeRouteGroup }}" />
                    <div class="flex items-end">
                        <x-primary-button type="submit">
                            <i class="mr-2 fas fa-filter"></i>
                            Filter
                        </x-primary-button>
                    </div>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Period Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px" aria-label="Tabs">                    <a href="{{ route('global-kilometer-reports.index', ['period' => 1, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 1 (1-15)
                </a>
                <a href="{{ route('global-kilometer-reports.index', ['period' => 2, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 2 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 2 (16-{{ Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->endOfMonth()->day }})
                </a>
            </nav>
        </div>
    </div>

    <!-- Route Group Tabs -->
    <div class="mb-6">
        <div class="overflow-x-auto border-b border-gray-200">
            <nav class="flex -mb-px space-x-2" aria-label="Route Groups">
                @foreach($routeGroups as $group)
                    @if($group !== 'all')
                    <a href="{{ route('global-kilometer-reports.index', ['period' => $period, 'group' => $group, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                       class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == $group ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $group }}
                    </a>
                    @endif
                @endforeach
                <a href="{{ route('global-kilometer-reports.index', ['period' => $period, 'group' => 'all', 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                   class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Semua Rute
                </a>
            </nav>
        </div>
    </div>

    <!-- Global KM Report Table -->
    <div class="overflow-x-auto">
        <x-card>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Periode: {{ $period == 1 ? '1-15' : '16-' . Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->endOfMonth()->day }} {{ Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->format('F Y') }}
                    @if($activeRouteGroup != 'all')
                        - Rute {{ $activeRouteGroup }}
                    @endif
                </h3>
                <div class="flex space-x-2">
                    <a href="{{ route('global-kilometer-reports.export.excel', ['period' => $period, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25">
                        <i class="mr-2 fas fa-file-excel"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('global-kilometer-reports.export.pdf', ['period' => $period, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-red-600 border border-transparent rounded-md hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25">
                        <i class="mr-2 fas fa-file-pdf"></i>
                        Export PDF
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto" id="global-km-report-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 py-3 pl-6 pr-6 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-100">
                                Unit
                            </th>
                            <th scope="col" class="sticky z-10 py-3 pl-6 pr-6 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-100 left-24">
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
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase bg-gray-100">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($routes as $route)
                            <tr class="bg-gray-100">
                                <td colspan="{{ count($dates) + 3 }}" class="sticky left-0 z-10 px-6 py-2 text-sm font-bold text-gray-900 bg-gray-100 whitespace-nowrap">
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
                                    <td class="sticky left-0 z-10 py-2 pl-8 pr-6 text-sm font-medium text-gray-900 whitespace-nowrap bg-gray-50">
                                        {{ $unit->unit_number }} - {{ $unit->plate_number }}
                                    </td>
                                    <td class="px-6 py-2 text-sm font-medium text-gray-900 whitespace-nowrap">
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
                                    <td class="px-6 py-2 text-sm font-bold text-center text-gray-900 bg-gray-100 whitespace-nowrap">
                                        0.0
                                    </td>
                                </tr>
                                @else
                                    @php $driverCounter = 0; @endphp
                                    @foreach($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates)
                                        <tr class="{{ $driverCounter == 0 ? 'bg-gray-50' : 'hover:bg-gray-50' }}">
                                            @if($driverCounter == 0)
                                            <td class="sticky left-0 z-10 py-2 pl-8 pr-6 text-sm font-medium text-gray-900 whitespace-nowrap bg-gray-50" rowspan="{{ $rowspan }}">
                                                {{ $unit->unit_number }} - {{ $unit->plate_number }}
                                            </td>
                                            @endif
                                            
                                            <td class="px-6 py-2 text-sm font-medium text-gray-900 whitespace-nowrap">
                                                {{ $drivers[$driverId]->name ?? 'Unknown Driver' }}
                                                <span class="block text-xs text-gray-500">{{ $driverDates[array_key_first($driverDates)]->shift ?? 'No Shift' }}</span>
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
                                                            <i class="text-yellow-500 fas fa-exclamation-triangle" title="Unit dalam maintenance"></i>
                                                        @endif
                                                        @if($unit->status !== 'aktif')
                                                            <i class="text-red-500 fas fa-exclamation-circle" title="Unit tidak aktif"></i>
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-6 py-2 text-sm font-bold text-center text-gray-900 bg-gray-100 whitespace-nowrap">
                                                {{ isset($routeDriverTotals[$route->id][$driverId]) ? number_format($routeDriverTotals[$route->id][$driverId], 1) : '0.0' }}
                                            </td>
                                        </tr>
                                        @php $driverCounter++; @endphp
                                    @endforeach
                                @endif
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="font-bold bg-gray-200">
                        <tr>
                            <th scope="row" class="sticky left-0 z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-200">
                                Total
                            </th>
                            <th scope="row" class="z-10 px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-200">
                                -
                            </th>
                            @foreach($dates as $date)
                                <th scope="row" class="px-6 py-3 text-xs font-medium text-center text-gray-700">
                                    {{ isset($dateTotals[$date]) ? number_format($dateTotals[$date], 1) : '0.0' }}
                                </th>
                            @endforeach
                            <th scope="row" class="px-6 py-3 text-sm font-bold text-center text-gray-900 bg-gray-300">
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
            <h3 class="mb-4 text-lg font-medium text-gray-900">Keterangan</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <div class="flex items-center mb-2">
                        <div class="w-4 h-4 mr-2 bg-yellow-100"></div>
                        <span class="text-sm text-gray-700">Hari Libur</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <div class="w-4 h-4 mr-2 bg-orange-100"></div>
                        <span class="text-sm text-gray-700">Hari Sabtu/Minggu</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <div class="w-4 h-4 mr-2 bg-red-100"></div>
                        <span class="text-sm text-gray-700">Kilometer di bawah 150</span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center mb-2">
                        <i class="mr-2 text-yellow-500 fas fa-exclamation-triangle"></i>
                        <span class="text-sm text-gray-700">Unit dalam maintenance</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <i class="mr-2 text-red-500 fas fa-exclamation-circle"></i>
                        <span class="text-sm text-gray-700">Unit tidak aktif</span>
                    </div>
                    <div class="flex items-center mb-2">
                        <span class="mr-2 text-xs text-gray-400">(2)</span>
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

@extends('modules.admin.layouts.main')

@section('title', 'Laporan Kilometer Global')

@push('styles')
<style>
    @import url('{{ asset('css/global-kilometer-reports.css') }}');
</style>
@endpush

@section('content')
<div x-data="globalKilometerReport()" x-cloak>
<div class="w-full px-4 container-fluid">
    <!-- Generate Modal -->
    
    @include('modules.admin.global-kilometer-reports.components.generate-modal')
    <x-page-title>
        <x-slot name="title">
            <div class="flex items-center">
                <i class="mr-3 text-2xl text-indigo-500 fas fa-chart-line"></i>
                <div>
                    <h1 class="text-2xl font-bold">Laporan Kilometer Global</h1>
                    <p class="text-sm font-thin text-gray-500">Statistik kilometer per pengemudi dan unit</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="actions">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600">
                    <i class="mr-2 fas fa-arrow-left"></i>
                    Kembali
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />
    <x-toast id="km-toast" />
    
    <div class="p-6 mb-4 bg-white rounded-lg shadow-md">

        <!-- Filter Controls -->
        <div class="p-4 mb-6 border border-gray-200 rounded-lg shadow-sm filter-container">
            <h3 class="mb-3 text-lg font-medium text-gray-700">
                Filter Data
            </h3>
            
            <form id="filter-form" method="GET" action="{{ route('global-kilometer-reports.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
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
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600">
                        <i class="mr-2 fas fa-filter"></i>
                        Filter
                    </button>
                </div>
            </form>
        </div>

    <!-- Main Tabs Wrapper -->
    <div class="mb-6">
        <!-- Period Tabs -->
        <div class="p-4 mb-4 bg-white rounded-lg shadow-sm">
            <h4 class="flex items-center mb-3 text-base font-medium text-gray-700">
                <i class="mr-2 text-indigo-500 fas fa-calendar"></i>
                Periode Laporan
            </h4>
            <div class="overflow-hidden border rounded-lg">
                <div class="flex bg-gradient-to-r from-gray-50 to-gray-100">
                    <a href="{{ route('global-kilometer-reports.index', ['period' => 1, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                       class="flex-1 py-3 px-4 text-center font-medium text-sm border-r border-gray-200 {{ $period == 1 ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <div class="flex items-center justify-center">
                            <i class="mr-2 fas fa-calendar-day"></i>
                            Periode 1 (1-15)
                        </div>
                    </a>
                    <a href="{{ route('global-kilometer-reports.index', ['period' => 2, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                       class="flex-1 py-3 px-4 text-center font-medium text-sm {{ $period == 2 ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                        <div class="flex items-center justify-center">
                            <i class="mr-2 fas fa-calendar-day"></i>
                            Periode 2 (16-{{ Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->endOfMonth()->day }})
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Route Group Tabs -->
        <div class="p-4 bg-white rounded-lg shadow-sm">
            <h4 class="flex items-center mb-3 text-base font-medium text-gray-700">
                <i class="mr-2 text-indigo-500 fas fa-route"></i>
                Filter Rute
            </h4>
            <div class="overflow-hidden border rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <div class="flex flex-wrap bg-gradient-to-r from-gray-50 to-gray-100">
                        @foreach($routeGroups as $group)
                            @if($group !== 'all')
                            <a href="{{ route('global-kilometer-reports.index', ['period' => $period, 'group' => $group, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                               class="py-3 px-4 text-center font-medium text-sm border-r border-gray-200 {{ $activeRouteGroup == $group ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                                <div class="flex items-center">
                                    <i class="mr-2 fas fa-route"></i>
                                    Rute {{ $group }}
                                </div>
                            </a>
                            @endif
                        @endforeach
                        <a href="{{ route('global-kilometer-reports.index', ['period' => $period, 'group' => 'all', 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                           class="py-3 px-4 text-center font-medium text-sm {{ $activeRouteGroup == 'all' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                            <div class="flex items-center">
                                <i class="mr-2 fas fa-globe-asia"></i>
                                Semua Rute
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global KM Report Table -->
    <div>
        <x-card>
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="flex items-center text-lg font-medium text-gray-900">
                    <i class="mr-2 text-indigo-500 fas fa-calendar-alt"></i>
                    <span>
                        Periode: {{ $period == 1 ? '1-15' : '16-' . Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->endOfMonth()->day }} {{ Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->format('F Y') }}
                        @if($activeRouteGroup != 'all')
                            - Rute {{ $activeRouteGroup }}
                        @endif
                    </span>
                </h3>
                <div class="flex flex-wrap gap-2 mt-2 sm:mt-0">
                    <button type="button" 
                        @click="openGenerateModal()" 
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600">
                        <i class="mr-2 fas fa-sync-alt"></i>
                        Generate
                    </button>
                    <a href="{{ route('global-kilometer-reports.export.excel', ['period' => $period, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                       class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600">
                        <i class="mr-2 fas fa-file-excel"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('global-kilometer-reports.export.pdf', ['period' => $period, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                       class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600">
                        <i class="mr-2 fas fa-file-pdf"></i>
                        Export PDF
                    </a>
                    @if(env('APP_ENV') == 'local')
                    <form action="{{ route('global-kilometer-reports.reset') }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mereset semua data laporan kilometer global? Tindakan ini tidak dapat dibatalkan.')">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600">
                            <i class="mr-2 fas fa-trash-alt"></i>
                            Reset Data
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            
            <div class="scroll-controls-sticky">
                <button id="scroll-left" class="scroll-button">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button id="scroll-right" class="scroll-button">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <div class="table-wrap" id="global-km-report-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th scope="col" class="sticky left-0 z-20 py-2 pl-2 pr-2 text-[12px] font-medium tracking-wider text-left text-gray-500 uppercase bg-gray-100 border-b border-gray-200 min-w-[8rem]">
                                <span class="sr-only">Driver</span>
                            </th>
                            <!-- Empty column to align date headers with data rows -->
                            <th scope="col" class="sticky left-[8rem] z-10 px-0 py-1 text-center font-medium text-transparent uppercase tracking-wider border-b border-gray-200 bg-gray-100" style="min-width:0;width:0;border-right:none;padding-right:0;margin-right:0;"></th>
                            @foreach($dates as $date)
                                @php
                                    $dateObj = \Carbon\Carbon::parse($date);
                                    $isWeekend = $dateObj->isWeekend();
                                    $isHoliday = isset($holidays[$date]);
                                    $headerClass = $isHoliday ? 'highlight-holiday' : ($isWeekend ? 'highlight-saturday' : '');
                                @endphp
                                <th scope="col" class="date-column text-center font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200 {{ $headerClass }}">
                                    <div class="text-[11px]">{{ $dateObj->format('d') }}</div>
                                    <div class="text-[9px] font-bold {{ $isWeekend ? 'text-amber-600' : '' }}">{{ $dateObj->format('D') }}</div>
                                    @if($isHoliday)
                                        <div class="text-red-600 text-[9px] cursor-help" title="{{ $holidays[$date]->name }}" data-tooltip="{{ $holidays[$date]->name }}">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                    @endif
                                </th>
                            @endforeach
                            <th scope="col" class="px-2 py-1 text-xs font-medium tracking-wider text-center text-gray-500 uppercase bg-gray-100 border-b border-gray-200 min-w-[2.8rem]">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 text-[12px]">
                        @foreach($routes as $route)
                            <!-- Route Group Header (colspan for overview) -->
                            <tr class="route-group" data-route-id="{{ $route->id }}">
                                <td colspan="{{ count($dates) + 3 }}" class="px-4 py-2 border-b-2 border-gray-300 bg-gray-100 font-bold text-gray-800 text-[12px]">
                                    <div class="flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 mr-2 text-white bg-indigo-600 rounded-full">
                                            <i class="fas fa-route"></i>
                                        </span>
                                        <span class="truncate">{{ $route->route_number }} - <span class="text-gray-700">{{ $route->name }}</span></span>
                                    </div>
                                    <div class="ml-8 text-xs text-gray-600">{{ count($route->units ?? []) }} Unit</div>
                                </td>
                            </tr>
                            @foreach($route->units as $unit)
                                <!-- Unit Group Header (colspan for overview) -->
                                <tr class="unit-group" data-unit-id="{{ $unit->id }}">
                                    <td colspan="{{ count($dates) + 3 }}" class="px-4 py-2 border-b-2 border-gray-200 bg-gray-50 font-bold text-gray-700 text-[12px]">
                                        <div class="flex items-center">
                                            <span class="flex items-center justify-center w-5 h-5 mr-2 text-white bg-blue-500 rounded-full">
                                                <i class="fas fa-bus"></i>
                                            </span>
                                            <span class="truncate">{{ $unit->unit_number }} - <span class="text-gray-600">{{ $unit->plate_number }}</span></span>
                                            <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full {{ $unit->status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ ucfirst($unit->status) }}
                                            </span>
                                        </div>
                                        <div class="ml-8 text-xs text-gray-500">{{ isset($reportsByRouteUnitDriverDate[$route->id][$unit->id]) ? count($reportsByRouteUnitDriverDate[$route->id][$unit->id]) : 0 }} Pengemudi • {{ number_format($routeUnitTotals[$route->id][$unit->id] ?? 0, 1) }} km</div>
                                    </td>
                                </tr>
                                @if(isset($reportsByRouteUnitDriverDate[$route->id][$unit->id]) && count($reportsByRouteUnitDriverDate[$route->id][$unit->id]) > 0)
                                    @foreach($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates)
                                        @php
                                            $driverTotalKm = 0;
                                            foreach($driverDates as $dateData) {
                                                $driverTotalKm += $dateData->kilometers ?? 0;
                                            }
                                            $rowClass = ($driverTotalKm > 0 && $driverTotalKm <= 170) ? 'driver-row bg-red-50' : 'driver-row';
                                        @endphp
                                        <tr class="{{ $rowClass }}">
                                            <td class="sticky left-0 z-10 px-1 py-0.5 text-x font-medium text-gray-900 {{ ($driverTotalKm > 0 && $driverTotalKm <= 170) ? 'bg-red-50' : 'bg-white' }} border-b border-gray-200 whitespace-nowrap min-w-[6.5rem]">
                                                <div class="flex items-center">
                                                    @php
                                                        $driverType = $drivers[$driverId]->type ?? null;
                                                        $driverIconBg = 'bg-amber-100 text-amber-700';
                                                        if ($driverType === 'batangan') {
                                                            $driverIconBg = 'bg-green-100 text-green-700';
                                                        } elseif ($driverType === 'cadangan') {
                                                            $driverIconBg = 'bg-purple-100 text-purple-700';
                                                        }
                                                    @endphp
                                                    <span class="flex items-center justify-center w-4 h-4 mr-0.5 rounded-full {{ $driverIconBg }}">
                                                        <i class="text-[9px] fas fa-user-tie"></i>
                                                    </span>
                                                    <span class="text-[11px] truncate max-w-[5.5rem]">{{ $drivers[$driverId]->name ?? 'Unknown Driver' }}</span>
                                                    @if(isset($unitRenops) && in_array($unit->id, $unitRenops))
                                                        <span class="flex items-center justify-center w-3 h-3 ml-0.5 bg-blue-600 rounded-full"><i class="text-[8px] text-white fas fa-exclamation"></i></span>
                                                    @endif
                                                </div>
                                            </td>
                                            <!-- Empty cell to align with header -->
                                            <td class="px-0 py-0 border-b border-gray-200 whitespace-nowrap" style="min-width:0;width:0;border-right:none;padding-right:0;margin-right:0;"></td>
                                            @foreach($dates as $date)
                                                @php
                                                    $dateObj = \Carbon\Carbon::parse($date);
                                                    $isWeekend = $dateObj->isWeekend();
                                                    $isHoliday = isset($holidays[$date]);
                                                    $isUnitMaintenance = in_array($unit->id, $maintenanceUnitsByDate[$date] ?? []);
                                                    $cellClass = $isHoliday ? 'highlight-holiday' : ($isWeekend ? 'highlight-saturday' : '');
                                                    $km = isset($driverDates[$date]) ? $driverDates[$date]->kilometers : 0;
                                                    $originalKm = isset($driverDates[$date]) ? $driverDates[$date]->original_kilometers : 0;
                                                    $driverCount = isset($driverDates[$date]) ? $driverDates[$date]->driver_count : 0;
                                                    $kmBelowThreshold = $originalKm > 0 && $originalKm < 150;
                                                    $cellBgClass = $kmBelowThreshold ? 'low-kilometers' : ($cellClass);
                                                    $isUnitRenops = isset($unitRenops) && in_array($unit->id, $unitRenops);
                                                @endphp
                                                <td class="date-column whitespace-nowrap text-center border-b border-gray-200 {{ $cellBgClass }}">
                                                    @if($km > 0)
                                                        <div class="relative">
                                                            <div class="font-semibold text-xs {{ $kmBelowThreshold ? 'text-red-700' : 'text-gray-700' }}">{{ number_format($km, 1) }}</div>
                                                            @if($isUnitMaintenance)
                                                                <div class="text-yellow-500 text-[8px]" title="Unit dalam maintenance">
                                                                    <i class="fas fa-exclamation"></i>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-gray-300 text-[9px]">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-2 py-2 text-xs font-bold text-center text-gray-900 border-b border-gray-200 bg-gray-50 whitespace-nowrap min-w-[4rem]">
                                                <span class="text-xs">{{ isset($routeDriverTotals[$route->id][$driverId]) ? number_format($routeDriverTotals[$route->id][$driverId], 1) : '0.0' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <!-- Unit subtotal row -->
                                    <tr class="bg-blue-50">
                                        <td class="sticky left-0 z-10 px-6 py-2 text-sm font-medium text-blue-800 border-t border-blue-200 whitespace-nowrap bg-blue-50 min-w-[8rem]">
                                            <div class="flex items-center justify-end pl-8">
                                                <span class="text-xs font-medium uppercase">Subtotal Unit</span>
                                            </div>
                                        </td>
                                        <!-- Empty cell for alignment -->
                                        <td class="sticky left-[8rem] z-10 px-0 py-0 border-t border-blue-200 bg-blue-50" style="min-width:0;width:0;border-right:none;padding:0;margin:0;"></td>
                                        @foreach($dates as $date)
                                            @php
                                                $unitDateTotal = 0;
                                                foreach ($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates) {
                                                    if (isset($driverDates[$date])) {
                                                        $unitDateTotal += $driverDates[$date]->original_kilometers;
                                                    }
                                                }
                                            @endphp
                                            <td class="px-4 py-2 text-xs font-semibold text-center text-blue-800 border-t border-blue-200 whitespace-nowrap bg-blue-50 min-w-[5.5rem]">
                                                {{ $unitDateTotal > 0 ? number_format($unitDateTotal, 1) : '-' }}
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-2 text-sm font-bold text-center text-blue-900 bg-blue-100 border-t border-blue-200 whitespace-nowrap min-w-[6rem]">
                                            {{ isset($routeUnitTotals[$route->id][$unit->id]) ? number_format($routeUnitTotals[$route->id][$unit->id], 1) : '0.0' }}
                                        </td>
                                    </tr>
                                @else
                                    <!-- No driver row -->
                                    <tr class="bg-gray-50 hover:bg-gray-100">
                                        <td class="sticky left-0 z-10 px-6 py-3 text-sm font-medium text-gray-500 border-b border-gray-200 whitespace-nowrap bg-gray-50 min-w-[8rem]">
                                            <div class="flex items-center pl-8">
                                                <i class="mr-2 text-gray-400 fas fa-user-slash"></i>
                                                Tidak ada pengemudi
                                            </div>
                                        </td>
                                        <!-- Empty cell for alignment -->
                                        <td class="sticky left-[8rem] z-10 px-0 py-0 border-b border-gray-200 bg-gray-50" style="min-width:0;width:0;border-right:none;padding:0;margin:0;"></td>
                                        @foreach($dates as $date)
                                            <td class="bg-gray-50 border-b border-gray-200 min-w-[5.5rem]"></td>
                                        @endforeach
                                        <td class="bg-gray-50 border-b border-gray-200 min-w-[6rem]"></td>
                                    </tr>
                                    <!-- Unit subtotal row (still show even if no driver) -->
                                    <tr class="bg-blue-50">
                                        <td class="sticky left-0 z-10 px-6 py-2 text-sm font-medium text-blue-800 border-t border-blue-200 whitespace-nowrap bg-blue-50 min-w-[8rem]">
                                            <div class="flex items-center justify-end pl-8">
                                                <span class="text-xs font-medium uppercase">Subtotal Unit</span>
                                            </div>
                                        </td>
                                        <!-- Empty cell for alignment -->
                                        <td class="sticky left-[8rem] z-10 px-0 py-0 border-t border-blue-200 bg-blue-50" style="min-width:0;width:0;border-right:none;padding:0;margin:0;"></td>
                                        @foreach($dates as $date)
                                            <td class="px-4 py-2 text-xs font-semibold text-center text-blue-800 border-t border-blue-200 whitespace-nowrap bg-blue-50 min-w-[5.5rem]">
                                                <i class="text-gray-300 fas fa-ban"></i>
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-2 text-sm font-bold text-center text-blue-900 bg-blue-100 border-t border-blue-200 whitespace-nowrap min-w-[6rem]">
                                            {{ isset($routeUnitTotals[$route->id][$unit->id]) ? number_format($routeUnitTotals[$route->id][$unit->id], 1) : '0.0' }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            <!-- Route subtotal row -->
                            <tr class="bg-indigo-50">
                                <td class="sticky left-0 z-10 px-6 py-2 text-sm font-medium text-indigo-800 border-t border-indigo-200 whitespace-nowrap bg-indigo-50 min-w-[8rem]">
                                    <div class="flex items-center justify-end">
                                        <span class="text-xs font-medium uppercase">Subtotal Rute</span>
                                    </div>
                                </td>
                                <!-- Empty cell for alignment -->
                                <td class="sticky left-[8rem] z-10 px-0 py-0 border-t border-indigo-200 bg-indigo-50" style="min-width:0;width:0;border-right:none;padding:0;margin:0;"></td>
                                @foreach($dates as $date)
                                    @php
                                        $routeDateTotal = 0;
                                        foreach ($route->units as $unit) {
                                            if (isset($reportsByRouteUnitDriverDate[$route->id][$unit->id])) {
                                                foreach ($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates) {
                                                    if (isset($driverDates[$date])) {
                                                        $routeDateTotal += $driverDates[$date]->original_kilometers;
                                                    }
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="px-4 py-2 text-xs font-semibold text-center text-indigo-800 border-t border-indigo-200 whitespace-nowrap bg-indigo-50 min-w-[5.5rem]">
                                        {{ $routeDateTotal > 0 ? number_format($routeDateTotal, 1) : '-' }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-2 text-sm font-bold text-center text-indigo-900 bg-indigo-100 border-t border-indigo-200 whitespace-nowrap min-w-[6rem]">
                                    {{ isset($routeTotals[$route->id]) ? number_format($routeTotals[$route->id], 1) : '0.0' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="font-bold">
                        <tr class="bg-gradient-to-r from-gray-200 to-gray-300">
                            <th scope="row" class="sticky left-0 z-10 px-6 py-4 text-sm font-bold tracking-wider text-left text-gray-700 uppercase bg-gray-200 border-t-2 border-gray-400 min-w-[8rem]">
                                <div class="flex items-center">
                                    <i class="mr-2 text-gray-700 fas fa-calculator"></i>
                                    Total Keseluruhan
                                </div>
                            </th>
                            <!-- Empty cell for alignment -->
                            <th class="sticky left-[8rem] z-10 px-0 bg-gray-200 border-t-2 border-gray-400" style="min-width:0;width:0;border-right:none;padding:0;margin:0;"></th>
                            @foreach($dates as $date)
                                <th scope="row" class="px-6 py-4 text-sm font-semibold text-center text-gray-700 border-t-2 border-gray-400 min-w-[5.5rem]">
                                    {{ isset($dateTotals[$date]) ? number_format($dateTotals[$date], 1) : '0.0' }}
                                </th>
                            @endforeach
                            <th scope="row" class="px-6 py-4 text-lg font-bold text-center text-indigo-800 bg-gray-300 border-t-2 border-gray-400 min-w-[6rem]">
                                {{ number_format($grandTotal, 1) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>
    
    @include('modules.admin.global-kilometer-reports.components.legend')
</div>

@push('scripts')
<script src="{{ asset('js/global-kilometer-report/index.js') }}"></script>
@endpush
</div>
@endsection

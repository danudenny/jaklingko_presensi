@extends('modules.admin.layouts.main')

@section('title', 'Jadwal Pengemudi')

@push('styles')
<style>
    @import url('{{ asset('css/schedule/consolidated.css') }}');
</style>
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/schedule/consolidated.js') }}"></script>
<script src="{{ asset('js/schedule/editor.js') }}"></script>
<script src="{{ asset('js/schedule/toast.js') }}"></script>
@endpush
<div class="w-full px-4 container-fluid">
    <x-page-title>
        <x-slot name="title">
            <div class="flex items-center">
                <i class="mr-3 text-2xl text-indigo-500 fas fa-calendar-alt"></i>
                <div>
                    <h1 class="text-2xl font-bold">Jadwal Pengemudi</h1>
                    <p class="text-sm font-thin text-gray-500">Manajemen jadwal pengemudi</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="actions">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('schedules.generate.form') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600">
                    <i class="mr-2 fas fa-calendar-plus"></i>
                    Buat Jadwal
                </a>
                <button id="edit-schedule-btn" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-500 hover:to-orange-600">
                    <i class="mr-2 fas fa-edit"></i>
                    <span id="edit-btn-text">Edit Jadwal</span>
                </button>
                <button id="show-unassigned-btn" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600">
                    <i class="mr-2 fas fa-user-slash"></i>
                    Pengemudi Belum Terjadwal
                </button>
                
                <button id="show-stats-btn" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-500 hover:to-purple-600">
                    <i class="mr-2 fas fa-chart-bar"></i>
                    Statistik
                </button>
                
                <div class="relative dropdown">
                    <button class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow dropdown-toggle bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600" type="button">
                        <i class="mr-2 fas fa-file-export"></i>
                        Export
                        <i class="ml-2 text-xs fas fa-chevron-down"></i>
                    </button>
                    <div class="absolute right-0 z-50 hidden w-48 p-2 mt-2 space-y-1 bg-white rounded-md shadow-lg dropdown-menu ring-1 ring-black ring-opacity-5">
                        <a href="{{ route('schedules.export.excel', ['month' => $month, 'year' => $year, 'period' => $period, 'route' => $selectedRoute, 'driver_type' => $selectedDriverType, 'driver' => $selectedDriver, 'unit' => $selectedUnit, 'shift' => $selectedShift]) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900">
                            <i class="mr-2 text-green-500 fas fa-file-excel"></i>
                            Excel
                        </a>
                        <a href="{{ route('schedules.export.pdf', ['month' => $month, 'year' => $year, 'period' => $period, 'route' => $selectedRoute, 'driver_type' => $selectedDriverType, 'driver' => $selectedDriver, 'unit' => $selectedUnit, 'shift' => $selectedShift]) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900">
                            <i class="mr-2 text-red-500 fas fa-file-pdf"></i>
                            PDF
                        </a>
                        <a href="{{ route('schedules.export.matrix-pdf', ['month' => $month, 'year' => $year, 'period' => $period, 'route' => $selectedRoute, 'driver_type' => $selectedDriverType, 'driver' => $selectedDriver, 'unit' => $selectedUnit, 'shift' => $selectedShift]) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900">
                            <i class="mr-2 text-purple-500 fas fa-table"></i>
                            Matrix PDF
                        </a>
                        @if(app()->environment('local'))
                        <div class="my-1 border-t border-gray-100"></div>
                        <form action="{{ route('schedules.reset-all') }}" method="POST" onsubmit="return confirm('PERINGATAN: Semua data jadwal akan dihapus. Apakah Anda yakin?');">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 text-xs font-medium text-left text-red-600 rounded-md hover:bg-red-50 hover:text-red-800">
                                <i class="mr-2 fas fa-trash-alt"></i>
                                Reset Data Jadwal
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </x-slot>
    </x-page-title>
    
    <div class="p-6 bg-white rounded-lg shadow-md">
        <!-- Statistics -->
        <!-- @include('modules.admin.schedules.components.stats') -->

        <!-- Filter Controls -->
        @include('modules.admin.schedules.components.filters')

        <!-- Period Tabs -->
        <div class="mb-6">
            <h3 class="mb-3 text-lg font-medium text-gray-700">
                <i class="mr-2 text-indigo-500 fas fa-calendar-week"></i>Pilih Periode
            </h3>
            <div class="flex max-w-md p-1 space-x-1 bg-gray-100 border border-gray-200 rounded-lg shadow-sm">
                <a href="{{ route('schedules.index', array_merge(request()->query(), ['period' => 1])) }}" 
                    class="w-1/2 py-2 px-4 text-center rounded-md transition-all duration-200 {{ $period == 1 ? 'bg-white shadow-sm font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i class="mr-2 fas fa-calendar-day"></i>
                    Periode 1
                    <div class="text-xs {{ $period == 1 ? 'text-indigo-500' : 'text-gray-500' }}">1-15 {{ $monthName }}</div>
                </a>
                <a href="{{ route('schedules.index', array_merge(request()->query(), ['period' => 2])) }}" 
                    class="w-1/2 py-2 px-4 text-center rounded-md transition-all duration-200 {{ $period == 2 ? 'bg-white shadow-sm font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i class="mr-2 fas fa-calendar-day"></i>
                    Periode 2
                    <div class="text-xs {{ $period == 2 ? 'text-indigo-500' : 'text-gray-500' }}">16-{{ Carbon\Carbon::parse($endDate)->format('d') }} {{ $monthName }}</div>
                </a>
            </div>
        </div>

        <!-- Schedule Matrix Table -->
        <div class="max-w-full overflow-x-auto">
            <div class="w-full align-middle bg-white border border-gray-200 rounded-lg shadow-sm">
                <table class="w-full divide-y divide-gray-200 table-auto">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-route"></i> Rute
                                </div>
                            </th>
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-bus"></i> Unit
                                </div>
                            </th>
                            <th scope="col" class="w-32 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-user"></i> Pengemudi
                                </div>
                            </th>
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-clock"></i> Shift
                                </div>
                            </th>
                            @foreach($dateRange as $date)
                                @php
                                    // $date is already defined from the foreach
                                    $dayName = Carbon\Carbon::parse($date)->format('l');
                                    $day = substr($dayName, 0, 3);
                                    $isHoliday = isset($holidays[$date]);
                                    $holidayName = $isHoliday ? $holidays[$date] : '';
                                    
                                    // Set classes based on day of week
                                    if ($dayName === 'Sunday') {
                                        $bgClass = 'bg-red-50';
                                        $textClass = 'text-red-700';
                                        $subTextClass = 'text-red-600';
                                    } elseif ($dayName === 'Saturday') {
                                        $bgClass = 'bg-orange-50';
                                        $textClass = 'text-orange-700';
                                        $subTextClass = 'text-orange-600';
                                    } else {
                                        $bgClass = 'bg-gray-50';
                                        $textClass = 'text-gray-700';
                                        $subTextClass = 'text-gray-500';
                                    }
                                    
                                    // Format the date for comparison with the holidays array
                                    $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                    
                                    // Add highlight classes for specific days
                                    $highlightClass = '';
                                    if ($dayName === 'Saturday' || $dayName === 'Sunday') {
                                        $highlightClass = 'highlight-saturday';
                                    }
                                    
                                    // Add holiday highlight class - takes precedence
                                    if ($isHoliday) {
                                        $highlightClass = 'highlight-holiday';
                                    }
                                @endphp
                                <th scope="col" class="w-8 px-1 py-3 text-xs font-medium tracking-wider text-center {{ $textClass }} uppercase {{ $bgClass }} {{ $highlightClass }} relative" 
                                    @if($isHoliday) title="{{ $holidayName }}" data-tooltip="{{ $holidayName }}" @endif>
                                    {{ Carbon\Carbon::parse($date)->format('d') }}
                                    <div class="{{ $subTextClass }} text-xxs">
                                        {{ $day }}
                                        @if($isHoliday)
                                            <div class="mt-1 text-red-600 text-xxs">
                                                <i class="fas fa-star-of-life"></i>
                                            </div>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-700 uppercase bg-gray-50">
                                <div class="flex items-center justify-center">
                                    <i class="mr-1 text-indigo-400 fas fa-calculator"></i> Total
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @if(count($routeUnitDrivers) === 0)
                            <tr>
                                <td colspan="{{ 4 + count($dateRange) + 1 }}" class="px-3 py-8 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="mb-3 text-5xl text-gray-300 fas fa-calendar-times"></i>
                                        <p class="text-lg text-gray-500">Tidak ada jadwal untuk periode ini.</p>
                                        <p class="mt-1 text-sm text-gray-400">Silakan coba filter lain atau buat jadwal baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($routeUnitDrivers as $routeGroup)
                                <tr class="bg-indigo-50 route-header" data-route-id="{{ $routeGroup['route']->id }}" onclick="toggleRouteContent({{ $routeGroup['route']->id }}, event)">
                                    <td colspan="{{ 4 + count($dateRange) + 1 }}" class="px-3 py-3 font-medium text-indigo-900 border-b border-indigo-100">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex items-center justify-center w-6 h-6 mr-2 text-white bg-indigo-600 rounded-full">
                                                    <i class="text-xs fas fa-route"></i>
                                                </div>
                                                <span class="font-semibold">Rute {{ $routeGroup['route']->route_number }}</span>
                                                <span class="mx-2">-</span>
                                                <span>{{ $routeGroup['route']->name }}</span>
                                            </div>
                                            <div class="toggle-icon">
                                                <i class="text-indigo-500 fas fa-chevron-down"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tbody class="route-content" data-route-content="{{ $routeGroup['route']->id }}">


                                @foreach($routeGroup['units'] as $unitGroup)
                                    <tr class="bg-blue-50 unit-header" data-unit-id="{{ $unitGroup['unit']->id }}" onclick="toggleUnitContent({{ $unitGroup['unit']->id }}, event)">
                                        <td class="px-3 py-2 text-right"></td>
                                        <td colspan="{{ 3 + count($dateRange) + 1 }}" class="px-3 py-2 font-medium text-blue-800 border-b border-blue-100">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="flex items-center justify-center w-5 h-5 mr-2 text-white bg-blue-500 rounded-full">
                                                        <i class="text-xs fas fa-bus"></i>
                                                    </div>
                                                    Unit {{ $unitGroup['unit']->unit_number }} 
                                                    @if($unitGroup['unit']->plate_number)
                                                        <span class="text-xs text-blue-600 ml-1 bg-blue-100 px-2 py-0.5 rounded-full">({{ $unitGroup['unit']->plate_number }})</span>
                                                    @endif
                                                </div>
                                                <div class="toggle-icon">
                                                    <i class="text-blue-500 fas fa-chevron-down"></i>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tbody class="unit-content" data-unit-content="{{ $unitGroup['unit']->id }}">


                                    @foreach($unitGroup['drivers'] as $driverInfo)
                                        @foreach(['pagi', 'siang'] as $shift)
                                            @if(!empty($driverInfo['shifts'][$shift]['dates']) || !empty($driverInfo['shifts'][$shift]['backup_dates']))
                                                <tr class="transition-colors hover:bg-gray-50">
                                                    <td class="px-3 py-2 text-xs text-right text-gray-500"></td>
                                                    <td class="px-3 py-2 text-xs text-right text-gray-500"></td>
                                                    <td class="px-3 py-3 text-sm">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0">
                                                                <span class="inline-flex items-center justify-center h-7 w-7 rounded-full {{ $driverInfo['driver']->type == 'batangan' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                                                    <i class="text-xs fas fa-user"></i>
                                                                </span>
                                                            </div>
                                                            <div class="ml-3">
                                                                <a href="{{ route('drivers.show', $driverInfo['driver']->id) }}" class="font-medium text-gray-800 hover:text-indigo-600 hover:underline">{{ $driverInfo['driver']->name }}</a>
                                                                <p class="text-xs {{ $driverInfo['driver']->type == 'batangan' ? 'text-emerald-600' : 'text-gray-500' }}">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $driverInfo['driver']->type == 'batangan' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }} text-xxs font-medium">
                                                                        {{ ucfirst($driverInfo['driver']->type) }}
                                                                    </span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-3 text-sm">
                                                        @if($shift == 'pagi')
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <i class="mr-1 text-blue-600 fas fa-sun"></i>
                                                                Pagi
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                                <i class="mr-1 fas fa-moon text-amber-600"></i>
                                                                Siang
                                                            </span>
                                                        @endif
                                                    </td>

                                                    @foreach($dateRange as $date)
                                                        @php
                                                            $isAssigned = in_array($date, $driverInfo['shifts'][$shift]['dates']);
                                                            $isBackup = in_array($date, $driverInfo['shifts'][$shift]['backup_dates']);
                                                            $isOnLeave = isset($driversOnLeave[$date]) && isset($driversOnLeave[$date][$driverInfo['driver']->id]);
                                                            $isInMaintenance = in_array($date, $driverInfo['shifts'][$shift]['maintenance_dates']);
                                                            
                                                            // Determine day type for highlighting
                                                            $dayName = \Carbon\Carbon::parse($date)->format('l');
                                                            
                                                            // Format the date for comparison with the holidays array
                                                            $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                                            
                                                            // Check if this date is a holiday using the holidays array from the controller
                                                            $isHoliday = isset($holidays[$formattedDate]);
                                                            
                                                            // Set highlight class based on day type
                                                            $cellHighlightClass = '';
                                                            if ($dayName === 'Saturday' || $dayName === 'Sunday') {
                                                                $cellHighlightClass = 'highlight-saturday';
                                                            }
                                                            
                                                            // Holiday takes precedence over weekend
                                                            if ($isHoliday) {
                                                                $cellHighlightClass = 'highlight-holiday';
                                                            }
                                                        @endphp
                                                        <td class="px-1 py-2 text-center {{ $cellHighlightClass }}">
                                                            @php
                                                                // Check if this unit is in renops for this date
                                                                $isUnitInRenops = isset($unitRenops[$formattedDate]) && isset($unitRenops[$formattedDate][$unitGroup['unit']->id]);
                                                            @endphp
                                                            
                                                            @if($isUnitInRenops)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full renops-indicator" title="Unit Tidak Beroperasi (Renops)">
                                                                    <i class="text-sm fas fa-exclamation"></i>
                                                                </span>
                                                            @elseif($isInMaintenance)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full bg-teal-100 text-teal-800 hover:bg-teal-200" title="Unit Dalam Perawatan">
                                                                    <i class="text-sm fas fa-wrench"></i>
                                                                </span>
                                                            @elseif($isOnLeave)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full bg-red-100 text-red-800 hover:bg-red-200" title="Pengemudi Sedang Cuti">
                                                                    <i class="text-sm fas fa-times"></i>
                                                                </span>
                                                            @elseif($isAssigned)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full hover:bg-green-200 {{ $driverInfo['driver']->type == 'batangan' ? 'bg-green-100 text-green-800' : 'cadangan-checkmark' }}" title="Pengemudi Dijadwalkan">
                                                                    <i class="text-sm fas fa-check"></i>
                                                                </span>
                                                            @elseif($isBackup)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full bg-amber-100 text-amber-800 hover:bg-amber-200" title="Pengemudi Backup">
                                                                    <i class="text-sm fas fa-user-shield"></i>
                                                                </span>
                                                            @else
                                                                <span class="inline-block w-6 h-6 transition-all border border-gray-200 rounded-full hover:bg-gray-100"></span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    
                                                    <td class="px-3 py-3 text-sm font-medium text-center bg-gray-50">
                                                        @php
                                                            $totalAssigned = count($driverInfo['shifts'][$shift]['dates']);
                                                            $totalBackup = count($driverInfo['shifts'][$shift]['backup_dates']);
                                                            $total = $totalAssigned + $totalBackup;
                                                        @endphp
                                                        <div class="flex flex-col items-center justify-center">
                                                            <span class="text-lg font-semibold {{ $total > 0 ? 'text-indigo-600' : 'text-gray-400' }}">{{ $total }}</span>
                                                            @if($totalAssigned > 0 && $totalBackup > 0)
                                                                <span class="text-gray-500 text-xxs">({{ $totalAssigned }}+{{ $totalBackup }})</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tbody> <!-- Close unit-content -->
                                @endforeach
                                </tbody> <!-- Close route-content -->
                            </tbody>
                                
                                <!-- Spacer row between route groups -->
                                <tr class="h-4">
                                    <td colspan="{{ 4 + count($dateRange) + 1 }}" class="border-b"></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend -->
        @include('modules.admin.schedules.components.legends')
        
        <!-- Unassigned Drivers -->
        @include('modules.admin.schedules.components.unassigned-drivers')
        
        <!-- Stats Drawer -->
        @include('modules.admin.schedules.components.stats-drawer')
    </div>
</div>
@endsection

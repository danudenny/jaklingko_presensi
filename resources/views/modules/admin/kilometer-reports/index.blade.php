@extends('modules.admin.layouts.main')

@section('title', 'Laporan Kilometer')

@section('content')
<div class="container mx-auto" x-data="kilometerReport()">
    <x-page-title>
        <x-slot name="title">Laporan Kilometer</x-slot>
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
    
    <!-- Month/Year Filter -->
    <div class="mb-6">
        <x-card>
            <form id="filter-form" method="GET" action="{{ route('kilometer-reports.index') }}">
                <div class="flex space-x-4">
                    <div>
                        <x-input-label for="month" value="Bulan" class="font-medium" />
                        <select id="month" name="month" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                        <select id="year" name="year" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
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
                            <i class="fas fa-filter mr-2"></i>
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
            <nav class="-mb-px flex" aria-label="Tabs">
                <a href="{{ route('kilometer-reports.index', ['period' => 1, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 1 (1-15)
                </a>
                <a href="{{ route('kilometer-reports.index', ['period' => 2, 'group' => $activeRouteGroup, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 2 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 2 (16-{{ Carbon\Carbon::createFromDate(request('year', Carbon\Carbon::now()->year), request('month', Carbon\Carbon::now()->month), 1)->endOfMonth()->day }})
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
                    <a href="{{ route('kilometer-reports.index', ['period' => $period, 'group' => $group, 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                       class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == $group ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $group }}
                    </a>
                    @endif
                @endforeach
                <a href="{{ route('kilometer-reports.index', ['period' => $period, 'group' => 'all', 'month' => request('month', Carbon\Carbon::now()->month), 'year' => request('year', Carbon\Carbon::now()->year)]) }}" 
                   class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Semua Rute
                </a>
            </nav>
        </div>
    </div>

    <!-- KM Report Table -->
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
                    <a href="{{ route('kilometer-reports.export.excel', ['period' => $period, 'group' => $activeRouteGroup]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-excel mr-2"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('kilometer-reports.export.pdf', ['period' => $period, 'group' => $activeRouteGroup]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                    <button type="button" @click="toggleImportModal" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-500 active:bg-purple-700 focus:outline-none focus:border-purple-700 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-import mr-2"></i>
                        Import
                    </button>
                    <button type="button" @click="toggleEditMode" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas" :class="editMode ? 'fa-eye' : 'fa-edit'" x-text="editMode ? ' View Mode' : ' Edit Mode'"></i>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto" id="km-report-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="pl-6 pr-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-100 z-10">
                                Unit
                            </th>
                            @foreach($dates as $date)
                                @php
                                    $dateObj = \Carbon\Carbon::parse($date);
                                    $isWeekend = $dateObj->isWeekend();
                                    $isHoliday = isset($holidays[$date]);
                                    $cellClass = $isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : '');
                                @endphp
                                <th scope="col" class="px-6 py-3  text-center text-xs font-medium text-gray-500 uppercase tracking-wider {{ $cellClass }}">
                                    {{ $dateObj->format('d') }}<br>
                                    <span class="text-xs">{{ $dateObj->format('D') }}</span>
                                    @if($isHoliday)
                                        <span class="ml-1 text-yellow-600 cursor-help" title="{{ $holidays[$date]->name }}">
                                            <i class="fas fa-asterisk text-red-600"></i>
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
                            <!-- Route Header Row -->
                            <tr class="bg-gray-100">
                                <td colspan="{{ count($dates) + 2 }}" class="px-6 py-2 whitespace-nowrap text-sm font-bold text-gray-900 sticky left-0 bg-gray-100 z-10">
                                    {{ $route->route_number }} - {{ $route->name }}
                                </td>
                            </tr>
                            
                            <!-- Unit Rows for this Route -->
                            @foreach($route->units as $unit)
                                @php
                                    $rowKilometers = isset($routeUnitTotals[$route->id][$unit->id]) ? $routeUnitTotals[$route->id][$unit->id] : 0;
                                    $rowClass = ($rowKilometers > 0 && $rowKilometers <= 170) ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50';
                                @endphp
                                <tr class="{{ $rowClass }} border-t border-gray-200">
                                    <td class="pl-8 pr-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 z-10 {{ ($rowKilometers > 0 && $rowKilometers <= 170) ? 'bg-red-50 hover:bg-red-100' : 'bg-white hover:bg-gray-50' }}">
                                        <a href="{{ route('kilometer-reports.show', ['unit' => $unit->id, 'period' => $period, 'month' => $month, 'year' => $year]) }}" class="text-indigo-600 hover:text-indigo-900" title="Lihat detail unit">
                                            KWK-{{ $unit->unit_number }} <i class="fas fa-arrow-up-right-from-square ml-1 text-[10px]"></i>
                                        </a>
                                        @if($unit->status !== 'aktif')
                                            <i class="fas fa-exclamation-circle text-red-500 ml-1" title="Unit tidak aktif"></i>
                                        @endif
                                    </td>
                                    @foreach($dates as $date)
                                        @php
                                            $dateObj = \Carbon\Carbon::parse($date);
                                            $isWeekend = $dateObj->isWeekend();
                                            $isHoliday = isset($holidays[$date]);
                                            $isUnitMaintenance = in_array($unit->id, $maintenanceUnitsByDate[$date] ?? []);
                                            
                                            $kilometers = isset($reportsByRouteUnitDate[$route->id][$unit->id][$date]) ? 
                                                $reportsByRouteUnitDate[$route->id][$unit->id][$date]->kilometers : 0;
                                            
                                            $kmBelowThreshold = $kilometers > 0 && $kilometers <= 170;
                                            
                                            // Priority-based cell coloring
                                            if ($kmBelowThreshold) {
                                                $cellClass = 'bg-red-500 text-white font-bold';
                                            } elseif ($isHoliday) {
                                                $cellClass = 'bg-yellow-50';
                                            } elseif ($isWeekend) {
                                                $cellClass = 'bg-orange-50';
                                            } else {
                                                $cellClass = '';
                                            }
                                            
                                            // Debug output (remove after testing)
                                            // dump("Date: $date, KM: $kilometers, Below threshold: " . ($kmBelowThreshold ? 'true' : 'false') . ", Class: $cellClass");
                                        @endphp
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-center text-gray-500 {{ $cellClass }}" 
                                            x-data="{ 
                                                isEditing: false, 
                                                kilometers: '{{ $kilometers }}'
                                            }">
                                            <!-- View Mode -->
                                            <template x-if="!editMode">
                                                <div> {{-- Fixed the syntax error here --}}
                                                    @if($kilometers > 0)
                                                        {{ number_format($kilometers, 1) }}
                                                        @if($isUnitMaintenance)
                                                            <i class="fas fa-exclamation-triangle text-yellow-500" title="Unit dalam maintenance"></i>
                                                        @endif
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </template>
                                            
                                            <!-- Edit Mode -->
                                            <template x-if="editMode">
                                                <div class="flex flex-col space-y-2">
                                                    <!-- Current Values -->
                                                    <div x-show="!isEditing" class="flex flex-col items-center">
                                                        @if($kilometers > 0)
                                                            <span>{{ number_format($kilometers, 1) }}</span>
                                                        @else
                                                            <span>-</span>
                                                        @endif
                                                        
                                                        <!-- Edit Button -->
                                                        <button @click="isEditing = true" class="mt-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                    </div>
                                                    
                                                    <!-- Edit Form -->
                                                    <div x-show="isEditing" class="flex flex-col space-y-2">
                                                        <input 
                                                            type="number" 
                                                            step="0.1" 
                                                            min="0" 
                                                            max="999.9" 
                                                            x-model="kilometers" 
                                                            class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                                            placeholder="KM"
                                                        >
                                                        
                                                        <!-- Action Buttons -->
                                                        <div class="flex space-x-1 justify-center">
                                                            <button 
                                                                @click="saveKilometers($event, '{{ $unit->id }}', '{{ $route->id }}', '{{ $date }}')" 
                                                                class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded hover:bg-green-200"
                                                            >
                                                                <i class="fas fa-save"></i>
                                                            </button>
                                                            <button 
                                                                @click="isEditing = false" 
                                                                class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded hover:bg-red-200"
                                                            >
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </td>
                                    @endforeach
                                    <td class="px-6 py-2 whitespace-nowrap text-sm text-center font-bold text-gray-900 bg-gray-100">
                                        {{ isset($routeUnitTotals[$route->id][$unit->id]) ? number_format($routeUnitTotals[$route->id][$unit->id], 1) : '0.0' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-200 font-bold">
                        <tr>
                            <th scope="row" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-200 z-10">
                                Total
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

    <!-- Import Modal -->
    @include('modules.admin.kilometer-reports.components.import-modal')
    
    <!-- Download Template Modal -->
    @include('modules.admin.kilometer-reports.components.download-template-modal')
</div>

@push('scripts')
<script src="{{ asset('js/kilometer-report/index.js') }}"></script>
@endpush
@endsection

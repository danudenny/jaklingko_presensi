@extends('modules.admin.layouts.main')

@section('title', 'Rencana Operasi Unit')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<style>
    .unit-card {
        transition: all 0.2s ease;
    }
    
    .unit-card.selected {
        border-color: #ef4444;
        background-color: #fee2e2;
    }
    
    .unit-card.non-pool-unit {
        opacity: 0.8;
        pointer-events: none; /* Make non-pool units non-interactive */
    }
    
    .progress-container {
        width: 100px;
        height: 8px;
        background-color: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .progress-bar {
        height: 100%;
        border-radius: 4px;
        transition: width 0.3s ease;
    }
    
    .create-button {
        position: relative;
        z-index: 10;
    }
</style>
@endpush

@section('content')
<div class="container px-4 py-6 mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Rencana Operasi Unit</h1>
        <a href="{{ route('renops.settings') }}" class="px-4 py-2 font-bold text-white bg-blue-600 rounded hover:bg-blue-700">
            <i class="mr-2 fas fa-cog"></i> Pengaturan
        </a>
    </div>

    <div class="p-6 mb-6 bg-white rounded-lg shadow-md">
        <form id="date-form" action="{{ route('renops.index') }}" method="GET" class="mb-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="date" class="block mb-1 text-sm font-medium text-gray-700">Tanggal</label>
                    <input type="text" id="date" name="date" value="{{ $date->format('Y-m-d') }}" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 font-bold text-white bg-blue-600 rounded hover:bg-blue-700">
                        <i class="mr-2 fas fa-search"></i> Cari
                    </button>
                </div>
            </div>
        </form>

        @if(isset($error))
            <div class="relative px-4 py-3 mb-4 text-red-700 bg-red-100 border border-red-400 rounded" role="alert">
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif

        @if($dayType)
            <div class="mb-4">
                <div class="p-4 border-l-4 border-blue-400 bg-blue-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="text-blue-400 fas fa-info-circle"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                @if($dayType == 'saturday')
                                    Tanggal {{ $date->translatedFormat('d F Y') }} adalah hari <strong>Sabtu</strong>.
                                    Maksimal unit yang dapat beroperasi adalah <strong>{{ $maxLimit }}</strong> unit (80% dari total).
                                @elseif($dayType == 'sunday')
                                    Tanggal {{ $date->translatedFormat('d F Y') }} adalah hari <strong>Minggu</strong>.
                                    Maksimal unit yang dapat beroperasi adalah <strong>{{ $maxLimit }}</strong> unit (70% dari total).
                                @elseif($dayType == 'holiday')
                                    Tanggal {{ $date->translatedFormat('d F Y') }} adalah hari libur <strong>{{ $holiday->name }}</strong>.
                                    Maksimal unit yang dapat beroperasi adalah <strong>{{ $maxLimit }}</strong> unit (70% dari total).
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <h2 class="text-lg font-semibold text-gray-800">Pilih Unit</h2>
                        @if(isset($settings) && $settings->isAutomatic())
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="mr-1 fas fa-robot"></i> Mode Otomatis
                            </span>
                        @else
                            <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <i class="mr-1 fas fa-hand-pointer"></i> Mode Manual
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center">
                        <span class="mr-2 text-sm text-gray-600">Terpilih: <span id="selected-count">{{ $currentCount ?? 0 }}</span> / <span>{{ $maxLimit }}</span></span>
                        <div class="progress-container">
                            <div class="bg-blue-600 progress-bar" style="width: {{ ($currentCount / $maxLimit) * 100 }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col mb-4 space-y-2 md:flex-row md:space-y-0 md:space-x-2">
                    <div class="md:w-1/4">
                        <label for="route-filter" class="block mb-1 text-sm font-medium text-gray-700">Filter Rute</label>
                        <select id="route-filter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">Semua Rute</option>
                            @foreach(\App\Models\Route::active()->orderBy('route_number')->get() as $route)
                                <option value="{{ $route->id }}">{{  $route->route_number }} - {{ $route->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:w-1/4">
                        <label for="pool-filter" class="block mb-1 text-sm font-medium text-gray-700">Status Unit</label>
                        <select id="pool-filter"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="">Semua Unit</option>
                            <option value="pool">Unit Pool</option>
                            <option value="non-pool">Unit Non-Pool</option>
                        </select>
                    </div>
                    <div class="relative flex-1">
                        <label for="unit-search" class="block mb-1 text-sm font-medium text-gray-700">Cari Unit</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="text-gray-400 fas fa-search"></i>
                            </div>
                            <input type="text" id="unit-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Cari unit...">
                        </div>
                    </div>
                </div>

                @if(isset($settings) && $settings->isAutomatic())
                    <div class="p-4 mb-4 border-l-4 border-green-400 bg-green-50">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="text-green-400 fas fa-robot"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">
                                    <strong>Mode Otomatis Aktif:</strong> Unit dipilih secara otomatis berdasarkan pengaturan ambang batas.
                                    Anda tetap dapat mengubah pilihan unit secara manual di bawah ini.
                                </p>
                                <div class="flex items-center mt-3">
                                    <button type="button" id="generate-automatic" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-1.5 px-3 rounded-md mr-3">
                                        <i class="mr-1 fas fa-magic"></i> Generate Otomatis
                                    </button>
                                    <a href="{{ route('renops.settings') }}" class="text-sm font-medium text-green-700 underline">
                                        Buka Pengaturan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="units-container" class="overflow-y-auto max-h-96">
                @else
                    <div id="units-container" class="overflow-y-auto max-h-96">
                @endif
                    @php
                        // Group units by routes
                        $unitsByRoute = [];
                        $unitsWithoutRoute = [];
                        
                        foreach($units as $unit) {
                            if($unit->routes->count() > 0) {
                                foreach($unit->routes as $route) {
                                    if(!isset($unitsByRoute[$route->id])) {
                                        $unitsByRoute[$route->id] = [
                                            'route' => $route,
                                            'units' => []
                                        ];
                                    }
                                    $unitsByRoute[$route->id]['units'][] = $unit;
                                }
                            } else {
                                $unitsWithoutRoute[] = $unit;
                            }
                        }
                        
                        // Sort routes by route number
                        uksort($unitsByRoute, function($a, $b) use ($unitsByRoute) {
                            return $unitsByRoute[$a]['route']->route_number <=> $unitsByRoute[$b]['route']->route_number;
                        });
                    @endphp
                    
                    <!-- Units grouped by routes with accordion -->
                    @foreach($unitsByRoute as $routeGroup)
                        <div class="mb-6" x-data="{ open: true }">
                            <h3 @click="open = !open" class="flex items-center justify-between p-2 mb-3 font-semibold text-gray-800 transition duration-150 bg-gray-100 rounded cursor-pointer text-md hover:bg-gray-200">
                                <div>
                                    <i class="mr-2 fas fa-route"></i> Rute {{ $routeGroup['route']->route_number }} - {{ $routeGroup['route']->name }}
                                    <span class="ml-2 text-sm font-normal text-gray-600">({{ count($routeGroup['units']) }} Unit)</span>
                                </div>
                                <div>
                                    <i class="fas" :class="{'fa-chevron-down': open, 'fa-chevron-right': !open}"></i>
                                </div>
                            </h3>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                @foreach($routeGroup['units'] as $unit)
                                    <div class="unit-card border rounded-lg p-3 {{ !$unit->is_pool ? 'border-dashed border-orange-300 bg-orange-50 non-pool-unit' : (isset($settings) && $settings->isAutomatic() ? '' : 'cursor-pointer hover:bg-gray-50') }} {{ in_array($unit->id, $renopsUnits ?? []) ? 'selected' : '' }}"
                                         data-unit-id="{{ $unit->id }}"
                                         data-unit-number="{{ $unit->unit_number }}"
                                         data-plate-number="{{ $unit->plate_number }}"
                                         data-is-pool="{{ $unit->is_pool ? 'true' : 'false' }}"
                                         data-route-ids="{{ $unit->routes->pluck('id')->implode(',') }}">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h3 class="font-semibold text-gray-800">{{ $unit->unit_number }}</h3>
                                                <p class="text-sm text-gray-600">{{ $unit->plate_number }}</p>
                                                <div class="flex items-center mt-1">
                                                    @if(!$unit->is_pool)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mr-1">
                                                            <i class="mr-1 text-xs fas fa-exclamation-triangle"></i> Non-Pool
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="status-badge px-2 py-1 text-xs font-medium rounded-full {{ in_array($unit->id, $renopsUnits ?? []) ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ in_array($unit->id, $renopsUnits ?? []) ? 'Tidak Beroperasi' : 'Tidak Dipilih' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    
                    <!-- Units without routes with accordion -->
                    @if(count($unitsWithoutRoute) > 0)
                        <div class="mb-6" x-data="{ open: true }">
                            <h3 @click="open = !open" class="flex items-center justify-between p-2 mb-3 font-semibold text-gray-800 transition duration-150 bg-gray-100 rounded cursor-pointer text-md hover:bg-gray-200">
                                <div>
                                    <i class="mr-2 fas fa-question-circle"></i> Unit Tanpa Rute
                                    <span class="ml-2 text-sm font-normal text-gray-600">({{ count($unitsWithoutRoute) }} Unit)</span>
                                </div>
                                <div>
                                    <i class="fas" :class="{'fa-chevron-down': open, 'fa-chevron-right': !open}"></i>
                                </div>
                            </h3>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95" x-transition:enter-end="opacity-100 transform scale-100" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                                @foreach($unitsWithoutRoute as $unit)
                                    <div class="unit-card border rounded-lg p-3 {{ !$unit->is_pool ? 'border-dashed border-orange-300 bg-orange-50 non-pool-unit' : (isset($settings) && $settings->isAutomatic() ? '' : 'cursor-pointer hover:bg-gray-50') }} {{ in_array($unit->id, $renopsUnits ?? []) ? 'selected' : '' }}"
                                         data-unit-id="{{ $unit->id }}"
                                         data-unit-number="{{ $unit->unit_number }}"
                                         data-plate-number="{{ $unit->plate_number }}"
                                         data-is-pool="{{ $unit->is_pool ? 'true' : 'false' }}"
                                         data-route-ids="">
                                        <div class="flex items-start justify-between">
                                            <div>
                                                <h3 class="font-semibold text-gray-800">{{ $unit->unit_number }}</h3>
                                                <p class="text-sm text-gray-600">{{ $unit->plate_number }}</p>
                                                <div class="flex items-center mt-1">
                                                    @if(!$unit->is_pool)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mr-1">
                                                            <i class="mr-1 text-xs fas fa-exclamation-triangle"></i> Non-Pool
                                                        </span>
                                                    @endif
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mr-1">
                                                        <i class="mr-1 text-xs fas fa-exclamation-circle"></i> Tanpa Rute
                                                    </span>
                                                </div>
                                            </div>
                                            <span class="status-badge px-2 py-1 text-xs font-medium rounded-full {{ in_array($unit->id, $renopsUnits ?? []) ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ in_array($unit->id, $renopsUnits ?? []) ? 'Tidak Beroperasi' : 'Tidak Dipilih' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

        @else
            <div class="py-8 text-center">
                <i class="mb-4 text-5xl text-gray-400 fas fa-calendar-times"></i>
                <p class="text-gray-600">Silakan pilih tanggal akhir pekan atau hari libur untuk mengelola rencana operasi unit.</p>
            </div>
        @endif
    </div>
</div>

<!-- Notification Modal -->
<div id="notification-modal" class="fixed inset-0 z-50 hidden w-full h-full overflow-y-auto bg-gray-600 bg-opacity-50" x-data="{ show: false, type: 'success', title: '', message: '', confirmCallback: null, cancelCallback: null, showCancel: false }" x-show="show" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click.away="show = false">
    <div class="relative p-5 mx-auto bg-white border rounded-md shadow-lg top-20 w-96">
        <div class="mt-3">
            <div class="flex items-center justify-between pb-3">
                <h3 class="text-lg font-medium" :class="{
                    'text-green-700': type === 'success',
                    'text-red-700': type === 'error',
                    'text-blue-700': type === 'info',
                    'text-yellow-700': type === 'warning',
                }" x-text="title"></h3>
                <button type="button" @click="show = false" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="py-3 mt-2 px-7">
                <div class="flex items-center justify-center">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto mb-4 rounded-full" :class="{
                        'bg-green-100': type === 'success',
                        'bg-red-100': type === 'error',
                        'bg-blue-100': type === 'info',
                        'bg-yellow-100': type === 'warning',
                    }">
                        <i class="text-2xl fas" :class="{
                            'fa-check text-green-600': type === 'success',
                            'fa-times text-red-600': type === 'error',
                            'fa-info-circle text-blue-600': type === 'info',
                            'fa-exclamation-triangle text-yellow-600': type === 'warning',
                        }"></i>
                    </div>
                </div>
                <p class="text-sm text-center text-gray-700" x-text="message"></p>
            </div>
            <div class="flex justify-end py-3 border-t px-7">
                <template x-if="showCancel">
                    <button type="button" @click="cancelCallback ? cancelCallback() : (show = false)" class="px-4 py-2 mr-2 font-bold text-white bg-gray-500 rounded hover:bg-gray-600">
                        Batal
                    </button>
                </template>
                <button type="button" @click="confirmCallback ? confirmCallback() : (show = false)" class="px-4 py-2 font-bold text-white rounded" :class="{
                    'bg-green-600 hover:bg-green-700': type === 'success',
                    'bg-red-600 hover:bg-red-700': type === 'error',
                    'bg-blue-600 hover:bg-blue-700': type === 'info',
                    'bg-yellow-600 hover:bg-yellow-700': type === 'warning',
                }">
                    OK
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for date range selection -->
<div id="period-modal" class="fixed inset-0 z-50 hidden w-full h-full overflow-y-auto bg-gray-600 bg-opacity-50">
    <div class="relative p-5 mx-auto bg-white border rounded-md shadow-lg top-20 w-96">
        <div class="mt-3">
            <div class="flex items-center justify-between pb-3">
                <h3 class="text-lg font-medium text-gray-900">Pilih Periode Generasi</h3>
                <button type="button" id="close-modal" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="py-3 mt-2 px-7">
                <div class="mb-4">
                    <label for="month-picker" class="block mb-1 text-sm font-medium text-gray-700">Bulan</label>
                    <input type="month" id="month-picker" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700">Periode</label>
                    <div class="space-y-2">
                        <div class="flex items-center">
                            <input type="radio" id="period-first" name="period" value="first" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500" checked>
                            <label for="period-first" class="block ml-2 text-sm text-gray-700">Periode Pertama (1-15)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="period-second" name="period" value="second" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <label for="period-second" class="block ml-2 text-sm text-gray-700">Periode Kedua (16-akhir bulan)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="period-full" name="period" value="full" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <label for="period-full" class="block ml-2 text-sm text-gray-700">Bulan Penuh</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end py-3 border-t px-7">
                <button type="button" id="cancel-generate" class="px-4 py-2 mr-2 font-bold text-white bg-gray-500 rounded hover:bg-gray-600">
                    Batal
                </button>
                <button type="button" id="confirm-generate" class="px-4 py-2 font-bold text-white bg-green-600 rounded hover:bg-green-700">
                    <i class="mr-2 fas fa-check"></i> Generate
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Main application controller
    const RenopsApp = {
        // DOM elements
        elements: {
            unitsContainer: document.getElementById('units-container'),
            unitSearch: document.getElementById('unit-search'),
            routeFilter: document.getElementById('route-filter'),
            poolFilter: document.getElementById('pool-filter'),
            saveChangesBtn: document.getElementById('save-changes'),
            deletePlanBtn: document.getElementById('delete-plan'),
            selectedCountSpan: document.getElementById('selected-count'),
            unitCards: document.querySelectorAll('.unit-card'),
            generateAutomaticBtn: document.getElementById('generate-automatic'),
            periodModal: document.getElementById('period-modal'),
            closeModalBtn: document.getElementById('close-modal'),
            cancelGenerateBtn: document.getElementById('cancel-generate'),
            confirmGenerateBtn: document.getElementById('confirm-generate'),
            monthPicker: document.getElementById('month-picker')
        },
        
        // Configuration data
        config: {
            date: '{{ $date->format('Y-m-d') }}',
            dayType: '{{ $dayType ?? "" }}',
            holidayId: '{{ $holiday->id ?? "" }}',
            maxLimit: {{ $maxLimit ?? 0 }},
            autoSuggestion: @json($autoSuggestion ?? null),
            isAutomatic: {{ isset($settings) && $settings->isAutomatic() ? 'true' : 'false' }}
        },
        
        // Notification modal helper
        notification: {
            modal: null,
            alpine: null,
            
            // Initialize notification modal
            init: function() {
                this.modal = document.getElementById('notification-modal');
                if (this.modal) {
                    this.alpine = this.modal.__x;
                }
            },
            
            // Show success notification
            success: function(title, message, callback = null) {
                if (this.alpine) {
                    this.alpine.$data.type = 'success';
                    this.alpine.$data.title = title;
                    this.alpine.$data.message = message;
                    this.alpine.$data.confirmCallback = callback;
                    this.alpine.$data.showCancel = false;
                    this.alpine.$data.show = true;
                    this.modal.classList.remove('hidden');
                } else {
                    alert(message);
                    if (callback) callback();
                }
            },
            
            // Show error notification
            error: function(title, message, callback = null) {
                if (this.alpine) {
                    this.alpine.$data.type = 'error';
                    this.alpine.$data.title = title;
                    this.alpine.$data.message = message;
                    this.alpine.$data.confirmCallback = callback;
                    this.alpine.$data.showCancel = false;
                    this.alpine.$data.show = true;
                    this.modal.classList.remove('hidden');
                } else {
                    alert(message);
                    if (callback) callback();
                }
            },
            
            // Show confirmation dialog
            confirm: function(title, message, confirmCallback, cancelCallback = null) {
                if (this.alpine) {
                    this.alpine.$data.type = 'warning';
                    this.alpine.$data.title = title;
                    this.alpine.$data.message = message;
                    this.alpine.$data.confirmCallback = confirmCallback;
                    this.alpine.$data.cancelCallback = cancelCallback;
                    this.alpine.$data.showCancel = true;
                    this.alpine.$data.show = true;
                    this.modal.classList.remove('hidden');
                } else {
                    if (confirm(message)) {
                        if (confirmCallback) confirmCallback();
                    } else {
                        if (cancelCallback) cancelCallback();
                    }
                }
            }
        },
        
        // Initialize the application
        init: function() {
            this.initializeFlatpickr();
            this.setupEventListeners();
            this.initializeAutoSuggestion();
            this.initializeMonthPicker();
            this.updateSelectedCount();
            this.notification.init();
        },
        
        // Initialize flatpickr calendar
        initializeFlatpickr: function() {
            flatpickr("#date", {
                dateFormat: "Y-m-d",
                locale: "id",
                disableMobile: true,
                allowInput: true,
                theme: "material_blue",
                onChange: function(selectedDates, dateStr) {
                    document.getElementById('date-form').submit();
                }
            });
        },
        
        // Initialize month picker with current month
        initializeMonthPicker: function() {
            if (this.elements.monthPicker) {
                const currentDate = new Date();
                const currentMonth = currentDate.getFullYear() + '-' + 
                    String(currentDate.getMonth() + 1).padStart(2, '0');
                this.elements.monthPicker.value = currentMonth;
            }
        },
        
        // Setup all event listeners
        setupEventListeners: function() {
            this.setupFilterListeners();
            this.setupUnitCardListeners();
            this.setupSaveChangesListener();
            this.setupDeletePlanListener();
            this.setupModalListeners();
        },
        
        // Setup filter listeners for search, route, and pool status
        setupFilterListeners: function() {
            const self = this;
            if (this.elements.routeFilter) {
                this.elements.routeFilter.addEventListener('change', function() {
                    self.filterUnits();
                });
            }
            
            if (this.elements.poolFilter) {
                this.elements.poolFilter.addEventListener('change', function() {
                    self.filterUnits();
                });
            }
            
            if (this.elements.unitSearch) {
                this.elements.unitSearch.addEventListener('input', function() {
                    self.filterUnits();
                });
            }
        },
        
        // Setup unit card click listeners
        setupUnitCardListeners: function() {
            const self = this;
            this.elements.unitCards.forEach(card => {
                // Only add click listeners to pool units (our own units)
                if (card.getAttribute('data-is-pool') === 'true') {
                    card.addEventListener('click', function() {
                        self.toggleUnitSelection(this);
                    });
                }
            });
        },
        
        // Setup save changes button listener
        setupSaveChangesListener: function() {
            const self = this;
            if (this.elements.saveChangesBtn) {
                this.elements.saveChangesBtn.addEventListener('click', function() {
                    self.saveChanges(this);
                });
            }
        },
        
        // Setup delete plan button listener
        setupDeletePlanListener: function() {
            const self = this;
            if (this.elements.deletePlanBtn) {
                this.elements.deletePlanBtn.addEventListener('click', function() {
                    self.deletePlan(this);
                });
            }
        },
        
        // Setup modal related listeners
        setupModalListeners: function() {
            const self = this;
            const { periodModal, generateAutomaticBtn, closeModalBtn, 
                    cancelGenerateBtn, confirmGenerateBtn } = this.elements;
            
            // Open modal
            if (generateAutomaticBtn && periodModal) {
                generateAutomaticBtn.addEventListener('click', function() {
                    periodModal.classList.remove('hidden');
                });
            }
            
            // Close modal handlers
            if (closeModalBtn && periodModal) {
                closeModalBtn.addEventListener('click', function() {
                    periodModal.classList.add('hidden');
                });
            }
            
            if (cancelGenerateBtn && periodModal) {
                cancelGenerateBtn.addEventListener('click', function() {
                    periodModal.classList.add('hidden');
                });
            }
            
            // Confirm generation handler
            if (confirmGenerateBtn && periodModal) {
                confirmGenerateBtn.addEventListener('click', function() {
                    self.generateAutomaticPlan(this);
                });
            }
        },
        
        // We're no longer auto-selecting units when loading the page
        // This prevents automatic generation when navigating to the page
        initializeAutoSuggestion: function() {
            // Just highlight existing renops units that are already in the database
            // without triggering click events that would send AJAX requests
            const renopsUnits = @json($renopsUnits ?? []);
            
            if (renopsUnits && renopsUnits.length > 0) {
                this.elements.unitCards.forEach(card => {
                    const unitId = parseInt(card.getAttribute('data-unit-id'));
                    if (renopsUnits.includes(unitId)) {
                        // Just add the selected class without triggering click event
                        card.classList.add('selected');
                        
                        // Update status badge manually
                        const statusBadge = card.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.textContent = 'Tidak Beroperasi';
                            statusBadge.classList.remove('bg-gray-100', 'text-gray-800');
                            statusBadge.classList.add('bg-red-100', 'text-red-800');
                        }
                    }
                });
                
                // Update the count display
                this.updateSelectedCount();
            }
        },
        
        // Filter units by search term, route, and pool status
        filterUnits: function() {
            const searchTerm = this.elements.unitSearch.value.toLowerCase();
            const selectedRouteId = this.elements.routeFilter.value;
            const poolFilter = this.elements.poolFilter.value;
            
            this.elements.unitCards.forEach(card => {
                const unitNumber = card.getAttribute('data-unit-number').toLowerCase();
                const plateNumber = card.getAttribute('data-plate-number').toLowerCase();
                const routeIds = card.getAttribute('data-route-ids');
                const isPool = card.getAttribute('data-is-pool') === 'true';
                
                const matchesSearch = unitNumber.includes(searchTerm) || plateNumber.includes(searchTerm);
                const matchesRoute = !selectedRouteId || (routeIds && routeIds.split(',').includes(selectedRouteId));
                const matchesPool = poolFilter === '' || 
                                   (poolFilter === 'pool' && isPool) || 
                                   (poolFilter === 'non-pool' && !isPool);
                
                card.style.display = (matchesSearch && matchesRoute && matchesPool) ? '' : 'none';
            });
        },
        
        // Toggle unit selection state
        toggleUnitSelection: function(card) {
            const unitId = card.getAttribute('data-unit-id');
            
            // Toggle visual selection
            card.classList.toggle('selected');
            
            // Update status badge
            const statusBadge = card.querySelector('.status-badge');
            if (card.classList.contains('selected')) {
                statusBadge.textContent = 'Tidak Beroperasi';
                statusBadge.classList.remove('bg-gray-100', 'text-gray-800');
                statusBadge.classList.add('bg-red-100', 'text-red-800');
            } else {
                statusBadge.textContent = 'Tidak Dipilih';
                statusBadge.classList.remove('bg-red-100', 'text-red-800');
                statusBadge.classList.add('bg-gray-100', 'text-gray-800');
            }
            
            this.updateSelectedCount();
            
            // Send AJAX request to toggle unit
            this.updateUnitStatus(unitId, card);
        },
        
        // Update unit status via AJAX
        updateUnitStatus: function(unitId, card) {
            const { date, dayType, holidayId } = this.config;
            
            // Get the route ID from the route filter or from the card's routes
            let routeId = this.elements.routeFilter.value;
            if (!routeId) {
                const routeIds = card.getAttribute('data-route-ids');
                if (routeIds) {
                    const routeIdArray = routeIds.split(',');
                    if (routeIdArray.length > 0) {
                        routeId = routeIdArray[0]; // Use the first route if multiple
                    }
                }
            }
            
            if (date && dayType) {
                fetch('{{ route('renops.toggle-unit') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        unit_id: unitId,
                        route_id: routeId,
                        date: date,
                        day_type: dayType,
                        holiday_id: holidayId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Error:', data.message);
                        // Revert the visual change if there was an error
                        this.revertCardSelection(card);
                        this.notification.error('Gagal', data.message || 'Terjadi kesalahan saat mengubah status unit.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    this.revertCardSelection(card);
                    this.notification.error('Gagal', 'Terjadi kesalahan saat mengubah status unit.');
                });
            }
        },
        
        // Revert card selection state in case of error
        revertCardSelection: function(card) {
            card.classList.toggle('selected');
            const statusBadge = card.querySelector('.status-badge');
            statusBadge.textContent = card.classList.contains('selected') ? 'Tidak Beroperasi' : 'Tidak Dipilih';
            statusBadge.classList.toggle('bg-red-100');
            statusBadge.classList.toggle('text-red-800');
            statusBadge.classList.toggle('bg-gray-100');
            statusBadge.classList.toggle('text-gray-800');
            this.updateSelectedCount();
        },
        
        // Update the selected count display and progress bar
        updateSelectedCount: function() {
            const selectedCards = document.querySelectorAll('.unit-card.selected');
            this.elements.selectedCountSpan.textContent = selectedCards.length;
            
            // Update progress bar
            const progressBar = document.querySelector('.progress-bar');
            const percentage = this.config.maxLimit > 0 ? (selectedCards.length / this.config.maxLimit) * 100 : 0;
            progressBar.style.width = `${percentage}%`;
            
            // Change color based on percentage
            progressBar.classList.remove('bg-blue-600', 'bg-yellow-600', 'bg-red-600');
            if (percentage > 90) {
                progressBar.classList.add('bg-red-600');
            } else if (percentage > 70) {
                progressBar.classList.add('bg-yellow-600');
            } else {
                progressBar.classList.add('bg-blue-600');
            }
            
            // Check if we're exceeding the maximum limit
            if (selectedCards.length > this.config.maxLimit) {
                this.elements.selectedCountSpan.classList.add('text-red-600', 'font-bold');
            } else {
                this.elements.selectedCountSpan.classList.remove('text-red-600', 'font-bold');
            }
        },
        
        // Save changes to the plan
        saveChanges: function(button) {
            const selectedUnits = Array.from(document.querySelectorAll('.unit-card.selected'))
                .map(card => card.getAttribute('data-unit-id'));
            
            if (selectedUnits.length > this.config.maxLimit) {
                this.notification.error('Batas Maksimum Terlampaui', `Tidak dapat menyimpan perubahan. Batas maksimum adalah ${this.config.maxLimit} unit.`);
                return;
            }
            
            button.disabled = true;
            button.innerHTML = '<i class="mr-2 fas fa-circle-notch fa-spin"></i> Menyimpan...';
            
            fetch(`/renops/${this.config.date}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    unit_ids: selectedUnits,
                    day_type: this.config.dayType,
                    holiday_id: this.config.dayType === 'holiday' ? this.config.holidayId : null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.notification.success('Berhasil', 'Rencana operasi unit berhasil diperbarui.', function() {
                        window.location.reload();
                    });
                } else {
                    this.notification.error('Gagal', data.message || 'Gagal memperbarui rencana operasi unit.');
                    button.disabled = false;
                    button.innerHTML = '<i class="mr-2 fas fa-save"></i> Simpan Perubahan';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.notification.error('Terjadi Kesalahan', 'Terjadi kesalahan saat memperbarui rencana operasi unit.');
                button.disabled = false;
                button.innerHTML = '<i class="mr-2 fas fa-save"></i> Simpan Perubahan';
            });
        },
        
        // Delete the operational plan
        deletePlan: function(button) {
            const self = this;
            
            this.notification.confirm('Konfirmasi Hapus', 'Apakah Anda yakin ingin menghapus rencana operasi ini?', function() {
                button.disabled = true;
                button.innerHTML = '<i class="mr-2 fas fa-circle-notch fa-spin"></i> Menghapus...';
                
                fetch(`/renops/${self.config.date}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        self.notification.success('Berhasil', 'Rencana operasi unit berhasil dihapus.', function() {
                            window.location.href = '{{ route('renops.index') }}';
                        });
                    } else {
                        self.notification.error('Gagal', data.message || 'Gagal menghapus rencana operasi unit.');
                        button.disabled = false;
                        button.innerHTML = '<i class="mr-2 fas fa-trash-alt"></i> Hapus Rencana';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    self.notification.error('Terjadi Kesalahan', 'Terjadi kesalahan saat menghapus rencana operasi unit.');
                    button.disabled = false;
                    button.innerHTML = '<i class="mr-2 fas fa-trash-alt"></i> Hapus Rencana';
                });
            });
        },
        
        // Generate automatic operation plan
        generateAutomaticPlan: function(button) {
            // Get selected period
            const selectedPeriodElement = document.querySelector('input[name="period"]:checked');
            if (!selectedPeriodElement) {
                this.notification.error('Input Diperlukan', 'Silakan pilih periode terlebih dahulu.');
                return;
            }
            
            const selectedPeriod = selectedPeriodElement.value;
            const selectedMonth = this.elements.monthPicker.value;
            
            if (!selectedMonth) {
                this.notification.error('Input Diperlukan', 'Silakan pilih bulan terlebih dahulu.');
                return;
            }
            
            // Parse the month value (YYYY-MM)
            const [year, month] = selectedMonth.split('-');
            
            // Create start and end dates based on period
            let startDate, endDate;
            
            if (selectedPeriod === 'first') {
                // First period: 1-15
                startDate = `${year}-${month}-01`;
                endDate = `${year}-${month}-15`;
            } else if (selectedPeriod === 'second') {
                // Second period: 16-end of month
                startDate = `${year}-${month}-16`;
                // Calculate last day of month
                const lastDay = new Date(year, parseInt(month), 0).getDate();
                endDate = `${year}-${month}-${lastDay}`;
            } else {
                // Full month
                startDate = `${year}-${month}-01`;
                // Calculate last day of month
                const lastDay = new Date(year, parseInt(month), 0).getDate();
                endDate = `${year}-${month}-${lastDay}`;
            }
            
            // Confirm before proceeding
            const periodText = selectedPeriod === 'first' ? 'periode pertama (1-15)' : 
                            (selectedPeriod === 'second' ? 'periode kedua (16-akhir bulan)' : 'bulan penuh');
            
            const self = this;
            this.notification.confirm(
                'Konfirmasi Generasi', 
                `Apakah Anda yakin ingin membuat rencana operasi otomatis untuk ${periodText}? Ini akan menggantikan rencana yang sudah ada.`,
                function() {
                    // Disable button and show loading state
                    button.disabled = true;
                    button.innerHTML = '<i class="mr-2 fas fa-circle-notch fa-spin"></i> Memproses...';
                    
                    // Close the modal
                    self.elements.periodModal.classList.add('hidden');
                    
                    // Make API request
                    fetch('{{ route("renops.generate-automatic") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            start_date: startDate,
                            end_date: endDate,
                            period: selectedPeriod
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            self.notification.success(
                                'Berhasil', 
                                data.message || 'Rencana operasi otomatis berhasil dibuat.',
                                function() {
                                    // Reload page if we're viewing a date that was just processed
                                    const currentViewDate = '{{ $date->format("Y-m-d") }}';
                                    const processedDates = data.results?.details?.map(detail => detail.date) || [];
                                    
                                    if (processedDates.includes(currentViewDate)) {
                                        window.location.reload();
                                    }
                                }
                            );
                        } else {
                            self.notification.error('Gagal', data.message || 'Gagal membuat rencana operasi otomatis.');
                        }
                        button.disabled = false;
                        button.innerHTML = '<i class="mr-2 fas fa-check"></i> Generate';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        self.notification.error('Terjadi Kesalahan', 'Terjadi kesalahan saat membuat rencana operasi otomatis.');
                        button.disabled = false;
                        button.innerHTML = '<i class="mr-2 fas fa-check"></i> Generate';
                    });
                }
            );
        }
    };
    
    // Initialize the application
    RenopsApp.init();
});
</script>
@endpush

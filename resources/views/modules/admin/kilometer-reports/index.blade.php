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
                    <a href="{{ route('kilometer-reports.index', ['period' => $period, 'group' => $group]) }}" 
                       class="py-3 px-4 text-center border-b-2 font-medium text-sm whitespace-nowrap {{ $activeRouteGroup == $group ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $group }}
                    </a>
                    @endif
                @endforeach
                <a href="{{ route('kilometer-reports.index', ['period' => $period, 'group' => 'all']) }}" 
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
                            <!-- Route Header Row -->
                            <tr class="bg-gray-100">
                                <td colspan="{{ count($dates) + 2 }}" class="px-6 py-2 whitespace-nowrap text-sm font-bold text-gray-900 sticky left-0 bg-gray-100 z-10">
                                    {{ $route->route_number }} - {{ $route->name }}
                                </td>
                            </tr>
                            
                            <!-- Unit Rows for this Route -->
                            @foreach($route->units as $unit)
                                <tr class="hover:bg-gray-50 border-t border-gray-200">
                                    <td class="pl-8 pr-6 py-2 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white z-10 hover:bg-gray-50">
                                        <a href="{{ route('kilometer-reports.show', ['unit' => $unit->id, 'period' => $period]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $unit->unit_number }} - {{ $unit->plate_number }}
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
                                            $cellClass = $isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : '');
                                            
                                            $kilometers = isset($reportsByRouteUnitDate[$route->id][$unit->id][$date]) ? 
                                                $reportsByRouteUnitDate[$route->id][$unit->id][$date]->kilometers : 0;
                                            
                                            $kmBelowThreshold = $kilometers > 0 && $kilometers < 150;
                                            $cellBgClass = $kmBelowThreshold ? 'bg-red-100' : ($isHoliday ? 'bg-yellow-50' : ($isWeekend ? 'bg-orange-50' : ''));
                                        @endphp
                                        <td class="px-6 py-2 whitespace-nowrap text-sm text-center text-gray-500 {{ $cellBgClass }}" x-data="{ 
                                            isEditing: false, 
                                            kilometers: '{{ $kilometers }}'
                                        }">
                                            <!-- View Mode -->
                                            <template x-if="!editMode">
                                                <div>
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
    <div x-show="showImportModal" class="fixed inset-0 overflow-y-auto z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div x-show="showImportModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" role="dialog" aria-modal="true" aria-labelledby="modal-headline">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-purple-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-file-import text-purple-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-headline">
                                Import Laporan Kilometer
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 mb-4">
                                    Silakan unduh template dan isi data kilometer, kemudian unggah file yang telah diisi.
                                </p>
                                
                                <div class="mb-4">
                                    <a href="{{ route('kilometer-reports.template', ['period' => $period, 'group' => $activeRouteGroup]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                                        <i class="fas fa-download mr-2"></i>
                                        Download Template
                                    </a>
                                </div>
                                
                                <form id="import-form" action="{{ route('kilometer-reports.import') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="period" value="{{ $period }}">
                                    <input type="hidden" name="group" value="{{ $activeRouteGroup }}">
                                    
                                    <div class="mb-4">
                                        <label for="import_file" class="block text-sm font-medium text-gray-700 mb-1">File Excel</label>
                                        <input type="file" id="import_file" name="import_file" accept=".xlsx,.xls" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" required>
                                        <p class="mt-1 text-xs text-gray-500">Format file: .xlsx, .xls</p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="submitImportForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-purple-600 text-base font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Import
                    </button>
                    <button type="button" @click="showImportModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function kilometerReport() {
        return {
            editMode: false,
            showImportModal: false,
            toggleImportModal() {
                this.showImportModal = !this.showImportModal;
            },
            toggleEditMode() {
                if (this.editMode) {
                    // Switching from edit mode to view mode
                    this.editMode = false;
                    
                    // Refresh the table data
                    this.refreshTableData();
                } else {
                    // Switching from view mode to edit mode
                    this.editMode = true;
                }
            },
            refreshTableData() {
                // Show loading toast
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        message: 'Memperbarui data...',
                        type: 'info',
                        duration: 2000
                    }
                }));
                
                // Reload the table content via AJAX
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newTable = doc.getElementById('km-report-table');
                        
                        if (newTable) {
                            document.getElementById('km-report-table').innerHTML = newTable.innerHTML;
                            
                            // Show success toast
                            window.dispatchEvent(new CustomEvent('toast', {
                                detail: {
                                    message: 'Data berhasil diperbarui',
                                    type: 'success',
                                    duration: 3000
                                }
                            }));
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing data:', error);
                        
                        // Show error toast
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                message: 'Gagal memperbarui data',
                                type: 'error',
                                duration: 3000
                            }
                        }));
                    });
            },
            saveKilometers(event, unitId, routeId, date) {
                const kilometers = event.target.closest('td').querySelector('input').value;
                
                if (!kilometers || kilometers <= 0) {
                    // Show error toast
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: 'Masukkan jumlah kilometer yang valid',
                            type: 'error',
                            duration: 3000
                        }
                    }));
                    return;
                }
                
                // Show loading state
                const saveButton = event.target.closest('button');
                const originalHTML = saveButton.innerHTML;
                saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                saveButton.disabled = true;
                
                fetch('{{ route("kilometer-reports.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        unit_id: unitId,
                        route_id: routeId,
                        date: date,
                        kilometers: kilometers
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Reset editing state
                        const td = event.target.closest('td');
                        td.__x.$data.isEditing = false;
                        
                        // Show success toast
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                message: 'Data kilometer berhasil disimpan',
                                type: 'success',
                                duration: 3000
                            }
                        }));
                        
                        // Update the displayed value without reloading
                        const valueDisplay = td.querySelector('div > span');
                        if (valueDisplay) {
                            valueDisplay.textContent = parseFloat(kilometers).toFixed(1);
                        }
                    } else {
                        // Show error toast
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                message: 'Error: ' + data.message,
                                type: 'error',
                                duration: 3000
                            }
                        }));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Show warning toast
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: 'Mungkin tersimpan, refresh untuk melihat',
                            type: 'warning',
                            duration: 3000
                        }
                    }));
                    
                    // Reset editing state
                    const td = event.target.closest('td');
                    td.__x.$data.isEditing = false;
                })
                .finally(() => {
                    // Restore button state
                    saveButton.innerHTML = originalHTML;
                    saveButton.disabled = false;
                });
            },
            submitImportForm() {
                const form = document.getElementById('import-form');
                form.submit();
            }
        }
    }
</script>
@endpush
@endsection

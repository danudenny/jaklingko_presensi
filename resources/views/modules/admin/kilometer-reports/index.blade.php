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

    <!-- Period Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
                <a href="{{ route('kilometer-reports.index', ['period' => 1]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 1 (1-15)
                </a>
                <a href="{{ route('kilometer-reports.index', ['period' => 2]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 2 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 2 (16-{{ Carbon\Carbon::now()->endOfMonth()->format('d') }})
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
                </h3>
                <div class="flex space-x-2">
                    <a href="{{ route('kilometer-reports.export.excel', ['period' => $period]) }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-excel mr-2"></i>
                        Export Excel
                    </a>
                    <a href="{{ route('kilometer-reports.export.pdf', ['period' => $period]) }}" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                    <button type="button" @click="toggleEditMode" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas" :class="editMode ? 'fa-eye' : 'fa-edit'"></i>
                        <span class="ml-2" x-text="editMode ? 'Mode Lihat' : 'Mode Edit'"></span>
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto" id="km-report-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 w-48">
                                Rute / Unit
                            </th>
                            @foreach($dates as $date)
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ \Carbon\Carbon::parse($date)->format('d') }}
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
                                <td class="px-6 py-3 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-gray-100 z-10">
                                    <div class="flex items-center justify-between">
                                        <span class="font-bold">{{ $route->route_number }} - {{ $route->name }}</span>
                                        @if(isset($routeTotals[$route->id]))
                                            <span class="text-xs text-gray-500">({{ count(array_filter($routeUnitTotals[$route->id] ?? [])) }} unit)</span>
                                        @endif
                                    </div>
                                </td>
                                @foreach($dates as $date)
                                    <td class="px-6 py-3 whitespace-nowrap text-sm text-center text-gray-500 bg-gray-100">
                                        <!-- Date total for this route -->
                                        @php
                                            $routeDateTotal = 0;
                                            if(isset($reportsByRouteUnitDate[$route->id])) {
                                                foreach($reportsByRouteUnitDate[$route->id] as $unitReports) {
                                                    if(isset($unitReports[$date])) {
                                                        $routeDateTotal += $unitReports[$date]->kilometers;
                                                    }
                                                }
                                            }
                                        @endphp
                                        {{ $routeDateTotal > 0 ? number_format($routeDateTotal, 1) : '-' }}
                                    </td>
                                @endforeach
                                <td class="px-6 py-3 whitespace-nowrap text-sm text-center font-bold text-gray-900 bg-gray-200">
                                    {{ isset($routeTotals[$route->id]) ? number_format($routeTotals[$route->id], 1) : '0.0' }}
                                </td>
                            </tr>
                            
                            <!-- Unit Rows for this Route -->
                            @foreach($route->units as $unit)
                                <tr class="hover:bg-gray-50 border-t border-gray-200">
                                    <td class="pl-10 pr-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white z-10 hover:bg-gray-50">
                                        <a href="{{ route('kilometer-reports.show', ['unit' => $unit->id, 'period' => $period]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            {{ $unit->unit_number }} - {{ $unit->plate_number }}
                                        </a>
                                    </td>
                                    @foreach($dates as $date)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500" x-data="{ 
                                            isEditing: false, 
                                            kilometers: '{{ isset($reportsByRouteUnitDate[$route->id][$unit->id][$date]) ? $reportsByRouteUnitDate[$route->id][$unit->id][$date]->kilometers : '' }}'
                                        }">
                                            <!-- View Mode -->
                                            <template x-if="!editMode">
                                                <div>
                                                    @if(isset($reportsByRouteUnitDate[$route->id][$unit->id][$date]))
                                                        {{ number_format($reportsByRouteUnitDate[$route->id][$unit->id][$date]->kilometers, 1) }}
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
                                                        @if(isset($reportsByRouteUnitDate[$route->id][$unit->id][$date]))
                                                            <span>{{ number_format($reportsByRouteUnitDate[$route->id][$unit->id][$date]->kilometers, 1) }}</span>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-gray-900 bg-gray-100">
                                        {{ isset($routeUnitTotals[$route->id][$unit->id]) ? number_format($routeUnitTotals[$route->id][$unit->id], 1) : '0.0' }}
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <th scope="row" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                                Total
                            </th>
                            @foreach($dates as $date)
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-700">
                                    {{ isset($dateTotals[$date]) ? number_format($dateTotals[$date], 1) : '0.0' }}
                                </th>
                            @endforeach
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-700 bg-gray-200">
                                {{ number_format($grandTotal, 1) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-card>
    </div>
</div>

@push('scripts')
<script>
    function kilometerReport() {
        return {
            editMode: false,
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
            }
        }
    }
</script>
@endpush
@endsection

@extends('modules.admin.layouts.main')

@section('title', 'Detail Laporan Kilometer Unit')

@section('content')
<div class="container mx-auto" x-data="kilometerReport()">
    <x-page-title>
        <x-slot name="title">Detail Laporan Kilometer: {{ $unit->unit_number }} - {{ $unit->plate_number }}</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('kilometer-reports.index', ['period' => $period, 'month' => $month, 'year' => $year]) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
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
                <a href="{{ route('kilometer-reports.show', ['unit' => $unit->id, 'period' => 1, 'month' => $month, 'year' => $year]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 1 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 1 (1-15)
                </a>
                <a href="{{ route('kilometer-reports.show', ['unit' => $unit->id, 'period' => 2, 'month' => $month, 'year' => $year]) }}" 
                   class="py-4 px-6 text-center border-b-2 font-medium text-sm {{ $period == 2 ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Periode 2 (16-{{ Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('d') }})
                </a>
            </nav>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- KM Report Table -->
        <div class="md:col-span-2">
            <x-card>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">
                        Laporan Kilometer per Rute - Periode: {{ $period == 1 ? '1-15' : '16-' . Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->format('d') }} {{ Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}
                    </h3>
                    <div>
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
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10">
                                    Rute
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
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 sticky left-0 bg-white z-10 hover:bg-gray-50">
                                        {{ $route->route_number }} - {{ $route->name }}
                                    </td>
                                    @foreach($dates as $date)
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500" x-data="{ 
                                            isEditing: false, 
                                            kilometers: '{{ isset($reportsByRouteAndDate[$route->id][$date]) ? $reportsByRouteAndDate[$route->id][$date]->kilometers : '' }}'
                                        }">
                                            <!-- View Mode -->
                                            <template x-if="!editMode">
                                                <div>
                                                    @if(isset($reportsByRouteAndDate[$route->id][$date]))
                                                        {{ number_format($reportsByRouteAndDate[$route->id][$date]->kilometers, 1) }}
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </template>
                                            
                                            <!-- Edit Mode -->
                                            <template x-if="editMode">
                                                <div class="flex flex-col space-y-2">
                                                    <!-- Current Value -->
                                                    <div x-show="!isEditing" class="flex flex-col items-center">
                                                        @if(isset($reportsByRouteAndDate[$route->id][$date]))
                                                            <span>{{ number_format($reportsByRouteAndDate[$route->id][$date]->kilometers, 1) }}</span>
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
                                        {{ isset($routeTotals[$route->id]) ? number_format($routeTotals[$route->id], 1) : '0.0' }}
                                    </td>
                                </tr>
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

        <!-- Related Information -->
        <div>
            <!-- Unit Problems -->
            <x-card class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Masalah Unit Terkait</h3>
                
                @if($unitProblems->count() > 0)
                    <div class="space-y-4">
                        @foreach($unitProblems as $problem)
                            <div class="p-4 border rounded-md hover:bg-gray-50">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">{{ $problem->date_reported->format('d/m/Y') }}</span>
                                    <a href="{{ route('unit-problems.show', $problem->id) }}" class="text-xs text-indigo-600 hover:text-indigo-900">
                                        Lihat Detail
                                    </a>
                                </div>
                                <p class="text-sm text-gray-700 mt-2 line-clamp-2">{{ $problem->description }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500">
                        <p>Tidak ada masalah unit dalam periode ini</p>
                    </div>
                @endif
            </x-card>

            <!-- Schedules -->
            <x-card>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Jadwal Unit Terkait</h3>
                
                @if($schedules->count() > 0)
                    <div class="space-y-4">
                        @foreach($schedules->take(5) as $schedule)
                            <div class="p-4 border rounded-md hover:bg-gray-50">
                                <div class="flex justify-between">
                                    <span class="text-sm font-medium text-gray-500">{{ $schedule->schedule_date->format('d/m/Y') }}</span>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $schedule->shift == 'Pagi' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $schedule->shift }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-700 mt-2">
                                    Pengemudi: {{ $schedule->driver->name ?? 'Tidak ada' }}
                                </p>
                            </div>
                        @endforeach

                        @if($schedules->count() > 5)
                            <div class="text-center">
                                <p class="text-sm text-gray-500">Menampilkan 5 dari {{ $schedules->count() }} jadwal</p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4 text-gray-500">
                        <p>Tidak ada jadwal dalam periode ini</p>
                    </div>
                @endif
            </x-card>
        </div>
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
                
                // Reload the table content via AJAX with current month and year parameters
                const currentUrl = new URL(window.location.href);
                // Ensure month and year parameters are preserved
                if (!currentUrl.searchParams.has('month')) {
                    currentUrl.searchParams.set('month', '{{ $month }}');
                }
                if (!currentUrl.searchParams.has('year')) {
                    currentUrl.searchParams.set('year', '{{ $year }}');
                }
                fetch(currentUrl.toString())
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
                        kilometers: kilometers,
                        month: {{ $month }},
                        year: {{ $year }}
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

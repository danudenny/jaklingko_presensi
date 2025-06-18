@extends('modules.admin.layouts.main')

@section('title', 'Export Schedule Summary')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Export Schedule Summary</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                <i class="mr-2 fas fa-arrow-left"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <!-- Materialized View Status -->
    <x-card class="mb-6">
        <div class="p-4 rounded-lg bg-blue-50">
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="text-sm font-medium text-blue-900">⚡ Lightning Fast Multi-Sheet Export</h4>
                    <p class="mt-1 text-sm text-blue-700">
                        Export menggunakan materialized view dengan format multi-sheet. Setiap bulan akan menjadi worksheet terpisah dengan total hari kerja driver per bulan.
                    </p>
                    <div id="materializedStats" class="mt-3 text-xs text-blue-600">
                        <!-- Stats will be loaded here -->
                    </div>
                </div>
                <button type="button" 
                        id="refreshMaterialized" 
                        class="inline-flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="mr-1 fas fa-sync-alt"></i>
                    Refresh Data
                </button>
            </div>
        </div>
    </x-card>

    <x-card>
        <div class="mb-6">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Filter Export Data</h3>
            <p class="mb-6 text-sm text-gray-600">
                Export akan menghasilkan file Excel dengan multiple worksheets. Setiap worksheet berisi data untuk satu bulan dengan kolom: ID, Nama Driver, Unit, Rute, No Rekening, dan Total Days (jumlah hari kerja dalam bulan tersebut).
            </p>
        </div>

        <form action="{{ route('schedules.export.summary.excel') }}" method="POST" class="space-y-6" id="exportForm" onsubmit="return handleExportSubmit()">
            @csrf
            
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Date Range -->
                <div class="space-y-4">
                    <div>
                        <label for="date_range_type" class="block text-sm font-medium text-gray-700">Rentang Tanggal</label>
                        <select name="date_range_type" 
                                id="date_range_type" 
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                onchange="toggleDateRangeInputs()">
                            <option value="custom" {{ old('date_range_type') == 'custom' ? 'selected' : '' }}>Custom (Pilih Tanggal)</option>
                            <option value="month" {{ old('date_range_type') == 'month' ? 'selected' : '' }}>Per Bulan</option>
                            <option value="year" {{ old('date_range_type') == 'year' ? 'selected' : '' }}>Per Tahun</option>
                            <option value="ytd" {{ old('date_range_type') == 'ytd' ? 'selected' : '' }}>Year to Date (YTD)</option>
                            <option value="all" {{ old('date_range_type') == 'all' ? 'selected' : '' }}>Semua Data</option>
                        </select>
                        @error('date_range_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Custom Date Range -->
                    <div id="custom-date-range" class="space-y-3">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                            <input type="date" 
                                   name="start_date" 
                                   id="start_date" 
                                   value="{{ old('start_date') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                            <input type="date" 
                                   name="end_date" 
                                   id="end_date" 
                                   value="{{ old('end_date') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Month Year Selection -->
                    <div id="month-year-range" class="space-y-3" style="display: none;">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="selected_month" class="block text-sm font-medium text-gray-700">Bulan</label>
                                <select name="selected_month" 
                                        id="selected_month" 
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="1" {{ old('selected_month') == '1' ? 'selected' : '' }}>Januari</option>
                                    <option value="2" {{ old('selected_month') == '2' ? 'selected' : '' }}>Februari</option>
                                    <option value="3" {{ old('selected_month') == '3' ? 'selected' : '' }}>Maret</option>
                                    <option value="4" {{ old('selected_month') == '4' ? 'selected' : '' }}>April</option>
                                    <option value="5" {{ old('selected_month') == '5' ? 'selected' : '' }}>Mei</option>
                                    <option value="6" {{ old('selected_month') == '6' ? 'selected' : '' }}>Juni</option>
                                    <option value="7" {{ old('selected_month') == '7' ? 'selected' : '' }}>Juli</option>
                                    <option value="8" {{ old('selected_month') == '8' ? 'selected' : '' }}>Agustus</option>
                                    <option value="9" {{ old('selected_month') == '9' ? 'selected' : '' }}>September</option>
                                    <option value="10" {{ old('selected_month') == '10' ? 'selected' : '' }}>Oktober</option>
                                    <option value="11" {{ old('selected_month') == '11' ? 'selected' : '' }}>November</option>
                                    <option value="12" {{ old('selected_month') == '12' ? 'selected' : '' }}>Desember</option>
                                </select>
                                @error('selected_month')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="selected_year_month" class="block text-sm font-medium text-gray-700">Tahun</label>
                                <select name="selected_year_month" 
                                        id="selected_year_month" 
                                        class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @for($year = 2020; $year <= date('Y') + 2; $year++)
                                        <option value="{{ $year }}" {{ (old('selected_year_month') == $year || (!old('selected_year_month') && $year == date('Y'))) ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                    @endfor
                                </select>
                                @error('selected_year_month')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Year Only Selection -->
                    <div id="year-only-range" class="space-y-3" style="display: none;">
                        <div>
                            <label for="selected_year" class="block text-sm font-medium text-gray-700">Tahun</label>
                            <select name="selected_year" 
                                    id="selected_year" 
                                    class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @for($year = 2020; $year <= date('Y') + 2; $year++)
                                    <option value="{{ $year }}" {{ (old('selected_year') == $year || (!old('selected_year') && $year == date('Y'))) ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                            @error('selected_year')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="space-y-4">
                    <div>
                        <label for="route_id" class="block text-sm font-medium text-gray-700">Rute</label>
                        <select name="route_id" 
                                id="route_id" 
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                onchange="loadUnitsForRoute()">
                            <option value="all">Semua Rute</option>
                            @foreach($routes as $route)
                                <option value="{{ $route->id }}" {{ old('route_id') == $route->id ? 'selected' : '' }}>
                                    {{ $route->route_number }} - {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('route_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="unit_search" class="block text-sm font-medium text-gray-700">Unit</label>
                        <div class="relative mt-1">
                            <input type="text" 
                                   id="unit_search" 
                                   placeholder="Cari unit..."
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                   autocomplete="off"
                                   disabled>
                            <input type="hidden" name="unit_ids" id="unit_ids" value="{{ old('unit_ids', 'all') }}">
                            
                            <!-- Dropdown results -->
                            <div id="unit_dropdown" class="absolute z-10 hidden w-full overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-60">
                                <div class="p-2 border-b border-gray-200 bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <label class="flex items-center text-sm font-medium text-gray-700">
                                            <input type="checkbox" id="unit_all" class="mr-2" checked onchange="toggleAllUnits()">
                                            Semua Unit
                                        </label>
                                        <button type="button" id="clear_units" class="text-xs text-red-600 hover:text-red-800" onclick="clearAllUnits()">
                                            Clear
                                        </button>
                                    </div>
                                </div>
                                <div id="unit_list" class="p-2 space-y-1">
                                    <!-- Units will be loaded here via JavaScript -->
                                </div>
                            </div>
                        </div>
                        <div id="selected_units_display" class="mt-2">
                            <div class="mb-2 text-sm text-gray-600">
                                <span class="font-medium">Terpilih:</span> <span id="selected_units_count">Semua Unit</span>
                            </div>
                            <div id="selected_units_tags" class="flex flex-wrap gap-1">
                                <!-- Selected unit tags will appear here -->
                            </div>
                        </div>
                        @error('unit_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="driver_type" class="block text-sm font-medium text-gray-700">Tipe Driver</label>
                        <select name="driver_type" 
                                id="driver_type" 
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Semua Tipe</option>
                            <option value="batangan" {{ old('driver_type') == 'batangan' ? 'selected' : '' }}>Batangan</option>
                            <option value="cadangan" {{ old('driver_type') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                        </select>
                        @error('driver_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Export Info -->
            <div class="p-4 border border-blue-200 rounded-md bg-blue-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="text-blue-400 fas fa-info-circle"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Informasi Export</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="space-y-1 list-disc list-inside">
                                <li><strong>Kolom yang Diekspor:</strong> ID, Nama Driver, Unit, Rute, No Rekening, Total Days</li>
                                <li><strong>Data:</strong> Ringkasan jadwal berdasarkan driver, unit, dan rute</li>
                                <li><strong>Total Days:</strong> Jumlah hari driver bekerja pada unit dan rute tertentu</li>
                                <li><strong>Status:</strong> Hanya jadwal dengan status 'scheduled' yang diekspor</li>
                                <li><strong>Format:</strong> Excel (.xlsx)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Large Data Warning -->
            <div id="largeDataWarning" class="hidden p-4 mb-6 border border-yellow-200 rounded-lg bg-yellow-50">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="w-5 h-5 text-yellow-400 fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Export Data Besar</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>Anda mengekspor data dalam rentang besar. Untuk performa optimal, pertimbangkan untuk menggunakan filter yang lebih spesifik.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('schedules.index') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Batal
                </a>
                <button type="submit" 
                        id="exportButton"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-download" id="exportIcon"></i>
                    <span id="exportText">Export ke Excel</span>
                </button>
            </div>
        </form>
    </x-card>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 z-50 items-center justify-center hidden bg-black bg-opacity-50">
    <div class="max-w-md p-8 text-center bg-white rounded-lg shadow-xl">
        <div class="flex items-center justify-center mb-4">
            <div class="w-8 h-8 border-4 border-green-600 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <h3 class="mb-2 text-lg font-semibold text-gray-900">Mengekspor Data</h3>
        <p class="text-sm text-gray-600" id="loadingMessage">Mohon tunggu, sedang memproses data...</p>
    </div>
</div>

@push('styles')
<style>
    #unit_dropdown {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .unit-item:hover {
        background-color: #f9fafb;
    }
    
    .unit-item label {
        cursor: pointer;
        user-select: none;
    }
    
    #unit_search:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    #unit_search:disabled {
        background-color: #f9fafb;
        cursor: not-allowed;
    }
    
    .unit-item input[type="checkbox"] {
        accent-color: #3b82f6;
    }
    
    #unit_all {
        accent-color: #059669;
    }
    
    #selected_units_tags {
        min-height: 24px;
    }
    
    #selected_units_tags span {
        animation: fadeIn 0.2s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }
    
    #unit_dropdown::-webkit-scrollbar {
        width: 6px;
    }
    
    #unit_dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    #unit_dropdown::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    #unit_dropdown::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    #clear_units {
        transition: all 0.15s ease;
    }
    
    #clear_units:hover {
        transform: scale(1.05);
    }
    
    /* Loading overlay styles */
    #loadingOverlay.show {
        display: flex !important;
    }
    
    /* Button loading state */
    #exportButton:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    /* Spinner animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .animate-spin {
        animation: spin 1s linear infinite;
    }
</style>
@endpush

@push('scripts')
<script>
    // Toggle date range inputs based on selection
    function toggleDateRangeInputs() {
        const dateRangeType = document.getElementById('date_range_type').value;
        const customRange = document.getElementById('custom-date-range');
        const monthYearRange = document.getElementById('month-year-range');
        const yearOnlyRange = document.getElementById('year-only-range');
        const largeDataWarning = document.getElementById('largeDataWarning');
        
        // Hide all first
        customRange.style.display = 'none';
        monthYearRange.style.display = 'none';
        yearOnlyRange.style.display = 'none';
        largeDataWarning.classList.add('hidden');
        
        // Show relevant inputs and warnings
        switch(dateRangeType) {
            case 'custom':
                customRange.style.display = 'block';
                break;
            case 'month':
                monthYearRange.style.display = 'block';
                break;
            case 'year':
                yearOnlyRange.style.display = 'block';
                largeDataWarning.classList.remove('hidden');
                break;
            case 'ytd':
                largeDataWarning.classList.remove('hidden');
                break;
            case 'all':
                largeDataWarning.classList.remove('hidden');
                break;
        }
    }

    // Load units based on selected route
    async function loadUnitsForRoute() {
        const routeId = document.getElementById('route_id').value;
        const unitSearch = document.getElementById('unit_search');
        const unitList = document.getElementById('unit_list');
        
        // Clear previous selections
        clearAllUnits();
        
        if (routeId === 'all') {
            // Load all units
            unitSearch.disabled = true;
            unitSearch.placeholder = 'Pilih rute terlebih dahulu';
            unitList.innerHTML = '<div class="p-2 text-sm text-gray-500">Pilih rute terlebih dahulu untuk memilih unit</div>';
            return;
        }
        
        try {
            // Enable unit search
            unitSearch.disabled = false;
            unitSearch.placeholder = 'Cari unit...';
            
            // Fetch units for the selected route
            const response = await fetch(`/schedules/units-for-route/${routeId}`);
            const units = await response.json();
            
            // Clear and populate unit list
            unitList.innerHTML = '';
            
            if (units.length === 0) {
                unitList.innerHTML = '<div class="p-2 text-sm text-gray-500">Tidak ada unit untuk rute ini</div>';
                return;
            }
            
            units.forEach(unit => {
                const unitItem = document.createElement('div');
                unitItem.className = 'unit-item flex items-center hover:bg-gray-50 p-1 rounded cursor-pointer';
                unitItem.setAttribute('data-unit-id', unit.id);
                unitItem.setAttribute('data-unit-number', unit.unit_number);
                
                unitItem.innerHTML = `
                    <input type="checkbox" name="unit_checkbox" value="${unit.id}" id="unit_${unit.id}" class="mr-2" onchange="updateSelectedUnits()">
                    <label for="unit_${unit.id}" class="flex-1 text-sm text-gray-700 cursor-pointer">${unit.unit_number}</label>
                `;
                
                unitList.appendChild(unitItem);
            });
            
        } catch (error) {
            console.error('Error loading units:', error);
            unitList.innerHTML = '<div class="p-2 text-sm text-red-500">Error loading units</div>';
        }
    }

    // Toggle all units selection
    function toggleAllUnits() {
        const allCheckbox = document.getElementById('unit_all');
        const unitCheckboxes = document.querySelectorAll('input[name="unit_checkbox"]');
        const unitSearch = document.getElementById('unit_search');
        
        if (allCheckbox.checked) {
            // Uncheck all individual units
            unitCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('unit_ids').value = 'all';
            updateSelectedUnitsDisplay();
            unitSearch.value = '';
        } else {
            // Allow individual selection
            updateSelectedUnits();
        }
    }

    // Clear all unit selections
    function clearAllUnits() {
        const allCheckbox = document.getElementById('unit_all');
        const unitCheckboxes = document.querySelectorAll('input[name="unit_checkbox"]');
        
        allCheckbox.checked = true;
        unitCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        document.getElementById('unit_ids').value = 'all';
        document.getElementById('unit_search').value = '';
        updateSelectedUnitsDisplay();
    }

    // Update selected units when individual checkboxes change
    function updateSelectedUnits() {
        const allCheckbox = document.getElementById('unit_all');
        const unitCheckboxes = document.querySelectorAll('input[name="unit_checkbox"]:checked');
        
        if (unitCheckboxes.length === 0) {
            // No units selected, select all
            allCheckbox.checked = true;
            document.getElementById('unit_ids').value = 'all';
        } else {
            // Some units selected
            allCheckbox.checked = false;
            const selectedIds = Array.from(unitCheckboxes).map(cb => cb.value);
            document.getElementById('unit_ids').value = selectedIds.join(',');
        }
        
        updateSelectedUnitsDisplay();
    }

    // Update the display of selected units
    function updateSelectedUnitsDisplay() {
        const unitIds = document.getElementById('unit_ids').value;
        const selectedUnitsCount = document.getElementById('selected_units_count');
        const selectedUnitsTags = document.getElementById('selected_units_tags');
        
        if (unitIds === 'all') {
            selectedUnitsCount.textContent = 'Semua Unit';
            selectedUnitsTags.innerHTML = '';
        } else {
            const ids = unitIds.split(',').filter(id => id.trim() !== '');
            selectedUnitsCount.textContent = `${ids.length} unit terpilih`;
            
            // Create tags for selected units
            selectedUnitsTags.innerHTML = '';
            ids.forEach(unitId => {
                const unitItem = document.querySelector(`[data-unit-id="${unitId}"]`);
                if (unitItem) {
                    const unitNumber = unitItem.getAttribute('data-unit-number');
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded';
                    tag.innerHTML = `
                        ${unitNumber}
                        <button type="button" class="ml-1 text-blue-600 hover:text-blue-800" onclick="removeUnitFromSelection('${unitId}')">
                            <i class="text-xs fas fa-times"></i>
                        </button>
                    `;
                    selectedUnitsTags.appendChild(tag);
                }
            });
        }
    }

    // Remove a specific unit from selection
    function removeUnitFromSelection(unitId) {
        const checkbox = document.getElementById(`unit_${unitId}`);
        if (checkbox) {
            checkbox.checked = false;
            updateSelectedUnits();
        }
    }

    // Enhanced unit search functionality
    function setupUnitSearch() {
        const searchInput = document.getElementById('unit_search');
        const dropdown = document.getElementById('unit_dropdown');
        const unitList = document.getElementById('unit_list');

        // Show/hide dropdown
        searchInput.addEventListener('focus', function() {
            if (!this.disabled) {
                dropdown.classList.remove('hidden');
            }
        });

        searchInput.addEventListener('blur', function() {
            // Delay hiding to allow clicking on options
            setTimeout(() => {
                dropdown.classList.add('hidden');
            }, 200);
        });

        // Search functionality
        searchInput.addEventListener('input', function() {
            if (this.disabled) return;
            
            const searchTerm = this.value.toLowerCase();
            const unitItems = document.querySelectorAll('.unit-item');
            
            unitItems.forEach(item => {
                const unitNumber = item.getAttribute('data-unit-number');
                if (unitNumber && unitNumber.toLowerCase().includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
            
            dropdown.classList.remove('hidden');
        });

        // Handle clicking on unit items
        document.addEventListener('click', function(e) {
            if (e.target.closest('.unit-item')) {
                const checkbox = e.target.closest('.unit-item').querySelector('input[type="checkbox"]');
                if (checkbox && e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateSelectedUnits();
                }
            }
        });
    }

    // Toggle date range inputs based on selection
    function toggleDateRangeInputs() {
        const dateRangeType = document.getElementById('date_range_type').value;
        const customRange = document.getElementById('custom-date-range');
        const monthYearRange = document.getElementById('month-year-range');
        const yearOnlyRange = document.getElementById('year-only-range');
        
        // Hide all first
        customRange.style.display = 'none';
        monthYearRange.style.display = 'none';
        yearOnlyRange.style.display = 'none';
        
        // Show relevant inputs
        switch(dateRangeType) {
            case 'custom':
                customRange.style.display = 'block';
                break;
            case 'month':
                monthYearRange.style.display = 'block';
                break;
            case 'year':
                yearOnlyRange.style.display = 'block';
                break;
            case 'ytd':
            case 'all':
                // No additional inputs needed
                break;
        }
    }

    // Load units based on selected route
    async function loadUnitsForRoute() {
        const routeId = document.getElementById('route_id').value;
        const unitSearch = document.getElementById('unit_search');
        const unitList = document.getElementById('unit_list');
        
        // Clear previous selections
        clearAllUnits();
        
        if (routeId === 'all') {
            // Load all units
            unitSearch.disabled = true;
            unitSearch.placeholder = 'Pilih rute terlebih dahulu';
            unitList.innerHTML = '<div class="p-2 text-sm text-gray-500">Pilih rute terlebih dahulu untuk memilih unit</div>';
            return;
        }
        
        try {
            // Enable unit search
            unitSearch.disabled = false;
            unitSearch.placeholder = 'Cari unit...';
            
            // Fetch units for the selected route
            const response = await fetch(`/schedules/units-for-route/${routeId}`);
            const units = await response.json();
            
            // Clear and populate unit list
            unitList.innerHTML = '';
            
            if (units.length === 0) {
                unitList.innerHTML = '<div class="p-2 text-sm text-gray-500">Tidak ada unit untuk rute ini</div>';
                return;
            }
            
            units.forEach(unit => {
                const unitItem = document.createElement('div');
                unitItem.className = 'unit-item flex items-center hover:bg-gray-50 p-1 rounded cursor-pointer';
                unitItem.setAttribute('data-unit-id', unit.id);
                unitItem.setAttribute('data-unit-number', unit.unit_number);
                
                unitItem.innerHTML = `
                    <input type="checkbox" name="unit_checkbox" value="${unit.id}" id="unit_${unit.id}" class="mr-2" onchange="updateSelectedUnits()">
                    <label for="unit_${unit.id}" class="flex-1 text-sm text-gray-700 cursor-pointer">${unit.unit_number}</label>
                `;
                
                unitList.appendChild(unitItem);
            });
            
        } catch (error) {
            console.error('Error loading units:', error);
            unitList.innerHTML = '<div class="p-2 text-sm text-red-500">Error loading units</div>';
        }
    }

    // Toggle all units selection
    function toggleAllUnits() {
        const allCheckbox = document.getElementById('unit_all');
        const unitCheckboxes = document.querySelectorAll('input[name="unit_checkbox"]');
        const unitSearch = document.getElementById('unit_search');
        
        if (allCheckbox.checked) {
            // Uncheck all individual units
            unitCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            document.getElementById('unit_ids').value = 'all';
            updateSelectedUnitsDisplay();
            unitSearch.value = '';
        } else {
            // Allow individual selection
            updateSelectedUnits();
        }
    }

    // Clear all unit selections
    function clearAllUnits() {
        const allCheckbox = document.getElementById('unit_all');
        const unitCheckboxes = document.querySelectorAll('input[name="unit_checkbox"]');
        
        allCheckbox.checked = true;
        unitCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        document.getElementById('unit_ids').value = 'all';
        document.getElementById('unit_search').value = '';
        updateSelectedUnitsDisplay();
    }

    // Update selected units when individual checkboxes change
    function updateSelectedUnits() {
        const allCheckbox = document.getElementById('unit_all');
        const unitCheckboxes = document.querySelectorAll('input[name="unit_checkbox"]:checked');
        
        if (unitCheckboxes.length === 0) {
            // No units selected, select all
            allCheckbox.checked = true;
            document.getElementById('unit_ids').value = 'all';
        } else {
            // Some units selected
            allCheckbox.checked = false;
            const selectedIds = Array.from(unitCheckboxes).map(cb => cb.value);
            document.getElementById('unit_ids').value = selectedIds.join(',');
        }
        
        updateSelectedUnitsDisplay();
    }

    // Update the display of selected units
    function updateSelectedUnitsDisplay() {
        const unitIds = document.getElementById('unit_ids').value;
        const selectedUnitsCount = document.getElementById('selected_units_count');
        const selectedUnitsTags = document.getElementById('selected_units_tags');
        
        if (unitIds === 'all') {
            selectedUnitsCount.textContent = 'Semua Unit';
            selectedUnitsTags.innerHTML = '';
        } else {
            const ids = unitIds.split(',').filter(id => id.trim() !== '');
            selectedUnitsCount.textContent = `${ids.length} unit terpilih`;
            
            // Create tags for selected units
            selectedUnitsTags.innerHTML = '';
            ids.forEach(unitId => {
                const unitItem = document.querySelector(`[data-unit-id="${unitId}"]`);
                if (unitItem) {
                    const unitNumber = unitItem.getAttribute('data-unit-number');
                    const tag = document.createElement('span');
                    tag.className = 'inline-flex items-center px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded';
                    tag.innerHTML = `
                        ${unitNumber}
                        <button type="button" class="ml-1 text-blue-600 hover:text-blue-800" onclick="removeUnitFromSelection('${unitId}')">
                            <i class="text-xs fas fa-times"></i>
                        </button>
                    `;
                    selectedUnitsTags.appendChild(tag);
                }
            });
        }
    }

    // Remove a specific unit from selection
    function removeUnitFromSelection(unitId) {
        const checkbox = document.getElementById(`unit_${unitId}`);
        if (checkbox) {
            checkbox.checked = false;
            updateSelectedUnits();
        }
    }

    // Enhanced unit search functionality
    function setupUnitSearch() {
        const searchInput = document.getElementById('unit_search');
        const dropdown = document.getElementById('unit_dropdown');
        const unitList = document.getElementById('unit_list');

        // Show/hide dropdown
        searchInput.addEventListener('focus', function() {
            if (!this.disabled) {
                dropdown.classList.remove('hidden');
            }
        });

        searchInput.addEventListener('blur', function() {
            // Delay hiding to allow clicking on options
            setTimeout(() => {
                dropdown.classList.add('hidden');
            }, 200);
        });

        // Search functionality
        searchInput.addEventListener('input', function() {
            if (this.disabled) return;
            
            const searchTerm = this.value.toLowerCase();
            const unitItems = document.querySelectorAll('.unit-item');
            
            unitItems.forEach(item => {
                const unitNumber = item.getAttribute('data-unit-number');
                if (unitNumber && unitNumber.toLowerCase().includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
            
            dropdown.classList.remove('hidden');
        });

        // Handle clicking on unit items
        document.addEventListener('click', function(e) {
            if (e.target.closest('.unit-item')) {
                const checkbox = e.target.closest('.unit-item').querySelector('input[type="checkbox"]');
                if (checkbox && e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                    updateSelectedUnits();
                }
            }
        });
    }

    // Initialize everything when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date range toggle
        toggleDateRangeInputs();
        
        // Initialize unit search
        setupUnitSearch();
        
        // Set current month and year as defaults
        const currentDate = new Date();
        const currentMonth = currentDate.getMonth() + 1;
        const currentYear = currentDate.getFullYear();
        
        // Set default month
        const monthSelect = document.getElementById('selected_month');
        if (monthSelect && !monthSelect.value) {
            monthSelect.value = currentMonth;
        }
        
        // Set default dates for custom range if not set
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        
        if (!startDateInput.value) {
            const firstDay = new Date(currentYear, currentMonth - 1, 1);
            startDateInput.value = firstDay.toISOString().split('T')[0];
        }
        
        if (!endDateInput.value) {
            endDateInput.value = currentDate.toISOString().split('T')[0];
        }

        // Initialize route selection
        const routeSelect = document.getElementById('route_id');
        if (routeSelect.value !== 'all') {
            loadUnitsForRoute();
        }

        // Set initial unit display
        updateSelectedUnitsDisplay();
    });

    // Handle export form submission with loading state
    function handleExportSubmit() {
        const form = document.getElementById('exportForm');
        const dateRangeType = document.getElementById('date_range_type').value;
        
        // Validate form before showing loading
        if (!form.checkValidity()) {
            return true; // Let browser handle validation
        }
        
        // Show loading with appropriate message
        let loadingMessage = 'Mohon tunggu, sedang memproses dan mengunduh data...';
        if (dateRangeType === 'year' || dateRangeType === 'all') {
            loadingMessage = 'Memproses data besar, mohon tunggu...';
        }
        
        showLoadingOverlay(loadingMessage);
        
        // Set a timeout to hide loading overlay
        setTimeout(() => {
            hideLoadingOverlay();
        }, 10000); // 10 seconds timeout
        
        return true; // Allow form submission to proceed
    }

    // Show loading overlay
    function showLoadingOverlay(message = 'Mohon tunggu, sedang memproses data...') {
        const overlay = document.getElementById('loadingOverlay');
        const loadingMessage = document.getElementById('loadingMessage');
        const exportButton = document.getElementById('exportButton');
        const exportIcon = document.getElementById('exportIcon');
        const exportText = document.getElementById('exportText');
        
        // Update loading message
        loadingMessage.textContent = message;
        
        // Show overlay
        overlay.classList.add('show');
        
        // Update button state
        exportButton.disabled = true;
        exportIcon.className = 'mr-2 fas fa-spinner animate-spin';
        exportText.textContent = 'Mengekspor...';
        
        // Also hide when window regains focus (user might have saved file)
        const hideOnFocus = () => {
            setTimeout(() => {
                hideLoadingOverlay();
                window.removeEventListener('focus', hideOnFocus);
            }, 2000);
        };
        window.addEventListener('focus', hideOnFocus);
    }

    // Hide loading overlay
    function hideLoadingOverlay() {
        const overlay = document.getElementById('loadingOverlay');
        const exportButton = document.getElementById('exportButton');
        const exportIcon = document.getElementById('exportIcon');
        const exportText = document.getElementById('exportText');
        
        // Hide overlay
        overlay.classList.remove('show');
        
        // Reset button state
        exportButton.disabled = false;
        exportIcon.className = 'mr-2 fas fa-download';
        exportText.textContent = 'Export ke Excel';
    }

    // Show success message
    function showSuccessMessage() {
        // Create or update a temporary success message
        let successMessage = document.getElementById('tempSuccessMessage');
        if (!successMessage) {
            successMessage = document.createElement('div');
            successMessage.id = 'tempSuccessMessage';
            successMessage.className = 'fixed top-4 right-4 z-50 p-4 text-green-800 bg-green-100 border border-green-200 rounded-lg shadow-lg';
            successMessage.innerHTML = `
                <div class="flex items-center">
                    <i class="mr-2 fas fa-check-circle"></i>
                    <span>Export berhasil! File sedang diunduh...</span>
                </div>
            `;
            document.body.appendChild(successMessage);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (successMessage && successMessage.parentNode) {
                    successMessage.parentNode.removeChild(successMessage);
                }
            }, 5000);
        }
    }

    // Refresh materialized view data
    async function refreshMaterializedView() {
        const refreshButton = document.getElementById('refreshMaterialized');
        const originalText = refreshButton.innerHTML;
        
        // Show loading state
        refreshButton.innerHTML = '<i class="mr-1 fas fa-spinner fa-spin"></i> Refreshing...';
        refreshButton.disabled = true;
        
        try {
            const response = await fetch('{{ route("schedules.export.summary.refresh-materialized") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({}),
            });
            
            const result = await response.json();
            if (result.success) {
                // Update stats display
                loadMaterializedViewStats();
                
                // Show success message
                showMessage('✅ ' + result.message, 'success');
            } else {
                showMessage('❌ Error: ' + result.message, 'error');
            }
        } catch (error) {
            showMessage('❌ Network error: ' + error.message, 'error');
        } finally {
            // Reset button state
            refreshButton.innerHTML = originalText;
            refreshButton.disabled = false;
        }
    }

    // Load materialized view stats
    async function loadMaterializedViewStats() {
        try {
            const response = await fetch('{{ route("schedules.export.summary.materialized-stats") }}');
            const result = await response.json();
            
            if (result.success) {
                const stats = result.data;
                const statsContainer = document.getElementById('materializedStats');
                const lastUpdated = stats.last_updated ? new Date(stats.last_updated).toLocaleString() : 'Never';
                const dateRange = stats.earliest_date && stats.latest_date ? 
                    `${stats.earliest_date} to ${stats.latest_date}` : 'No data';
                
                statsContainer.innerHTML = `
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-blue-600">Monthly Records:</span>
                            <span class="font-semibold text-blue-900">${stats.total_records?.toLocaleString() || 0}</span>
                        </div>
                        <div>
                            <span class="text-blue-600">Status:</span>
                            <span class="font-semibold ${stats.is_fresh ? 'text-green-600' : 'text-orange-600'}">
                                ${stats.is_fresh ? '✅ Fresh' : '⚠️ Stale'}
                            </span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-blue-600">Month Range:</span>
                            <span class="font-semibold text-blue-900">${dateRange}</span>
                        </div>
                        <div class="col-span-2">
                            <span class="text-blue-600">Last Updated:</span>
                            <span class="font-semibold text-blue-900">${lastUpdated}</span>
                        </div>
                        <div class="col-span-2 mt-1 text-xs text-blue-500">
                            💡 Multi-sheet export: Each month becomes a separate worksheet
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading materialized stats:', error);
        }
    }

    // Show message function
    function showMessage(message, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.temp-message');
        existingMessages.forEach(msg => msg.remove());
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `temp-message fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm ${
            type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
            type === 'error' ? 'bg-red-100 text-red-800 border border-red-200' :
            'bg-blue-100 text-blue-800 border border-blue-200'
        }`;
        messageDiv.innerHTML = `
            <div class="flex items-start">
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        document.body.appendChild(messageDiv);
        
        // Auto remove after 8 seconds
        setTimeout(() => {
            if (messageDiv && messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 8000);
    }

    // Initial load of materialized view stats
    document.addEventListener('DOMContentLoaded', function() {
        loadMaterializedViewStats();
    });

    // Refresh button click handler
    document.getElementById('refreshMaterialized').addEventListener('click', function() {
        refreshMaterializedView();
    });
</script>
@endpush
@endsection

@extends('modules.admin.layouts.main')

@section('title', 'Export Log Perawatan')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Export Log Perawatan</x-slot>
        <x-slot name="actions">
            <a href="{{ route('maintenance-logs.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                <i class="mr-2 fas fa-arrow-left"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <div class="mb-6">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Filter Export Data</h3>
            <p class="mb-6 text-sm text-gray-600">
                Export akan menghasilkan 2 sheet: Summary (ringkasan) dan Detailed Breakdown (rincian biaya dan foto dengan path absolut).
            </p>
        </div>

        <form action="{{ route('maintenance-logs.export.excel') }}" method="POST" class="space-y-6">
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
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700">Tanggal Akhir</label>
                            <input type="date" 
                                   name="end_date" 
                                   id="end_date" 
                                   value="{{ old('end_date') }}"
                                   class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
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
                                    {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
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
                                        <div class="flex items-center">
                                            <input type="checkbox" id="unit_all" class="mr-2" checked onchange="toggleAllUnits()">
                                            <label for="unit_all" class="text-sm font-medium text-gray-700 cursor-pointer">Semua Unit</label>
                                        </div>
                                        <button type="button" id="clear_units" class="text-xs text-red-600 hover:text-red-800" onclick="clearAllUnits()">
                                            Clear All
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
                                <li><strong>Sheet 1 - Summary:</strong> Ringkasan semua log perawatan dengan total biaya</li>
                                <li><strong>Sheet 2 - Detailed Breakdown:</strong> Rincian lengkap termasuk breakdown biaya dan path foto absolut</li>
                                <li>Path foto menggunakan URL absolut yang dapat diakses langsung</li>
                                <li>Pilihan rentang tanggal: Custom, Per Bulan, Per Tahun, YTD, atau Semua Data</li>
                                <li><strong>Filter Terhubung:</strong> Pilih rute terlebih dahulu, kemudian pilih unit yang terkait dengan rute tersebut</li>
                                <li>Unit dapat dipilih lebih dari satu (multiple selection)</li>
                                <li>Unit dapat dicari dengan mengetik nomor unit</li>
                                <li>Format file: Excel (.xlsx)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Info -->
            <div class="p-4 border border-green-200 rounded-md bg-green-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="text-green-400 fas fa-filter"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Cara Menggunakan Filter</h3>
                        <div class="mt-2 text-sm text-green-700">
                            <ol class="space-y-1 list-decimal list-inside">
                                <li><strong>Pilih Rute:</strong> Pilih rute terlebih dahulu dari dropdown</li>
                                <li><strong>Pilih Unit:</strong> Setelah memilih rute, unit terkait akan muncul</li>
                                <li><strong>Multiple Selection:</strong> Centang unit yang diinginkan (bisa lebih dari satu)</li>
                                <li><strong>Search Unit:</strong> Ketik nomor unit untuk mencari dengan cepat</li>
                                <li><strong>All Units:</strong> Centang "Semua Unit" untuk memilih semua unit dalam rute</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date Range Info -->
            <div class="p-4 border border-yellow-200 rounded-md bg-yellow-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="text-yellow-400 fas fa-calendar-alt"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Opsi Rentang Tanggal</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="space-y-1 list-disc list-inside">
                                <li><strong>Custom:</strong> Pilih tanggal mulai dan akhir secara manual</li>
                                <li><strong>Per Bulan:</strong> Pilih bulan dan tahun tertentu</li>
                                <li><strong>Per Tahun:</strong> Pilih tahun tertentu (1 Januari - 31 Desember)</li>
                                <li><strong>YTD (Year to Date):</strong> Dari 1 Januari hingga hari ini</li>
                                <li><strong>Semua Data:</strong> Export semua data tanpa filter tanggal</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('maintenance-logs.index') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Batal
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-download"></i>
                    Export ke Excel
                </button>
            </div>
        </form>
    </x-card>
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
    
    /* Custom checkbox styling */
    .unit-item input[type="checkbox"] {
        accent-color: #3b82f6;
    }
    
    #unit_all {
        accent-color: #059669;
    }
    
    /* Selected unit tags styling */
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
    
    /* Hide scrollbar but keep functionality */
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
    
    /* Clear button styling */
    #clear_units {
        transition: all 0.15s ease;
    }
    
    #clear_units:hover {
        transform: scale(1.05);
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
            const response = await fetch(`/maintenance-logs/units-for-route/${routeId}`);
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
</script>
@endpush
@endsection

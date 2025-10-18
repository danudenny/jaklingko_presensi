<div class="p-4 mb-6 border border-gray-200 rounded-lg shadow-sm bg-gradient-to-r from-gray-50 to-gray-100">
    <h3 class="mb-3 text-lg font-medium text-gray-700">
        <i class="mr-2 text-indigo-500 fas fa-filter"></i>Filter Jadwal
    </h3>
    
    <form id="filter-form" method="GET" class="space-y-4">
        <!-- First Row: Date and Route Filters -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
        <div>
            <x-input-label for="month" value="Bulan" class="font-medium text-gray-700" />
            <div class="relative">
                <select id="month" name="month" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::create(null, $m, 1)->format('F') }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-white bg-indigo-500 rounded-r-md cursor-pointer">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
        
        <div>
            <x-input-label for="year" value="Tahun" class="font-medium text-gray-700" />
            <div class="relative">
                <select id="year" name="year" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    @foreach(range(date('Y')-2, date('Y')+1) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-white bg-indigo-500 rounded-r-md cursor-pointer">
                    <i class="fas fa-calendar"></i>
                </div>
            </div>
        </div>
        
        <div>
            <x-input-label for="shift" value="Shift" class="font-medium text-gray-700" />
            <div class="relative">
                <select id="shift" name="shift" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">-- Semua Shift --</option>
                    <option value="pagi" {{ $selectedShift == 'pagi' ? 'selected' : '' }}>Pagi</option>
                    <option value="siang" {{ $selectedShift == 'siang' ? 'selected' : '' }}>Siang</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-white bg-indigo-500 rounded-r-md cursor-pointer">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <div>
            <x-input-label for="route" value="Rute" class="font-medium text-gray-700" />
            <div class="relative">
                <select id="route" name="route" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">-- Semua Rute --</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ $selectedRoute == $route->id ? 'selected' : '' }}>
                            {{ $route->route_number }} - {{ $route->name }}
                        </option>
                    @endforeach
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-white bg-indigo-500 rounded-r-md cursor-pointer">
                    <i class="fas fa-route"></i>
                </div>
            </div>
        </div>
        </div>
        
        <!-- Second Row: Driver and Unit Filters -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
        <div>
            <x-input-label for="driver_type" value="Jenis Pengemudi" class="font-medium text-gray-700" />
            <div class="relative">
                <select id="driver_type" name="driver_type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">-- Semua Jenis --</option>
                    <option value="batangan" {{ $selectedDriverType == 'batangan' ? 'selected' : '' }}>Batangan</option>
                    <option value="cadangan" {{ $selectedDriverType == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center px-2 text-white bg-indigo-500 rounded-r-md cursor-pointer">
                    <i class="fas fa-user-tag"></i>
                </div>
            </div>
        </div>
        
        <div>
            <x-input-label for="driver" value="Pengemudi" class="font-medium text-gray-700" />
            <div class="relative" 
                    x-data="{ 
                    open: false, 
                    search: '{{ $selectedDriver ? ($drivers->firstWhere('id', $selectedDriver)->name) : '-- Semua Pengemudi --' }}', 
                    selectedOption: '{{ $selectedDriver }}',
                    defaultSearch: '{{ $selectedDriver ? ($drivers->firstWhere('id', $selectedDriver)->name) : '-- Semua Pengemudi --' }}',
                    isFirstInput: true,
                    filterDrivers(term) {
                        if (this.isFirstInput && term === this.defaultSearch) {
                            this.search = '';
                            this.isFirstInput = false;
                            return;
                        }
                        
                        if (!term) return;
                        
                        document.querySelectorAll('[data-driver-item]').forEach(el => {
                            const driverName = el.getAttribute('data-driver-name').toLowerCase();
                            el.style.display = driverName.includes(term.toLowerCase()) ? '' : 'none';
                        });
                    }
                    }"
                    x-on:click.away="open = false">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Ketik untuk mencari pengemudi..."
                        class="block w-full pl-10 pr-10 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        x-on:click="open = !open; $nextTick(() => { if (isFirstInput && search === defaultSearch) { search = ''; isFirstInput = false; $el.focus(); } })"
                        x-model="search"
                        x-on:input.debounce.250ms="filterDrivers(search)"
                        autocomplete="off"
                        readonly
                        onfocus="this.removeAttribute('readonly');"
                    />
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-cur">
                        <i class="text-indigo-500 fas fa-search"></i>
                    </div>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <span class="flex items-center justify-center w-6 h-6 text-gray-100 bg-indigo-500 rounded-full">
                            <i class="text-xs fas fa-user"></i>
                        </span>
                    </div>
                    <input type="hidden" name="driver" :value="selectedOption" />
                </div>
                
                <div 
                    x-show="open"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute z-40 w-full mt-1 overflow-auto bg-white rounded-md shadow-lg max-h-60"
                    style="display: none;"
                >
                    <div class="sticky top-0 p-2 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-medium text-gray-600">Pilih pengemudi</div>
                            <button type="button" @click="open = false" class="text-gray-500 hover:text-gray-700">
                                <i class="text-xs fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="py-1 text-sm cursor-pointer hover:bg-gray-50" 
                                x-on:click="selectedOption = ''; open = false; search = '-- Semua Pengemudi --'">
                            <span class="flex items-center block px-4 py-2">
                                <i class="mr-2 text-gray-500 fas fa-globe-asia"></i>
                                -- Semua Pengemudi --
                            </span>
                        </div>
                        
                        <div class="py-8 text-center" 
                                x-show="search !== defaultSearch && !Array.from(document.querySelectorAll('[data-driver-item]')).some(el => el.style.display !== 'none')"
                                style="display: none;">
                            <i class="mb-2 text-2xl text-gray-300 fas fa-search"></i>
                            <p class="text-sm text-gray-500">Tidak ditemukan hasil.</p>
                            <p class="text-sm text-gray-500">Tidak ditemukan pengemudi yang cocok</p>
                        </div>
                        
                        <div class="py-1 border-t border-gray-100">
                            <div class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 sticky top-[41px]">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-5 h-5 mr-2 rounded-full bg-emerald-100 text-emerald-700">
                                        <i class="fas fa-user-tie text-xxs"></i>
                                    </span>
                                    Pengemudi Tetap (Batangan)
                                </div>
                            </div>
                            @foreach($drivers->where('type', 'batangan') as $driver)
                                <div class="text-sm cursor-pointer hover:bg-gray-50" 
                                        x-on:click="selectedOption = '{{ $driver->id }}'; open = false; search = `{{ addslashes($driver->name) }}`"
                                        data-driver-item
                                        data-driver-name="{{ $driver->name }}"
                                        data-driver-type="batangan"
                                        style="{{ request('driver_type') == 'cadangan' ? 'display: none;' : '' }}"
                                >
                                    <span class="block px-4 py-2 {{ $selectedDriver == $driver->id ? 'text-indigo-600 font-medium bg-indigo-50' : 'text-gray-700' }}">
                                        {{ $driver->name }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="py-1 border-t border-gray-100">
                            <div class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 sticky top-[41px]">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-5 h-5 mr-2 text-gray-700 bg-gray-100 rounded-full">
                                        <i class="fas fa-user text-xxs"></i>
                                    </span>
                                    Pengemudi Cadangan
                                </div>
                            </div>
                            @foreach($drivers->where('type', 'cadangan') as $driver)
                                <div class="text-sm cursor-pointer hover:bg-gray-50" 
                                        x-on:click="selectedOption = '{{ $driver->id }}'; open = false; search = `{{ addslashes($driver->name) }}`"
                                        data-driver-item
                                        data-driver-name="{{ $driver->name }}"
                                        data-driver-type="cadangan"
                                        style="{{ request('driver_type') == 'batangan' ? 'display: none;' : '' }}"
                                >
                                    <span class="block px-4 py-2 {{ $selectedDriver == $driver->id ? 'text-indigo-600 font-medium bg-indigo-50' : 'text-gray-700' }}">
                                        {{ $driver->name }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div>
            <x-input-label for="unit" value="Unit" class="font-medium text-gray-700" />
            <div class="relative" 
                    x-data="{
                    open: false, 
                    search: '{{ $selectedUnit ? ($units->firstWhere('id', $selectedUnit)->unit_number . ($units->firstWhere('id', $selectedUnit)->plate_number ? ' ('.$units->firstWhere('id', $selectedUnit)->plate_number.')' : '')) : '-- Semua Unit --' }}', 
                    selectedOption: '{{ $selectedUnit }}',
                    defaultSearch: '{{ $selectedUnit ? ($units->firstWhere('id', $selectedUnit)->unit_number . ($units->firstWhere('id', $selectedUnit)->plate_number ? ' ('.$units->firstWhere('id', $selectedUnit)->plate_number.')' : '')) : '-- Semua Unit --' }}',
                    isFirstInput: true,
                    filterUnits(term) {
                        // Clear default text on first input
                        if (this.isFirstInput && term === this.defaultSearch) {
                            this.search = '';
                            this.isFirstInput = false;
                            return;
                        }
                        
                        // Skip filtering if empty
                        if (!term) return;
                        
                        document.querySelectorAll('[data-unit-item]').forEach(el => {
                            const unitInfo = el.getAttribute('data-unit-info').toLowerCase();
                            el.style.display = unitInfo.includes(term.toLowerCase()) ? '' : 'none';
                        });
                    }
                    }"
                    x-on:click.away="open = false">
                <div class="relative">
                    <input
                        type="text"
                        placeholder="Ketik untuk mencari unit..."
                        class="block w-full pl-10 pr-10 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        x-on:click="open = !open; $nextTick(() => { if (isFirstInput && search === defaultSearch) { search = ''; isFirstInput = false; $el.focus(); } })"
                        x-model="search"
                        x-on:input.debounce.250ms="filterUnits(search)"
                        autocomplete="off"
                        readonly
                        onfocus="this.removeAttribute('readonly');"
                    />
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-cur">
                        <i class="text-indigo-500 fas fa-search"></i>
                    </div>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <span class="flex items-center justify-center w-6 h-6 text-gray-100 bg-blue-500 rounded-full">
                            <i class="text-xs fas fa-bus"></i>
                        </span>
                    </div>
                    <input type="hidden" name="unit" :value="selectedOption" />
                </div>
                
                <div 
                    x-show="open" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute z-40 w-full mt-1 overflow-auto bg-white rounded-md shadow-lg max-h-60"
                    style="display: none;"
                >
                    <div class="sticky top-0 p-2 border-b border-gray-200 bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-medium text-gray-600">Pilih unit</div>
                            <button type="button" @click="open = false" class="text-gray-500 hover:text-gray-700">
                                <i class="text-xs fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div>
                        <div class="py-1 text-sm cursor-pointer hover:bg-gray-50" 
                                x-on:click="selectedOption = ''; open = false; search = '-- Semua Unit --'">
                            <span class="flex items-center block px-4 py-2">
                                <i class="mr-2 text-gray-500 fas fa-globe-asia"></i>
                                -- Semua Unit --
                            </span>
                        </div>
                        
                        <div class="py-8 text-center" 
                                x-show="search !== defaultSearch && !Array.from(document.querySelectorAll('[data-unit-item]')).some(el => el.style.display !== 'none')"
                                style="display: none;">
                            <i class="mb-2 text-2xl text-gray-300 fas fa-search"></i>
                            <p class="text-sm text-gray-500">Tidak ditemukan unit yang cocok</p>
                        </div>
                        
                        <div class="py-1 border-t border-gray-100">
                            <div class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 sticky top-[41px]">
                                <div class="flex items-center">
                                    <span class="inline-flex items-center justify-center w-5 h-5 mr-2 text-blue-700 bg-blue-100 rounded-full">
                                        <i class="fas fa-bus text-xxs"></i>
                                    </span>
                                    Unit Kendaraan
                                </div>
                            </div>
                            @foreach($units as $unit)
                                @php 
                                    $unitDisplay = $unit->unit_number . ($unit->plate_number ? ' ('.$unit->plate_number.')' : '');
                                    $unitSearchValue = strtolower($unit->unit_number . ' ' . $unit->plate_number);
                                @endphp
                                <div class="text-sm cursor-pointer hover:bg-gray-50" 
                                        x-on:click="selectedOption = '{{ $unit->id }}'; open = false; search = `{{ addslashes($unitDisplay) }}`"
                                        data-unit-item
                                        data-unit-info="{{ $unitSearchValue }}"
                                >
                                    <span class="block px-4 py-2 {{ $selectedUnit == $unit->id ? 'text-indigo-600 font-medium bg-indigo-50' : 'text-gray-700' }}">
                                        <span class="font-medium">{{ $unit->unit_number }}</span>
                                        @if($unit->plate_number)
                                            <span class="text-gray-500">{{ ' ('.$unit->plate_number.')' }}</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow-sm bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 active:from-indigo-700 active:to-indigo-800">
                <i class="mr-2 fas fa-search"></i>Terapkan Filter
            </button>
            <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase transition duration-150 ease-in-out bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring focus:ring-gray-200 focus:border-gray-400 active:bg-gray-100">
                <i class="mr-2 fas fa-times"></i>Reset
            </a>
            <div class="ml-2 text-xs text-gray-500">
                <i class="mr-1 fas fa-info-circle"></i>
                Tips: Filter dapat dikombinasikan untuk menyaring jadwal
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const driverTypeSelect = document.getElementById('driver_type');
    
    // Function to filter drivers based on selected driver type
    function filterDriversByType(selectedType) {
        const batanganDrivers = document.querySelectorAll('[data-driver-type="batangan"]');
        const cadanganDrivers = document.querySelectorAll('[data-driver-type="cadangan"]');
        const batanganSection = document.querySelector('[data-driver-type="batangan"]')?.closest('.py-1');
        const cadanganSection = document.querySelector('[data-driver-type="cadangan"]')?.closest('.py-1');
        
        if (selectedType === 'batangan') {
            // Show only batangan drivers
            batanganDrivers.forEach(driver => driver.style.display = '');
            cadanganDrivers.forEach(driver => driver.style.display = 'none');
        } else if (selectedType === 'cadangan') {
            // Show only cadangan drivers
            batanganDrivers.forEach(driver => driver.style.display = 'none');
            cadanganDrivers.forEach(driver => driver.style.display = '');
        } else {
            // Show all drivers
            batanganDrivers.forEach(driver => driver.style.display = '');
            cadanganDrivers.forEach(driver => driver.style.display = '');
        }
    }
    
    // Listen for changes in driver type selection
    if (driverTypeSelect) {
        driverTypeSelect.addEventListener('change', function() {
            filterDriversByType(this.value);
        });
        
        // Apply initial filter on page load
        filterDriversByType(driverTypeSelect.value);
    }
});
</script>
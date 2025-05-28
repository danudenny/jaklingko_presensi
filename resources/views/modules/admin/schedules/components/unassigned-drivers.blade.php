<!-- Unassigned Drivers Drawer -->
<div id="unassigned-drivers-drawer" class="fixed top-0 right-0 z-50 h-full w-80 md:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="sticky top-0 z-10 flex items-center justify-between p-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <h3 class="flex items-center text-lg font-medium">
            <i class="mr-2 fas fa-user-slash"></i>Pengemudi Belum Terjadwal
        </h3>
        <button id="close-unassigned-btn" class="p-1 text-white transition-colors rounded-full hover:bg-white hover:bg-opacity-20">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="p-5">
        <!-- Filter Section -->
        <div class="mb-5">
            <div class="mb-3">
                <label for="unassigned-filter-type" class="block text-sm font-medium text-gray-700">Tipe Pengemudi</label>
                <select id="unassigned-filter-type" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <option value="">Semua</option>
                    <option value="batangan">Batangan</option>
                    <option value="cadangan">Cadangan</option>
                </select>
            </div>
        </div>

        <!-- Unassigned Batangan Drivers Section -->
        <div class="mb-5">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Pengemudi Batangan</h4>
            <div id="unassigned-batangan-list" class="space-y-2">
                @foreach($unassignedBatanganDrivers ?? [] as $driver)
                <div class="flex items-center justify-between p-2 border border-gray-200 rounded-md hover:bg-gray-50">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 mr-3 text-green-800 bg-green-100 rounded-full shadow-sm">
                            <i class="fas fa-user"></i>
                        </span>
                        <div>
                            <span class="block text-sm font-medium text-gray-900">{{ $driver->name }}</span>
                            <span class="block text-xs text-gray-500">ID: {{ $driver->id }}</span>
                        </div>
                    </div>
                    <div class="text-xs text-right">
                        <span class="block font-medium">{{ $driver->total_schedules ?? 0 }} Jadwal</span>
                        <span class="block text-gray-500">{{ $driver->total_morning ?? 0 }} Pagi / {{ $driver->total_afternoon ?? 0 }} Siang</span>
                    </div>
                </div>
                @endforeach
                @if(empty($unassignedBatanganDrivers) || count($unassignedBatanganDrivers) === 0)
                <div class="text-center py-3 text-sm text-gray-500">
                    <i class="fas fa-check-circle text-green-500 mr-1"></i> Semua pengemudi batangan sudah terjadwal
                </div>
                @endif
            </div>
        </div>
        
        <!-- Unassigned Cadangan Drivers Section -->
        <div class="mb-5">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Pengemudi Cadangan</h4>
            <div id="unassigned-cadangan-list" class="space-y-2">
                @foreach($unassignedCadanganDrivers ?? [] as $driver)
                <div class="flex items-center justify-between p-2 border border-gray-200 rounded-md hover:bg-gray-50">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-7 h-7 mr-3 rounded-full cadangan-checkmark shadow-sm">
                            <i class="fas fa-user"></i>
                        </span>
                        <div>
                            <span class="block text-sm font-medium text-gray-900">{{ $driver->name }}</span>
                            <span class="block text-xs text-gray-500">ID: {{ $driver->id }}</span>
                        </div>
                    </div>
                    <div class="text-xs text-right">
                        <span class="block font-medium">{{ $driver->total_schedules ?? 0 }} Jadwal</span>
                        <span class="block text-gray-500">{{ $driver->total_morning ?? 0 }} Pagi / {{ $driver->total_afternoon ?? 0 }} Siang</span>
                    </div>
                </div>
                @endforeach
                @if(empty($unassignedCadanganDrivers) || count($unassignedCadanganDrivers) === 0)
                <div class="text-center py-3 text-sm text-gray-500">
                    <i class="fas fa-check-circle text-green-500 mr-1"></i> Semua pengemudi cadangan sudah terjadwal
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Overlay for closing drawer when clicking outside -->
<div id="unassigned-overlay" class="fixed inset-0 z-40 hidden bg-black bg-opacity-50 transition-opacity duration-300 opacity-0"></div>

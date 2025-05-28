<!-- Statistics Drawer -->
<div id="stats-drawer" class="fixed top-0 right-0 z-50 h-full w-80 md:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="sticky top-0 z-10 flex items-center justify-between p-4 bg-gradient-to-r from-purple-600 to-purple-700 text-white">
        <h3 class="flex items-center text-lg font-medium">
            <i class="mr-2 fas fa-chart-bar"></i>Statistik Jadwal
        </h3>
        <button id="close-stats-btn" class="p-1 text-white transition-colors rounded-full hover:bg-white hover:bg-opacity-20">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="p-5">
        <!-- Period Information -->
        <div class="mb-5 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700 mb-2">Informasi Periode</h4>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <p class="text-xs text-gray-500">Periode</p>
                    <p class="font-medium">{{ $period == 1 ? '1-15' : '16-' . Carbon\Carbon::parse($endDate)->format('d') }} {{ $monthName }} {{ $year }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Rentang Tanggal</p>
                    <p class="font-medium">{{ Carbon\Carbon::parse($startDate)->format('d M') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>
                </div>
            </div>
        </div>
        
        <!-- Main Stats -->
        <div class="space-y-4 mb-6">
            <div class="p-4 transition-all border border-blue-200 rounded-lg shadow-sm bg-gradient-to-r from-blue-50 to-blue-100">
                <div class="flex items-center">
                    <div class="p-3 text-white bg-blue-500 rounded-lg shadow-md">
                        <i class="text-lg fas fa-calendar-check"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-900">Total Jadwal</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-blue-700">{{ $totalAssignments }}</p>
                            <p class="ml-2 text-xs text-blue-500">penugasan</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-4 transition-all border rounded-lg shadow-sm bg-gradient-to-r from-emerald-50 to-emerald-100 border-emerald-200">
                <div class="flex items-center">
                    <div class="p-3 text-white rounded-lg shadow-md bg-emerald-500">
                        <i class="text-lg fas fa-user-check"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-emerald-900">Total Pengemudi</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-emerald-700">{{ $uniqueDriversCount }}</p>
                            <p class="ml-2 text-xs text-emerald-500">pengemudi</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-4 transition-all border rounded-lg shadow-sm bg-gradient-to-r from-amber-50 to-amber-100 border-amber-200">
                <div class="flex items-center">
                    <div class="p-3 text-white rounded-lg shadow-md bg-amber-500">
                        <i class="text-lg fas fa-bus"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-amber-900">Total Unit</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-amber-700">{{ $uniqueUnitsCount }}</p>
                            <p class="ml-2 text-xs text-amber-500">kendaraan</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="p-4 transition-all border border-indigo-200 rounded-lg shadow-sm bg-gradient-to-r from-indigo-50 to-indigo-100">
                <div class="flex items-center">
                    <div class="p-3 text-white bg-indigo-500 rounded-lg shadow-md">
                        <i class="text-lg fas fa-calendar-day"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-indigo-900">Rentang Hari</p>
                        <div class="flex items-baseline">
                            <p class="text-2xl font-semibold text-indigo-700">{{ count($dateRange) }}</p>
                            <p class="ml-2 text-xs text-indigo-500">hari</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schedule Distribution -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Distribusi Jadwal</h4>
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 mr-3 text-yellow-800 bg-yellow-100 rounded-full">
                            <i class="fas fa-sun"></i>
                        </span>
                        <span class="font-medium text-yellow-800">Shift Pagi</span>
                    </div>
                    <span class="text-lg font-semibold text-yellow-700">
                        @php
                            $morningCount = 0;
                            foreach ($routeUnitDrivers as $routeData) {
                                foreach ($routeData['units'] as $unitData) {
                                    foreach ($unitData['drivers'] as $driverData) {
                                        // Count regular morning shifts
                                        if (!empty($driverData['shifts']['pagi']['dates'])) {
                                            $morningCount += count($driverData['shifts']['pagi']['dates']);
                                        }
                                        // Count backup morning shifts
                                        if (!empty($driverData['shifts']['pagi']['backup_dates'])) {
                                            $morningCount += count($driverData['shifts']['pagi']['backup_dates']);
                                        }
                                    }
                                }
                            }
                        @endphp
                        {{ $morningCount }}
                    </span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center">
                        <span class="inline-flex items-center justify-center w-8 h-8 mr-3 text-blue-800 bg-blue-100 rounded-full">
                            <i class="fas fa-moon"></i>
                        </span>
                        <span class="font-medium text-blue-800">Shift Siang</span>
                    </div>
                    <span class="text-lg font-semibold text-blue-700">
                        @php
                            $eveningCount = 0;
                            foreach ($routeUnitDrivers as $routeData) {
                                foreach ($routeData['units'] as $unitData) {
                                    foreach ($unitData['drivers'] as $driverData) {
                                        // Count regular evening shifts
                                        if (!empty($driverData['shifts']['siang']['dates'])) {
                                            $eveningCount += count($driverData['shifts']['siang']['dates']);
                                        }
                                        // Count backup evening shifts
                                        if (!empty($driverData['shifts']['siang']['backup_dates'])) {
                                            $eveningCount += count($driverData['shifts']['siang']['backup_dates']);
                                        }
                                    }
                                }
                            }
                        @endphp
                        {{ $eveningCount }}
                    </span>
                </div>
            </div>
        </div>
        
    </div>
</div>

<!-- Overlay for closing drawer when clicking outside -->
<div id="stats-overlay" class="fixed inset-0 z-40 hidden bg-black bg-opacity-50 transition-opacity duration-300 opacity-0"></div>

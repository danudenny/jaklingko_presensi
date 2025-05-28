<div class="mb-6">
    <h3 class="mb-3 text-lg font-medium text-gray-700">
        <i class="mr-2 text-gray-500 fas fa-chart-bar"></i>Statistik Jadwal
    </h3>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div class="p-4 transition-all border border-blue-200 rounded-lg shadow-sm bg-gradient-to-r from-blue-50 to-blue-100 hover:shadow-md">
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
        
        <div class="p-4 transition-all border rounded-lg shadow-sm bg-gradient-to-r from-emerald-50 to-emerald-100 border-emerald-200 hover:shadow-md">
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
        
        <div class="p-4 transition-all border rounded-lg shadow-sm bg-gradient-to-r from-amber-50 to-amber-100 border-amber-200 hover:shadow-md">
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
        
        <div class="p-4 transition-all border border-indigo-200 rounded-lg shadow-sm bg-gradient-to-r from-indigo-50 to-indigo-100 hover:shadow-md">
            <div class="flex items-center">
                <div class="p-3 text-white bg-indigo-500 rounded-lg shadow-md">
                    <i class="text-lg fas fa-calendar-alt"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-indigo-900">Periode Aktif</p>
                    <p class="text-xl font-semibold text-indigo-700">{{ $period == 1 ? '1-15' : '16-'.$endDate }}</p>
                    <p class="text-xs text-indigo-600">{{ $monthName }} {{ $year }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
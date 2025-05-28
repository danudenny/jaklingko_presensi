<div class="p-4 mt-4 rounded-md bg-gray-50">
    <h3 class="text-lg font-medium text-gray-700">
        <i class="mr-2 text-gray-500 fas fa-info-circle"></i>Keterangan:
    </h3>
    <div class="flex flex-wrap gap-4 mt-3">
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center w-6 h-6 mr-2 text-green-800 bg-green-100 rounded-full">
                <i class="text-sm fas fa-check"></i>
            </span>
            <span class="text-sm text-gray-700">Pengemudi Batangan</span>
        </div>
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full cadangan-checkmark">
                <i class="text-sm fas fa-check"></i>
            </span>
            <span class="text-sm text-gray-700">Pengemudi Cadangan</span>
        </div>
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-amber-100 text-amber-800">
                <i class="text-sm fas fa-question"></i>
            </span>
            <span class="text-sm text-gray-700">Pengemudi Backup</span>
        </div>
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full renops-indicator">
                <i class="text-sm fas fa-exclamation"></i>
            </span>
            <span class="text-sm text-gray-700">Unit Tidak Beroperasi (Renops)</span>
        </div>
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center px-2 py-1 mr-2 text-xs font-medium text-blue-600 bg-blue-100 rounded">
                <i class="mr-1 fas fa-sun"></i>Pagi
            </span>
            <span class="text-sm text-gray-700">Shift Pagi</span>
        </div>
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center px-2 py-1 mr-2 text-xs font-medium rounded text-amber-600 bg-amber-100">
                <i class="mr-1 fas fa-moon"></i>Siang
            </span>
            <span class="text-sm text-gray-700">Shift Siang</span>
        </div>
        
        <div class="w-full pt-2 mt-2 border-t border-gray-200"></div>
        
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center w-6 h-6 mr-2 text-red-700 rounded bg-red-50">
                <i class="text-xs fas fa-star-of-life"></i>
            </span>
            <span class="text-sm text-gray-700">Hari Libur Nasional</span>
        </div>
        <div class="flex items-center">
            <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded bg-amber-50 text-amber-700">
                <span class="text-xs font-medium">Sab</span>
            </span>
            <span class="text-sm text-gray-700">Hari Libur Akhir Pekan</span>
        </div>
        
        <div class="w-full pt-2 mt-2 border-t border-gray-200"></div>
        
        <div id="edit-mode-legend" class="hidden w-full">
            <h4 class="mt-2 mb-2 text-sm font-medium text-gray-700">Mode Edit:</h4>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center">
                    <input type="checkbox" checked class="w-5 h-5 mr-2 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Centang untuk menjadwalkan pengemudi</span>
                </div>
                <div class="flex items-center">
                    <button id="toggle-all-btn" class="px-3 py-1 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Toggle Semua
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
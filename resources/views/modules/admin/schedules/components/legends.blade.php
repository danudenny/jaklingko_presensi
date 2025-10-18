<!-- Floating button to open legends drawer -->
<button id="show-legends-btn" class="fixed z-50 flex items-center justify-center p-3 text-white transition-all duration-300 rounded-full shadow-lg bottom-6 right-6 bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 hover:scale-110">
    <i class="text-xl fas fa-info-circle"></i>
</button>

<!-- Legends Drawer -->
<div id="legends-drawer" class="fixed top-0 right-0 z-50 h-full w-80 md:w-96 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="sticky top-0 z-10 flex items-center justify-between p-4 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white">
        <h3 class="flex items-center text-lg font-medium">
            <i class="mr-2 fas fa-info-circle"></i>Keterangan
        </h3>
        <button id="close-legends-btn" class="p-1 text-white transition-colors rounded-full hover:bg-white hover:bg-opacity-20">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="p-5">
        <!-- Shift Display Section -->
        <div class="mb-5">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Shift Pengemudi</h4>
            <div class="grid grid-cols-1 gap-3">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-blue-800 bg-blue-100 rounded shadow-sm">P</span>
                    <span class="text-sm text-gray-700">Shift Pagi - Batangan (Biru Tua)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-sky-800 bg-sky-100 rounded shadow-sm">P</span>
                    <span class="text-sm text-gray-700">Shift Pagi - Cadangan (Biru Muda)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-orange-800 bg-orange-100 rounded shadow-sm">S</span>
                    <span class="text-sm text-gray-700">Shift Siang - Batangan (Oranye)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-amber-800 bg-amber-100 rounded shadow-sm">S</span>
                    <span class="text-sm text-gray-700">Shift Siang - Cadangan (Kuning)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-purple-800 bg-purple-100 rounded shadow-sm">P+S</span>
                    <span class="text-sm text-gray-700">Kedua Shift (Ungu)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded shadow-sm">B</span>
                    <span class="text-sm text-gray-700">Backup (Kuning Cerah)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold rounded bg-red-100 text-red-800 shadow-sm">OFF</span>
                    <span class="text-sm text-gray-700">Cuti / Libur (Merah)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs text-gray-300 border border-gray-200 rounded shadow-sm">-</span>
                    <span class="text-sm text-gray-700">Tidak Dijadwalkan</span>
                </div>
            </div>
        </div>
        
        <!-- Unit Status Section -->
        <div class="mb-5">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Status Unit</h4>
            <div class="grid grid-cols-1 gap-3">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold text-teal-800 bg-teal-100 rounded shadow-sm">M</span>
                    <span class="text-sm text-gray-700">Unit Dalam Perawatan (Maintenance)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-10 h-7 mr-3 text-xs font-semibold rounded renops-indicator shadow-sm">R</span>
                    <span class="text-sm text-gray-700">Unit Tidak Beroperasi (Renops)</span>
                </div>
            </div>
        </div>
        
        <!-- Shift Section -->
        <div class="mb-5">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Waktu Shift</h4>
            <div class="grid grid-cols-1 gap-3">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center px-3 py-1 mr-3 text-xs font-medium text-blue-600 bg-blue-100 rounded shadow-sm">
                        <i class="mr-1 fas fa-sun"></i>Pagi
                    </span>
                    <span class="text-sm text-gray-700">Shift Pagi (06:00 - 14:00)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center px-3 py-1 mr-3 text-xs font-medium rounded text-amber-600 bg-amber-100 shadow-sm">
                        <i class="mr-1 fas fa-moon"></i>Siang
                    </span>
                    <span class="text-sm text-gray-700">Shift Siang (14:00 - 22:00)</span>
                </div>
            </div>
        </div>
        
        <!-- Holiday Section -->
        <div class="mb-5">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Hari Libur</h4>
            <div class="grid grid-cols-1 gap-3">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-7 h-7 mr-3 text-red-700 rounded bg-red-50 shadow-sm">
                        <i class="text-xs fas fa-star-of-life"></i>
                    </span>
                    <span class="text-sm text-gray-700">Hari Libur Nasional</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-7 h-7 mr-3 rounded bg-amber-50 text-amber-700 shadow-sm">
                        <span class="text-xs font-medium">Sab</span>
                    </span>
                    <span class="text-sm text-gray-700">Hari Libur Akhir Pekan</span>
                </div>
            </div>
        </div>
        
        <!-- Edit Mode Section -->
        <div id="edit-mode-legend" class="hidden w-full">
            <h4 class="text-sm font-semibold text-gray-600 mb-3 border-b pb-1">Mode Edit</h4>
            <div class="flex flex-wrap gap-4">
                <div class="flex items-center">
                    <input type="checkbox" checked class="w-5 h-5 mr-2 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">Centang untuk menjadwalkan pengemudi</span>
                </div>
                <div class="flex items-center mt-2">
                    <button id="toggle-all-btn" class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm transition-colors">
                        Toggle Semua
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay for closing drawer when clicking outside -->
<div id="legends-overlay" class="fixed inset-0 z-40 hidden bg-black bg-opacity-50 transition-opacity duration-300 opacity-0"></div>

<!-- Inline JavaScript for drawer functionality -->
<script>
    // Wait for DOM to be fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const showLegendsBtn = document.getElementById('show-legends-btn');
        const closeLegendsBtn = document.getElementById('close-legends-btn');
        const legendsDrawer = document.getElementById('legends-drawer');
        const legendsOverlay = document.getElementById('legends-overlay');
        
        // Function to open the legends drawer
        function openLegendsDrawer() {
            console.log('Opening drawer');
            legendsDrawer.classList.remove('translate-x-full');
            legendsOverlay.classList.remove('hidden');
            setTimeout(() => {
                legendsOverlay.classList.add('opacity-100');
                legendsOverlay.classList.remove('opacity-0');
            }, 50);
            document.body.classList.add('overflow-hidden');
        }
        
        // Function to close the legends drawer
        function closeLegendsDrawer() {
            console.log('Closing drawer');
            legendsDrawer.classList.add('translate-x-full');
            legendsOverlay.classList.add('opacity-0');
            legendsOverlay.classList.remove('opacity-100');
            setTimeout(() => {
                legendsOverlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }
        
        // Add event listeners
        if (showLegendsBtn) {
            showLegendsBtn.addEventListener('click', openLegendsDrawer);
            console.log('Show button listener added');
        } else {
            console.log('Show button not found');
        }
        
        if (closeLegendsBtn) {
            closeLegendsBtn.addEventListener('click', closeLegendsDrawer);
            console.log('Close button listener added');
        } else {
            console.log('Close button not found');
        }
        
        if (legendsOverlay) {
            legendsOverlay.addEventListener('click', closeLegendsDrawer);
            console.log('Overlay listener added');
        } else {
            console.log('Overlay not found');
        }
        
        // Close drawer with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !legendsDrawer.classList.contains('translate-x-full')) {
                closeLegendsDrawer();
            }
        });
        
        console.log('Legends drawer script initialized');
    });
</script>
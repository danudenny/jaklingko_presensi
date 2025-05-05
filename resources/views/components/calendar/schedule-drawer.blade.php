<div id="schedule-drawer" class="fixed inset-0 overflow-hidden z-50 hidden">
    <!-- Backdrop -->
    <div id="schedule-drawer-backdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    
    <!-- Drawer panel -->
    <div class="fixed inset-y-0 right-0 max-w-full flex">
        <div id="schedule-drawer-panel" class="w-screen max-w-md transform transition ease-in-out duration-300 translate-x-full">
            <div class="h-full flex flex-col bg-white shadow-xl overflow-y-auto">
                <!-- Header -->
                <div class="px-4 py-6 bg-blue-700 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 id="schedule-drawer-title" class="text-lg font-medium text-white">Detail Jadwal</h2>
                        <div class="ml-3 h-7 flex items-center">
                            <button onclick="closeScheduleDrawer()" class="bg-blue-700 rounded-md text-blue-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                                <span class="sr-only">Close panel</span>
                                <i class="fa-solid fa-xmark h-6 w-6"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="relative flex-1 px-4 py-6 sm:px-6">
                    <!-- Loading spinner -->
                    <div id="schedule-drawer-loading" class="flex justify-center items-center h-full">
                        <i class="fa-solid fa-spinner animate-spin h-10 w-10 text-blue-500"></i>
                    </div>
                    
                    <!-- Schedule details -->
                    <div id="schedule-drawer-content" class="space-y-6 hidden">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 id="driver-name" class="text-lg font-medium text-gray-900"></h3>
                                    <p id="driver-type" class="mt-1 text-sm text-gray-500"></p>
                                </div>
                                <span id="shift-badge-morning" class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800 hidden">Shift Pagi</span>
                                <span id="shift-badge-evening" class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800 hidden">Shift Siang</span>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <dl class="divide-y divide-gray-200">
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                                    <dd id="schedule-date" class="text-sm text-gray-900"></dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Rute</dt>
                                    <dd id="route-name" class="text-sm text-gray-900"></dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Unit</dt>
                                    <dd id="unit-info" class="text-sm text-gray-900"></dd>
                                </div>
                                <div id="backup-driver-container" class="py-3 flex justify-between hidden">
                                    <dt class="text-sm font-medium text-gray-500">Pengemudi Backup</dt>
                                    <dd id="backup-driver-name" class="text-sm text-gray-900"></dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <a id="edit-schedule-link" href="#" class="flex-1 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-center">
                                Edit Jadwal
                            </a>
                            <button id="delete-schedule-button" class="flex-1 bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Hapus Jadwal
                            </button>
                        </div>
                    </div>
                    
                    <!-- Error message -->
                    <div id="schedule-drawer-error" class="text-center py-10 hidden">
                        <i class="fa-solid fa-calendar-xmark mx-auto h-12 w-12 text-gray-400"></i>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak dapat memuat data jadwal</h3>
                        <p class="mt-1 text-sm text-gray-500">Silakan coba lagi nanti atau hubungi administrator.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="date-drawer" class="fixed inset-0 overflow-hidden z-50 hidden">
    <!-- Backdrop -->
    <div id="date-drawer-backdrop" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    
    <!-- Drawer panel -->
    <div class="fixed inset-y-0 right-0 max-w-full flex">
        <div id="date-drawer-panel" class="w-screen max-w-md transform transition ease-in-out duration-300 translate-x-full">
            <div class="h-full flex flex-col bg-white shadow-xl overflow-y-auto">
                <!-- Header -->
                <div class="px-4 py-6 bg-blue-700 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 id="date-drawer-title" class="text-lg font-medium text-white">Jadwal Tanggal</h2>
                        <div class="ml-3 h-7 flex items-center">
                            <button onclick="closeDateDrawer()" class="bg-blue-700 rounded-md text-blue-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                                <span class="sr-only">Close panel</span>
                                <i class="fa-solid fa-xmark h-6 w-6"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="relative flex-1 px-4 py-6 sm:px-6">
                    <!-- Loading spinner -->
                    <div id="date-drawer-loading" class="flex justify-center items-center h-full">
                        <i class="fa-solid fa-spinner animate-spin h-10 w-10 text-blue-500"></i>
                    </div>
                    
                    <!-- Schedule summary -->
                    <div id="date-drawer-content" class="space-y-6 hidden">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="text-center">
                                <h3 id="date-formatted" class="text-lg font-medium text-gray-900"></h3>
                                <div class="mt-2 flex justify-center space-x-4">
                                    <div class="text-center">
                                        <span id="morning-count" class="block text-2xl font-bold text-yellow-600">0</span>
                                        <span class="text-sm text-gray-500">Shift Pagi</span>
                                    </div>
                                    <div class="text-center">
                                        <span id="evening-count" class="block text-2xl font-bold text-indigo-600">0</span>
                                        <span class="text-sm text-gray-500">Shift Siang</span>
                                    </div>
                                    <div class="text-center">
                                        <span id="total-count" class="block text-2xl font-bold text-blue-600">0</span>
                                        <span class="text-sm text-gray-500">Total</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Driver list -->
                        <div class="border-t border-gray-200 pt-4">
                            <h4 class="font-medium text-gray-900 mb-4">Daftar Pengemudi</h4>
                            
                            <!-- Morning shift -->
                            <div id="morning-shift-container" class="mb-6">
                                <h5 class="text-sm font-medium text-yellow-600 mb-2 flex items-center">
                                    <i class="fa-solid fa-sun mr-1"></i>
                                    Shift Pagi
                                </h5>
                                <div id="morning-schedules" class="space-y-2">
                                    <!-- Morning schedules will be inserted here -->
                                </div>
                            </div>
                            
                            <!-- Evening shift -->
                            <div id="evening-shift-container" class="mb-6">
                                <h5 class="text-sm font-medium text-indigo-600 mb-2 flex items-center">
                                    <i class="fa-solid fa-sun mr-1"></i>
                                    Shift Siang
                                </h5>
                                <div id="evening-schedules" class="space-y-2">
                                    <!-- Evening schedules will be inserted here -->
                                </div>
                            </div>
                            
                            <div class="mt-6 flex justify-center">
                                <a id="view-all-link" href="#" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <i class="fa-solid fa-eye mr-2"></i>
                                    Lihat Detail Jadwal
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- No schedules message -->
                    <div id="date-drawer-empty" class="text-center py-10 hidden">
                        <i class="fa-solid fa-calendar-xmark mx-auto h-12 w-12 text-gray-400"></i>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada jadwal</h3>
                        <p class="mt-1 text-sm text-gray-500">Tidak ada jadwal untuk tanggal ini.</p>
                        <div class="mt-6">
                            <a href="{{ route('schedules.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fa-solid fa-plus mr-2"></i>
                                Tambah Jadwal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

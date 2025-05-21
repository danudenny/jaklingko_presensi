@props(['title', 'toggleMobileMenu' => false])

<header class="bg-white shadow-sm z-10 border-b border-gray-200">
    <div class="px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
            <!-- Left side with mobile menu button and title -->
            <div class="flex items-center">
                @if($toggleMobileMenu)
                <!-- Mobile menu button -->
                <button 
                    @click="mobileMenuOpen = !mobileMenuOpen" 
                    type="button" 
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 lg:hidden"
                >
                    <span class="sr-only">Open main menu</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                @endif

                <h1 class="text-xl font-semibold text-gray-800 ml-2 lg:ml-0">{{ $title }}</h1>
            </div>
            
            <!-- Right side with user menu -->
            <div class="flex items-center space-x-2 sm:space-x-4">
                <!-- Global Search (hidden on mobile) -->
                <div x-data="{ isOpen: false, searchQuery: '', searchResults: { drivers: [], units: [], routes: [] }, isLoading: false }" class="hidden md:block relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center" x-show="isLoading">
                        <svg class="animate-spin h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        placeholder="Cari pengemudi, unit, rute..." 
                        class="block w-64 pl-10 pr-10 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors"
                        x-model="searchQuery"
                        @focus="isOpen = true"
                        @click.away="isOpen = false"
                        @keyup.debounce.300ms="
                            if (searchQuery.length >= 2) {
                                isLoading = true;
                                fetch(`{{ route('global.search') }}?query=${searchQuery}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        searchResults = data;
                                        isOpen = (data.drivers.length > 0 || data.units.length > 0 || data.routes.length > 0);
                                        isLoading = false;
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        isLoading = false;
                                    });
                            } else {
                                isOpen = false;
                            }
                        "
                    >
                    
                    <!-- Search Results Dropdown -->
                    <div 
                        x-show="isOpen" 
                        x-transition:enter="transition ease-out duration-200" 
                        x-transition:enter-start="transform opacity-0 scale-95" 
                        x-transition:enter-end="transform opacity-100 scale-100" 
                        x-transition:leave="transition ease-in duration-75" 
                        x-transition:leave-start="transform opacity-100 scale-100" 
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 mt-2 w-80 bg-white rounded-md shadow-lg z-50 overflow-hidden"
                        style="display: none;"
                    >
                        <div class="max-h-96 overflow-y-auto">
                            <!-- Drivers Section -->
                            <template x-if="searchResults.drivers.length > 0">
                                <div>
                                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                        <h3 class="text-xs font-semibold text-gray-600 uppercase">Pengemudi</h3>
                                    </div>
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="driver in searchResults.drivers" :key="driver.id">
                                            <a :href="driver.url" class="block px-4 py-3 hover:bg-gray-50">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900" x-text="driver.name"></p>
                                                        <div class="flex items-center">
                                                            <span 
                                                                class="text-xs px-1.5 py-0.5 rounded-full mr-1"
                                                                :class="driver.type === 'batangan' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800'"
                                                                x-text="driver.type === 'batangan' ? 'Batangan' : 'Cadangan'"
                                                            ></span>
                                                            <p class="text-xs text-gray-500" x-text="driver.identifier"></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Units Section -->
                            <template x-if="searchResults.units.length > 0">
                                <div>
                                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                        <h3 class="text-xs font-semibold text-gray-600 uppercase">Unit</h3>
                                    </div>
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="unit in searchResults.units" :key="unit.id">
                                            <a :href="unit.url" class="block px-4 py-3 hover:bg-gray-50">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900" x-text="'Unit ' + unit.unit_number"></p>
                                                        <div class="flex items-center">
                                                            <span 
                                                                class="text-xs px-1.5 py-0.5 rounded-full mr-1"
                                                                :class="unit.is_pool ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'"
                                                                x-text="unit.is_pool ? 'Pool' : 'Non-Pool'"
                                                            ></span>
                                                            <p class="text-xs text-gray-500" x-text="unit.plate_number"></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- Routes Section -->
                            <template x-if="searchResults.routes.length > 0">
                                <div>
                                    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
                                        <h3 class="text-xs font-semibold text-gray-600 uppercase">Rute</h3>
                                    </div>
                                    <div class="divide-y divide-gray-100">
                                        <template x-for="route in searchResults.routes" :key="route.id">
                                            <a :href="route.url" class="block px-4 py-3 hover:bg-gray-50">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 rounded-full bg-purple-100 flex items-center justify-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-3">
                                                        <p class="text-sm font-medium text-gray-900" x-text="'Rute ' + route.route_number"></p>
                                                        <p class="text-xs text-gray-500" x-text="route.name"></p>
                                                    </div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            
                            <!-- No Results -->
                            <template x-if="searchQuery.length >= 2 && searchResults.drivers.length === 0 && searchResults.units.length === 0 && searchResults.routes.length === 0 && !isLoading">
                                <div class="px-4 py-6 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500">Tidak ada hasil ditemukan</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div x-data="{ open: false }" class="relative">
                    <button 
                        @click="open = !open" 
                        class="p-1.5 rounded-full text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 relative"
                    >
                        <span class="sr-only">View notifications</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <div class="absolute top-0 right-0 h-2.5 w-2.5 bg-red-500 rounded-full border border-white"></div>
                    </button>
                    
                    <div 
                        x-show="open" 
                        @click.away="open = false" 
                        x-transition:enter="transition ease-out duration-200" 
                        x-transition:enter-start="transform opacity-0 scale-95" 
                        x-transition:enter-end="transform opacity-100 scale-100" 
                        x-transition:leave="transition ease-in duration-75" 
                        x-transition:leave-start="transform opacity-100 scale-100" 
                        x-transition:leave-end="transform opacity-0 scale-95" 
                        class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                        style="display: none;"
                    >
                        <div class="py-1">
                            <div class="px-4 py-2 border-b border-gray-200">
                                <h3 class="text-sm font-medium text-gray-800">Notifications</h3>
                            </div>
                            <div class="max-h-60 overflow-y-auto">
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex">
                                        <div class="flex-shrink-0 bg-blue-500 h-10 w-10 rounded-full flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">New leave request</p>
                                            <p class="text-xs text-gray-500">John Doe has requested leave for 3 days</p>
                                            <p class="text-xs text-gray-400 mt-1">2 minutes ago</p>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex">
                                        <div class="flex-shrink-0 bg-green-500 h-10 w-10 rounded-full flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">Schedule confirmed</p>
                                            <p class="text-xs text-gray-500">Bus #123 schedule has been confirmed</p>
                                            <p class="text-xs text-gray-400 mt-1">1 hour ago</p>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="px-4 py-2 text-center border-t border-gray-200">
                                <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all notifications</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User dropdown -->
                <div x-data="{ open: false }" class="relative">
                    <button 
                        @click="open = !open" 
                        class="flex items-center space-x-2 p-1 rounded-full text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                            <span class="text-white font-medium text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                        </div>
                        <span class="hidden md:inline-block text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="hidden md:inline-block h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <div 
                        x-show="open" 
                        @click.away="open = false" 
                        x-transition:enter="transition ease-out duration-200" 
                        x-transition:enter-start="transform opacity-0 scale-95" 
                        x-transition:enter-end="transform opacity-100 scale-100" 
                        x-transition:leave="transition ease-in duration-75" 
                        x-transition:leave-start="transform opacity-100 scale-100" 
                        x-transition:leave-end="transform opacity-0 scale-95" 
                        class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
                        style="display: none;"
                    >
                        <div class="py-1">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</a>
                            <a href="{{ route('profile.settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header> 
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
                <!-- Search (hidden on mobile) -->
                <div class="hidden md:block relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" placeholder="Search..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-colors">
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
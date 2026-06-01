<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Presensi') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Flowbite JS for sidebar dropdown functionality -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    
    <!-- Toastr CSS and JS for notifications -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <!-- Toastr Configuration -->
    <script>
        // Configure Toastr options
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: "toast-top-right",
            timeOut: 3000,
            extendedTimeOut: 1000,
            preventDuplicates: true
        };
    </script>
    
    <!-- Custom Styles -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-800 h-full">
    <div x-data="{ mobileMenuOpen: false, drawerOpen: false, drawerContent: null, drawerTitle: '' }" class="flex h-full">
        <!-- Mobile Menu Overlay -->
        <div 
            x-show="mobileMenuOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false"
            class="fixed inset-0 z-20 bg-gray-600 bg-opacity-75 lg:hidden"
            style="display: none;"
        ></div>

        <!-- Mobile Sidebar -->
        <div 
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-0 flex z-40 lg:hidden"
            style="display: none;"
        >
            <div class="relative flex-1 flex flex-col max-w-xs w-full bg-gray-900">
                <div class="absolute top-0 right-0 -mr-12 pt-2">
                    <button 
                        @click="mobileMenuOpen = false"
                        class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
                    >
                        <span class="sr-only">Close sidebar</span>
                        <i class="fas fa-times h-6 w-6 text-white"></i>
                    </button>
                </div>
                @include('components.sidebar', ['mobile' => true])
            </div>
            <div class="flex-shrink-0 w-14" aria-hidden="true">
                <!-- Force sidebar to shrink to fit close icon -->
            </div>
        </div>

        <!-- Desktop Sidebar -->
        <div class="hidden lg:flex lg:shrink-0">
            @include('components.sidebar', ['mobile' => false])
        </div>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
            <!-- Top Navigation -->
            @include('components.navbar', ['title' => $header ?? 'Dashboard', 'toggleMobileMenu' => true])

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="py-6">
                    <!-- Flash Messages -->
                    <div class="px-4 sm:px-6 lg:px-8">
                        <x-flash-message />
                    </div>

                    <!-- Main Content Container -->
                    <div class="px-4 sm:px-6 lg:px-8">
                        @yield('content')
                    </div>
                </div>
            </main>

            <!-- Footer -->
            @include('components.footer')
        </div>
        
        <!-- Right Wide Drawer -->
        <div 
            x-show="drawerOpen" 
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="drawerOpen = false"
            class="fixed inset-0 z-20 bg-gray-600 bg-opacity-75"
            style="display: none;"
        ></div>
        
        <div 
            x-show="drawerOpen"
            x-transition:enter="transition ease-in-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-300 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 flex z-40 max-w-md w-full bg-white shadow-xl"
            style="display: none;"
        >
            <div class="relative flex-1 flex flex-col w-full">
                <!-- Drawer Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900" x-text="drawerTitle"></h2>
                    <button 
                        @click="drawerOpen = false"
                        class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500"
                    >
                        <span class="sr-only">Close drawer</span>
                        <i class="fas fa-times h-6 w-6"></i>
                    </button>
                </div>
                
                <!-- Drawer Content -->
                <div class="flex-1 overflow-y-auto p-4" x-html="drawerContent"></div>
            </div>
        </div>
    </div>

    <!-- Alpine.js Store Setup -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('sidebar', {
                collapsed: localStorage.getItem('sidebar_collapsed') === 'true',
                toggle() {
                    this.collapsed = !this.collapsed;
                    localStorage.setItem('sidebar_collapsed', this.collapsed);
                }
            });
            
            Alpine.data('drawer', () => ({
                open(title, content) {
                    this.drawerTitle = title;
                    this.drawerContent = content;
                    this.drawerOpen = true;
                }
            }));
        });
    </script>

    @stack('scripts')
</body>
</html>
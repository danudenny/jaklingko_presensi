@props(['mobile' => false])

<div 
    x-data 
    class="{{ $mobile ? 'h-full' : 'h-screen fixed left-0 top-0' }} flex flex-col bg-gray-900 overflow-y-auto transition-all duration-300"
    :class="{
        'w-64': !$store.sidebar.collapsed && !{{ $mobile ? 'true' : 'false' }}, 
        'w-20': $store.sidebar.collapsed && !{{ $mobile ? 'true' : 'false' }}, 
        'w-full': {{ $mobile ? 'true' : 'false' }}
    }"
>
    <!-- Logo -->
    <div class="flex h-16 shrink-0 items-center justify-between px-4 bg-gray-800">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span class="ml-2 text-xl font-bold text-white transition-opacity duration-300" :class="$store.sidebar.collapsed && !{{ $mobile ? 'true' : 'false' }} ? 'opacity-0 hidden' : 'opacity-100'">{{ config('app.name', 'Presensi') }}</span>
        </a>
        @if(!$mobile)
        <button 
            @click="$store.sidebar.toggle()"
            class="p-1 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path x-show="!$store.sidebar.collapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                <path x-show="$store.sidebar.collapsed" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
            </svg>
        </button>
        @endif
    </div>
    
    <!-- User Info -->
    <div class="border-t border-b border-gray-700 py-4 px-4 shrink-0">
        <div class="flex items-center">
            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center">
                <span class="text-white font-medium">{{ substr(Auth::user()->name, 0, 1) }}</span>
            </div>
            <div class="ml-3 transition-opacity duration-300" :class="$store.sidebar.collapsed && !{{ $mobile ? 'true' : 'false' }} ? 'opacity-0 hidden' : 'opacity-100'">
                <p class="text-sm font-medium text-white">{{ Auth::user()->name }}</p>
                <p class="text-xs text-gray-400">
                    @if(Auth::user()->isSuperAdmin())
                        Super Admin
                    @else
                        Admin
                    @endif
                </p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto mt-4 px-2 space-y-1">
        @include('components.sidebar-item', [
            'route' => 'dashboard',
            'icon' => 'home',
            'text' => 'Dashboard'
        ])

        @if (Auth::user()->isSuperAdmin())
        @include('components.sidebar-item', [
            'route' => 'users.index',
            'icon' => 'users',
            'text' => 'Users'
        ])
        @endif

        @include('components.sidebar-item', [
            'route' => 'drivers.index',
            'icon' => 'user',
            'text' => 'Pengemudi'
        ])

        @include('components.sidebar-item', [
            'route' => 'units.index',
            'icon' => 'truck',
            'text' => 'Unit'   
        ])

        @include('components.sidebar-item', [
            'route' => 'routes.index',
            'icon' => 'map',
            'text' => 'Rute'
        ])
        
        @include('components.sidebar-item', [
            'route' => 'schedules.index',
            'icon' => 'calendar',
            'text' => 'Jadwal'
        ])

        @include('components.sidebar-item', [
            'route' => 'leave-requests.index',
            'icon' => 'document',
            'text' => 'Pengajuan Cuti'
        ])

        @include('components.sidebar-item', [
            'route' => 'unit-problems.index',
            'icon' => 'exclamation-triangle',
            'text' => 'Laporan Masalah'
        ])
        
        @include('components.sidebar-item', [
            'route' => 'maintenance-logs.index',
            'icon' => 'tools',
            'text' => 'Log Perawatan'
        ])
        
        @include('components.sidebar-item', [
            'route' => 'kilometer-reports.index',
            'icon' => 'tachometer-alt',
            'text' => 'Laporan KM'
        ])

        @include('components.sidebar-item', [
            'route' => 'reports.index',
            'icon' => 'chart-bar',
            'text' => 'Laporan'
        ])
    </nav>
    
    <!-- Logout -->
    <div class="px-3 py-4 border-t border-gray-700 mt-auto shrink-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center px-4 py-2 text-sm font-medium text-gray-300 rounded-md hover:bg-gray-700 hover:text-white group transition-colors duration-200">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span class="ml-3 transition-opacity duration-300" :class="$store.sidebar.collapsed && !{{ $mobile ? 'true' : 'false' }} ? 'opacity-0 hidden' : 'opacity-100'">Keluar</span>
            </button>
        </form>
    </div>
</div> 
@props(['route', 'icon', 'text'])

<a href="{{ route($route) }}" 
   class="flex items-center px-4 py-2 text-sm font-medium rounded-md group transition-colors duration-200 {{ request()->routeIs($route) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
    <div class="flex-shrink-0 inline-flex items-center justify-center h-6 w-6 relative">
        <!-- Pulsing red icon -->
        <span class="text-red-500 animate-pulse">
            @if($icon === 'home')
                <i class="fa-solid fa-house"></i>
            @elseif($icon === 'users')
                <i class="fa-solid fa-users"></i>
            @elseif($icon === 'user')
                <i class="fa-solid fa-user"></i>
            @elseif($icon === 'truck')
                <i class="fa-solid fa-truck"></i>
            @elseif($icon === 'map')
                <i class="fa-solid fa-map"></i>
            @elseif($icon === 'calendar')
                <i class="fa-solid fa-calendar"></i>
            @elseif($icon === 'document')
                <i class="fa-solid fa-file"></i>
            @elseif($icon === 'chart-bar')
                <i class="fa-solid fa-chart-bar"></i>
            @else
                <i class="fa-solid fa-{{ $icon }}"></i>
            @endif
        </span>
    </div>
    
    <span class="ml-3 transition-opacity duration-300" x-bind:class="$store.sidebar.collapsed ? 'opacity-0 hidden' : 'opacity-100'">{{ $text }}</span>
    
    <!-- Tooltip for collapsed sidebar -->
    <div x-show="$store.sidebar.collapsed" 
         x-cloak
         class="absolute left-20 z-50 mt-2 px-2 py-1 bg-gray-800 rounded-md text-xs text-white opacity-0 group-hover:opacity-100 transform transition-opacity duration-300 pointer-events-none">
        {{ $text }}
    </div>
</a>

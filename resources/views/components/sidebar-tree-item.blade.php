@props(['route', 'text'])

<a href="{{ route($route) }}" 
   class="flex items-center py-2 pl-2 pr-4 text-sm font-medium rounded-md transition-colors duration-200 {{ request()->routeIs($route) ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' }}">
    <span class="ml-2">{{ $text }}</span>
</a>

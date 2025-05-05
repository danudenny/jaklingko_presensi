@props(['title', 'value', 'icon', 'color' => 'blue'])

<div class="bg-white rounded-lg shadow p-6 border-l-4 border-{{ $color }}-500 transition-transform duration-200 hover:scale-105">
    <div class="flex items-center">
        <div class="p-3 rounded-full bg-{{ $color }}-100 mr-4">
            @switch($icon)
                @case('user')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-{{ $color }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    @break
                @case('bus')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-{{ $color }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    @break
                @case('route')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-{{ $color }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    @break
                @case('calendar')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-{{ $color }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    @break
                @default
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-{{ $color }}-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
            @endswitch
        </div>
        <div>
            <p class="text-gray-500 text-sm">{{ $title }}</p>
            <p class="text-2xl font-bold">{{ $value }}</p>
        </div>
    </div>
</div>

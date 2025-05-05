@props([
    'day' => 1,
    'date' => null,
    'isToday' => false,
    'hasSchedules' => false,
    'schedules' => [],
])

@php
    $dateString = $date->format('Y-m-d');
    $cellClass = $isToday ? 'ring-2 ring-blue-500' : '';
    $cellClass .= $hasSchedules ? ' cursor-pointer hover:bg-gray-50' : '';
@endphp

<div class="min-h-[120px] bg-white p-1 {{ $cellClass }}" 
     @if($hasSchedules)
     onclick="openDateDrawer('{{ $dateString }}')"
     @endif>
    <div class="flex justify-between items-center">
        <div class="text-xs p-1 {{ $isToday ? 'font-bold text-blue-600' : 'text-gray-700' }}">
            {{ $day }}
        </div>
        @if($hasSchedules)
            <div class="px-1 py-0.5 text-xs rounded-full bg-blue-500 text-white">
                {{ count($schedules) }}
            </div>
        @endif
    </div>

    @if($hasSchedules)
        <div class="mt-1 space-y-1 max-h-[100px] overflow-y-auto">
            @foreach($schedules as $schedule)
                <div class="{{ $schedule->shift == 'pagi' || $schedule->shift == 'morning' ? 'bg-yellow-100 text-yellow-800' : 'bg-indigo-100 text-indigo-800' }} px-1 py-0.5 text-xs rounded cursor-pointer hover:bg-gray-50 transition"
                     onclick="event.stopPropagation(); openScheduleDrawer({{ $schedule->id }})">
                    <div class="font-medium truncate">
                        {{ $schedule->shift == 'pagi' || $schedule->shift == 'morning' ? '🌅' : '🌙' }}
                        {{ $schedule->driver->name }}
                    </div>
                    <div class="truncate">
                        {{ $schedule->route->name }} - {{ $schedule->unit->unit_number }}
                    </div>
                    <div class="truncate text-[10px] {{ $schedule->driver->type == 'batangan' ? 'text-green-800' : 'text-purple-800' }}">
                        {{ $schedule->driver->type == 'batangan' ? 'Batangan' : 'Cadangan' }}
                    </div>
                </div>
            @endforeach
        </div>
        @if(count($schedules) > 3)
            <a href="{{ route('schedules.index', ['date' => $dateString]) }}" class="block text-center text-xs text-blue-600 hover:text-blue-800 mt-1">
                Lihat semua ({{ count($schedules) }})
            </a>
        @endif
    @endif
</div>

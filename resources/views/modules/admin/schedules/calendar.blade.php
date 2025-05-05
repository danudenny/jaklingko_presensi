@extends('modules.admin.layouts.main')

@section('title', 'Jadwal Kalender')
@section('header', 'Jadwal Kalender')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Jadwal Kalender</h1>
            <div class="flex space-x-2">
                <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fa-solid fa-table"></i>
                    Tampilan Daftar
                </a>
                <a href="{{ route('schedules.weekly') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fa-solid fa-table"></i>
                    Tampilan Mingguan
                </a>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fa-solid fa-plus"></i>
                        Tambah Jadwal
                    </button>
                    <div x-show="open" @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                        <div class="py-1" role="none">
                            <a href="{{ route('schedules.create') }}" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" role="menuitem">Tambah Manual</a>
                            <a href="{{ route('schedules.generate.form') }}" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" role="menuitem">Buat Otomatis</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <!-- Calendar Navigation -->
                <div class="flex justify-between items-center mb-6">
                    <a href="{{ route('schedules.calendar', ['month' => $previousMonth->month, 'year' => $previousMonth->year]) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fa-solid fa-arrow-left"></i>
                        {{ $previousMonth->locale('id')->isoFormat('MMMM Y') }}
                    </a>
                    <h2 class="text-xl font-semibold text-gray-900">{{ $date->locale('id')->isoFormat('MMMM Y') }}</h2>
                    <a href="{{ route('schedules.calendar', ['month' => $nextMonth->month, 'year' => $nextMonth->year]) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        {{ $nextMonth->locale('id')->isoFormat('MMMM Y') }}
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <!-- Filter Controls -->
                <div class="mb-4">
                    <form action="{{ route('schedules.calendar') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="month" value="{{ $date->month }}">
                        <input type="hidden" name="year" value="{{ $date->year }}">
                        
                        <div>
                            <label for="driver_type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Pengemudi</label>
                            <select id="driver_type" name="driver_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Semua Tipe</option>
                                <option value="batangan" {{ request('driver_type') == 'batangan' ? 'selected' : '' }}>Batangan</option>
                                <option value="cadangan" {{ request('driver_type') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="route_id" class="block text-sm font-medium text-gray-700 mb-1">Rute</label>
                            <select id="route_id" name="route_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Semua Rute</option>
                                @foreach(\App\Models\Route::active()->get() as $route)
                                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                            <select id="unit_id" name="unit_id" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                <option value="">Semua Unit</option>
                                @foreach(\App\Models\Unit::active()->get() as $unit)
                                    <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>{{ $unit->unit_number }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <i class="fa-solid fa-filter"></i>
                                Filter
                            </button>
                            <a href="{{ route('schedules.calendar', ['month' => $date->month, 'year' => $date->year]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-px bg-gray-200 border border-gray-200 rounded-lg overflow-hidden">
                    <!-- Calendar Headers (Days of Week) -->
                    @foreach(['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'] as $dayName)
                        <div class="bg-gray-100 p-2 text-center font-medium text-gray-700">
                            {{ $dayName }}
                        </div>
                    @endforeach

                    <!-- Calendar Days -->
                    @php
                        $firstDayOfMonth = $date->copy()->firstOfMonth();
                        $lastDayOfMonth = $date->copy()->lastOfMonth();

                        // Get the day of week of the first day (0 = Sunday, 6 = Saturday)
                        $firstDayOfWeek = $firstDayOfMonth->dayOfWeek;

                        // Calculate the previous month's days that need to be shown
                        $daysFromPreviousMonth = $firstDayOfWeek;

                        // Calculate the next month's days that need to be shown
                        $daysInCurrentMonth = $date->daysInMonth;
                        $totalDaysToShow = ceil(($daysFromPreviousMonth + $daysInCurrentMonth) / 7) * 7;
                        $daysFromNextMonth = $totalDaysToShow - $daysFromPreviousMonth - $daysInCurrentMonth;

                        // Get the previous month's last days
                        $previousMonth = $date->copy()->subMonth();
                        $previousMonthDays = $previousMonth->daysInMonth;

                        // Current date for highlighting today
                        $today = \Carbon\Carbon::now()->format('Y-m-d');
                    @endphp

                    <!-- Previous Month Days -->
                    @for($i = $daysFromPreviousMonth - 1; $i >= 0; $i--)
                        @php
                            $day = $previousMonthDays - $i;
                            $currentDate = $previousMonth->copy()->day($day);
                            $dateString = $currentDate->format('Y-m-d');
                        @endphp
                        <div class="min-h-[120px] bg-white p-1 text-gray-400">
                            <div class="text-xs p-1">{{ $day }}</div>
                        </div>
                    @endfor

                    <!-- Current Month Days -->
                    @for($day = 1; $day <= $daysInCurrentMonth; $day++)
                        @php
                            $currentDate = $date->copy()->day($day);
                            $dateString = $currentDate->format('Y-m-d');
                            $isToday = $dateString === $today;
                            $hasSchedules = isset($schedules[$dateString]) && count($schedules[$dateString]) > 0;
                        @endphp
                        
                        <x-calendar.day-cell 
                            :day="$day" 
                            :date="$currentDate" 
                            :isToday="$isToday" 
                            :hasSchedules="$hasSchedules"
                            :schedules="$hasSchedules ? $schedules[$dateString] : []"
                        />
                    @endfor

                    <!-- Next Month Days -->
                    @for($day = 1; $day <= $daysFromNextMonth; $day++)
                        <div class="min-h-[120px] bg-white p-1 text-gray-400">
                            <div class="text-xs p-1">{{ $day }}</div>
                        </div>
                    @endfor
                </div>

                <!-- Legend -->
                <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-2">
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-yellow-100 mr-1 border border-yellow-200 rounded-sm"></span>
                        <span>Shift Pagi</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-indigo-100 mr-1 border border-indigo-200 rounded-sm"></span>
                        <span>Shift Siang</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-blue-50 mr-1 border border-blue-200 rounded-sm"></span>
                        <span>Hari Ini</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-green-100 mr-1 border border-green-200 rounded-sm"></span>
                        <span>Pengemudi Batangan</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-purple-100 mr-1 border border-purple-200 rounded-sm"></span>
                        <span>Pengemudi Cadangan</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include drawer components -->
<x-calendar.date-drawer />
<x-calendar.schedule-drawer />

@endsection

@push('scripts')
    <x-calendar.calendar-scripts />
@endpush

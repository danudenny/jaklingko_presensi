@extends('modules.admin.layouts.main')

@section('title', 'Jadwal Mingguan')
@section('header', 'Jadwal Mingguan')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Jadwal Mingguan</h1>
            <div class="flex space-x-2">
                <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                    Tampilan Daftar
                </a>
                <a href="{{ route('schedules.calendar') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Tampilan Kalender
                </a>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
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
                <!-- Date Navigation -->
                <div class="flex justify-between items-center mb-6">
                    @php
                        $prevWeek = \Carbon\Carbon::parse($startDate)->subWeek()->format('Y-m-d');
                        $nextWeek = \Carbon\Carbon::parse($startDate)->addWeek()->format('Y-m-d');
                    @endphp
                    <a href="{{ route('schedules.weekly', ['start_date' => $prevWeek]) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Minggu Sebelumnya
                    </a>
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ \Carbon\Carbon::parse($startDate)->format('d M') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </h2>
                    <a href="{{ route('schedules.weekly', ['start_date' => $nextWeek]) }}" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Minggu Berikutnya
                        <svg class="-mr-1 ml-1 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- Weekly Schedule Grid -->
                <div class="overflow-x-auto">
                    <div class="mb-4">
                        <form action="{{ route('schedules.weekly') }}" method="GET" class="flex flex-wrap gap-4 items-end">
                            <input type="hidden" name="start_date" value="{{ $startDate }}">
                            
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
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                                    </svg>
                                    Filter
                                </button>
                                <a href="{{ route('schedules.weekly', ['start_date' => $startDate]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift Pagi</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift Siang</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @for($i = 0; $i < 7; $i++)
                                @php
                                    $currentDate = \Carbon\Carbon::parse($startDate)->addDays($i);
                                    $dateString = $currentDate->format('Y-m-d');
                                    $isToday = $currentDate->isToday();
                                @endphp
                                <tr class="{{ $isToday ? 'bg-blue-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $currentDate->locale('id')->isoFormat('dddd') }}</div>
                                        <div class="text-sm text-gray-500">{{ $currentDate->locale('id')->isoFormat('D MMMM Y') }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if(isset($schedules[$dateString]))
                                            @foreach($schedules[$dateString] as $schedule)
                                                @if($schedule->shift == 'morning' || $schedule->shift == 'pagi')
                                                    <div class="mb-2 p-2 border rounded bg-yellow-50 border-yellow-200 hover:bg-yellow-100 transition cursor-pointer" 
                                                         x-data="{}" 
                                                         @click="$dispatch('open-drawer', { schedule: {{ $schedule->id }} })">
                                                        <div class="flex justify-between">
                                                            <div class="font-medium">{{ $schedule->driver->name }}</div>
                                                            <div class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">Shift Pagi</div>
                                                        </div>
                                                        <div class="text-sm mt-1">
                                                            <span class="text-gray-600">Rute:</span> {{ $schedule->route->name }}
                                                        </div>
                                                        <div class="text-sm">
                                                            <span class="text-gray-600">Unit:</span> {{ $schedule->unit->unit_number }}
                                                        </div>
                                                        <div class="text-xs mt-1 inline-flex items-center px-2 py-0.5 rounded-full {{ $schedule->driver->type == 'batangan' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                                            {{ $schedule->driver->type == 'batangan' ? 'Batangan' : 'Cadangan' }}
                                                        </div>
                                                        @if($schedule->backup_driver_id)
                                                            <div class="text-xs text-gray-600 mt-1">
                                                                <span class="font-medium">Backup:</span> {{ $schedule->backupDriver->name }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                            @if(!$schedules[$dateString]->where('shift', 'pagi')->count() && !$schedules[$dateString]->where('shift', 'morning')->count())
                                                <div class="text-sm text-gray-500">Tidak ada jadwal</div>
                                            @endif
                                        @else
                                            <div class="text-sm text-gray-500">Tidak ada jadwal</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if(isset($schedules[$dateString]))
                                            @foreach($schedules[$dateString] as $schedule)
                                                @if($schedule->shift == 'evening' || $schedule->shift == 'siang')
                                                    <div class="mb-2 p-2 border rounded bg-indigo-50 border-indigo-200 hover:bg-indigo-100 transition cursor-pointer"
                                                         x-data="{}"
                                                         @click="$dispatch('open-drawer', { schedule: {{ $schedule->id }} })">
                                                        <div class="flex justify-between">
                                                            <div class="font-medium">{{ $schedule->driver->name }}</div>
                                                            <div class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-800">Shift Siang</div>
                                                        </div>
                                                        <div class="text-sm mt-1">
                                                            <span class="text-gray-600">Rute:</span> {{ $schedule->route->name }}
                                                        </div>
                                                        <div class="text-sm">
                                                            <span class="text-gray-600">Unit:</span> {{ $schedule->unit->unit_number }}
                                                        </div>
                                                        <div class="text-xs mt-1 inline-flex items-center px-2 py-0.5 rounded-full {{ $schedule->driver->type == 'batangan' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                                            {{ $schedule->driver->type == 'batangan' ? 'Batangan' : 'Cadangan' }}
                                                        </div>
                                                        @if($schedule->backup_driver_id)
                                                            <div class="text-xs text-gray-600 mt-1">
                                                                <span class="font-medium">Backup:</span> {{ $schedule->backupDriver->name }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endforeach
                                            @if(!$schedules[$dateString]->where('shift', 'siang')->count() && !$schedules[$dateString]->where('shift', 'evening')->count())
                                                <div class="text-sm text-gray-500">Tidak ada jadwal</div>
                                            @endif
                                        @else
                                            <div class="text-sm text-gray-500">Tidak ada jadwal</div>
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <!-- Legend -->
                <div class="mt-4 grid grid-cols-2 md:grid-cols-5 gap-2">
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-yellow-50 mr-1 border border-yellow-200 rounded-sm"></span>
                        <span>Shift Pagi</span>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="w-3 h-3 inline-block bg-indigo-50 mr-1 border border-indigo-200 rounded-sm"></span>
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

<!-- Schedule Detail Drawer -->
<div x-data="{ 
    open: false, 
    scheduleId: null,
    scheduleData: null,
    loading: false,
    async fetchSchedule(id) {
        this.loading = true;
        try {
            const response = await fetch(`/schedules/${id}`);
            if (!response.ok) throw new Error('Failed to fetch schedule');
            const data = await response.json();
            this.scheduleData = data;
        } catch (error) {
            console.error('Error fetching schedule:', error);
        } finally {
            this.loading = false;
        }
    }
}" 
     @open-drawer.window="open = true; scheduleId = $event.detail.schedule; fetchSchedule(scheduleId)"
     x-show="open" 
     class="fixed inset-0 overflow-hidden z-50" 
     x-cloak>
    
    <!-- Backdrop -->
    <div x-show="open" 
         x-transition:enter="transition-opacity ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-75" 
         @click="open = false"></div>
    
    <!-- Drawer panel -->
    <div class="fixed inset-y-0 right-0 max-w-full flex">
        <div x-show="open"
             x-transition:enter="transform transition ease-in-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transform transition ease-in-out duration-300"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="w-screen max-w-md"
             @click.away="open = false">
            
            <div class="h-full flex flex-col bg-white shadow-xl overflow-y-auto">
                <!-- Header -->
                <div class="px-4 py-6 bg-blue-700 sm:px-6">
                    <div class="flex items-start justify-between">
                        <h2 class="text-lg font-medium text-white" x-text="loading ? 'Memuat Detail Jadwal...' : 'Detail Jadwal'"></h2>
                        <div class="ml-3 h-7 flex items-center">
                            <button @click="open = false" class="bg-blue-700 rounded-md text-blue-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                                <span class="sr-only">Close panel</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="relative flex-1 px-4 py-6 sm:px-6">
                    <!-- Loading spinner -->
                    <div x-show="loading" class="flex justify-center items-center h-full">
                        <svg class="animate-spin h-10 w-10 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <!-- Schedule details -->
                    <div x-show="!loading && scheduleData" class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900" x-text="scheduleData?.driver?.name"></h3>
                                    <p class="mt-1 text-sm text-gray-500" x-text="scheduleData?.driver?.type === 'batangan' ? 'Pengemudi Batangan' : 'Pengemudi Cadangan'"></p>
                                </div>
                                <span x-show="scheduleData?.shift === 'pagi'" class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Shift Pagi</span>
                                <span x-show="scheduleData?.shift === 'siang'" class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">Shift Siang</span>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <dl class="divide-y divide-gray-200">
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                                    <dd class="text-sm text-gray-900" x-text="new Date(scheduleData?.schedule_date).toLocaleDateString('id-ID', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'})"></dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Rute</dt>
                                    <dd class="text-sm text-gray-900" x-text="scheduleData?.route?.name"></dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Unit</dt>
                                    <dd class="text-sm text-gray-900" x-text="scheduleData?.unit?.unit_number + ' - ' + scheduleData?.unit?.plate_number"></dd>
                                </div>
                                <div class="py-3 flex justify-between" x-show="scheduleData?.backup_driver_id">
                                    <dt class="text-sm font-medium text-gray-500">Pengemudi Backup</dt>
                                    <dd class="text-sm text-gray-900" x-text="scheduleData?.backup_driver?.name"></dd>
                                </div>
                            </dl>
                        </div>
                        
                        <div class="flex space-x-3 pt-4">
                            <a :href="`/schedules/${scheduleId}/edit`" class="flex-1 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 text-center">
                                Edit Jadwal
                            </a>
                            <button @click="if(confirm('Apakah Anda yakin ingin menghapus jadwal ini?')) { window.location.href = `/schedules/${scheduleId}/delete` }" class="flex-1 bg-red-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Hapus Jadwal
                            </button>
                        </div>
                    </div>
                    
                    <!-- Error message -->
                    <div x-show="!loading && !scheduleData" class="text-center py-10">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak dapat memuat data jadwal</h3>
                        <p class="mt-1 text-sm text-gray-500">Silakan coba lagi nanti atau hubungi administrator.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        // Any additional Alpine.js initialization can go here
    });
</script>
@endpush
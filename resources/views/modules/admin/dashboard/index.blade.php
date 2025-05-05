@extends('modules.admin.layouts.main')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
        <div class="px-6 py-5 sm:px-8 bg-gradient-to-r from-blue-500 to-blue-600">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">Selamat Datang, {{ Auth::user()->name }}</h2>
                    <p class="mt-1 text-blue-100">Berikut adalah informasi sistem penjadwalan unit hari ini.</p>
                </div>
                <div class="mt-4 md:mt-0 flex space-x-3">
                    <a href="{{ route('schedules.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Jadwal Baru
                    </a>
                    <a href="{{ route('reports.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-700 bg-opacity-60 hover:bg-opacity-70 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Lihat Laporan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="ml-5">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Pengemudi Aktif</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ App\Models\Driver::where('status', 'aktif')->count() }}</dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('drivers.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat semua pengemudi →</a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div class="ml-5">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Unit Aktif</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ App\Models\Unit::where('status', 'aktif')->count() }}</dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('units.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat semua unit →</a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-500 rounded-md p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                </div>
                <div class="ml-5">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Rute Aktif</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ App\Models\Route::where('status', 'aktif')->count() }}</dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('routes.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat semua rute →</a>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-5">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Permintaan Cuti</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ App\Models\LeaveRequest::where('status', 'pending')->count() }}</dd>
                    </dl>
                </div>
            </div>
            <div class="mt-4">
                <a href="{{ route('leave-requests.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat semua permintaan →</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Today's Schedule -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Jadwal Hari Ini</h3>
                <a href="{{ route('schedules.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat semua</a>
            </div>
            
            <div class="p-6">
                @php
                    $today = now()->format('Y-m-d');
                    $schedules = App\Models\Schedule::whereDate('schedule_date', $today)
                        ->with(['driver', 'unit', 'route'])
                        ->take(5)
                        ->get();
                @endphp
                
                @if($schedules->count() > 0)
                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengemudi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bus</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rute</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($schedules as $schedule)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $schedule->driver->name ?? 'Unassigned' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $schedule->unit->number ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $schedule->route->name ?? 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $schedule->start_time }} - {{ $schedule->end_time }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($schedule->status == 'completed')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Selesai
                                            </span>
                                        @elseif($schedule->status == 'in_progress')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                Sedang Berjalan
                                            </span>
                                        @elseif($schedule->status == 'cancelled')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Dibatalkan
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Terjadwal
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada jadwal hari ini</h3>
                        <p class="mt-1 text-sm text-gray-500">Tidak ada jadwal unit untuk hari ini.</p>
                        <div class="mt-6">
                            <a href="{{ route('schedules.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Buat Jadwal Baru
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Leave Requests -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Permintaan Cuti Terbaru</h3>
                <a href="{{ route('leave-requests.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Lihat semua</a>
            </div>
            
            <div class="p-6">
                @php
                    $leaveRequests = App\Models\LeaveRequest::with('driver')
                        ->orderBy('created_at', 'desc')
                        ->take(5)
                        ->get();
                @endphp
                
                @if($leaveRequests->count() > 0)
                    <div class="overflow-x-auto -mx-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengemudi</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($leaveRequests as $leaveRequest)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $leaveRequest->driver->name ?? 'Unknown' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $leaveRequest->start_date }} to {{ $leaveRequest->end_date }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ \Illuminate\Support\Str::limit($leaveRequest->reason, 30) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($leaveRequest->status == 'approved')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Disetujui
                                            </span>
                                        @elseif($leaveRequest->status == 'rejected')
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Ditolak
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Menunggu
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada permintaan cuti</h3>
                        <p class="mt-1 text-sm text-gray-500">Tidak ada permintaan cuti untuk ditampilkan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Aksi Cepat</h3>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('schedules.create') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Jadwal Baru
                </a>
                
                <a href="{{ route('drivers.create') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                    Tambah Pengemudi
                </a>
                
                <a href="{{ route('units.create') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tambah Bus
                </a>
                
                <a href="{{ route('reports.index') }}" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gray-700 hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Buat Laporan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection 
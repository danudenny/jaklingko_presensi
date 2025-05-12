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
            </div>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                    <i class="fas fa-users text-white"></i>
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
                    <i class="fas fa-car text-white"></i>
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
                    <i class="fas fa-route text-white"></i>
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
                    <i class="fas fa-calendar-alt text-white"></i>
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

    <!-- Expiring Documents Section -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden mt-6">
        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Dokumen Unit yang Akan Kedaluwarsa</h3>
        </div>

        <div class="p-6">
            @php
                $threeMonthsFromNow = now()->addMonths(3);

                // Get units with expiring STNK
                $expiringStnkUnits = App\Models\Unit::where('status', 'aktif')
                    ->whereNotNull('expired_stnk')
                    ->whereDate('expired_stnk', '<=', $threeMonthsFromNow)
                    ->whereDate('expired_stnk', '>=', now())
                    ->orderBy('expired_stnk')
                    ->get();

                // Get units with expiring KIR
                $expiringKirUnits = App\Models\Unit::where('status', 'aktif')
                    ->whereNotNull('expired_kir')
                    ->whereDate('expired_kir', '<=', $threeMonthsFromNow)
                    ->whereDate('expired_kir', '>=', now())
                    ->orderBy('expired_kir')
                    ->get();

                // Get units with expiring KP
                $expiringKpUnits = App\Models\Unit::where('status', 'aktif')
                    ->whereNotNull('expired_kp')
                    ->whereDate('expired_kp', '<=', $threeMonthsFromNow)
                    ->whereDate('expired_kp', '>=', now())
                    ->orderBy('expired_kp')
                    ->get();

                // Get units with already expired documents
                $expiredUnits = App\Models\Unit::where('status', 'aktif')
                    ->where(function($query) {
                        $query->whereDate('expired_stnk', '<', now())
                            ->orWhereDate('expired_kir', '<', now())
                            ->orWhereDate('expired_kp', '<', now());
                    })
                    ->get();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- STNK Card -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="px-4 py-3 bg-blue-50 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-md font-medium text-blue-800">STNK</h4>
                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                            {{ $expiringStnkUnits->count() }}
                        </span>
                    </div>
                    <div class="p-4">
                        @if($expiringStnkUnits->count() > 0)
                            <ul class="divide-y divide-gray-200">
                                @foreach($expiringStnkUnits as $unit)
                                    @php
                                        $daysUntilExpiry = (int)now()->diffInDays($unit->expired_stnk, false);
                                        $monthsUntilExpiry = (int)floor($daysUntilExpiry / 30);
                                        $remainingDays = (int)($daysUntilExpiry % 30);
                                    @endphp
                                    <li class="py-2">
                                        <div class="flex justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">{{ $unit->unit_number }}</span>
                                                <span class="text-sm text-gray-500 ml-2">{{ $unit->plate_number }}</span>
                                            </div>
                                            <div>
                                                @if($daysUntilExpiry <= 30)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        {{ $daysUntilExpiry }} hari
                                                    </span>
                                                @elseif($monthsUntilExpiry >= 1)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        {{ $monthsUntilExpiry }} bulan {{ $remainingDays }} hari
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        {{ $daysUntilExpiry }} hari
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500">Tidak ada STNK yang akan kedaluwarsa</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- KIR Card -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="px-4 py-3 bg-green-50 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-md font-medium text-green-800">KIR</h4>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                            {{ $expiringKirUnits->count() }}
                        </span>
                    </div>
                    <div class="p-4">
                        @if($expiringKirUnits->count() > 0)
                            <ul class="divide-y divide-gray-200">
                                @foreach($expiringKirUnits as $unit)
                                    @php
                                        $daysUntilExpiry = (int)now()->diffInDays($unit->expired_kir, false);
                                        $monthsUntilExpiry = (int)floor($daysUntilExpiry / 30);
                                        $remainingDays = (int)($daysUntilExpiry % 30);
                                    @endphp
                                    <li class="py-2">
                                        <div class="flex justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">{{ $unit->unit_number }}</span>
                                                <span class="text-sm text-gray-500 ml-2">{{ $unit->plate_number }}</span>
                                            </div>
                                            <div>
                                                @if($daysUntilExpiry <= 30)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        {{ $daysUntilExpiry }} hari
                                                    </span>
                                                @elseif($monthsUntilExpiry >= 1)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                        {{ $monthsUntilExpiry }} bulan {{ $remainingDays }} hari
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        {{ $daysUntilExpiry }} hari
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500">Tidak ada KIR yang akan kedaluwarsa</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- KP Card -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="px-4 py-3 bg-purple-50 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-md font-medium text-purple-800">KP</h4>
                        <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded-full">
                            {{ $expiringKpUnits->count() }}
                        </span>
                    </div>
                    <div class="p-4">
                        @if($expiringKpUnits->count() > 0)
                            <ul class="divide-y divide-gray-200">
                                @foreach($expiringKpUnits as $unit)
                                    @php
                                        $daysUntilExpiry = (int)now()->diffInDays($unit->expired_kp, false);
                                        $monthsUntilExpiry = (int)floor($daysUntilExpiry / 30);
                                        $remainingDays = (int)($daysUntilExpiry % 30);
                                    @endphp
                                    <li class="py-2">
                                        <div class="flex justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">{{ $unit->unit_number }}</span>
                                                <span class="text-sm text-gray-500 ml-2">{{ $unit->plate_number }}</span>
                                            </div>
                                            <div>
                                                @if($daysUntilExpiry <= 30)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        {{ $daysUntilExpiry }} hari
                                                    </span>
                                                @elseif($monthsUntilExpiry >= 1)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                        {{ $monthsUntilExpiry }} bulan {{ $remainingDays }} hari
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        {{ $daysUntilExpiry }} hari
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500">Tidak ada KP yang akan kedaluwarsa</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Already Expired Documents -->
            @if($expiredUnits->count() > 0)
                <div class="mt-6">
                    <h4 class="text-md font-medium text-red-800 mb-3">Dokumen yang Telah Kedaluwarsa</h4>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-red-200">
                        <div class="p-4">
                            <ul class="divide-y divide-gray-200">
                                @foreach($expiredUnits as $unit)
                                    <li class="py-2">
                                        <div class="flex flex-col sm:flex-row sm:justify-between">
                                            <div class="mb-2 sm:mb-0">
                                                <span class="text-sm font-medium text-gray-900">{{ $unit->unit_number }}</span>
                                                <span class="text-sm text-gray-500 ml-2">{{ $unit->plate_number }}</span>
                                            </div>
                                            <div class="flex flex-wrap gap-2">
                                                @if($unit->expired_stnk && $unit->expired_stnk < now())
                                                    @php
                                                        $daysExpired = (int)now()->diffInDays($unit->expired_stnk);
                                                    @endphp
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        STNK: {{ $daysExpired }} hari
                                                    </span>
                                                @endif

                                                @if($unit->expired_kir && $unit->expired_kir < now())
                                                    @php
                                                        $daysExpired = (int)now()->diffInDays($unit->expired_kir);
                                                    @endphp
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        KIR: {{ $daysExpired }} hari
                                                    </span>
                                                @endif

                                                @if($unit->expired_kp && $unit->expired_kp < now())
                                                    @php
                                                        $daysExpired = (int)now()->diffInDays($unit->expired_kp);
                                                    @endphp
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                        KP: {{ $daysExpired }} hari
                                                    </span>
                                                @endif

                                                <a href="{{ route('units.edit', $unit->id) }}" class="px-2 inline-flex text-xs leading-5 font-semibold text-indigo-600 hover:text-indigo-900">
                                                    Edit
                                                </a>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif
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
                        <i class="fas fa-calendar-times mx-auto h-12 w-12 text-gray-400"></i>
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
                                        <div class="text-sm text-gray-900">{{ $leaveRequest->start_date->format('d M Y') }} <small class="text-gray-500">s/d</small> {{ $leaveRequest->end_date->format('d M Y') }} <small class="text-blue-800 bg-blue-200 px-2 py-0.5 rounded text-xs inline-block">{{ $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1 }} hari</small> </div>
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
                        <i class="fas fa-calendar-times mx-auto h-12 w-12 text-gray-400"></i>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada permintaan cuti</h3>
                        <p class="mt-1 text-sm text-gray-500">Tidak ada permintaan cuti untuk ditampilkan.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Renops Units Section -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 overflow-hidden mt-6">
        <div class="px-6 py-5 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">Unit Renops</h3>
        </div>

        <div class="p-6">
            @php
                // Get units with renops plans using the unit_renops table
                $startDate = now();
                $endDate = now()->addDays(7);

                // Get upcoming renops plans for the next 7 days
                $upcomingRenops = App\Models\UnitRenops::whereBetween('date', [$startDate, $endDate])
                    ->with('unit', 'holiday')
                    ->get()
                    ->groupBy('date');

                // Get unique units that have renops plans in the system
                $renopsUnitIds = App\Models\UnitRenops::distinct('unit_id')->pluck('unit_id');
                $renopsUnits = App\Models\Unit::where('status', 'aktif')
                    ->whereIn('id', $renopsUnitIds)
                    ->get();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Renops Units Card -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="px-4 py-3 bg-indigo-50 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-md font-medium text-indigo-800">Unit Terdaftar di Renops</h4>
                        <span class="px-2 py-1 bg-indigo-100 text-indigo-800 text-xs font-semibold rounded-full">
                            {{ $renopsUnits->count() }}
                        </span>
                    </div>
                    <div class="p-4">
                        @if($renopsUnits->count() > 0)
                            <ul class="divide-y divide-gray-200">
                                @foreach($renopsUnits->take(10) as $unit)
                                    <li class="py-2">
                                        <div class="flex justify-between">
                                            <div>
                                                <span class="text-sm font-medium text-gray-900">{{ $unit->unit_number }}</span>
                                                <span class="text-sm text-gray-500 ml-2">{{ $unit->plate_number }}</span>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if($renopsUnits->count() > 5)
                                <div class="mt-4 text-center">
                                    <a href="{{ url('/renops') }}" class="inline-flex items-center px-3 py-1.5 border border-indigo-300 text-xs font-medium rounded-md text-indigo-700 bg-indigo-50 hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                                        Lihat Semua ({{ $renopsUnits->count() }})
                                        <i class="ml-1.5 fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500">Tidak ada unit dengan status renops</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Upcoming Renops Card -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-200">
                    <div class="px-4 py-3 bg-amber-50 border-b border-gray-200 flex items-center justify-between">
                        <h4 class="text-md font-medium text-amber-800">Jadwal Renops 7 Hari Ke Depan</h4>
                        <span class="px-2 py-1 bg-amber-100 text-amber-800 text-xs font-semibold rounded-full">
                            {{ $upcomingRenops->count() }}
                        </span>
                    </div>
                    <div class="p-4">
                        @if($upcomingRenops->count() > 0)
                            <ul class="divide-y divide-gray-200">
                                @foreach($upcomingRenops->take(5) as $date => $renopsForDate)
                                    <li class="py-2">
                                        <div class="mb-1">
                                            <span class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
                                            @php
                                                $dayType = $renopsForDate->first()->day_type;
                                                $holiday = $renopsForDate->first()->holiday;
                                            @endphp
                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($dayType == 'saturday') bg-blue-100 text-blue-800
                                                @elseif($dayType == 'sunday') bg-purple-100 text-purple-800
                                                @else bg-red-100 text-red-800 @endif">
                                                @if($dayType == 'saturday')
                                                    Sabtu
                                                @elseif($dayType == 'sunday')
                                                    Minggu
                                                @else
                                                    {{ $holiday ? $holiday->name : 'Libur' }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($renopsForDate->take(8) as $renops)
                                                @if($renops->unit)
                                                    <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">
                                                        {{ $renops->unit->unit_number }}
                                                    </span>
                                                @endif
                                            @endforeach
                                            @if($renopsForDate->count() > 8)
                                                <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs font-medium rounded">
                                                    +{{ $renopsForDate->count() - 8 }} lainnya
                                                </span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                            @if($upcomingRenops->count() > 5)
                                <div class="mt-4 text-center">
                                    <a href="{{ url('/renops') }}" class="inline-flex items-center px-3 py-1.5 border border-amber-300 text-xs font-medium rounded-md text-amber-700 bg-amber-50 hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors">
                                        Lihat Semua Jadwal
                                        <i class="ml-1.5 fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-4">
                                <p class="text-sm text-gray-500">Tidak ada jadwal renops dalam 7 hari ke depan</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>


</div>
@endsection

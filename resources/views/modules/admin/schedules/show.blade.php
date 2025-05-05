@extends('modules.admin.layouts.main')

@section('title', 'Detail Jadwal')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Detail Jadwal</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.edit', $schedule) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                <i class="fas fa-edit mr-2"></i>
                Edit Jadwal
            </a>
            <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jadwal ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-trash mr-2"></i>
                    Hapus Jadwal
                </button>
            </form>
        </x-slot>
    </x-page-title>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Schedule Information -->
        <x-card>
            <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Jadwal</h2>
            
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d F Y') }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Shift</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full {{ $schedule->shift == 'pagi' || $schedule->shift == 'morning' ? 'bg-yellow-100 text-yellow-800' : 'bg-indigo-100 text-indigo-800' }}">
                                <i class="fa-solid fa-clock mr-2"></i>
                                {{ $schedule->shift == 'pagi' || $schedule->shift == 'morning' ? 'Pagi' : 'Siang' }}
                            </span>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full {{ 
                                ($schedule->status == 'scheduled' ? 'bg-green-100 text-green-800' : 
                                ($schedule->status == 'unavailable' ? 'bg-red-100 text-red-800' : 
                                ($schedule->status == 'on_leave' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'))) }}">
                                <i class="fa-solid fa-circle mr-2"></i>
                                {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                            </span>
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->notes ?? 'Tidak ada catatan' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </x-card>

        <!-- Driver Information -->
        <x-card>
            <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Pengemudi</h2>
            
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Pengemudi</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium">{{ substr($schedule->driver->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $schedule->driver->name }}</div>
                                    <div class="text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $schedule->driver->type == 'batangan' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ $schedule->driver->type == 'batangan' ? 'Batangan' : 'Cadangan' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </dd>
                    </div>
                    
                    @if($schedule->backup_driver_id)
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Pengemudi Backup</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-yellow-500 flex items-center justify-center">
                                    <span class="text-white font-medium">{{ substr($schedule->backupDriver->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $schedule->backupDriver->name }}</div>
                                    <div class="text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $schedule->backupDriver->type == 'batangan' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ $schedule->backupDriver->type == 'batangan' ? 'Batangan' : 'Cadangan' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </dd>
                    </div>
                    @endif
                    
                    <div class="bg-{{ $schedule->backup_driver_id ? 'gray-50' : 'white' }} px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">ID Karyawan</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->driver->employee_id ?? 'Tidak tersedia' }}
                        </dd>
                    </div>
                    
                    <div class="bg-{{ $schedule->backup_driver_id ? 'white' : 'gray-50' }} px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Kontak</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <div class="mb-1">
                                <i class="fas fa-phone text-gray-500 mr-2"></i>
                                {{ $schedule->driver->phone ?? 'Tidak tersedia' }}
                            </div>
                            <div>
                                <i class="fas fa-envelope text-gray-500 mr-2"></i>
                                {{ $schedule->driver->email ?? 'Tidak tersedia' }}
                            </div>
                        </dd>
                    </div>
                </dl>
            </div>
        </x-card>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Route Information -->
        <x-card>
            <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Rute</h2>
            
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nomor Rute</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->route->route_number }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nama Rute</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->route->name }}
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Deskripsi</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->route->description ?? 'Tidak ada deskripsi' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </x-card>

        <!-- Unit Information -->
        <x-card>
            <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Unit</h2>
            
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Nomor Unit</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->unit->unit_number }}
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Plat Nomor</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <div class="inline-block bg-yellow-300 border border-black rounded-lg p-1 px-2 flex justify-center items-center max-w-xs shadow-md">
                                <div class="text-black font-bold text-xs tracking-wider">
                                    @php
                                        $plateNumber = $schedule->unit->plate_number;
                                        $formattedPlate = substr($plateNumber, 0, 1) . ' ' . substr($plateNumber, 1, 4) . ' ' . substr($plateNumber, 5);
                                    @endphp
                                    {{ $formattedPlate }}
                                </div>
                            </div>
                        </dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Kapasitas</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $schedule->unit->capacity ?? 'Tidak tersedia' }} Penumpang
                        </dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full {{ $schedule->unit->status == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                <i class="fa-solid fa-circle mr-2"></i>
                                {{ ucfirst($schedule->unit->status) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </x-card>
    </div>

    <div class="mt-6 flex justify-between">
        <a href="{{ route('schedules.index', ['start_date' => $schedule->schedule_date, 'end_date' => $schedule->schedule_date]) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-arrow-left mr-2"></i>
            Kembali
        </a>
        
        @if($schedule->status == 'scheduled')
        <a href="{{ route('schedules.unavailable', $schedule) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-500 active:bg-yellow-700 focus:outline-none focus:border-yellow-700 focus:ring ring-yellow-300 disabled:opacity-25 transition ease-in-out duration-150">
            <i class="fas fa-ban mr-2"></i>
            Tandai Tidak Tersedia
        </a>
        @endif
    </div>
</div>
@endsection
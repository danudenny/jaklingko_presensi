@extends('modules.admin.layouts.main')

@section('title', 'Edit Jadwal')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Edit Jadwal</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.show', $schedule) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-eye mr-2"></i>
                Lihat Detail
            </a>
        </x-slot>
    </x-page-title>

    <x-card>
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                <p class="font-bold">Terjadi kesalahan:</p>
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('schedules.update', $schedule) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Schedule Information -->
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Jadwal</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="schedule_date" class="block text-sm font-medium text-gray-700">Tanggal Jadwal</label>
                            <input type="date" name="schedule_date" id="schedule_date" value="{{ old('schedule_date', $schedule->schedule_date) }}" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                        
                        <div>
                            <label for="shift" class="block text-sm font-medium text-gray-700">Shift</label>
                            <select name="shift" id="shift" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="morning" {{ (old('shift', $schedule->shift) == 'morning' || old('shift', $schedule->shift) == 'pagi') ? 'selected' : '' }}>Pagi</option>
                                <option value="evening" {{ (old('shift', $schedule->shift) == 'evening' || old('shift', $schedule->shift) == 'siang') ? 'selected' : '' }}>Siang</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" onchange="toggleBackupDriverSection()">
                                <option value="scheduled" {{ old('status', $schedule->status) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                <option value="completed" {{ old('status', $schedule->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="unavailable" {{ old('status', $schedule->status) == 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                                <option value="on_leave" {{ old('status', $schedule->status) == 'on_leave' ? 'selected' : '' }}>On Leave</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">Catatan</label>
                            <textarea name="notes" id="notes" rows="3" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">{{ old('notes', $schedule->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Driver and Route Information -->
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Pengemudi & Rute</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="driver_id" class="block text-sm font-medium text-gray-700">Pengemudi</label>
                            <select name="driver_id" id="driver_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" {{ old('driver_id', $schedule->driver_id) == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }} ({{ ucfirst($driver->type) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div id="backup_driver_section" class="{{ old('status', $schedule->status) == 'unavailable' ? '' : 'hidden' }}">
                            <label for="backup_driver_id" class="block text-sm font-medium text-gray-700">Pengemudi Backup</label>
                            <select name="backup_driver_id" id="backup_driver_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                <option value="">-- Pilih Pengemudi Backup --</option>
                                @foreach ($backupDrivers as $driver)
                                    <option value="{{ $driver->id }}" {{ old('backup_driver_id', $schedule->backup_driver_id) == $driver->id ? 'selected' : '' }}>
                                        {{ $driver->name }} ({{ ucfirst($driver->type) }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Hanya menampilkan pengemudi yang tersedia pada tanggal dan shift ini.</p>
                        </div>
                        
                        <div>
                            <label for="route_id" class="block text-sm font-medium text-gray-700">Rute</label>
                            <select name="route_id" id="route_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @foreach ($routes as $route)
                                    <option value="{{ $route->id }}" {{ old('route_id', $schedule->route_id) == $route->id ? 'selected' : '' }}>
                                        {{ $route->route_number }} - {{ $route->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="unit_id" class="block text-sm font-medium text-gray-700">Unit</label>
                            <select name="unit_id" id="unit_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @foreach ($units as $unit)
                                    <option value="{{ $unit->id }}" {{ old('unit_id', $schedule->unit_id) == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->unit_number }} - {{ $unit->plate_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-between pt-5">
                <a href="{{ route('schedules.index', ['start_date' => $schedule->schedule_date, 'end_date' => $schedule->schedule_date]) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-save mr-2"></i>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
    function toggleBackupDriverSection() {
        const status = document.getElementById('status').value;
        const backupDriverSection = document.getElementById('backup_driver_section');
        
        if (status === 'unavailable') {
            backupDriverSection.classList.remove('hidden');
        } else {
            backupDriverSection.classList.add('hidden');
        }
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleBackupDriverSection();
    });
</script>
@endpush
@extends('modules.admin.layouts.main')

@section('title', 'Pengaturan Jadwal Pengemudi')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Jadwal Pengemudi</h1>
        <a href="{{ route('drivers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Pengaturan Ambang Batas Jadwal Pengemudi</h2>
            <p class="text-sm text-gray-600 mb-4">
                Pengaturan ini menentukan jumlah minimum dan maksimum jadwal yang harus dipenuhi oleh setiap pengemudi dalam satu periode.
                Hal ini memastikan semua pengemudi mendapatkan kesempatan yang sama dalam penjadwalan.
            </p>
            
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Informasi Pengemudi:</strong>
                            Total pengemudi aktif: <strong>{{ $driverCounts['total'] }}</strong> pengemudi
                            ({{ $driverCounts['batangan'] }} batangan, {{ $driverCounts['cadangan'] }} cadangan)
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('driver.schedule.settings.update') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                @foreach($settings as $setting)
                <div class="border rounded-lg p-4 {{ $setting->driver_type === 'batangan' ? 'bg-indigo-50' : 'bg-amber-50' }}">
                    <input type="hidden" name="settings[{{ $loop->index }}][id]" value="{{ $setting->id }}">
                    
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold mb-2">
                            Pengemudi {{ ucfirst($setting->driver_type) }}
                            <span class="text-sm font-normal text-gray-500">
                                ({{ $driverCounts[$setting->driver_type] ?? 0 }} pengemudi)
                            </span>
                        </h3>
                        <p class="text-sm text-gray-600">
                            Pengaturan untuk pengemudi dengan tipe {{ $setting->driver_type }}.
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="min-{{ $setting->driver_type }}" class="block text-sm font-medium text-gray-700 mb-1">Minimum Jadwal</label>
                            <input type="number" name="settings[{{ $loop->index }}][min_schedules]" id="min-{{ $setting->driver_type }}" 
                                value="{{ $setting->min_schedules }}" min="1" max="30" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Jumlah minimum jadwal per periode</p>
                        </div>
                        
                        <div>
                            <label for="max-{{ $setting->driver_type }}" class="block text-sm font-medium text-gray-700 mb-1">Maksimum Jadwal</label>
                            <input type="number" name="settings[{{ $loop->index }}][max_schedules]" id="max-{{ $setting->driver_type }}" 
                                value="{{ $setting->max_schedules }}" min="1" max="30" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Jumlah maksimum jadwal per periode</p>
                        </div>
                        
                        <div>
                            <label for="period-{{ $setting->driver_type }}" class="block text-sm font-medium text-gray-700 mb-1">Hari Per Periode</label>
                            <input type="number" name="settings[{{ $loop->index }}][period_days]" id="period-{{ $setting->driver_type }}" 
                                value="{{ $setting->period_days }}" min="1" max="31" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="mt-1 text-xs text-gray-500">Jumlah hari dalam satu periode</p>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes-{{ $setting->driver_type }}" class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea name="settings[{{ $loop->index }}][notes]" id="notes-{{ $setting->driver_type }}" rows="2" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $setting->notes }}</textarea>
                        <p class="mt-1 text-xs text-gray-500">Catatan tambahan (opsional)</p>
                    </div>
                </div>
                @endforeach
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Informasi Penggunaan</h2>
        
        <div class="space-y-4">
            <div>
                <h3 class="font-medium text-gray-800">Bagaimana pengaturan ini digunakan?</h3>
                <p class="text-sm text-gray-600">
                    Pengaturan ini digunakan saat membuat jadwal untuk memastikan setiap pengemudi mendapatkan jumlah jadwal yang adil 
                    dalam satu periode. Sistem akan mencoba memenuhi jadwal minimum untuk semua pengemudi dan tidak akan melebihi batas maksimum.
                </p>
            </div>
            
            <div>
                <h3 class="font-medium text-gray-800">Apa itu periode jadwal?</h3>
                <p class="text-sm text-gray-600">
                    Periode jadwal adalah rentang waktu (dalam hari) di mana target jadwal minimum dan maksimum berlaku. 
                    Secara default, satu periode adalah 15 hari.
                </p>
            </div>
            
            <div>
                <h3 class="font-medium text-gray-800">Perbedaan antara pengemudi batangan dan cadangan</h3>
                <p class="text-sm text-gray-600">
                    Pengemudi batangan biasanya memiliki target jadwal yang lebih tinggi dibandingkan dengan pengemudi cadangan.
                    Ini karena pengemudi batangan adalah pengemudi tetap yang diprioritaskan dalam penjadwalan.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure max is always greater than or equal to min
        const minInputs = document.querySelectorAll('input[name$="[min_schedules]"]');
        const maxInputs = document.querySelectorAll('input[name$="[max_schedules]"]');
        
        minInputs.forEach((minInput, index) => {
            const maxInput = maxInputs[index];
            
            minInput.addEventListener('change', function() {
                if (parseInt(minInput.value) > parseInt(maxInput.value)) {
                    maxInput.value = minInput.value;
                }
            });
            
            maxInput.addEventListener('change', function() {
                if (parseInt(maxInput.value) < parseInt(minInput.value)) {
                    minInput.value = maxInput.value;
                }
            });
        });
    });
</script>
@endpush

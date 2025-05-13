@extends('modules.admin.layouts.main')

@section('title', 'Pengaturan Rencana Operasi Unit')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Pengaturan Rencana Operasi Unit</h1>
        <a href="{{ route('renops.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="{{ route('renops.settings.update') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Mode Rencana Operasi</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <input type="radio" id="mode-manual" name="mode" value="manual" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ $settings->mode === 'manual' ? 'checked' : '' }}>
                        <label for="mode-manual" class="ml-2 block text-sm text-gray-700">Manual</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="mode-automatic" name="mode" value="automatic" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ $settings->mode === 'automatic' ? 'checked' : '' }}>
                        <label for="mode-automatic" class="ml-2 block text-sm text-gray-700">Otomatis</label>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    <span class="font-medium">Manual:</span> Anda dapat memilih unit secara manual untuk rencana operasi.<br>
                    <span class="font-medium">Otomatis:</span> Sistem akan memilih unit secara otomatis berdasarkan ambang batas yang ditentukan.
                </p>
            </div>
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Tipe Unit</h2>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center">
                        <input type="radio" id="unit-type-all" name="unit_type" value="all" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ $settings->unit_type === 'all' || !isset($settings->unit_type) ? 'checked' : '' }}>
                        <label for="unit-type-all" class="ml-2 block text-sm text-gray-700">Semua Unit (Pool dan Non-Pool)</label>
                    </div>
                    <div class="flex items-center">
                        <input type="radio" id="unit-type-pool" name="unit_type" value="pool" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ $settings->unit_type === 'pool' ? 'checked' : '' }}>
                        <label for="unit-type-pool" class="ml-2 block text-sm text-gray-700">Hanya Pool</label>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">
                    <span class="font-medium">Semua Unit:</span> Sistem akan menggunakan semua unit (pool dan non-pool) untuk rencana operasi.<br>
                    <span class="font-medium">Hanya Pool:</span> Sistem hanya akan menggunakan unit pool untuk rencana operasi otomatis.
                </p>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Ambang Batas Unit</h2>
                <p class="text-sm text-gray-500 mb-4">Total unit aktif saat ini: <span class="font-medium">{{ $totalUnits }}</span> unit</p>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="saturday-threshold" class="block text-sm font-medium text-gray-700 mb-1">Hari Sabtu (%)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" name="saturday_threshold" id="saturday-threshold" min="1" max="100" step="0.01" value="{{ $settings->saturday_threshold }}" 
                                class="block w-full rounded-md border-gray-300 pl-3 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="80">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Jumlah unit: <span id="saturday-count">{{ ceil($totalUnits * $settings->saturday_threshold / 100) }}</span> unit
                        </p>
                    </div>
                    
                    <div>
                        <label for="sunday-threshold" class="block text-sm font-medium text-gray-700 mb-1">Hari Minggu (%)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" name="sunday_threshold" id="sunday-threshold" min="1" max="100" step="0.01" value="{{ $settings->sunday_threshold }}" 
                                class="block w-full rounded-md border-gray-300 pl-3 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="70">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Jumlah unit: <span id="sunday-count">{{ ceil($totalUnits * $settings->sunday_threshold / 100) }}</span> unit
                        </p>
                    </div>
                    
                    <div>
                        <label for="holiday-threshold" class="block text-sm font-medium text-gray-700 mb-1">Hari Libur (%)</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="number" name="holiday_threshold" id="holiday-threshold" min="1" max="100" step="0.01" value="{{ $settings->holiday_threshold }}" 
                                class="block w-full rounded-md border-gray-300 pl-3 pr-12 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="70">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="text-gray-500 sm:text-sm">%</span>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            Jumlah unit: <span id="holiday-count">{{ ceil($totalUnits * $settings->holiday_threshold / 100) }}</span> unit
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea id="notes" name="notes" rows="3" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $settings->notes }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Catatan tambahan untuk pengaturan rencana operasi (opsional)</p>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const totalUnits = {{ $totalUnits }};
        const poolUnits = {{ App\Models\Unit::active()->where('is_pool', true)->count() }};
        const nonPoolUnits = {{ App\Models\Unit::active()->where('is_pool', false)->count() }};
        
        const saturdayThreshold = document.getElementById('saturday-threshold');
        const sundayThreshold = document.getElementById('sunday-threshold');
        const holidayThreshold = document.getElementById('holiday-threshold');
        
        const saturdayCount = document.getElementById('saturday-count');
        const sundayCount = document.getElementById('sunday-count');
        const holidayCount = document.getElementById('holiday-count');
        
        const unitTypeAll = document.getElementById('unit-type-all');
        const unitTypePool = document.getElementById('unit-type-pool');
        
        // Function to update all unit counts
        function updateUnitCounts() {
            const currentTotal = unitTypeAll.checked ? totalUnits : poolUnits;
            saturdayCount.textContent = Math.ceil(currentTotal * saturdayThreshold.value / 100);
            sundayCount.textContent = Math.ceil(currentTotal * sundayThreshold.value / 100);
            holidayCount.textContent = Math.ceil(currentTotal * holidayThreshold.value / 100);
        }
        
        // Update unit counts when thresholds change
        saturdayThreshold.addEventListener('input', updateUnitCounts);
        sundayThreshold.addEventListener('input', updateUnitCounts);
        holidayThreshold.addEventListener('input', updateUnitCounts);
        
        // Update unit counts when unit type changes
        unitTypeAll.addEventListener('change', updateUnitCounts);
        unitTypePool.addEventListener('change', updateUnitCounts);
    });
</script>
@endpush

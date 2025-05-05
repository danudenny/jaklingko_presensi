@extends('modules.admin.layouts.main')

@section('title', 'Tambah Pengajuan Cuti')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <style>
        .days-count {
            display: inline-flex;
            align-items: center;
            background-color: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 0.25rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4F46E5;
        }
        
        .flatpickr-calendar.inline {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            border-radius: 0.5rem;
            border: 1px solid #E5E7EB;
            margin-top: 0.5rem;
        }
        
        .date-preview {
            display: flex;
            align-items: center;
            margin-top: 0.5rem;
            padding: 0.5rem;
            background-color: #F9FAFB;
            border-radius: 0.375rem;
            border: 1px solid #E5E7EB;
        }
        
        .date-picker-container {
            position: relative;
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto py-6">
    <x-page-title>
        <x-slot name="title">Tambah Pengajuan Cuti</x-slot>
        <x-slot name="actions">
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('leave-requests.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Driver -->
                <div>
                    <label for="driver_id" class="block text-sm font-medium text-gray-700">Pengemudi</label>
                    <select id="driver_id" name="driver_id" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('driver_id') border-red-500 @enderror">
                        <option value="">Pilih Pengemudi</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id') == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }} ({{ ucfirst($driver->type) }})
                            </option>
                        @endforeach
                    </select>
                    @error('driver_id')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Type -->
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">Tipe Cuti</label>
                    <select id="type" name="type" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('type') border-red-500 @enderror">
                        <option value="">Pilih Tipe Cuti</option>
                        <option value="planned" {{ old('type') == 'planned' ? 'selected' : '' }}>Terencana</option>
                        <option value="sick" {{ old('type') == 'sick' ? 'selected' : '' }}>Sakit</option>
                        <option value="emergency" {{ old('type') == 'emergency' ? 'selected' : '' }}>Darurat</option>
                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                    @error('type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Date Range with Inline Calendar -->
                <div class="md:col-span-2">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="date-picker-container">
                            <label for="date-range" class="block text-sm font-medium text-gray-700">Periode Cuti</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <input type="text" id="date-range" name="date_range" placeholder="Pilih tanggal"
                                    class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 py-2 sm:text-sm border-gray-300 rounded-md @error('start_date') border-red-500 @enderror @error('end_date') border-red-500 @enderror"
                                    value="{{ old('start_date') && old('end_date') ? old('start_date') . ' to ' . old('end_date') : '' }}">
                                <input type="hidden" id="start_date" name="start_date" value="{{ old('start_date') }}">
                                <input type="hidden" id="end_date" name="end_date" value="{{ old('end_date') }}">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-calendar text-gray-400"></i>
                                </div>
                            </div>
                            
                            <div class="date-preview mt-2">
                                <div>
                                    <span class="text-sm text-gray-500">Dari:</span>
                                    <span id="start-date-display" class="ml-1 text-sm font-medium">-</span>
                                </div>
                                <div class="mx-2 text-gray-400">→</div>
                                <div>
                                    <span class="text-sm text-gray-500">Sampai:</span>
                                    <span id="end-date-display" class="ml-1 text-sm font-medium">-</span>
                                </div>
                                <div class="ml-auto">
                                    <span class="days-count">
                                        <i class="fas fa-calendar-day mr-1"></i>
                                        <span id="days-count">0</span> hari
                                    </span>
                                </div>
                            </div>
                            
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        
                        <!-- Documentation Image Upload -->
                        <div>
                            <label for="documentation" class="block text-sm font-medium text-gray-700">Dokumentasi (Opsional)</label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                <div class="space-y-1 text-center">
                                    <div id="preview-container" class="hidden mb-3">
                                        <img id="image-preview" class="mx-auto h-32 object-cover rounded" src="#" alt="Preview">
                                    </div>
                                    <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="flex text-sm text-gray-600">
                                        <label for="documentation" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                            <span>Upload file</span>
                                            <input id="documentation" name="documentation" type="file" class="sr-only" accept="image/*">
                                        </label>
                                        <p class="pl-1">atau drag and drop</p>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        PNG, JPG, GIF up to 2MB
                                    </p>
                                </div>
                            </div>
                            @error('documentation')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Reason -->
                <div class="md:col-span-2">
                    <label for="reason" class="block text-sm font-medium text-gray-700">Alasan Cuti</label>
                    <textarea id="reason" name="reason" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md @error('reason') border-red-500 @enderror">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Batal
                </a>
                <button type="submit" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-save mr-2"></i>
                    Simpan
                </button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize flatpickr for date range
        const dateRangePicker = flatpickr("#date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            minDate: "today",
            allowInput: false,
            disableMobile: true,
            inline: true,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0];
                    const endDate = selectedDates[1];
                    
                    // Update hidden inputs
                    document.getElementById('start_date').value = formatDate(startDate);
                    document.getElementById('end_date').value = formatDate(endDate);
                    
                    // Update display
                    document.getElementById('start-date-display').textContent = formatDisplayDate(startDate);
                    document.getElementById('end-date-display').textContent = formatDisplayDate(endDate);
                    
                    // Calculate days
                    const days = calculateDays(startDate, endDate);
                    document.getElementById('days-count').textContent = days;
                }
            }
        });
        
        // Initialize with any existing values
        const startDateValue = document.getElementById('start_date').value;
        const endDateValue = document.getElementById('end_date').value;
        
        if (startDateValue && endDateValue) {
            const startDate = new Date(startDateValue);
            const endDate = new Date(endDateValue);
            
            dateRangePicker.setDate([startDate, endDate]);
            
            document.getElementById('start-date-display').textContent = formatDisplayDate(startDate);
            document.getElementById('end-date-display').textContent = formatDisplayDate(endDate);
            
            const days = calculateDays(startDate, endDate);
            document.getElementById('days-count').textContent = days;
        }
        
        // Image preview
        const documentationInput = document.getElementById('documentation');
        const imagePreview = document.getElementById('image-preview');
        const previewContainer = document.getElementById('preview-container');
        
        documentationInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
        
        // Helper functions
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        function formatDisplayDate(date) {
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return date.toLocaleDateString('id-ID', options);
        }
        
        function calculateDays(startDate, endDate) {
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
            return diffDays;
        }
    });
</script>
@endpush
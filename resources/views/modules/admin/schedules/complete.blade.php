@extends('modules.admin.layouts.main')

@section('title', 'Lengkapi Jadwal yang Tidak Lengkap')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
@endpush

@section('content')
<div class="container py-6 mx-auto">
    <x-page-title>
        <x-slot name="title">Lengkapi Jadwal yang Tidak Lengkap</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                <i class="mr-2 fas fa-arrow-left"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-card>
        <div class="mb-6">
            <!-- Info Box -->
            <div class="p-4 mb-6 border border-blue-200 rounded-md bg-blue-50">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="text-blue-400 fas fa-info-circle"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Schedule Completion Service
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Tool ini akan mencari hari-hari yang hanya memiliki 1 shift dan melengkapinya menjadi 2 shift (pagi & siang).</p>
                            <p class="mt-1">Gunakan ini jika ada jadwal yang dibuat sebelum implementasi two-pass approach.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <form action="{{ route('schedules.complete') }}" method="POST" id="complete-form">
                @csrf
                <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
                    <!-- Route Selection -->
                    <div>
                        <label for="route_id" class="block mb-1 text-sm font-medium text-gray-700">Rute</label>
                        <select id="route_id" name="route_id" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md @error('route_id') border-red-500 @enderror">
                            <option value="">Pilih Rute</option>
                            @foreach(\App\Models\Route::active()->orderBy('route_number')->get() as $route)
                                <option value="{{ $route->id }}">{{ $route->route_number }} - {{ $route->name }}</option>
                            @endforeach
                        </select>
                        @error('route_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit Selection -->
                    <div>
                        <label for="unit_id" class="block mb-1 text-sm font-medium text-gray-700">
                            Unit 
                            <span class="text-xs font-normal text-gray-500">(Opsional)</span>
                        </label>
                        <select id="unit_id" name="unit_id" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md @error('unit_id') border-red-500 @enderror">
                            <option value="">Semua Unit dalam Rute</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Pilih unit spesifik atau biarkan kosong untuk melengkapi semua unit dalam rute
                        </p>
                        @error('unit_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date Range -->
                    <div class="md:col-span-2">
                        <label for="date-range" class="block mb-1 text-sm font-medium text-gray-700">Periode Jadwal</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="text" id="date-range" name="date_range" placeholder="Pilih tanggal"
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 py-2 sm:text-sm border-gray-300 rounded-md @error('start_date') @enderror @error('end_date') @enderror"
                                value="{{ old('start_date') && old('end_date') ? old('start_date') . ' to ' . old('end_date') : '' }}">
                            <input type="hidden" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}">
                            <input type="hidden" id="end_date" name="end_date" value="{{ old('end_date', now()->addDays(7)->format('Y-m-d')) }}">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <i class="text-gray-400 fas fa-calendar"></i>
                            </div>
                        </div>
                        
                        <div class="mt-2 date-preview">
                            <div>
                                <span class="text-sm text-gray-500">Dari:</span>
                                <span id="start-date-display" class="ml-1 text-sm font-medium">{{ now()->format('d M Y') }}</span>
                            </div>
                            <div class="mx-2 text-gray-400">→</div>
                            <div>
                                <span class="text-sm text-gray-500">Sampai:</span>
                                <span id="end-date-display" class="ml-1 text-sm font-medium">{{ now()->addDays(7)->format('d M Y') }}</span>
                            </div>
                            <div class="ml-auto">
                                <span class="days-count">
                                    <i class="mr-1 fas fa-calendar-day"></i>
                                    <span id="days-count">8</span> hari
                                </span>
                            </div>
                        </div>

                        @error('start_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('end_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end mt-6">
                    <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="mr-2 fas fa-check-double" id="button-icon"></i>
                        <span id="button-text">Lengkapi Jadwal</span>
                        <span id="loading-spinner" class="hidden ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </x-card>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

<script>
    $(document).ready(function() {
        // Format date to YYYY-MM-DD for hidden inputs
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Format date for display
        function formatDisplayDate(date) {
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            return date.toLocaleDateString('id-ID', options);
        }
        
        // Calculate days between two dates
        function calculateDays(startDate, endDate) {
            const diffTime = Math.abs(endDate - startDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            return diffDays;
        }
        
        // Initialize Flatpickr
        const fp = flatpickr("#date-range", {
            locale: "id",
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [$("#start_date").val(), $("#end_date").val()],
            inline: true,
            minDate: "2024-01-01", // Allow past dates for completion
            maxRange: 30,
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0];
                    const endDate = selectedDates[1];
                    
                    // Update hidden inputs
                    $("#start_date").val(formatDate(startDate));
                    $("#end_date").val(formatDate(endDate));
                    
                    // Update display
                    $("#start-date-display").text(formatDisplayDate(startDate));
                    $("#end-date-display").text(formatDisplayDate(endDate));
                    
                    // Calculate days
                    const days = calculateDays(startDate, endDate);
                    $("#days-count").text(days);
                }
            }
        });

        // Form submission handler
        $('#complete-form').on('submit', function(e) {
            const routeId = $('#route_id').val();
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (!routeId) {
                toastr.error('Silakan pilih rute terlebih dahulu.', 'Error');
                e.preventDefault();
                return false;
            }
            
            if (!startDate || !endDate) {
                toastr.error('Silakan pilih tanggal mulai dan tanggal akhir terlebih dahulu.', 'Error');
                e.preventDefault();
                return false;
            }

            // Show loading state
            $('#button-icon').addClass('hidden');
            $('#button-text').text('Memproses...');
            $('#loading-spinner').removeClass('hidden');
            $('#submit-button').attr('disabled', true).addClass('opacity-75');
            
            toastr.info('Memproses pelengkapan jadwal...', 'Memproses');
            
            return true;
        });

        // Load units when route changes
        $('#route_id').on('change', function() {
            const routeId = $(this).val();
            const unitSelect = $('#unit_id');
            
            unitSelect.html('<option value="">Semua Unit dalam Rute</option>');
            
            if (!routeId) return;
            
            fetch(`/drivers/get-units-for-route?route_id=${routeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.data.forEach(unit => {
                            const option = new Option(`${unit.unit_number} - ${unit.plate_number}`, unit.id);
                            unitSelect.append(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading units:', error);
                    toastr.error('Error loading units for the selected route');
                });
        });
    });
</script>
@endpush

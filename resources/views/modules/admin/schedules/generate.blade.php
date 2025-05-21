@extends('modules.admin.layouts.main')

@section('title', 'Pembuatan Jadwal Otomatis')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <style>
        .date-preset {
            display: inline-flex;
            align-items: center;
            background-color: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 0.25rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4F46E5;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .date-preset:hover {
            background-color: #DBEAFE;
            border-color: #93C5FD;
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
    </style>
@endpush

@section('content')
<div class="container mx-auto py-6">
    <x-page-title>
        <x-slot name="title">Pembuatan Jadwal Otomatis</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />
    
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Error dalam Pembuatan Jadwal
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <x-card>
        <div class="mb-6">  
            @if (session('generation_results'))
                <div class="mb-6">
                    <div class="bg-green-50 border-l-4 border-green-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400 text-xl"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    Hasil Pembuatan Jadwal
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Berhasil membuat {{ session('generation_results.created') }} jadwal.</p>
                                    @if (session('generation_results.skipped') > 0)
                                        <p class="text-yellow-700">{{ session('generation_results.skipped') }} jadwal dilewati karena tidak tersedia pengemudi.</p>
                                    @endif
                                    @if (session('generation_results.failed') > 0)
                                        <p class="text-yellow-700">Gagal membuat {{ session('generation_results.failed') }} jadwal.</p>
                                    @endif
                                    
                                    @if (session('generation_results.messages') && count(session('generation_results.messages')) > 0)
                                        <div class="mt-3">
                                            <p class="font-medium">Detail:</p>
                                            <div class="mt-1 max-h-40 overflow-y-auto bg-white p-2 rounded border border-gray-200">
                                                <ul class="list-disc pl-5 space-y-1 text-xs">
                                                    @foreach (session('generation_results.messages') as $message)
                                                        <li>{{ $message }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form action="{{ route('schedules.generate') }}" method="POST" id="generate-form">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="md:col-span-2">
                        <label for="date-range" class="block text-sm font-medium text-gray-700 mb-1">Periode Jadwal</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="text" id="date-range" name="date_range" placeholder="Pilih tanggal"
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 py-2 sm:text-sm border-gray-300 rounded-md @error('start_date') border-red-500 @enderror @error('end_date') border-red-500 @enderror"
                                value="{{ old('start_date') && old('end_date') ? old('start_date') . ' to ' . old('end_date') : '' }}">
                            <input type="hidden" id="start_date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}">
                            <input type="hidden" id="end_date" name="end_date" value="{{ old('end_date', now()->addDays(7)->format('Y-m-d')) }}">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar text-gray-400"></i>
                            </div>
                        </div>
                        
                        <div class="date-preview mt-2">
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
                                    <i class="fas fa-calendar-day mr-1"></i>
                                    <span id="days-count">8</span> hari
                                </span>
                            </div>
                        </div>
                        
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" data-days="0" class="date-preset">
                                <i class="fas fa-calendar-day mr-1"></i>
                                Hari Ini
                            </button>
                            <button type="button" data-days="7" class="date-preset">
                                <i class="fas fa-calendar-week mr-1"></i>
                                7 Hari Kedepan
                            </button>
                            <button type="button" data-days="15" class="date-preset">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                15 Hari Kedepan
                            </button>
                            <button type="button" data-days="30" class="date-preset">
                                <i class="fas fa-calendar mr-1"></i>
                                30 Hari Kedepan
                            </button>
                        </div>
                        
                        @error('start_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('end_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="rounded-md bg-yellow-50 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Perhatian
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Pembuatan jadwal otomatis akan mengikuti aturan berikut:</p>
                                <ul class="list-disc pl-5 mt-1">
                                    <li>Jadwal akan dibuat berdasarkan prioritas: pengemudi tetap (batangan) untuk unit tertentu, pengemudi tetap untuk rute, lalu pengemudi cadangan.</li>
                                    <li>Pengemudi tidak akan dijadwalkan untuk kedua shift (pagi dan siang) pada hari yang sama.</li>
                                    <li>Jadwal akan mempertimbangkan unit yang tidak beroperasi (renops).</li>
                                    <li>Jadwal akan mempertimbangkan pengemudi yang sedang cuti.</li>
                                    <li>Jadwal akan mempertimbangkan batas minimum dan maksimum hari kerja per pengemudi.</li>
                                    <li>Jadwal akan mempertimbangkan kualifikasi pengemudi untuk rute dan unit tertentu.</li>
                                </ul>
                                <p class="mt-2">Proses ini mungkin memerlukan waktu beberapa saat tergantung pada jumlah hari yang dipilih.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Buat Jadwal
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
<script>
    $(document).ready(function() {
        // Add form submission handler
        $('#generate-form').on('submit', function(e) {
            // Validate dates
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (!startDate || !endDate) {
                alert('Silakan pilih tanggal mulai dan tanggal akhir terlebih dahulu.');
                e.preventDefault();
                return false;
            }
            
            // Log form submission for debugging
            console.log('Form submitted with dates:', {
                start_date: startDate,
                end_date: endDate
            });
            
            // Allow form to submit normally - this will redirect to the date selection page
            return true;
        });
        
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
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
            return diffDays;
        }
        
        // Initialize Flatpickr with Airbnb theme
        const fp = flatpickr("#date-range", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [$("#start_date").val(), $("#end_date").val()],
            inline: true,
            minDate: "today",
            maxRange: 30, // Maximum range of 30 days
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const startDate = selectedDates[0];
                    const endDate = selectedDates[1];
                    
                    // Calculate the difference in days
                    const diffTime = Math.abs(endDate - startDate);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;  // +1 to include start day
                    
                    // If the range is more than 15 days (service limitation), adjust the end date
                    if (diffDays > 15) {
                        const maxDate = new Date(startDate);
                        maxDate.setDate(maxDate.getDate() + 14);  // +14 since we're including start date
                        instance.setDate([startDate, maxDate]);
                        alert("Maksimal rentang waktu adalah 15 hari untuk pembuatan jadwal. Tanggal akhir telah disesuaikan.");
                        return;
                    }
                    
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
        
        // Handle date preset buttons
        $('.date-preset').on('click', function() {
            const days = parseInt($(this).data('days'));
            
            const today = new Date();
            let endDate;
            
            if (days === 0) {
                // Just today
                endDate = new Date(today);
            } else {
                // Future date
                endDate = new Date(today);
                endDate.setDate(today.getDate() + days - 1); // -1 because we're including today
            }
            
            // Update the flatpickr instance
            fp.setDate([today, endDate]);
            
            // Update hidden inputs
            $("#start_date").val(formatDate(today));
            $("#end_date").val(formatDate(endDate));
            
            // Update display
            $("#start-date-display").text(formatDisplayDate(today));
            $("#end-date-display").text(formatDisplayDate(endDate));
            
            // Calculate days
            const daysCount = calculateDays(today, endDate);
            $("#days-count").text(daysCount);
        });
        
        // Initialize with any existing values
        const startDateValue = $("#start_date").val();
        const endDateValue = $("#end_date").val();
        
        if (startDateValue && endDateValue) {
            const startDate = new Date(startDateValue);
            const endDate = new Date(endDateValue);
            
            // Update display
            $("#start-date-display").text(formatDisplayDate(startDate));
            $("#end-date-display").text(formatDisplayDate(endDate));
            
            // Calculate days
            const days = calculateDays(startDate, endDate);
            $("#days-count").text(days);
        }
    });
</script>
@endpush

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

        .period-preset {
            display: inline-flex;
            align-items: center;
            background-color: #F0FDF4;
            border: 1px solid #BBFAD3;
            border-radius: 0.25rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #15803D;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .period-preset:hover {
            background-color: #DCFCE7;
            border-color: #86EFAC;
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

        .period-selector {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px dashed #D1D5DB;
            border-radius: 0.5rem;
            background-color: #F9FAFB;
        }

        .month-selector {
            max-width: 200px;
        }
    </style>
@endpush

@section('content')
<div class="container py-6 mx-auto">
    <x-page-title>
        <x-slot name="title">Pembuatan Jadwal Otomatis</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                <i class="mr-2 fas fa-arrow-left"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />
    
    @if ($errors->any())
        <div class="p-4 mb-6 border-l-4 border-red-400 bg-red-50">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="text-xl text-red-400 fas fa-exclamation-circle"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Error dalam Pembuatan Jadwal
                    </h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="pl-5 space-y-1 list-disc">
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
                    <div class="p-4 border-l-4 border-green-400 bg-green-50">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="text-xl text-green-400 fas fa-check-circle"></i>
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
                                            <div class="p-2 mt-1 overflow-y-auto bg-white border border-gray-200 rounded max-h-40">
                                                <ul class="pl-5 space-y-1 text-xs list-disc">
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
                <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="date-range" class="block mb-1 text-sm font-medium text-gray-700">Periode Jadwal</label>
                        <div class="relative rounded-md shadow-sm">
                            <input type="text" id="date-range" name="date_range" placeholder="Pilih tanggal"
                                class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-3 pr-10 py-2 sm:text-sm border-gray-300 rounded-md @error('start_date') border-red-500 @enderror @error('end_date') border-red-500 @enderror"
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
                        
                        <div class="flex flex-wrap gap-2 mt-3">
                            <button type="button" data-days="0" class="date-preset">
                                <i class="mr-1 fas fa-calendar-day"></i>
                                Hari Ini
                            </button>
                            <button type="button" data-days="7" class="date-preset">
                                <i class="mr-1 fas fa-calendar-week"></i>
                                7 Hari Kedepan
                            </button>
                            <button type="button" data-days="15" class="date-preset">
                                <i class="mr-1 fas fa-calendar-alt"></i>
                                15 Hari Kedepan
                            </button>
                            <button type="button" data-days="30" class="date-preset">
                                <i class="mr-1 fas fa-calendar"></i>
                                30 Hari Kedepan
                            </button>
                        </div>

                        <div class="period-selector">
                            <h4 class="mb-3 text-sm font-medium text-gray-700">
                                <i class="mr-1 fas fa-calendar-alt text-emerald-500"></i>
                                Jadwal per Periode
                            </h4>
                            
                            <div class="flex flex-wrap items-end gap-4">
                                <div>
                                    <label for="period-month" class="block mb-1 text-xs font-medium text-gray-500">Pilih Bulan</label>
                                    <select id="period-month" class="block w-full border-gray-300 rounded-md month-selector focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                                        @php
                                            $currentMonth = now()->month;
                                            $currentYear = now()->year;
                                        @endphp
                                        @for ($i = 0; $i < 6; $i++)
                                            @php
                                                $date = now()->addMonths($i);
                                                $monthNum = $date->format('n');
                                                $monthName = $date->format('F');
                                                $year = $date->format('Y');
                                                $value = $date->format('Y-m');
                                            @endphp
                                            <option value="{{ $value }}" {{ $i == 0 ? 'selected' : '' }}>{{ $monthName }} {{ $year }}</option>
                                        @endfor
                                    </select>
                                </div>
                                
                                <div class="flex gap-2">
                                    <button type="button" id="first-period-btn" class="period-preset">
                                        <i class="mr-1 fas fa-calendar-day"></i>
                                        Periode 1 (1-15)
                                    </button>
                                    <button type="button" id="second-period-btn" class="period-preset">
                                        <i class="mr-1 fas fa-calendar-week"></i>
                                        Periode 2 (16-akhir bulan)
                                    </button>
                                </div>
                            </div>
                            
                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                <i class="mr-1 fas fa-info-circle text-emerald-500"></i>
                                Pilih bulan dan periode untuk membuat jadwal otomatis sesuai periode tersebut
                            </div>
                        </div>

                        <div class="period-selector">
                            <span class="block mb-2 text-sm font-medium text-gray-700">Aturan Periode</span>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" data-period="mingguan" class="period-preset">
                                    <i class="mr-1 fas fa-calendar-week"></i>
                                    Mingguan
                                </button>
                                <button type="button" data-period="bulanan" class="period-preset">
                                    <i class="mr-1 fas fa-calendar-alt"></i>
                                    Bulanan
                                </button>
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

                <div class="p-4 mb-6 rounded-md bg-yellow-50">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="text-yellow-400 fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Perhatian
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Pembuatan jadwal otomatis akan mengikuti aturan berikut:</p>
                                <ul class="pl-5 mt-1 list-disc">
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
                    <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="mr-2 fas fa-calendar-plus" id="button-icon"></i>
                        <span id="button-text">Buat Jadwal</span>
                        <span id="loading-spinner" class="hidden ml-2">
                            <svg class="w-5 h-5 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
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
<script>
    $(document).ready(function() {
        // Add form submission handler
        $('#generate-form').on('submit', function(e) {
            // Validate dates
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (!startDate || !endDate) {
                toastr.error('Silakan pilih tanggal mulai dan tanggal akhir terlebih dahulu.', 'Error');
                e.preventDefault();
                return false;
            }
            
            // Calculate the difference in days
            const start = new Date(startDate);
            const end = new Date(endDate);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            
            // Show loading state
            $('#button-icon').addClass('hidden');
            $('#button-text').text('Memproses...');
            $('#loading-spinner').removeClass('hidden');
            $('#submit-button').attr('disabled', true).addClass('opacity-75');
            
            // Show toast notification
            toastr.info(
                `Memproses pembuatan jadwal untuk ${diffDays} hari dari ${formatDisplayDate(start)} hingga ${formatDisplayDate(end)}. ` +
                `Proses ini mungkin memerlukan waktu beberapa saat.`,
                'Memproses Jadwal'
            );
            
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
        
        // Get days in a month (considering leap years)
        function getDaysInMonth(year, month) {
            // JavaScript months are 0-indexed (January = 0, December = 11)
            return new Date(year, month, 0).getDate();
        }
        
        // Handle period buttons (1st or 2nd half of month)
        $('#first-period-btn').on('click', function() {
            // Update active states
            $('.period-preset').removeClass('active');
            $(this).addClass('active');
            $(this).css({
                'background-color': '#DCFCE7', 
                'border-color': '#86EFAC', 
                'box-shadow': '0 0 0 3px rgba(134, 239, 172, 0.3)'
            });
            $('#second-period-btn').css({
                'background-color': '#F0FDF4', 
                'border-color': '#BBFAD3',
                'box-shadow': 'none'
            });
            
            // Get selected month and year
            const selectedMonth = $('#period-month').val(); // Format: YYYY-MM
            const [year, month] = selectedMonth.split('-');
            
            // Create start date (1st day of month)
            const startDate = new Date(year, month - 1, 1);
            
            // Create end date (15th day of month)
            const endDate = new Date(year, month - 1, 15);
            
            // Update flatpickr
            fp.setDate([startDate, endDate]);
            
            // Update hidden inputs
            $("#start_date").val(formatDate(startDate));
            $("#end_date").val(formatDate(endDate));
            
            // Update display
            $("#start-date-display").text(formatDisplayDate(startDate));
            $("#end-date-display").text(formatDisplayDate(endDate));
            
            // Calculate days
            const daysCount = calculateDays(startDate, endDate);
            $("#days-count").text(daysCount);
        });
        
        $('#second-period-btn').on('click', function() {
            // Update active states
            $('.period-preset').removeClass('active');
            $(this).addClass('active');
            $(this).css({
                'background-color': '#DCFCE7', 
                'border-color': '#86EFAC',
                'box-shadow': '0 0 0 3px rgba(134, 239, 172, 0.3)'
            });
            $('#first-period-btn').css({
                'background-color': '#F0FDF4', 
                'border-color': '#BBFAD3',
                'box-shadow': 'none'
            });
            // Get selected month and year
            const selectedMonth = $('#period-month').val(); // Format: YYYY-MM
            const [year, month] = selectedMonth.split('-');
            
            // Create start date (16th day of month)
            const startDate = new Date(year, month - 1, 16);
            
            // Create end date (last day of month)
            const lastDay = getDaysInMonth(year, month);
            const endDate = new Date(year, month - 1, lastDay);
            
            // Update flatpickr
            fp.setDate([startDate, endDate]);
            
            // Update hidden inputs
            $("#start_date").val(formatDate(startDate));
            $("#end_date").val(formatDate(endDate));
            
            // Update display
            $("#start-date-display").text(formatDisplayDate(startDate));
            $("#end-date-display").text(formatDisplayDate(endDate));
            
            // Calculate days
            const daysCount = calculateDays(startDate, endDate);
            $("#days-count").text(daysCount);
        });
        
        // Re-trigger period buttons when month changes
        $('#period-month').change(function() {
            // If there was a period button previously clicked, trigger it again with the new month
            if ($('#first-period-btn').hasClass('active')) {
                $('#first-period-btn').trigger('click');
            } else if ($('#second-period-btn').hasClass('active')) {
                $('#second-period-btn').trigger('click');
            }
        });
        
        // Reset period button styling when date presets or calendar is used
        $('.date-preset').on('click', function() {
            resetPeriodButtons();
        });
        
        // Add event listener to the flatpickr instance to reset period buttons
        fp.config.onChange.push(function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                resetPeriodButtons();
            }
        });
        
        // Function to reset period button styling
        function resetPeriodButtons() {
            $('.period-preset').removeClass('active');
            $('.period-preset').css({
                'background-color': '#F0FDF4', 
                'border-color': '#BBFAD3',
                'box-shadow': 'none'
            });
        }
        
        // Handle period preset buttons
        $('.period-preset').on('click', function() {
            const period = $(this).data('period');
            const startDate = new Date();
            let endDate;
            
            if (period === 'mingguan') {
                // Set to next week
                endDate = new Date(startDate);
                endDate.setDate(startDate.getDate() + 6);
            } else if (period === 'bulanan') {
                // Set to next month
                endDate = new Date(startDate);
                endDate.setMonth(startDate.getMonth() + 1);
                endDate.setDate(0); // Last day of the month
            }
            
            // Update the flatpickr instance
            fp.setDate([startDate, endDate]);
            
            // Update hidden inputs
            $("#start_date").val(formatDate(startDate));
            $("#end_date").val(formatDate(endDate));
            
            // Update display
            $("#start-date-display").text(formatDisplayDate(startDate));
            $("#end-date-display").text(formatDisplayDate(endDate));
            
            // Calculate days
            const daysCount = calculateDays(startDate, endDate);
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
        
        // Show toaster notifications for generation results if available
        @if(session('generation_results'))
            @if(session('generation_results.created') > 0)
                toastr.success(
                    'Berhasil membuat {{ session("generation_results.created") }} jadwal.{{ session("generation_results.skipped") > 0 ? " " . session("generation_results.skipped") . " jadwal dilewati karena tidak tersedia pengemudi." : "" }}{{ session("generation_results.failed") > 0 ? " " . session("generation_results.failed") . " jadwal gagal dibuat." : "" }}',
                    'Pembuatan Jadwal Berhasil'
                );
            @elseif(session('generation_results.failed') > 0)
                toastr.error(
                    'Gagal membuat {{ session("generation_results.failed") }} jadwal. Silakan periksa detail untuk informasi lebih lanjut.',
                    'Pembuatan Jadwal Gagal'
                );
            @endif
        @endif
    });
</script>
@endpush

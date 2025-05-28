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
        
        /* Custom styles for weekend days (Saturday and Sunday) */
        .flatpickr-day.weekend {
            background-color: #FFEDD5; /* Orange background */
            border-color: #FDBA74;
        }
        
        .flatpickr-day.weekend:hover {
            background-color: #FED7AA;
        }
        
        /* Custom styles for holidays */
        .flatpickr-day.holiday {
            background-color: #FEE2E2; /* Red background */
            border-color: #FECACA;
            color: #EF4444;
            font-weight: bold;
        }
        
        .flatpickr-day.holiday:hover {
            background-color: #FCA5A5;
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


    <x-card>
        <div class="mb-6">  
            <form action="{{ route('schedules.generate') }}" method="POST" id="generate-form">
                @csrf
                <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
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

                        @error('start_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('end_date')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>


                <div class="flex justify-end">
                    <button type="submit" id="submit-button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="mr-2 fas fa-calendar-plus" id="button-icon"></i>
                        <span id="button-text">Buat Jadwal</span>
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
    // Get holiday dates from the server
    const holidays = @json(\App\Models\Holiday::active()->get(['date', 'name'])->map(function($holiday) {
        return [
            'date' => $holiday->date->format('Y-m-d'),
            'name' => $holiday->name
        ];
    }));
    
    // Create a map of holiday dates for quick lookup
    const holidayDates = holidays.map(h => h.date);
    
    // Function to check if a date is a weekend (Saturday or Sunday)
    function isWeekend(date) {
        const day = date.getDay();
        return day === 0 || day === 6; // 0 is Sunday, 6 is Saturday
    }
    
    // Function to check if a date is a holiday
    function isHoliday(dateStr) {
        return holidayDates.includes(dateStr);
    }
    
    // Function to get holiday info by date
    function getHolidayInfo(dateStr) {
        return holidays.find(h => h.date === dateStr);
    }
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
            locale: "id",
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: [$("#start_date").val(), $("#end_date").val()],
            inline: true,
            minDate: "today",
            maxRange: 30, // Maximum range of 30 days
            onDayCreate: function(dObj, dStr, fp, dayElem) {
                // Format date as YYYY-MM-DD for comparison using local timezone
                const date = dayElem.dateObj;
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const dateStr = `${year}-${month}-${day}`;
                
                // Check if the date is a weekend
                if (isWeekend(dayElem.dateObj)) {
                    dayElem.classList.add('weekend');
                }
                
                // Check if the date is a holiday
                if (isHoliday(dateStr)) {
                    dayElem.classList.add('holiday');
                    
                    // Add tooltip with holiday name if available
                    const holidayInfo = getHolidayInfo(dateStr);
                    if (holidayInfo && holidayInfo.name) {
                        dayElem.setAttribute('title', holidayInfo.name);
                    }
                }
            },
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
        
    });
</script>
@endpush

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

    <x-card>
        <div class="mb-6">
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Kriteria Pembuatan Jadwal Otomatis
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc pl-5 space-y-1">
                                <li>Prioritas diberikan kepada pengemudi dengan tipe 'batangan'. Jika tidak ada, maka akan ditugaskan pengemudi 'cadangan'.</li>
                                <li>Kendala shift: Jika pengemudi mendapatkan shift 'siang', maka tidak dapat diberi shift 'pagi' pada hari berikutnya.</li>
                                <li>Jika pengemudi sebelumnya berstatus 'cuti' atau 'nonaktif', jadwal berikutnya dapat berupa 'pagi' atau 'siang'.</li>
                                <li>Jika pengemudi sebelumnya mendapat shift 'pagi', dapat ditugaskan 'pagi' atau 'siang' pada hari berikutnya.</li>
                                <li>Pengemudi dapat ditugaskan dengan unit yang sama untuk backup.</li>
                                <li>Jika pengemudi 'batangan' sedang cuti, sistem akan mencari pengemudi 'batangan' backup dengan unit yang sama.</li>
                                <li>Pengemudi dengan jumlah jadwal terendah dalam bulan berjalan akan diprioritaskan.</li>
                                <li>Untuk hari kerja (Senin–Jumat), semua pengemudi akan dijadwalkan. Untuk akhir pekan, hanya 80% pengemudi 'batangan' yang dijadwalkan.</li>
                                <li>Pengemudi 'cadangan' selalu menjadi opsi terakhir.</li>
                                <li>Dalam satu hari, pengemudi hanya dapat ditugaskan dalam satu sesi atau shift (pagi atau siang).</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

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
                                    <p>Berhasil membuat {{ session('generation_results.success') }} jadwal.</p>
                                    @if (session('generation_results.failed') > 0)
                                        <p class="text-yellow-700">Gagal membuat {{ session('generation_results.failed') }} jadwal.</p>
                                    @endif
                                    
                                    @if (count(session('generation_results.messages')) > 0)
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
                                Pemberitahuan Penting
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>
                                    Ini akan membuat jadwal untuk semua unit dan shift dalam rentang tanggal yang dipilih.
                                    <strong>Jadwal yang sudah ada tidak akan ditimpa</strong>, melainkan jadwal baru akan dibuat untuk slot yang belum terjadwal.
                                </p>
                                <p class="mt-2">
                                    Sistem akan otomatis membuat catatan riwayat pengemudi (Driver History) untuk setiap jadwal yang dibuat.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
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
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    
                    // If the range is more than 30 days, adjust the end date
                    if (diffDays > 30) {
                        const maxDate = new Date(startDate);
                        maxDate.setDate(maxDate.getDate() + 30);
                        instance.setDate([startDate, maxDate]);
                        alert("Maksimal rentang waktu adalah 30 hari. Tanggal akhir telah disesuaikan.");
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

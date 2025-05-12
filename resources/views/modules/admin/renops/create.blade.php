@extends('modules.admin.layouts.main')

@section('title', 'Buat Rencana Operasi Unit')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<style>
    .unit-card {
        transition: all 0.3s ease;
    }
    .unit-card.selected {
        border-color: #4F46E5;
        background-color: #EEF2FF;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Buat Rencana Operasi Unit</h1>
        <a href="{{ route('renops.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form id="renops-form" action="{{ route('renops.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="text" id="date" name="date" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    <p class="mt-1 text-xs text-gray-500">Pilih tanggal akhir pekan atau hari libur</p>
                </div>

                <div>
                    <label for="day-type" class="block text-sm font-medium text-gray-700 mb-1">Jenis Hari</label>
                    <select id="day-type" name="day_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        <option value="">Pilih Jenis Hari</option>
                        <option value="saturday">Sabtu</option>
                        <option value="sunday">Minggu</option>
                        <option value="holiday">Hari Libur</option>
                    </select>
                </div>

                <div id="weekend-container">
                    <label for="weekend-select" class="block text-sm font-medium text-gray-700 mb-1">Pilihan Cepat Akhir Pekan</label>
                    <select id="weekend-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Pilih Akhir Pekan</option>
                        @foreach($weekends as $weekend)
                            <option value="{{ $weekend['date'] }}" data-type="{{ $weekend['type'] }}">
                                {{ \Carbon\Carbon::parse($weekend['date'])->translatedFormat('l, j F Y') }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Pilih akhir pekan yang akan datang</p>
                </div>

                <div id="holiday-container" class="hidden">
                    <label for="holiday-id" class="block text-sm font-medium text-gray-700 mb-1">Hari Libur</label>
                    <select id="holiday-id" name="holiday_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Pilih Hari Libur</option>
                        @foreach($holidays as $holiday)
                            <option value="{{ $holiday->id }}" data-date="{{ $holiday->date->format('Y-m-d') }}">
                                {{ $holiday->date->translatedFormat('d F Y') }} - {{ $holiday->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold text-gray-800">Pilih Unit</h2>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">Terpilih: <span id="selected-count">0</span> / <span id="max-limit">0</span></span>
                        <div class="w-48 bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex mb-4">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="unit-search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5" placeholder="Cari unit...">
                    </div>
                    <button type="button" id="select-all" class="ml-2 bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded">
                        Pilih Semua
                    </button>
                    <button type="button" id="deselect-all" class="ml-2 bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                        Batalkan Semua
                    </button>
                </div>

                <div id="units-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-96 overflow-y-auto">
                    @foreach($units as $unit)
                        <div class="unit-card border rounded-lg p-3 cursor-pointer hover:bg-gray-50" data-unit-id="{{ $unit->id }}" data-unit-number="{{ $unit->unit_number }}" data-plate-number="{{ $unit->plate_number }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-gray-800">{{ $unit->unit_number }}</h3>
                                    <p class="text-sm text-gray-600">{{ $unit->plate_number }}</p>
                                </div>
                                <span class="status-badge px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                    Tidak Dipilih
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <input type="hidden" id="selected-units" name="unit_ids" value="">

            <div class="flex justify-end">
                <button type="submit" id="submit-btn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Simpan Rencana
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('date');
        const dayTypeSelect = document.getElementById('day-type');
        const holidayContainer = document.getElementById('holiday-container');
        const holidaySelect = document.getElementById('holiday-id');
        const weekendContainer = document.getElementById('weekend-container');
        const weekendSelect = document.getElementById('weekend-select');
        const unitsContainer = document.getElementById('units-container');
        const unitSearch = document.getElementById('unit-search');
        const selectAllBtn = document.getElementById('select-all');
        const deselectAllBtn = document.getElementById('deselect-all');
        const selectedCountSpan = document.getElementById('selected-count');
        const maxLimitSpan = document.getElementById('max-limit');
        const progressBar = document.querySelector('.bg-blue-600');
        const submitBtn = document.getElementById('submit-btn');
        const selectedUnitsInput = document.getElementById('selected-units');
        const unitCards = document.querySelectorAll('.unit-card');
        const totalUnits = {{ $units->count() }};

        // Initialize flatpickr with Indonesian locale
        flatpickr("#date", {
            dateFormat: "Y-m-d",
            locale: "id",
            disableMobile: true,
            allowInput: true,
            theme: "material_blue",
            onChange: function(selectedDates, dateStr) {
                validateDate();
            }
        });

        // Handle day type change
        dayTypeSelect.addEventListener('change', function() {
            const selectedType = this.value;

            if (selectedType === 'holiday') {
                holidayContainer.classList.remove('hidden');
                weekendContainer.classList.add('hidden');
            } else {
                holidayContainer.classList.add('hidden');
                weekendContainer.classList.remove('hidden');
            }

            updateMaxLimit();
        });

        // Handle weekend selection
        weekendSelect.addEventListener('change', function() {
            if (!this.value) return;

            const selectedDate = this.value;
            const selectedType = this.options[this.selectedIndex].getAttribute('data-type');

            dateInput.value = selectedDate;
            dayTypeSelect.value = selectedType;

            updateMaxLimit();
        });

        // Handle holiday selection
        holidaySelect.addEventListener('change', function() {
            if (!this.value) return;

            const selectedDate = this.options[this.selectedIndex].getAttribute('data-date');
            dateInput.value = selectedDate;

            updateMaxLimit();
        });

        // Function to validate the selected date
        function validateDate() {
            const selectedDate = dateInput.value;
            if (!selectedDate) return;

            fetch(`/holidays/check-date-status?date=${selectedDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.isWeekend) {
                        dayTypeSelect.value = data.dayType;
                        holidayContainer.classList.add('hidden');
                        weekendContainer.classList.remove('hidden');
                    } else if (data.isHoliday) {
                        dayTypeSelect.value = 'holiday';
                        holidaySelect.value = data.holidayId;
                        holidayContainer.classList.remove('hidden');
                        weekendContainer.classList.add('hidden');
                    } else {
                        alert('Tanggal yang dipilih bukan akhir pekan atau hari libur. Silakan pilih tanggal lain.');
                        dateInput.value = '';
                        dayTypeSelect.value = '';
                    }

                    updateMaxLimit();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memvalidasi tanggal.');
                });
        }

        // Function to update the maximum limit based on day type
        function updateMaxLimit() {
            const dayType = dayTypeSelect.value;
            let maxLimit = 0;

            if (dayType === 'saturday') {
                maxLimit = Math.floor(totalUnits * 0.8); // 80% for Saturday
            } else if (dayType === 'sunday' || dayType === 'holiday') {
                maxLimit = Math.floor(totalUnits * 0.7); // 70% for Sunday and Holidays
            }

            maxLimitSpan.textContent = maxLimit;
            updateSelectedCount();

            return maxLimit;
        }

        // Function to update the selected count
        function updateSelectedCount() {
            const selectedCards = document.querySelectorAll('.unit-card.selected');
            const selectedCount = selectedCards.length;
            const maxLimit = parseInt(maxLimitSpan.textContent) || 0;

            selectedCountSpan.textContent = selectedCount;

            // Update progress bar
            const percentage = maxLimit > 0 ? (selectedCount / maxLimit) * 100 : 0;
            progressBar.style.width = `${percentage}%`;

            // Change color based on percentage
            if (percentage > 90) {
                progressBar.classList.remove('bg-blue-600');
                progressBar.classList.add('bg-red-600');
            } else if (percentage > 70) {
                progressBar.classList.remove('bg-blue-600', 'bg-red-600');
                progressBar.classList.add('bg-yellow-600');
            } else {
                progressBar.classList.remove('bg-yellow-600', 'bg-red-600');
                progressBar.classList.add('bg-blue-600');
            }

            // Check if we're exceeding the maximum limit
            if (selectedCount > maxLimit && maxLimit > 0) {
                selectedCountSpan.classList.add('text-red-600', 'font-bold');
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                selectedCountSpan.classList.remove('text-red-600', 'font-bold');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }

            // Update hidden input with selected unit IDs
            const selectedUnitIds = Array.from(selectedCards).map(card => card.getAttribute('data-unit-id'));
            selectedUnitsInput.value = JSON.stringify(selectedUnitIds);
        }

        // Filter units based on search
        unitSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            unitCards.forEach(card => {
                const unitNumber = card.getAttribute('data-unit-number').toLowerCase();
                const plateNumber = card.getAttribute('data-plate-number').toLowerCase();

                if (unitNumber.includes(searchTerm) || plateNumber.includes(searchTerm)) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });

        // Toggle unit selection
        unitCards.forEach(card => {
            card.addEventListener('click', function() {
                this.classList.toggle('selected');

                // Update status badge
                const statusBadge = this.querySelector('.status-badge');
                if (this.classList.contains('selected')) {
                    statusBadge.textContent = 'Dipilih';
                    statusBadge.classList.remove('bg-gray-100', 'text-gray-800');
                    statusBadge.classList.add('bg-green-100', 'text-green-800');
                } else {
                    statusBadge.textContent = 'Tidak Dipilih';
                    statusBadge.classList.remove('bg-green-100', 'text-green-800');
                    statusBadge.classList.add('bg-gray-100', 'text-gray-800');
                }

                updateSelectedCount();
            });
        });

        // Select all visible units
        selectAllBtn.addEventListener('click', function() {
            const visibleCards = Array.from(document.querySelectorAll('.unit-card:not(.hidden)'));
            const maxLimit = updateMaxLimit();

            // Check if selecting all would exceed the limit
            if (maxLimit > 0 && visibleCards.length > maxLimit) {
                if (!confirm(`Memilih semua unit akan melebihi batas maksimum (${maxLimit} unit). Apakah Anda ingin memilih ${maxLimit} unit pertama saja?`)) {
                    return;
                }

                // Select only up to the maximum limit
                visibleCards.slice(0, maxLimit).forEach(card => {
                    card.classList.add('selected');
                    const statusBadge = card.querySelector('.status-badge');
                    statusBadge.textContent = 'Dipilih';
                    statusBadge.classList.remove('bg-gray-100', 'text-gray-800');
                    statusBadge.classList.add('bg-green-100', 'text-green-800');
                });
            } else {
                // Select all visible units
                visibleCards.forEach(card => {
                    card.classList.add('selected');
                    const statusBadge = card.querySelector('.status-badge');
                    statusBadge.textContent = 'Dipilih';
                    statusBadge.classList.remove('bg-gray-100', 'text-gray-800');
                    statusBadge.classList.add('bg-green-100', 'text-green-800');
                });
            }

            updateSelectedCount();
        });

        // Deselect all units
        deselectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.unit-card.selected').forEach(card => {
                card.classList.remove('selected');
                const statusBadge = card.querySelector('.status-badge');
                statusBadge.textContent = 'Tidak Dipilih';
                statusBadge.classList.remove('bg-green-100', 'text-green-800');
                statusBadge.classList.add('bg-gray-100', 'text-gray-800');
            });

            updateSelectedCount();
        });

        // Form submission
        document.getElementById('renops-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const selectedCards = document.querySelectorAll('.unit-card.selected');
            const maxLimit = parseInt(maxLimitSpan.textContent) || 0;

            if (selectedCards.length === 0) {
                alert('Silakan pilih setidaknya satu unit.');
                return;
            }

            if (maxLimit > 0 && selectedCards.length > maxLimit) {
                alert(`Tidak dapat menyimpan rencana. Batas maksimum adalah ${maxLimit} unit.`);
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Menyimpan...';

            this.submit();
        });

        // Initialize
        updateMaxLimit();
    });
</script>
@endpush

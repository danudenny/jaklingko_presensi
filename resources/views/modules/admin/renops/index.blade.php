@extends('modules.admin.layouts.main')

@section('title', 'Rencana Operasi Unit')

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
    .progress-container {
        position: relative;
        width: 48px;
        height: 2.5px;
        background-color: #e5e7eb;
        border-radius: 9999px;
    }
    .progress-bar {
        position: absolute;
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }
    .create-button {
        position: relative;
        z-index: 10;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Rencana Operasi Unit</h1>
        <a href="{{ route('renops.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded create-button">
            <i class="fas fa-plus mr-2"></i> Buat Baru
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form id="date-form" action="{{ route('renops.index') }}" method="GET" class="mb-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="text" id="date" name="date" value="{{ $date->format('Y-m-d') }}" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        <i class="fas fa-search mr-2"></i> Cari
                    </button>
                </div>
            </div>
        </form>

        @if(isset($error))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ $error }}</span>
            </div>
        @endif

        @if($dayType)
            <div class="mb-4">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-info-circle text-blue-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                @if($dayType == 'saturday')
                                    Tanggal {{ $date->translatedFormat('d F Y') }} adalah hari <strong>Sabtu</strong>.
                                    Maksimal unit yang dapat beroperasi adalah <strong>{{ $maxLimit }}</strong> unit (80% dari total).
                                @elseif($dayType == 'sunday')
                                    Tanggal {{ $date->translatedFormat('d F Y') }} adalah hari <strong>Minggu</strong>.
                                    Maksimal unit yang dapat beroperasi adalah <strong>{{ $maxLimit }}</strong> unit (70% dari total).
                                @elseif($dayType == 'holiday')
                                    Tanggal {{ $date->translatedFormat('d F Y') }} adalah hari libur <strong>{{ $holiday->name }}</strong>.
                                    Maksimal unit yang dapat beroperasi adalah <strong>{{ $maxLimit }}</strong> unit (70% dari total).
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <h2 class="text-lg font-semibold text-gray-800">Pilih Unit</h2>
                    <div class="flex items-center">
                        <span class="text-sm text-gray-600 mr-2">Terpilih: <span id="selected-count">{{ $currentCount ?? 0 }}</span> / <span>{{ $maxLimit }}</span></span>
                        <div class="progress-container">
                            <div class="progress-bar bg-blue-600" style="width: {{ ($currentCount / $maxLimit) * 100 }}%"></div>
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
                </div>

                <div id="units-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 max-h-96 overflow-y-auto">
                    @foreach($units as $unit)
                        <div class="unit-card border rounded-lg p-3 cursor-pointer hover:bg-gray-50 {{ in_array($unit->id, $renopsUnits ?? []) ? 'selected' : '' }}"
                             data-unit-id="{{ $unit->id }}"
                             data-unit-number="{{ $unit->unit_number }}"
                             data-plate-number="{{ $unit->plate_number }}">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="font-semibold text-gray-800">{{ $unit->unit_number }}</h3>
                                    <p class="text-sm text-gray-600">{{ $unit->plate_number }}</p>
                                </div>
                                <span class="status-badge px-2 py-1 text-xs font-medium rounded-full {{ in_array($unit->id, $renopsUnits ?? []) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ in_array($unit->id, $renopsUnits ?? []) ? 'Dipilih' : 'Tidak Dipilih' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end space-x-2">
                <button type="button" id="delete-plan" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-trash-alt mr-2"></i> Hapus Rencana
                </button>
                <button type="button" id="save-changes" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>
        @else
            <div class="text-center py-8">
                <i class="fas fa-calendar-times text-gray-400 text-5xl mb-4"></i>
                <p class="text-gray-600">Silakan pilih tanggal akhir pekan atau hari libur untuk mengelola rencana operasi unit.</p>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const unitsContainer = document.getElementById('units-container');
        const unitSearch = document.getElementById('unit-search');
        const saveChangesBtn = document.getElementById('save-changes');
        const deletePlanBtn = document.getElementById('delete-plan');
        const selectedCountSpan = document.getElementById('selected-count');
        const date = '{{ $date->format('Y-m-d') }}';
        const dayType = '{{ $dayType ?? "" }}';
        const holidayId = '{{ $holiday->id ?? "" }}';
        const maxLimit = {{ $maxLimit ?? 0 }};
        const unitCards = document.querySelectorAll('.unit-card');

        // Initialize flatpickr with Indonesian locale
        flatpickr("#date", {
            dateFormat: "Y-m-d",
            locale: "id",
            disableMobile: true,
            allowInput: true,
            theme: "material_blue",
            onChange: function(selectedDates, dateStr) {
                document.getElementById('date-form').submit();
            }
        });

        // Function to update the selected count
        function updateSelectedCount() {
            const selectedCards = document.querySelectorAll('.unit-card.selected');
            selectedCountSpan.textContent = selectedCards.length;

            // Update progress bar
            const progressBar = document.querySelector('.progress-bar');
            const percentage = maxLimit > 0 ? (selectedCards.length / maxLimit) * 100 : 0;
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
            if (selectedCards.length > maxLimit) {
                selectedCountSpan.classList.add('text-red-600', 'font-bold');
            } else {
                selectedCountSpan.classList.remove('text-red-600', 'font-bold');
            }
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
                const unitId = this.getAttribute('data-unit-id');

                // Toggle visual selection
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

                // Send AJAX request to toggle unit
                if (date && dayType) {
                    fetch('{{ route('renops.toggle-unit') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            date: date,
                            unit_id: unitId,
                            day_type: dayType,
                            holiday_id: dayType === 'holiday' ? holidayId : null
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            // If failed, revert the selection
                            this.classList.toggle('selected');
                            statusBadge.textContent = this.classList.contains('selected') ? 'Dipilih' : 'Tidak Dipilih';
                            statusBadge.classList.toggle('bg-green-100');
                            statusBadge.classList.toggle('text-green-800');
                            statusBadge.classList.toggle('bg-gray-100');
                            statusBadge.classList.toggle('text-gray-800');
                            updateSelectedCount();

                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert the selection on error
                        this.classList.toggle('selected');
                        statusBadge.textContent = this.classList.contains('selected') ? 'Dipilih' : 'Tidak Dipilih';
                        statusBadge.classList.toggle('bg-green-100');
                        statusBadge.classList.toggle('text-green-800');
                        statusBadge.classList.toggle('bg-gray-100');
                        statusBadge.classList.toggle('text-gray-800');
                        updateSelectedCount();

                        alert('Terjadi kesalahan saat mengubah status unit.');
                    });
                }
            });
        });

        // Save changes
        if (saveChangesBtn) {
            saveChangesBtn.addEventListener('click', function() {
                const selectedUnits = Array.from(document.querySelectorAll('.unit-card.selected'))
                    .map(card => card.getAttribute('data-unit-id'));

                if (selectedUnits.length > maxLimit) {
                    alert(`Tidak dapat menyimpan perubahan. Batas maksimum adalah ${maxLimit} unit.`);
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Menyimpan...';

                fetch(`/renops/${date}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        unit_ids: selectedUnits,
                        day_type: dayType,
                        holiday_id: dayType === 'holiday' ? holidayId : null
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Rencana operasi unit berhasil diperbarui.');
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal memperbarui rencana operasi unit.');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Perubahan';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat memperbarui rencana operasi unit.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Perubahan';
                });
            });
        }

        // Delete plan
        if (deletePlanBtn) {
            deletePlanBtn.addEventListener('click', function() {
                if (confirm('Apakah Anda yakin ingin menghapus rencana operasi ini?')) {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-circle-notch fa-spin mr-2"></i> Menghapus...';

                    fetch(`/renops/${date}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Rencana operasi unit berhasil dihapus.');
                            window.location.href = '{{ route('renops.index') }}';
                        } else {
                            alert(data.message || 'Gagal menghapus rencana operasi unit.');
                            this.disabled = false;
                            this.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Hapus Rencana';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat menghapus rencana operasi unit.');
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-trash-alt mr-2"></i> Hapus Rencana';
                    });
                }
            });
        }

        // Initialize
        updateSelectedCount();
    });
</script>
@endpush

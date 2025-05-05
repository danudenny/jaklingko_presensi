@extends('modules.admin.layouts.main')

@section('title', 'Tambah Log Perawatan')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
@endpush

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Tambah Log Perawatan</x-slot>
        <x-slot name="actions">
            <a href="{{ route('maintenance-logs.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('maintenance-logs.store') }}" method="POST" enctype="multipart/form-data" id="maintenanceLogForm">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Unit Selection -->
                <div>
                    <x-input-label for="unit_id" value="Unit" />
                    <select name="unit_id" id="unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->unit_number }} - {{ $unit->plate_number }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="unit_id" class="mt-2" />
                </div>

                <!-- Route Selection (will be populated via AJAX) -->
                <div>
                    <x-input-label for="route_id" value="Rute" />
                    <select name="route_id" id="route_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required disabled>
                        <option value="">Pilih Rute</option>
                    </select>
                    <x-input-error for="route_id" class="mt-2" />
                </div>

                <!-- Date Reported -->
                <div>
                    <x-input-label for="date_reported" value="Tanggal" />
                    <input id="date_reported" class="mt-1 block w-full" type="text" name="date_reported" :value="old('date_reported', date('Y-m-d'))" required autocomplete="off" />
                    <x-input-error for="date_reported" class="mt-2" />
                </div>

                <!-- Time Reported -->
                <div>
                    <x-input-label for="time_reported" value="Waktu" />
                    <input id="time_reported" class="mt-1 block w-full" type="text" name="time_reported" :value="old('time_reported', date('H:i'))" required autocomplete="off" />
                    <x-input-error for="time_reported" class="mt-2" />
                </div>

                <!-- Shift -->
                <div>
                    <x-input-label for="shift" value="Shift" />
                    <select name="shift" id="shift" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">Pilih Shift</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift }}" {{ old('shift') == $shift ? 'selected' : '' }}>
                                {{ $shift }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error for="shift" class="mt-2" />
                </div>

                <!-- Driver Selection (will be populated via AJAX) -->
                <div>
                    <x-input-label for="driver_id" value="Pengemudi" />
                    <select name="driver_id" id="driver_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required disabled>
                        <option value="">Pilih Pengemudi</option>
                    </select>
                    <x-input-error for="driver_id" class="mt-2" />
                </div>

                <!-- Maintenance Type -->
                <div>
                    <x-input-label for="type" value="Tipe Perawatan" />
                    <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Tipe</option>
                        <option value="perbaikan" {{ old('type') == 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                        <option value="penggantian" {{ old('type') == 'penggantian' ? 'selected' : '' }}>Penggantian</option>
                    </select>
                    <x-input-error for="type" class="mt-2" />
                </div>

                <!-- Parts -->
                <div>
                    <x-input-label for="parts" value="Suku Cadang" />
                    <input id="parts" class="mt-1 block w-full" type="text" name="parts" :value="old('parts')" required />
                    <x-input-error for="parts" class="mt-2" />
                </div>

                <!-- Category (only for penggantian) -->
                <div id="categoryContainer" class="{{ old('type') !== 'penggantian' ? 'hidden' : '' }}">
                    <x-input-label for="category" value="Kategori" />
                    <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">Pilih Kategori</option>
                        <option value="baru" {{ old('category') == 'baru' ? 'selected' : '' }}>Baru</option>
                        <option value="bekas" {{ old('category') == 'bekas' ? 'selected' : '' }}>Bekas</option>
                    </select>
                    <x-input-error for="category" class="mt-2" />
                </div>

                <!-- Source of Sparepart -->
                <div>
                    <x-input-label for="source_of_sparepart" value="Sumber Suku Cadang" />
                    <input id="source_of_sparepart" class="mt-1 block w-full" type="text" name="source_of_sparepart" :value="old('source_of_sparepart')" required />
                    <x-input-error for="source_of_sparepart" class="mt-2" />
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <x-input-label for="description" value="Deskripsi" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('description') }}</textarea>
                <x-input-error for="description" class="mt-2" />
            </div>

            <!-- Costs -->
            <div class="mt-6">
                <x-input-label value="Biaya" />
                <div id="costsContainer" class="space-y-2">
                    <div class="flex items-center space-x-2">
                        <input class="w-full" type="text" name="costs[0][description]" placeholder="Deskripsi Biaya" :value="old('costs.0.description')" />
                        <input class="w-full" type="number" name="costs[0][amount]" placeholder="Jumlah (Rp)" :value="old('costs.0.amount')" min="0" />
                        <button type="button" class="px-2 py-1 bg-red-500 text-white rounded" onclick="removeRow(this)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="addCost" class="mt-2 inline-flex items-center px-3 py-1 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">
                    <i class="fas fa-plus mr-1"></i> Tambah Biaya
                </button>
            </div>

            <!-- Photos -->
            <div class="mt-6">
                <x-input-label for="photos" value="Foto (Maksimal 3)" />
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <i class="fas fa-camera text-gray-400 text-3xl"></i>
                        <div class="flex text-sm text-gray-600">
                            <label for="photos" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload foto</span>
                                <input id="photos" name="photos[]" type="file" class="sr-only" multiple accept="image/*" onchange="previewImages(event)">
                            </label>
                            <p class="pl-1">atau drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            PNG, JPG, GIF up to 2MB
                        </p>
                    </div>
                </div>
                <div id="imagePreviewContainer" class="mt-4 grid grid-cols-3 gap-4"></div>
                <x-input-error for="photos" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-button type="submit" class="bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>
                    Simpan
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    // Initialize date and time pickers
    flatpickr("#date_reported", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });

    flatpickr("#time_reported", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true
    });

    // Handle maintenance type change
    document.getElementById('type').addEventListener('change', function() {
        const categoryContainer = document.getElementById('categoryContainer');
        if (this.value === 'penggantian') {
            categoryContainer.classList.remove('hidden');
            document.getElementById('category').setAttribute('required', 'required');
        } else {
            categoryContainer.classList.add('hidden');
            document.getElementById('category').removeAttribute('required');
        }
    });

    // Handle unit selection change
    document.getElementById('unit_id').addEventListener('change', function() {
        const unitId = this.value;
        if (!unitId) {
            return;
        }

        // Reset and disable route and driver dropdowns
        const routeSelect = document.getElementById('route_id');
        routeSelect.innerHTML = '<option value="">Pilih Rute</option>';
        routeSelect.disabled = true;

        const driverSelect = document.getElementById('driver_id');
        driverSelect.innerHTML = '<option value="">Pilih Pengemudi</option>';
        driverSelect.disabled = true;

        // Fetch routes for the selected unit
        fetch(`/maintenance-logs/routes-for-unit/${unitId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    data.forEach(route => {
                        const option = document.createElement('option');
                        option.value = route.id;
                        option.textContent = `${route.route_number} - ${route.name}`;
                        routeSelect.appendChild(option);
                    });
                    routeSelect.disabled = false;
                }
            });
    });

    // Handle route selection change
    document.getElementById('route_id').addEventListener('change', function() {
        const unitId = document.getElementById('unit_id').value;
        const routeId = this.value;
        const dateReported = document.getElementById('date_reported').value;

        if (!unitId || !routeId || !dateReported) {
            return;
        }

        // Reset and disable driver dropdown
        const driverSelect = document.getElementById('driver_id');
        driverSelect.innerHTML = '<option value="">Pilih Pengemudi</option>';
        driverSelect.disabled = true;

        // Fetch drivers from schedule
        fetch('/maintenance-logs/driver-from-schedule', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                unit_id: unitId,
                route_id: routeId,
                date: dateReported
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.has_schedules) {
                data.schedules.forEach(schedule => {
                    const option = document.createElement('option');
                    option.value = schedule.driver.id;
                    option.textContent = `${schedule.driver.name} (${schedule.shift === 'morning' ? 'Pagi' : 'Siang'})`;
                    driverSelect.appendChild(option);
                });
                driverSelect.disabled = false;
            } else {
                // If no scheduled drivers, fetch all drivers assigned to the unit
                fetch(`/maintenance-logs/drivers-for-unit/${unitId}`)
                    .then(response => response.json())
                    .then(drivers => {
                        if (drivers.length > 0) {
                            drivers.forEach(driver => {
                                const option = document.createElement('option');
                                option.value = driver.id;
                                option.textContent = driver.name;
                                driverSelect.appendChild(option);
                            });
                            driverSelect.disabled = false;
                        }
                    });
            }
        });
    });

    // Date reported change handler
    document.getElementById('date_reported').addEventListener('change', function() {
        const routeSelect = document.getElementById('route_id');
        if (routeSelect.value) {
            // Trigger route change event to refresh drivers
            const event = new Event('change');
            routeSelect.dispatchEvent(event);
        }
    });

    // Handle costs
    let costIndex = 1;

    document.getElementById('addCost').addEventListener('click', function() {
        const container = document.getElementById('costsContainer');
        const div = document.createElement('div');
        div.className = 'flex items-center space-x-2';
        div.innerHTML = `
            <input class="w-full" type="text" name="costs[${costIndex}][description]" placeholder="Deskripsi Biaya" />
            <input class="w-full" type="number" name="costs[${costIndex}][amount]" placeholder="Jumlah (Rp)" min="0" />
            <button type="button" class="px-2 py-1 bg-red-500 text-white rounded" onclick="removeRow(this)">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(div);
        costIndex++;
    });

    function removeRow(button) {
        button.closest('div').remove();
    }

    // Image preview
    function previewImages(event) {
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '';

        const files = event.target.files;
        if (files.length > 3) {
            alert('Maksimal 3 foto yang dapat diunggah.');
            event.target.value = '';
            return;
        }

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'relative';
                div.innerHTML = `
                    <img src="${e.target.result}" class="h-32 w-full object-cover rounded" />
                    <button type="button" class="absolute top-0 right-0 bg-red-500 text-white rounded-full p-1" onclick="removePreview(this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                container.appendChild(div);
            };

            reader.readAsDataURL(file);
        }
    }

    function removePreview(button) {
        button.closest('div').remove();
        document.getElementById('photos').value = '';
    }
</script>
@endpush

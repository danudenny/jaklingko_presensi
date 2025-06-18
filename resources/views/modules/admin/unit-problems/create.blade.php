@extends('modules.admin.layouts.main')

@section('title', 'Tambah Laporan Masalah Unit')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Tambah Laporan Masalah Unit</x-slot>
        <x-slot name="actions">
            <a href="{{ route('unit-problems.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('unit-problems.store') }}" method="POST" enctype="multipart/form-data" id="unit-problem-form">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Unit -->
                <div>
                    <x-input-label for="unit_search" :value="__('Unit')" />
                    <div class="relative">
                        <input type="text" 
                               id="unit_search" 
                               placeholder="Cari unit..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               autocomplete="off"
                               value="{{ old('unit_id') ? $units->firstWhere('id', old('unit_id'))?->unit_number . ' - ' . $units->firstWhere('id', old('unit_id'))?->plate_number : '' }}">
                        <input type="hidden" name="unit_id" id="unit_id" value="{{ old('unit_id') }}" required>
                        
                        <!-- Dropdown results -->
                        <div id="unit_dropdown" class="absolute z-10 hidden w-full max-h-60 overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg">
                            <div id="unit_list" class="p-2 space-y-1">
                                @foreach($units as $unit)
                                    <div class="unit-item flex items-center hover:bg-gray-50 p-2 rounded cursor-pointer" 
                                         data-unit-id="{{ $unit->id }}" 
                                         data-unit-number="{{ $unit->unit_number }}"
                                         data-plate-number="{{ $unit->plate_number }}"
                                         onclick="selectUnit({{ $unit->id }}, '{{ $unit->unit_number }}', '{{ $unit->plate_number }}')">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900">{{ $unit->unit_number }}</div>
                                            <div class="text-xs text-gray-500">{{ $unit->plate_number }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                </div>

                <!-- Date Reported -->
                <div>
                    <x-input-label for="date_reported" :value="__('Tanggal Laporan')" />
                    <input id="date_reported" type="date" name="date_reported" value="{{ old('date_reported', date('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                    <x-input-error :messages="$errors->get('date_reported')" class="mt-2" />
                </div>

                <!-- Time Reported -->
                <div>
                    <x-input-label for="time_reported" :value="__('Waktu Laporan')" />
                    <input id="time_reported" type="time" name="time_reported" value="{{ old('time_reported', date('H:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                    <x-input-error :messages="$errors->get('time_reported')" class="mt-2" />
                </div>

                <!-- Driver -->
                <div>
                    <x-input-label for="driver_id" :value="__('Pengemudi')" />
                    <select id="driver_id" name="driver_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Unit, Tanggal & Shift terlebih dahulu</option>
                    </select>
                    <div class="mt-2 text-sm text-gray-500" id="driver-status"></div>
                    <x-input-error :messages="$errors->get('driver_id')" class="mt-2" />
                </div>

                <!-- Shift -->
                <div>
                    <x-input-label for="shift" :value="__('Shift')" />
                    <select id="shift" name="shift" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Shift</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift }}" {{ old('shift') == $shift ? 'selected' : '' }}>
                                {{ $shift }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('shift')" class="mt-2" />
                </div>

                <!-- Location -->
                <div>
                    <x-input-label for="location" :value="__('Lokasi (Opsional)')" />
                    <input id="location" type="text" name="location" value="{{ old('location') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>
            </div>

            <!-- On Schedule -->
            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" id="on_schedule" name="on_schedule" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ old('on_schedule') ? 'checked' : '' }} disabled>
                    <span class="ml-2 text-sm text-gray-600">Dalam Jadwal</span>
                </label>
                <x-input-error :messages="$errors->get('on_schedule')" class="mt-2" />
            </div>

            <!-- Needs Repair Toggle -->
            <div class="mb-6">
                <label class="flex items-center">
                    <span class="text-sm font-medium text-gray-700 mr-3">Butuh Perbaikan</span>
                    <div class="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                        <input type="checkbox" name="needs_repair" id="needs_repair" value="1" 
                               class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer transition-transform duration-200 ease-in-out transform"
                               {{ old('needs_repair') ? 'checked' : '' }}>
                        <label for="needs_repair" 
                               class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer transition-colors duration-200 ease-in-out"></label>
                    </div>
                    <span id="needs_repair_text" class="text-sm text-gray-600">{{ old('needs_repair') ? 'Ya' : 'Tidak' }}</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Centang jika masalah ini memerlukan perbaikan unit</p>
                <x-input-error :messages="$errors->get('needs_repair')" class="mt-2" />
            </div>

            <!-- Hidden field for schedule history ID -->
            <input type="hidden" id="schedule_history_id" name="schedule_history_id" value="{{ old('schedule_history_id') }}">

            <!-- Description -->
            <div class="mb-6">
                <x-input-label for="description" :value="__('Deskripsi Masalah')" />
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Photos -->
            <div class="mb-6">
                <x-input-label for="photos" :value="__('Foto (Min 1, Max 3)')" />
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="photos" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload foto</span>
                                <input id="photos" name="photos[]" type="file" class="sr-only" multiple accept="image/*" required>
                            </label>
                            <p class="pl-1">atau drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            PNG, JPG, GIF up to 2MB
                        </p>
                    </div>
                </div>
                <div id="photo-preview" class="mt-4 grid grid-cols-3 gap-4"></div>
                <x-input-error :messages="$errors->get('photos')" class="mt-2" />
                @if($errors->has('photos.*'))
                    <div class="mt-2">
                        @foreach($errors->get('photos.*') as $photoErrors)
                            @foreach($photoErrors as $error)
                                <p class="text-sm text-red-600">{{ $error }}</p>
                            @endforeach
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-end">
                <x-button>
                    Simpan Laporan
                </x-button>
            </div>
        </form>
    </x-card>
</div>

@push('scripts')
<style>
    .toggle-checkbox:checked {
        transform: translateX(100%);
        border-color: #10b981;
    }
    
    .toggle-checkbox:checked + .toggle-label {
        background-color: #10b981;
    }
    
    .toggle-label {
        border: 2px solid #d1d5db;
    }
    
    .toggle-checkbox {
        top: 0;
        left: 0;
        z-index: 2;
    }
    
    .toggle-checkbox:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }

    /* Unit search dropdown styling */
    #unit_dropdown {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .unit-item:hover {
        background-color: #f9fafb;
    }
    
    .unit-item {
        transition: background-color 0.15s ease;
    }
    
    #unit_search:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    /* Hide scrollbar but keep functionality */
    #unit_dropdown::-webkit-scrollbar {
        width: 6px;
    }
    
    #unit_dropdown::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    #unit_dropdown::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    #unit_dropdown::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* Photo preview styling */
    .photo-remove-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        background-color: #dc2626;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        border: none;
        cursor: pointer;
        opacity: 0;
        transition: all 0.2s ease;
        z-index: 10;
    }
    
    .photo-remove-btn:hover {
        background-color: #b91c1c;
        transform: scale(1.1);
    }
    
    .photo-preview-item:hover .photo-remove-btn {
        opacity: 1;
    }
    
    .photo-preview-item {
        position: relative;
        transition: transform 0.15s ease;
    }
    
    .photo-preview-item:hover {
        transform: scale(1.02);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const unitSearchInput = document.getElementById('unit_search');
        const unitIdInput = document.getElementById('unit_id');
        const unitDropdown = document.getElementById('unit_dropdown');
        const dateInput = document.getElementById('date_reported');
        const timeInput = document.getElementById('time_reported');
        const driverSelect = document.getElementById('driver_id');
        const shiftSelect = document.getElementById('shift');
        const onScheduleCheckbox = document.getElementById('on_schedule');
        const scheduleHistoryIdInput = document.getElementById('schedule_history_id');
        const driverStatusDiv = document.getElementById('driver-status');
        const photoInput = document.getElementById('photos');
        const photoPreview = document.getElementById('photo-preview');
        const needsRepairToggle = document.getElementById('needs_repair');
        const needsRepairText = document.getElementById('needs_repair_text');
        
        // Store schedule data globally
        let scheduleData = null;
        
        // Initialize selected files array
        window.selectedFiles = [];
        
        // Unit search functionality
        function setupUnitSearch() {
            // Show/hide dropdown
            unitSearchInput.addEventListener('focus', function() {
                unitDropdown.classList.remove('hidden');
                filterUnits();
            });

            unitSearchInput.addEventListener('blur', function() {
                // Delay hiding to allow clicking on options
                setTimeout(() => {
                    unitDropdown.classList.add('hidden');
                }, 200);
            });

            // Search functionality
            unitSearchInput.addEventListener('input', function() {
                filterUnits();
                unitDropdown.classList.remove('hidden');
                
                // Clear selection if input doesn't match any unit
                if (this.value === '') {
                    unitIdInput.value = '';
                }
            });
        }

        // Filter units based on search input
        function filterUnits() {
            const searchTerm = unitSearchInput.value.toLowerCase();
            const unitItems = document.querySelectorAll('.unit-item');
            
            unitItems.forEach(item => {
                const unitNumber = item.getAttribute('data-unit-number').toLowerCase();
                const plateNumber = item.getAttribute('data-plate-number').toLowerCase();
                
                if (unitNumber.includes(searchTerm) || plateNumber.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Select unit function (called from onclick)
        window.selectUnit = function(unitId, unitNumber, plateNumber) {
            unitIdInput.value = unitId;
            unitSearchInput.value = unitNumber + ' - ' + plateNumber;
            unitDropdown.classList.add('hidden');
            
            // Trigger load driver from schedule when unit is selected
            loadDriverFromSchedule();
        };
        
        // Function to load drivers from schedule
        function loadDriverFromSchedule() {
            const unitId = unitIdInput.value;
            const date = dateInput.value;
            
            if (!unitId || !date) {
                return;
            }
            
            // Show loading state
            driverStatusDiv.textContent = 'Mencari jadwal untuk unit ini...';
            // Use readonly style instead of disabling
            driverSelect.setAttribute('readonly', 'readonly');
            driverSelect.style.backgroundColor = '#f9fafb';
            driverSelect.style.cursor = 'not-allowed';
            shiftSelect.disabled = true;
            onScheduleCheckbox.disabled = true;
            
            // Fetch schedules for unit and date
            fetch('{{ route('unit-problems.get-driver-from-schedule') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    unit_id: unitId,
                    date: date
                })
            })
            .then(response => response.json())
            .then(data => {
                // Store schedule data globally
                scheduleData = data;
                
                // Clear previous options
                driverSelect.innerHTML = '';
                
                if (data.has_schedules) {
                    // Enable shift selection first
                    shiftSelect.disabled = false;
                    
                    // Update driver status
                    driverStatusDiv.innerHTML = '<span class="text-green-600">Jadwal ditemukan untuk unit ini. Silakan pilih shift terlebih dahulu.</span>';
                    
                    // Set default option for driver
                    driverSelect.innerHTML = '<option value="">Pilih Shift terlebih dahulu</option>';
                    // Use readonly style instead of disabling
                    driverSelect.setAttribute('readonly', 'readonly');
                    driverSelect.style.backgroundColor = '#f9fafb';
                    driverSelect.style.cursor = 'not-allowed';
                    
                    // Reset on_schedule and schedule_history_id
                    onScheduleCheckbox.checked = false;
                    scheduleHistoryIdInput.value = '';
                } else {
                    // No schedules found, load drivers assigned to this unit
                    loadDriversForUnit(unitId);
                    
                    driverStatusDiv.innerHTML = '<span class="text-yellow-600">Tidak ada jadwal untuk unit ini pada tanggal tersebut.</span>';
                    // Remove readonly attributes
                    driverSelect.removeAttribute('readonly');
                    driverSelect.style.backgroundColor = '';
                    driverSelect.style.cursor = '';
                    shiftSelect.disabled = false;
                    onScheduleCheckbox.disabled = false;
                    onScheduleCheckbox.checked = false;
                    scheduleHistoryIdInput.value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                driverStatusDiv.innerHTML = '<span class="text-red-600">Terjadi kesalahan saat mencari jadwal</span>';
                
                // Load drivers assigned to this unit as fallback
                loadDriversForUnit(unitId);
            });
        }
        
        // Function to update driver based on selected shift
        function updateDriverBasedOnShift() {
            if (!scheduleData || !scheduleData.has_schedules) {
                return;
            }
            
            const selectedShift = shiftSelect.value;
            if (!selectedShift) {
                // No shift selected, disable driver selection
                driverSelect.innerHTML = '<option value="">Pilih Shift terlebih dahulu</option>';
                // Use readonly style instead of disabling
                driverSelect.setAttribute('readonly', 'readonly');
                driverSelect.style.backgroundColor = '#f9fafb';
                driverSelect.style.cursor = 'not-allowed';
                onScheduleCheckbox.checked = false;
                scheduleHistoryIdInput.value = '';
                return;
            }
            
            // Find schedule for selected shift
            const matchingSchedule = scheduleData.schedules.find(schedule => 
                schedule.shift.toLowerCase() === selectedShift.toLowerCase()
            );
            
            // Clear previous options
            driverSelect.innerHTML = '';
            
            if (matchingSchedule) {
                // Driver found for this shift
                const option = document.createElement('option');
                option.value = matchingSchedule.driver.id;
                option.textContent = matchingSchedule.driver.name;
                option.selected = true;
                driverSelect.appendChild(option);
                
                // Set on schedule
                onScheduleCheckbox.checked = matchingSchedule.on_schedule;
                scheduleHistoryIdInput.value = matchingSchedule.schedule_history_id;
                
                driverStatusDiv.innerHTML = '<span class="text-green-600">Pengemudi ditemukan dalam jadwal untuk shift ini</span>';
                // Make the driver select readonly-like but not disabled
                driverSelect.setAttribute('readonly', 'readonly');
                driverSelect.style.backgroundColor = '#f9fafb';
                driverSelect.style.cursor = 'not-allowed';
                onScheduleCheckbox.disabled = true;
            } else {
                // No driver found for this shift
                driverStatusDiv.innerHTML = '<span class="text-yellow-600">Tidak ada pengemudi dalam jadwal untuk shift ini</span>';
                
                // Load drivers assigned to this unit
                loadDriversForUnit(unitIdInput.value);
                
                // Remove readonly attributes
                driverSelect.removeAttribute('readonly');
                driverSelect.style.backgroundColor = '';
                driverSelect.style.cursor = '';
                
                onScheduleCheckbox.checked = false;
                scheduleHistoryIdInput.value = '';
                onScheduleCheckbox.disabled = false;
            }
        }
        
        // Function to load drivers for a unit
        function loadDriversForUnit(unitId) {
            fetch(`{{ url('unit-problems/drivers-for-unit') }}/${unitId}`)
                .then(response => response.json())
                .then(drivers => {
                    // Clear previous options
                    driverSelect.innerHTML = '<option value="">Pilih Pengemudi</option>';
                    
                    if (drivers.length === 0) {
                        driverStatusDiv.innerHTML = '<span class="text-red-600">Tidak ada pengemudi yang ditugaskan untuk unit ini</span>';
                    } else {
                        // Add drivers to select
                        drivers.forEach(driver => {
                            const option = document.createElement('option');
                            option.value = driver.id;
                            option.textContent = driver.name;
                            driverSelect.appendChild(option);
                        });
                        
                        // Remove readonly attributes
                        driverSelect.removeAttribute('readonly');
                        driverSelect.style.backgroundColor = '';
                        driverSelect.style.cursor = '';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    driverStatusDiv.innerHTML = '<span class="text-red-600">Terjadi kesalahan saat memuat pengemudi</span>';
                });
        }
        
        // Initialize unit search
        setupUnitSearch();
        
        // Event listeners for date changes (unit is now handled by search)
        dateInput.addEventListener('change', loadDriverFromSchedule);
        
        // Event listener for shift changes
        shiftSelect.addEventListener('change', updateDriverBasedOnShift);
        
        // Needs repair toggle functionality
        needsRepairToggle.addEventListener('change', function() {
            needsRepairText.textContent = this.checked ? 'Ya' : 'Tidak';
        });
        
        // Photo preview functionality
        photoInput.addEventListener('change', function() {
            photoPreview.innerHTML = '';
            
            if (this.files.length > 3) {
                alert('Maksimal 3 foto yang diperbolehkan.');
                this.value = '';
                return;
            }
            
            // Convert FileList to Array for easier manipulation
            window.selectedFiles = Array.from(this.files);
            renderPhotoPreview();
        });
        
        // Function to render photo preview
        function renderPhotoPreview() {
            photoPreview.innerHTML = '';
            
            if (window.selectedFiles.length === 0) {
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'col-span-3 text-center text-gray-500 text-sm py-4';
                emptyMessage.textContent = 'Tidak ada foto yang dipilih';
                photoPreview.appendChild(emptyMessage);
                return;
            }
            
            window.selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'photo-preview-item relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'h-32 w-full object-cover rounded-md';
                    
                    // Add remove button
                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'photo-remove-btn';
                    removeButton.innerHTML = '<i class="fas fa-times"></i>';
                    removeButton.onclick = () => removePhoto(index);
                    removeButton.title = 'Hapus foto';
                    
                    div.appendChild(img);
                    div.appendChild(removeButton);
                    photoPreview.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            });
        }
        
        // Function to remove photo from selection
        function removePhoto(index) {
            window.selectedFiles.splice(index, 1);
            
            // Update the file input
            const dt = new DataTransfer();
            window.selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            photoInput.files = dt.files;
            
            // Re-render preview
            renderPhotoPreview();
        }
    });
</script>
@endpush

@endsection

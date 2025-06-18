@extends('modules.admin.layouts.main')

@section('title', 'Edit Laporan Masalah Unit')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Edit Laporan Masalah Unit</x-slot>
        <x-slot name="actions">
            <a href="{{ route('unit-problems.show', $unitProblem) }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-gray-600 border border-transparent rounded-md hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25">
                <i class="mr-2 fas fa-arrow-left"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('unit-problems.update', $unitProblem) }}" method="POST" enctype="multipart/form-data" id="unit-problem-edit-form">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 mb-6 md:grid-cols-2">
                <!-- Unit -->
                <div>
                    <x-input-label for="unit_search" :value="__('Unit')" />
                    <div class="relative">
                        <input type="text" 
                               id="unit_search" 
                               placeholder="Cari unit..."
                               class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                               autocomplete="off"
                               value="{{ old('unit_id', $unitProblem->unit_id) ? $units->firstWhere('id', old('unit_id', $unitProblem->unit_id))?->unit_number . ' - ' . $units->firstWhere('id', old('unit_id', $unitProblem->unit_id))?->plate_number : '' }}">
                        <input type="hidden" name="unit_id" id="unit_id" value="{{ old('unit_id', $unitProblem->unit_id) }}">
                        
                        <!-- Dropdown results -->
                        <div id="unit_dropdown" class="absolute z-10 hidden w-full overflow-y-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-60">
                            <div id="unit_list" class="p-2 space-y-1">
                                @foreach($units as $unit)
                                    <div class="flex items-center p-2 rounded cursor-pointer unit-item hover:bg-gray-50" 
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

                <!-- Driver -->
                <div>
                    <x-input-label for="driver_id" :value="__('Pengemudi')" />
                    <select id="driver_id" name="driver_id" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Pengemudi</option>
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ old('driver_id', $unitProblem->driver_id) == $driver->id ? 'selected' : '' }}>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('driver_id')" class="mt-2" />
                </div>

                <!-- Date Reported -->
                <div>
                    <x-input-label for="date_reported" :value="__('Tanggal Laporan')" />
                    <input id="date_reported" type="date" name="date_reported" value="{{ old('date_reported', $unitProblem->date_reported->format('Y-m-d')) }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                    <x-input-error :messages="$errors->get('date_reported')" class="mt-2" />
                </div>

                <!-- Time Reported -->
                <div>
                    <x-input-label for="time_reported" :value="__('Waktu Laporan')" />
                    <input id="time_reported" type="time" name="time_reported" value="{{ old('time_reported', \Carbon\Carbon::parse($unitProblem->time_reported)->format('H:i')) }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required />
                    <x-input-error :messages="$errors->get('time_reported')" class="mt-2" />
                </div>

                <!-- Shift -->
                <div>
                    <x-input-label for="shift" :value="__('Shift')" />
                    <select id="shift" name="shift" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">Pilih Shift</option>
                        @foreach($shifts as $shift)
                            <option value="{{ $shift }}" {{ old('shift', $unitProblem->shift) == $shift ? 'selected' : '' }}>
                                {{ $shift }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('shift')" class="mt-2" />
                </div>

                <!-- Location -->
                <div>
                    <x-input-label for="location" :value="__('Lokasi (Opsional)')" />
                    <input id="location" type="text" name="location" value="{{ old('location', $unitProblem->location) }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>
            </div>

            <!-- On Schedule -->
            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="on_schedule" value="1" class="text-indigo-600 border-gray-300 rounded shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ old('on_schedule', $unitProblem->on_schedule) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-600">Dalam Jadwal</span>
                </label>
                <x-input-error :messages="$errors->get('on_schedule')" class="mt-2" />
            </div>

            <!-- Hidden field for schedule history ID -->
            <input type="hidden" id="schedule_history_id" name="schedule_history_id" value="{{ old('schedule_history_id', $unitProblem->schedule_history_id) }}">

            <!-- Needs Repair Toggle -->
            <div class="mb-6">
                <label class="flex items-center">
                    <span class="mr-3 text-sm font-medium text-gray-700">Butuh Perbaikan</span>
                    <div class="relative inline-block w-10 mr-2 align-middle transition duration-200 ease-in select-none">
                        <input type="checkbox" name="needs_repair" id="needs_repair" value="1" 
                               class="absolute block w-6 h-6 transition-transform duration-200 ease-in-out transform bg-white border-4 rounded-full appearance-none cursor-pointer toggle-checkbox"
                               {{ old('needs_repair', $unitProblem->needs_repair) ? 'checked' : '' }}>
                        <label for="needs_repair" 
                               class="block h-6 overflow-hidden transition-colors duration-200 ease-in-out bg-gray-300 rounded-full cursor-pointer toggle-label"></label>
                    </div>
                    <span id="needs_repair_text" class="text-sm text-gray-600">{{ old('needs_repair', $unitProblem->needs_repair) ? 'Ya' : 'Tidak' }}</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Centang jika masalah ini memerlukan perbaikan unit</p>
                <x-input-error :messages="$errors->get('needs_repair')" class="mt-2" />
            </div>

            <!-- Description -->
            <div class="mb-6">
                <x-input-label for="description" :value="__('Deskripsi Masalah')" />
                <textarea id="description" name="description" rows="4" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('description', $unitProblem->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Current Photos -->
            <div class="mb-6">
                <x-input-label :value="__('Foto Saat Ini')" />
                <div class="grid grid-cols-1 gap-4 mt-2 md:grid-cols-3">
                    @forelse($unitProblem->photos as $photo)
                        <div class="relative group">
                            <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Foto Masalah Unit" class="object-cover w-full h-32 rounded-md">
                            <div class="absolute inset-0 flex items-center justify-center transition-all duration-200 bg-black bg-opacity-0 opacity-0 group-hover:bg-opacity-30 group-hover:opacity-100">
                                <form action="{{ route('unit-problems.delete-photo', $photo->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-white bg-red-600 rounded-full hover:bg-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">Tidak ada foto</p>
                    @endforelse
                </div>
            </div>

            <!-- Add New Photos -->
            <div class="mb-6">
                <x-input-label for="photos" :value="__('Tambah Foto Baru (Max 3 Total)')" />
                <div class="flex justify-center px-6 pt-5 pb-6 mt-1 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="w-12 h-12 mx-auto text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="photos" class="relative font-medium text-indigo-600 bg-white rounded-md cursor-pointer hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                <span>Upload foto</span>
                                <input id="photos" name="photos[]" type="file" class="sr-only" multiple accept="image/*">
                            </label>
                            <p class="pl-1">atau drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">
                            PNG, JPG, GIF up to 2MB
                        </p>
                    </div>
                </div>
                <div id="photo-preview" class="grid grid-cols-3 gap-4 mt-4"></div>
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
                <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-md hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25">
                    Simpan Perubahan
                </button>
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
        const photoInput = document.getElementById('photos');
        const photoPreview = document.getElementById('photo-preview');
        const currentPhotoCount = {{ $unitProblem->photos->count() }};
        const needsRepairToggle = document.getElementById('needs_repair');
        const needsRepairText = document.getElementById('needs_repair_text');
        
        window.selectedFiles = [];
        if (unitSearchInput && unitDropdown) {
            unitSearchInput.addEventListener('focus', function() {
                unitDropdown.classList.remove('hidden');
                filterUnits();
            });

            unitSearchInput.addEventListener('blur', function() {
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
        };
        
        // Needs repair toggle functionality
        if (needsRepairToggle && needsRepairText) {
            needsRepairToggle.addEventListener('change', function() {
                needsRepairText.textContent = this.checked ? 'Ya' : 'Tidak';
            });
        }
        
        // Photo preview functionality
        if (photoInput && photoPreview) {
            photoInput.addEventListener('change', function() {
                photoPreview.innerHTML = '';
                
                if (this.files.length + currentPhotoCount > 3) {
                    alert('Total foto tidak boleh lebih dari 3. Anda sudah memiliki ' + currentPhotoCount + ' foto.');
                    this.value = '';
                    return;
                }
                
                // Convert FileList to Array for easier manipulation
                window.selectedFiles = Array.from(this.files);
                renderPhotoPreview();
            });
        }
        
        // Function to render photo preview
        function renderPhotoPreview() {
            photoPreview.innerHTML = '';
            
            if (window.selectedFiles.length === 0) {
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'col-span-3 text-center text-gray-500 text-sm py-4';
                emptyMessage.textContent = 'Tidak ada foto baru yang dipilih';
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
                    removeButton.onclick = () => window.removePhoto(index);
                    removeButton.title = 'Hapus foto';
                    
                    div.appendChild(img);
                    div.appendChild(removeButton);
                    photoPreview.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            });
        }
        
        // Function to remove photo from selection
        window.removePhoto = function(index) {
            window.selectedFiles.splice(index, 1);
            
            // Update the file input
            const dt = new DataTransfer();
            window.selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            photoInput.files = dt.files;
            
            // Re-render preview
            renderPhotoPreview();
        };
        
        // Simple form submission debugging - no prevention
        if (form) {
            form.addEventListener('submit', function(e) {
                // Check all required fields
                const unitId = unitIdInput?.value;
                const driverId = document.getElementById('driver_id')?.value;
                const description = document.getElementById('description')?.value;
                const dateReported = document.getElementById('date_reported')?.value;
                const timeReported = document.getElementById('time_reported')?.value;
                // Check if any required field is missing
                if (!unitId || !driverId || !description || !dateReported || !timeReported) {
                    e.preventDefault();
                    alert('Harap lengkapi semua field yang diperlukan:\n' + 
                          (!unitId ? '- Unit\n' : '') +
                          (!driverId ? '- Pengemudi\n' : '') +
                          (!description ? '- Deskripsi\n' : '') +
                          (!dateReported ? '- Tanggal\n' : '') +
                          (!timeReported ? '- Waktu\n' : ''));
                    return false;
                }
            });
        }
    });
</script>
@endpush

@endsection

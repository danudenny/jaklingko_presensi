@extends('modules.admin.layouts.main')

@section('title', 'Edit Laporan Masalah Unit')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Edit Laporan Masalah Unit</x-slot>
        <x-slot name="actions">
            <a href="{{ route('unit-problems.show', $unitProblem) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('unit-problems.update', $unitProblem) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Unit -->
                <div>
                    <x-input-label for="unit_id" :value="__('Unit')" />
                    <select id="unit_id" name="unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="">Pilih Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id', $unitProblem->unit_id) == $unit->id ? 'selected' : '' }}>
                                {{ $unit->unit_number }} - {{ $unit->plate_number }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
                </div>

                <!-- Driver -->
                <div>
                    <x-input-label for="driver_id" :value="__('Pengemudi')" />
                    <select id="driver_id" name="driver_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
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
                    <input id="date_reported" type="date" name="date_reported" :value="old('date_reported', $unitProblem->date_reported->format('Y-m-d'))" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('date_reported')" class="mt-2" />
                </div>

                <!-- Time Reported -->
                <div>
                    <x-input-label for="time_reported" :value="__('Waktu Laporan')" />
                    <input id="time_reported" type="time" name="time_reported" :value="old('time_reported', \Carbon\Carbon::parse($unitProblem->time_reported)->format('H:i'))" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('time_reported')" class="mt-2" />
                </div>

                <!-- Shift -->
                <div>
                    <x-input-label for="shift" :value="__('Shift')" />
                    <select id="shift" name="shift" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
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
                    <input id="location" type="text" name="location" :value="old('location', $unitProblem->location)" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>
            </div>

            <!-- On Schedule -->
            <div class="mb-6">
                <label class="inline-flex items-center">
                    <input type="checkbox" name="on_schedule" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ old('on_schedule', $unitProblem->on_schedule) ? 'checked' : '' }}>
                    <span class="ml-2 text-sm text-gray-600">Dalam Jadwal</span>
                </label>
                <x-input-error :messages="$errors->get('on_schedule')" class="mt-2" />
            </div>

            <!-- Description -->
            <div class="mb-6">
                <x-input-label for="description" :value="__('Deskripsi Masalah')" />
                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('description', $unitProblem->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Current Photos -->
            <div class="mb-6">
                <x-input-label :value="__('Foto Saat Ini')" />
                <div class="mt-2 grid grid-cols-1 md:grid-cols-3 gap-4">
                    @forelse($unitProblem->photos as $photo)
                        <div class="relative group">
                            <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Foto Masalah Unit" class="h-32 w-full object-cover rounded-md">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                                <form action="{{ route('unit-problems.delete-photo', $photo->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus foto ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700">
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
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="photos" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
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
                <div id="photo-preview" class="mt-4 grid grid-cols-3 gap-4"></div>
                <x-input-error :messages="$errors->get('photos')" class="mt-2" />
                <x-input-error :messages="$errors->get('photos.*')" class="mt-2" />
            </div>

            <div class="flex justify-end">
                <x-button>
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </x-card>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const photoInput = document.getElementById('photos');
        const photoPreview = document.getElementById('photo-preview');
        const currentPhotoCount = {{ $unitProblem->photos->count() }};
        
        photoInput.addEventListener('change', function() {
            photoPreview.innerHTML = '';
            
            if (this.files.length + currentPhotoCount > 3) {
                alert('Total foto tidak boleh lebih dari 3. Anda sudah memiliki ' + currentPhotoCount + ' foto.');
                this.value = '';
                return;
            }
            
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'h-32 w-full object-cover rounded-md';
                    
                    div.appendChild(img);
                    photoPreview.appendChild(div);
                }
                
                reader.readAsDataURL(file);
            }
        });
    });
</script>
@endpush

@endsection

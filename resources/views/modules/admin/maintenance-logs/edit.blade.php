@extends('modules.admin.layouts.main')

@section('title', 'Edit Log Perawatan')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
@endpush

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Edit Log Perawatan</x-slot>
        <x-slot name="actions">
            <a href="{{ route('maintenance-logs.show', $maintenanceLog) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <x-card>
        <form action="{{ route('maintenance-logs.update', $maintenanceLog) }}" method="POST" enctype="multipart/form-data" id="maintenanceLogForm">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Unit Information (readonly) -->
                <div>
                    <x-input-label for="unit" value="Unit" />
                    <input id="unit" class="mt-1 block w-full bg-gray-100" type="text" value="{{ $maintenanceLog->unit->unit_number }} - {{ $maintenanceLog->unit->plate_number }}" readonly />
                </div>

                <!-- Route Information (readonly) -->
                <div>
                    <x-input-label for="route" value="Rute" />
                    <input id="route" class="mt-1 block w-full bg-gray-100" type="text" value="{{ $maintenanceLog->route->route_number }} - {{ $maintenanceLog->route->name }}" readonly />
                </div>

                <!-- Driver Information (readonly) -->
                <div>
                    <x-input-label for="driver" value="Pengemudi" />
                    <input id="driver" class="mt-1 block w-full bg-gray-100" type="text" value="{{ $maintenanceLog->driver->name }}" readonly />
                </div>

                <!-- Date and Time (readonly) -->
                <div>
                    <x-input-label for="date_time" value="Tanggal & Waktu" />
                    <input id="date_time" class="mt-1 block w-full bg-gray-100" type="text" value="{{ $maintenanceLog->date_reported->format('d/m/Y') }} {{ $maintenanceLog->time_reported->format('H:i') }}" readonly />
                </div>

                <!-- Maintenance Type -->
                <div>
                    <x-input-label for="type" value="Tipe Perawatan" />
                    <select name="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="perbaikan" {{ $maintenanceLog->type === 'perbaikan' ? 'selected' : '' }}>Perbaikan</option>
                        <option value="penggantian" {{ $maintenanceLog->type === 'penggantian' ? 'selected' : '' }}>Penggantian</option>
                        <option value="tidak_ada_perbaikan" {{ $maintenanceLog->type === 'tidak_ada_perbaikan' ? 'selected' : '' }}>Tidak Ada Perbaikan</option>
                    </select>
                    <x-input-error for="type" class="mt-2" />
                </div>

                <!-- Status -->
                <div>
                    <x-input-label for="status" value="Status" />
                    <select name="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                        <option value="pending" {{ $maintenanceLog->status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ $maintenanceLog->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ $maintenanceLog->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                    <x-input-error for="status" class="mt-2" />
                </div>

                <!-- Parts -->
                <div>
                    <x-input-label for="parts" value="Suku Cadang" />
                    <input id="parts" class="mt-1 block w-full" type="text" name="parts" :value="old('parts', $maintenanceLog->parts)" required />
                    <x-input-error for="parts" class="mt-2" />
                </div>

                <!-- Category (only for penggantian) -->
                <div id="categoryContainer" class="{{ $maintenanceLog->type !== 'penggantian' ? 'hidden' : '' }}">
                    <x-input-label for="category" value="Kategori" />
                    <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <option value="">Pilih Kategori</option>
                        <option value="baru" {{ $maintenanceLog->category === 'baru' ? 'selected' : '' }}>Baru</option>
                        <option value="bekas" {{ $maintenanceLog->category === 'bekas' ? 'selected' : '' }}>Bekas</option>
                    </select>
                    <x-input-error for="category" class="mt-2" />
                </div>

                <!-- Source of Sparepart -->
                <div>
                    <x-input-label for="source_of_sparepart" value="Sumber Suku Cadang" />
                    <input id="source_of_sparepart" class="mt-1 block w-full" type="text" name="source_of_sparepart" :value="old('source_of_sparepart', $maintenanceLog->source_of_sparepart)" required />
                    <x-input-error for="source_of_sparepart" class="mt-2" />
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <x-input-label for="description" value="Deskripsi" />
                <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>{{ old('description', $maintenanceLog->description) }}</textarea>
                <x-input-error for="description" class="mt-2" />
            </div>

            <!-- Costs -->
            <div class="mt-6">
                <x-input-label value="Biaya" />
                <div id="costsContainer" class="space-y-2">
                    @if($maintenanceLog->costs && count($maintenanceLog->costs) > 0)
                        @foreach($maintenanceLog->costs as $index => $cost)
                            <div class="flex items-center space-x-2">
                                <input class="w-full" type="text" name="costs[{{ $index }}][description]" placeholder="Deskripsi Biaya" value="{{ $cost['description'] }}" />
                                <input class="w-full" type="number" name="costs[{{ $index }}][amount]" placeholder="Jumlah (Rp)" value="{{ $cost['amount'] }}" min="0" />
                                <button type="button" class="px-2 py-1 bg-red-500 text-white rounded" onclick="removeRow(this)">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    @else
                        <div class="flex items-center space-x-2">
                            <input class="w-full" type="text" name="costs[0][description]" placeholder="Deskripsi Biaya" />
                            <input class="w-full" type="number" name="costs[0][amount]" placeholder="Jumlah (Rp)" min="0" />
                            <button type="button" class="px-2 py-1 bg-red-500 text-white rounded" onclick="removeRow(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    @endif
                </div>
                <button type="button" id="addCost" class="mt-2 inline-flex items-center px-3 py-1 bg-green-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-600">
                    <i class="fas fa-plus mr-1"></i> Tambah Biaya
                </button>
            </div>

            <!-- Existing Photos -->
            @if($maintenanceLog->photos->count() > 0)
                <div class="mt-6">
                    <x-input-label value="Foto Saat Ini" />
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($maintenanceLog->photos as $photo)
                            <div class="relative" id="photo-{{ $photo->id }}">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Maintenance Photo" class="w-full h-32 object-cover rounded-lg">
                                <button type="button" class="absolute top-2 right-2 bg-red-500 text-white p-1 rounded-full" onclick="deletePhoto({{ $photo->id }})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Add New Photos -->
            <div class="mt-6">
                <x-input-label for="photos" value="Tambah Foto Baru (Maksimal 3 Total)" />
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
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </x-card>
</div>
@endsection

@push('scripts')
<script>
    // Handle maintenance type change
    document.getElementById('type').addEventListener('change', function() {
        const categoryContainer = document.getElementById('categoryContainer');
        const partsInput = document.getElementById('parts');
        const sourceInput = document.getElementById('source_of_sparepart');
        
        if (this.value === 'penggantian') {
            categoryContainer.classList.remove('hidden');
            document.getElementById('category').setAttribute('required', 'required');
            partsInput.setAttribute('required', 'required');
            sourceInput.setAttribute('required', 'required');
        } else if (this.value === 'tidak_ada_perbaikan') {
            categoryContainer.classList.add('hidden');
            document.getElementById('category').removeAttribute('required');
            partsInput.removeAttribute('required');
            sourceInput.removeAttribute('required');
            if (partsInput.value === '' || partsInput.value === 'Tidak ada') {
                partsInput.value = 'Tidak ada';
            }
            if (sourceInput.value === '' || sourceInput.value === 'Tidak diperlukan') {
                sourceInput.value = 'Tidak diperlukan';
            }
        } else {
            categoryContainer.classList.add('hidden');
            document.getElementById('category').removeAttribute('required');
            partsInput.setAttribute('required', 'required');
            sourceInput.setAttribute('required', 'required');
        }
    });

    // Handle costs
    let costIndex = {{ $maintenanceLog->costs ? count($maintenanceLog->costs) : 1 }};

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

    // Delete photo
    function deletePhoto(photoId) {
        if (confirm('Apakah Anda yakin ingin menghapus foto ini?')) {
            fetch(`/maintenance-logs/photos/${photoId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById(`photo-${photoId}`).remove();
                } else {
                    alert('Gagal menghapus foto.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menghapus foto.');
            });
        }
    }

    // Image preview
    function previewImages(event) {
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '';

        const files = event.target.files;
        const currentPhotoCount = {{ $maintenanceLog->photos->count() }};
        
        if (files.length + currentPhotoCount > 3) {
            alert(`Maksimal 3 foto total. Anda sudah memiliki ${currentPhotoCount} foto.`);
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

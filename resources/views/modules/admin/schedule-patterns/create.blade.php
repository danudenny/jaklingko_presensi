@extends('modules.admin.layouts.main')

@section('title', 'Tambah Pola Jadwal')

@push('styles')
<style>
    .pattern-cell {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin: 0 2px;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
    }
    .pattern-pagi {
        background-color: #e3f2fd;
        color: #1e88e5;
        border: 2px solid #1e88e5;
    }
    .pattern-siang {
        background-color: #fff8e1;
        color: #ffa000;
        border: 2px solid #ffa000;
    }
    .pattern-none {
        background-color: #f5f5f5;
        color: #9e9e9e;
        border: 2px solid #9e9e9e;
    }
    .pattern-cell.selected {
        box-shadow: 0 0 0 2px #4caf50;
    }
</style>
@endpush

@section('content')
<div class="w-full px-4 container-fluid">
    <x-page-title>
        <x-slot name="title">
            <div class="flex items-center">
                <i class="mr-3 text-2xl text-teal-500 fas fa-clipboard-list"></i>
                <div>
                    <h1 class="text-2xl font-bold">Tambah Pola Jadwal</h1>
                    <p class="text-sm font-thin text-gray-500">Buat pola jadwal baru</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedule-patterns.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600">
                <i class="mr-2 fas fa-arrow-left"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <!-- Create Form -->
    <div class="p-4 bg-white rounded-lg shadow">
        <form action="{{ route('schedule-patterns.store') }}" method="POST" id="patternForm">
            @csrf
            
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nama Pola</label>
                        <input type="text" name="name" id="name" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" value="{{ old('name') }}" required>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="type" class="block text-sm font-medium text-gray-700">Tipe Pola</label>
                        <select name="type" id="type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            <option value="valid" {{ old('type') == 'valid' ? 'selected' : '' }}>Valid</option>
                            <option value="invalid" {{ old('type') == 'invalid' ? 'selected' : '' }}>Invalid</option>
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="driver_type" class="block text-sm font-medium text-gray-700">Tipe Pengemudi</label>
                        <select name="driver_type" id="driver_type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            <option value="all" {{ old('driver_type') == 'all' ? 'selected' : '' }}>Semua</option>
                            <option value="batangan" {{ old('driver_type') == 'batangan' ? 'selected' : '' }}>Batangan</option>
                            <option value="cadangan" {{ old('driver_type') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                        </select>
                        @error('driver_type')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="days" class="block text-sm font-medium text-gray-700">Jumlah Hari</label>
                        <select name="days" id="days" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                            @for ($i = 1; $i <= 15; $i++)
                                <option value="{{ $i }}" {{ old('days') == $i ? 'selected' : '' }}>{{ $i }} hari</option>
                            @endfor
                        </select>
                        @error('days')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea name="description" id="description" rows="3" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label for="is_active" class="block ml-2 text-sm font-medium text-gray-700">Aktif</label>
                        </div>
                        @error('is_active')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" name="create_complement" id="create_complement" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" value="1" {{ old('create_complement', 1) ? 'checked' : '' }}>
                            <label for="create_complement" class="block ml-2 text-sm font-medium text-gray-700">Buat Pola Pelengkap</label>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Secara otomatis membuat pola pelengkap untuk memastikan setiap hari memiliki shift Pagi dan Siang. Pola P akan dilengkapi dengan S, dan sebaliknya.</p>
                        @error('create_complement')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Pola Shift</label>
                        <div class="p-4 mt-2 border border-gray-300 rounded-md">
                            <div class="mb-4">
                                <div class="flex items-center mb-2">
                                    <div class="pattern-cell pattern-pagi" data-shift="P">P</div>
                                    <span class="ml-2 text-sm">Pagi</span>
                                </div>
                                <div class="flex items-center mb-2">
                                    <div class="pattern-cell pattern-siang" data-shift="S">S</div>
                                    <span class="ml-2 text-sm">Siang</span>
                                </div>
                                <div class="flex items-center">
                                    <div class="pattern-cell pattern-none" data-shift="N">-</div>
                                    <span class="ml-2 text-sm">Tidak Ada</span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <label class="block mb-2 text-sm font-medium text-gray-700">Pilih shift untuk setiap hari:</label>
                                <div id="patternBuilder" class="flex flex-wrap gap-2">
                                    <!-- Pattern cells will be generated here -->
                                </div>
                                
                                <div id="patternInputs">
                                    <!-- Hidden inputs will be generated here -->
                                </div>
                                
                                @error('pattern')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                @error('pattern.*')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end mt-6">
                <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600">
                    <i class="mr-2 fas fa-save"></i>
                    Simpan Pola Jadwal
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const daysSelect = document.getElementById('days');
        const patternBuilder = document.getElementById('patternBuilder');
        const patternInputs = document.getElementById('patternInputs');
        
        // Initial pattern generation
        generatePattern(daysSelect.value);
        
        // Update pattern when days change
        daysSelect.addEventListener('change', function() {
            generatePattern(this.value);
        });
        
        function generatePattern(days) {
            patternBuilder.innerHTML = '';
            patternInputs.innerHTML = '';
            
            for (let i = 0; i < days; i++) {
                // Create day label
                const dayLabel = document.createElement('div');
                dayLabel.className = 'flex flex-col items-center';
                
                const dayNumber = document.createElement('div');
                dayNumber.className = 'mb-1 text-xs font-medium text-gray-500';
                dayNumber.textContent = `Hari ${i + 1}`;
                
                const cellContainer = document.createElement('div');
                cellContainer.className = 'pattern-day';
                cellContainer.dataset.day = i;
                
                // Default to Pagi for first cell
                const defaultShift = i === 0 ? 'P' : 'N';
                
                // Create cell
                const cell = document.createElement('div');
                cell.className = `pattern-cell pattern-${defaultShift === 'P' ? 'pagi' : (defaultShift === 'S' ? 'siang' : 'none')} selected`;
                cell.dataset.shift = defaultShift;
                cell.textContent = defaultShift === 'N' ? '-' : defaultShift;
                
                // Create hidden input
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `pattern[${i}]`;
                input.value = defaultShift;
                patternInputs.appendChild(input);
                
                // Add click event to cycle through shifts
                cell.addEventListener('click', function() {
                    const currentShift = this.dataset.shift;
                    let nextShift;
                    
                    if (currentShift === 'P') nextShift = 'S';
                    else if (currentShift === 'S') nextShift = 'N';
                    else nextShift = 'P';
                    
                    // Update cell
                    this.dataset.shift = nextShift;
                    this.className = `pattern-cell pattern-${nextShift === 'P' ? 'pagi' : (nextShift === 'S' ? 'siang' : 'none')} selected`;
                    this.textContent = nextShift === 'N' ? '-' : nextShift;
                    
                    // Update hidden input
                    input.value = nextShift;
                });
                
                cellContainer.appendChild(cell);
                dayLabel.appendChild(dayNumber);
                dayLabel.appendChild(cellContainer);
                patternBuilder.appendChild(dayLabel);
            }
        }
    });
</script>
@endpush
@endsection

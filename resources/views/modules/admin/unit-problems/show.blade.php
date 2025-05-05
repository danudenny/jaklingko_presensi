@extends('modules.admin.layouts.main')

@section('title', 'Detail Laporan Masalah Unit')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Detail Laporan Masalah Unit</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('unit-problems.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali
                </a>
                <a href="{{ route('unit-problems.edit', $unitProblem) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-edit mr-2"></i>
                    Edit
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="md:col-span-2">
            <x-card>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Masalah Unit</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Unit</h4>
                        <p class="text-base">{{ $unitProblem->unit->unit_number }} - {{ $unitProblem->unit->plate_number }}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Pengemudi</h4>
                        <p class="text-base">{{ $unitProblem->driver->name }}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Tanggal Laporan</h4>
                        <p class="text-base">{{ $unitProblem->date_reported->format('d/m/Y') }}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Waktu Laporan</h4>
                        <p class="text-base">{{ \Carbon\Carbon::parse($unitProblem->time_reported)->format('H:i') }}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Shift</h4>
                        <p class="text-base">{{ $unitProblem->shift ?? '-' }}</p>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Status Jadwal</h4>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $unitProblem->on_schedule == 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $unitProblem->on_schedule == 0 ? 'Dalam Jadwal' : 'Diluar Jadwal' }}
                        </span>
                    </div>
                    
                    @if($unitProblem->location)
                    <div>
                        <h4 class="text-sm font-medium text-gray-500">Lokasi</h4>
                        <p class="text-base">{{ $unitProblem->location }}</p>
                    </div>
                    @endif
                </div>
                
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Deskripsi Masalah</h4>
                    <div class="p-4 bg-gray-50 rounded-md">
                        <p class="text-base whitespace-pre-line">{{ $unitProblem->description }}</p>
                    </div>
                </div>
            </x-card>
        </div>
        
        <!-- Photos -->
        <div>
            <x-card>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Foto Masalah Unit</h3>
                
                <div class="grid grid-cols-1 gap-4">
                    @forelse($unitProblem->photos as $photo)
                        <div class="relative group">
                            <a href="{{ asset('storage/' . $photo->photo_path) }}" target="_blank" class="block">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Foto Masalah Unit" class="w-full h-auto rounded-md object-cover shadow-sm hover:shadow-md transition-shadow duration-200" onerror="this.onerror=null; this.src='{{ asset('images/placeholder.png') }}'; this.classList.add('border', 'border-red-300'); this.parentNode.insertAdjacentHTML('afterend', '<p class=\'text-red-500 text-xs mt-1\'>Foto tidak dapat ditampilkan. Klik untuk melihat.</p>');">
                            </a>
                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 bg-black bg-opacity-30 rounded-md">
                                <a href="{{ asset('storage/' . $photo->photo_path) }}" target="_blank" class="p-2 bg-white rounded-full shadow-md hover:bg-gray-100 transition-colors duration-200">
                                    <i class="fas fa-search-plus text-gray-700"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-6 bg-gray-50 rounded-md">
                            <i class="fas fa-image text-gray-400 text-4xl mb-2"></i>
                            <p class="text-gray-500">Tidak ada foto</p>
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection

@extends('modules.admin.layouts.main')

@section('title', 'Detail Pola Jadwal')

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
</style>
@endpush

@section('content')
<div class="w-full px-4 container-fluid">
    <x-page-title>
        <x-slot name="title">
            <div class="flex items-center">
                <i class="mr-3 text-2xl text-teal-500 fas fa-clipboard-list"></i>
                <div>
                    <h1 class="text-2xl font-bold">Detail Pola Jadwal</h1>
                    <p class="text-sm font-thin text-gray-500">Lihat detail pola jadwal</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('schedule-patterns.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600">
                    <i class="mr-2 fas fa-arrow-left"></i>
                    Kembali
                </a>
                <a href="{{ route('schedule-patterns.edit', $pattern->id) }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600">
                    <i class="mr-2 fas fa-edit"></i>
                    Edit
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <!-- Pattern Details -->
    <div class="p-4 bg-white rounded-lg shadow">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div>
                <h2 class="mb-4 text-lg font-semibold">Informasi Pola</h2>
                
                <div class="mb-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Nama Pola</p>
                            <p class="text-base">{{ $pattern->name }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tipe Pola</p>
                            <p>
                                @if ($pattern->type == 'valid')
                                    <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">Valid</span>
                                @else
                                    <span class="px-2 py-1 text-xs text-red-800 bg-red-100 rounded-full">Invalid</span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tipe Pengemudi</p>
                            <p>
                                @if ($pattern->driver_type == 'batangan')
                                    <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">Batangan</span>
                                @elseif ($pattern->driver_type == 'cadangan')
                                    <span class="px-2 py-1 text-xs text-purple-800 bg-purple-100 rounded-full">Cadangan</span>
                                @else
                                    <span class="px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">Semua</span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500">Jumlah Hari</p>
                            <p class="text-base">{{ $pattern->days }} hari</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500">Status</p>
                            <p>
                                @if ($pattern->is_active)
                                    <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">Aktif</span>
                                @else
                                    <span class="px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">Non-aktif</span>
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500">Tanggal Dibuat</p>
                            <p class="text-base">{{ $pattern->created_at->format('d M Y H:i') }}</p>
                        </div>
                        
                        <div>
                            <p class="text-sm font-medium text-gray-500">Terakhir Diperbarui</p>
                            <p class="text-base">{{ $pattern->updated_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                
                @if ($pattern->description)
                    <div class="mt-6">
                        <p class="mb-2 text-sm font-medium text-gray-500">Deskripsi</p>
                        <div class="p-3 bg-gray-50 rounded-md">
                            <p class="text-sm">{{ $pattern->description }}</p>
                        </div>
                    </div>
                @endif
            </div>
            
            <div>
                <h2 class="mb-4 text-lg font-semibold">Visualisasi Pola</h2>
                
                <div class="p-4 border border-gray-300 rounded-md">
                    <div class="mb-4">
                        <div class="flex items-center mb-2">
                            <div class="pattern-cell pattern-pagi">P</div>
                            <span class="ml-2 text-sm">Pagi</span>
                        </div>
                        <div class="flex items-center mb-2">
                            <div class="pattern-cell pattern-siang">S</div>
                            <span class="ml-2 text-sm">Siang</span>
                        </div>
                        <div class="flex items-center">
                            <div class="pattern-cell pattern-none">-</div>
                            <span class="ml-2 text-sm">Tidak Ada</span>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <p class="mb-3 text-sm font-medium text-gray-700">Pola Shift:</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($pattern->pattern as $index => $shift)
                                <div class="flex flex-col items-center">
                                    <div class="mb-1 text-xs font-medium text-gray-500">Hari {{ $index + 1 }}</div>
                                    <div class="pattern-cell pattern-{{ $shift == 'P' ? 'pagi' : ($shift == 'S' ? 'siang' : 'none') }}">
                                        {{ $shift == 'N' ? '-' : $shift }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <p class="mb-2 text-sm font-medium text-gray-700">Analisis Pola:</p>
                        <div class="p-3 bg-gray-50 rounded-md">
                            @php
                                $pagiCount = collect($pattern->pattern)->filter(fn($s) => $s == 'P')->count();
                                $siangCount = collect($pattern->pattern)->filter(fn($s) => $s == 'S')->count();
                                $noneCount = collect($pattern->pattern)->filter(fn($s) => $s == 'N')->count();
                                $workDays = $pagiCount + $siangCount;
                                $workPercentage = round(($workDays / $pattern->days) * 100);
                            @endphp
                            
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                <div>
                                    <p class="text-sm"><span class="font-medium">Shift Pagi:</span> {{ $pagiCount }} hari</p>
                                </div>
                                <div>
                                    <p class="text-sm"><span class="font-medium">Shift Siang:</span> {{ $siangCount }} hari</p>
                                </div>
                                <div>
                                    <p class="text-sm"><span class="font-medium">Tidak Ada Shift:</span> {{ $noneCount }} hari</p>
                                </div>
                                <div>
                                    <p class="text-sm"><span class="font-medium">Total Hari Kerja:</span> {{ $workDays }} hari ({{ $workPercentage }}%)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('modules.admin.layouts.main')

@section('title', 'Pola Jadwal')

@push('styles')
<style>
    .pattern-cell {
        width: 30px;
        height: 30px;
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
    }
    .pattern-siang {
        background-color: #fff8e1;
        color: #ffa000;
    }
    .pattern-none {
        background-color: #f5f5f5;
        color: #9e9e9e;
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
                    <h1 class="text-2xl font-bold">Pola Jadwal</h1>
                    <p class="text-sm font-thin text-gray-500">Manajemen pola jadwal valid dan invalid</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedule-patterns.create') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-500 hover:to-teal-600">
                <i class="mr-2 fas fa-plus"></i>
                Tambah Pola Jadwal
            </a>
        </x-slot>
    </x-page-title>

    <!-- Filter Section -->
    <div class="p-4 mb-4 bg-white rounded-lg shadow">
        <form action="{{ route('schedule-patterns.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
            <div class="w-full md:w-auto">
                <label for="type" class="block text-sm font-medium text-gray-700">Tipe Pola</label>
                <select id="type" name="type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="valid" {{ $type == 'valid' ? 'selected' : '' }}>Valid</option>
                    <option value="invalid" {{ $type == 'invalid' ? 'selected' : '' }}>Invalid</option>
                </select>
            </div>
            
            <div class="w-full md:w-auto">
                <label for="driver_type" class="block text-sm font-medium text-gray-700">Tipe Pengemudi</label>
                <select id="driver_type" name="driver_type" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="all" {{ $driverType == 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="batangan" {{ $driverType == 'batangan' ? 'selected' : '' }}>Batangan</option>
                    <option value="cadangan" {{ $driverType == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                </select>
            </div>
            
            <div class="w-full md:w-auto">
                <label for="days" class="block text-sm font-medium text-gray-700">Jumlah Hari</label>
                <select id="days" name="days" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    <option value="">Semua</option>
                    @for ($i = 1; $i <= 15; $i++)
                        <option value="{{ $i }}" {{ $days == $i ? 'selected' : '' }}>{{ $i }} hari</option>
                    @endfor
                </select>
            </div>
            
            <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded-md hover:bg-blue-600">
                <i class="mr-2 fas fa-filter"></i>
                Filter
            </button>
            
            <a href="{{ route('schedule-patterns.index') }}" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                <i class="mr-2 fas fa-redo"></i>
                Reset
            </a>
        </form>
    </div>

    <!-- Patterns List -->
    <div class="p-4 bg-white rounded-lg shadow">
        @if ($patterns->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="text-left bg-gray-100">
                            <th class="p-3">Nama</th>
                            <th class="p-3">Tipe</th>
                            <th class="p-3">Tipe Pengemudi</th>
                            <th class="p-3">Jumlah Hari</th>
                            <th class="p-3">Pola</th>
                            <th class="p-3">Status</th>
                            <th class="p-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($patterns as $pattern)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="p-3">{{ $pattern->name }}</td>
                                <td class="p-3">
                                    @if ($pattern->type == 'valid')
                                        <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">Valid</span>
                                    @else
                                        <span class="px-2 py-1 text-xs text-red-800 bg-red-100 rounded-full">Invalid</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    @if ($pattern->driver_type == 'batangan')
                                        <span class="px-2 py-1 text-xs text-blue-800 bg-blue-100 rounded-full">Batangan</span>
                                    @elseif ($pattern->driver_type == 'cadangan')
                                        <span class="px-2 py-1 text-xs text-purple-800 bg-purple-100 rounded-full">Cadangan</span>
                                    @else
                                        <span class="px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">Semua</span>
                                    @endif
                                </td>
                                <td class="p-3">{{ $pattern->days }} hari</td>
                                <td class="p-3">
                                    <div class="flex">
                                        @foreach ($pattern->pattern as $shift)
                                            @if ($shift == 'P')
                                                <div class="pattern-cell pattern-pagi" title="Pagi">P</div>
                                            @elseif ($shift == 'S')
                                                <div class="pattern-cell pattern-siang" title="Siang">S</div>
                                            @else
                                                <div class="pattern-cell pattern-none" title="Tidak Ada">-</div>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                                <td class="p-3">
                                    @if ($pattern->is_active)
                                        <span class="px-2 py-1 text-xs text-green-800 bg-green-100 rounded-full">Aktif</span>
                                    @else
                                        <span class="px-2 py-1 text-xs text-gray-800 bg-gray-100 rounded-full">Non-aktif</span>
                                    @endif
                                </td>
                                <td class="p-3">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('schedule-patterns.edit', $pattern->id) }}" class="p-2 text-blue-500 rounded hover:bg-blue-100" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('schedule-patterns.show', $pattern->id) }}" class="p-2 text-green-500 rounded hover:bg-green-100" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('schedule-patterns.toggle-active', $pattern->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="p-2 rounded {{ $pattern->is_active ? 'text-orange-500 hover:bg-orange-100' : 'text-green-500 hover:bg-green-100' }}" title="{{ $pattern->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="fas {{ $pattern->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('schedule-patterns.destroy', $pattern->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pola jadwal ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-500 rounded hover:bg-red-100" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $patterns->links() }}
            </div>
        @else
            <div class="p-6 text-center">
                <p class="text-gray-500">Tidak ada pola jadwal yang ditemukan.</p>
                <a href="{{ route('schedule-patterns.create') }}" class="inline-block px-4 py-2 mt-4 text-white bg-teal-500 rounded-md hover:bg-teal-600">
                    <i class="mr-2 fas fa-plus"></i>
                    Tambah Pola Jadwal
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

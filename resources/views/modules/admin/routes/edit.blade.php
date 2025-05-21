@extends('modules.admin.layouts.main')

@section('title', 'Edit Rute')

@section('content')
<div class="container mx-auto py-6">
    <x-page-title>
        <x-slot name="title">Edit Rute</x-slot>
        <x-slot name="actions">
            <a href="{{ route('routes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
            <a href="{{ route('routes.show', $route->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-eye mr-2"></i>
                Lihat Detail
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <!-- Route Edit Form -->
        <div class="md:col-span-1">
            <x-card>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Edit Informasi Rute</h2>
                
                <form id="edit-route-form" method="POST" action="{{ route('routes.update', $route->id) }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div>
                        <x-input-label for="route_number" value="Nomor Rute" />
                        <x-text-input id="route_number" name="route_number" type="text" class="mt-1 block w-full" value="{{ old('route_number', $route->route_number) }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('route_number')" />
                    </div>
                    
                    <div>
                        <x-input-label for="name" value="Nama Rute" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $route->name) }}" required />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>
                    
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="aktif" {{ old('status', $route->status) == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ old('status', $route->status) == 'nonaktif' ? 'selected' : '' }}>Non Aktif</option>
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('status')" />
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <x-secondary-button type="button" onclick="window.location.href='{{ route('routes.index') }}'">
                            Batal
                        </x-secondary-button>
                        <x-primary-button type="submit">
                            Simpan Perubahan
                        </x-primary-button>
                    </div>
                </form>
            </x-card>
        </div>

        <!-- Units Associated with this Route -->
        <div class="md:col-span-2">
            <x-card>
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Unit Terkait</h2>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $route->units->count() }} Unit
                    </span>
                </div>
                
                @if($route->units->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Unit</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($route->units as $index => $unit)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $unit->unit_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $unit->plate_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $unit->status == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $unit->status == 'aktif' ? 'Aktif' : 'Non Aktif' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="{{ route('units.show', $unit->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form class="inline-block" method="POST" action="{{ route('routes.units.remove', ['route' => $route->id, 'unit' => $unit->id]) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus unit ini dari rute?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-unlink"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada unit terkait</h3>
                    <p class="mt-1 text-sm text-gray-500">Belum ada unit yang terkait dengan rute ini.</p>
                </div>
                @endif

                <!-- Add Unit to Route -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-md font-medium text-gray-900 mb-4">Tambah Unit ke Rute</h3>
                    
                    <form id="add-unit-form" method="POST" action="{{ route('routes.units.add', $route->id) }}" class="space-y-4">
                        @csrf
                        
                        <div>
                            <x-input-label for="unit_id" value="Pilih Unit" />
                            <select id="unit_id" name="unit_id" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full" required>
                                <option value="">-- Pilih Unit --</option>
                                @foreach(\App\Models\Unit::whereNotIn('id', $route->units->pluck('id'))->where('status', 'aktif')->get() as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->unit_number }} - {{ $unit->plate_number }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('unit_id')" />
                        </div>
                        
                        <div class="flex justify-end">
                            <x-primary-button type="submit">
                                <i class="fas fa-plus mr-2"></i> Tambah Unit
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </x-card>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Edit route form submission
        const editRouteForm = document.getElementById('edit-route-form');
        if (editRouteForm) {
            editRouteForm.addEventListener('submit', function(e) {
                // Form will submit normally
            });
        }

        // Add unit to route form submission
        const addUnitForm = document.getElementById('add-unit-form');
        if (addUnitForm) {
            addUnitForm.addEventListener('submit', function(e) {
                // Form will submit normally
            });
        }
    });
</script>
@endpush
@endsection
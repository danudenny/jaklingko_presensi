@extends('modules.admin.layouts.main')

@section('title', 'Detail Log Perawatan')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Detail Log Perawatan</x-slot>
        <x-slot name="actions">
            @if($maintenanceLog->status !== 'completed')
            <form method="POST" action="{{ route('maintenance-logs.update-status', $maintenanceLog) }}" class="inline-block mr-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="completed">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan log perawatan ini?')">
                    <i class="fas fa-check mr-2"></i>
                    Selesaikan Perawatan
                </button>
            </form>
            @endif
            <a href="{{ route('maintenance-logs.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="md:col-span-2">
            <x-card>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Perawatan</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Status Badge -->
                    <div class="md:col-span-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500">Status:</span>
                            <span class="px-3 py-1 text-sm rounded-full 
                                {{ $maintenanceLog->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($maintenanceLog->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800') }}">
                                {{ $maintenanceLog->status === 'pending' ? 'Pending' : 
                                   ($maintenanceLog->status === 'in_progress' ? 'In Progress' : 'Completed') }}
                            </span>
                        </div>
                    </div>

                    <!-- Unit Information -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Unit:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->unit->unit_number }} - {{ $maintenanceLog->unit->plate_number }}</p>
                    </div>

                    <!-- Route Information -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Rute:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->route->route_number }} - {{ $maintenanceLog->route->name }}</p>
                    </div>

                    <!-- Driver Information -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Pengemudi:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->driver->name }}</p>
                    </div>

                    <!-- Date and Time -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Tanggal & Waktu:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->date_reported->format('d/m/Y') }} {{ $maintenanceLog->time_reported->format('H:i') }}</p>
                    </div>

                    <!-- Shift -->
                    @if($maintenanceLog->shift)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Shift:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->shift }}</p>
                    </div>
                    @endif

                    <!-- Maintenance Type -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Tipe Perawatan:</span>
                        <p class="text-sm text-gray-900">{{ ucfirst($maintenanceLog->type) }}</p>
                    </div>

                    <!-- Parts -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Suku Cadang:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->parts }}</p>
                    </div>

                    <!-- Category (if applicable) -->
                    @if($maintenanceLog->category)
                    <div>
                        <span class="text-sm font-medium text-gray-500">Kategori:</span>
                        <p class="text-sm text-gray-900">{{ ucfirst($maintenanceLog->category) }}</p>
                    </div>
                    @endif

                    <!-- Source of Sparepart -->
                    <div>
                        <span class="text-sm font-medium text-gray-500">Sumber Suku Cadang:</span>
                        <p class="text-sm text-gray-900">{{ $maintenanceLog->source_of_sparepart }}</p>
                    </div>
                </div>

                <!-- Description -->
                <div class="mt-6">
                    <span class="text-sm font-medium text-gray-500">Deskripsi:</span>
                    <p class="mt-1 text-sm text-gray-900">{{ $maintenanceLog->description }}</p>
                </div>

                <!-- Costs -->
                @if($maintenanceLog->costs)
                <div class="mt-6">
                    <span class="text-sm font-medium text-gray-500">Biaya:</span>
                    <div class="mt-2 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @php $totalCost = 0; @endphp
                                @foreach($maintenanceLog->costs as $cost)
                                    @php $totalCost += $cost['amount']; @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $cost['description'] }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                                            Rp {{ number_format($cost['amount'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        Total
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                                        Rp {{ number_format($totalCost, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="{{ route('maintenance-logs.edit', $maintenanceLog) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <i class="fas fa-edit mr-2"></i>
                        Edit
                    </a>
                    <form action="{{ route('maintenance-logs.destroy', $maintenanceLog) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this maintenance log?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:border-red-700 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                            <i class="fas fa-trash mr-2"></i>
                            Hapus
                        </button>
                    </form>
                </div>
            </x-card>
        </div>

        <!-- Photos -->
        <div>
            <x-card>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Foto</h2>
                
                @if($maintenanceLog->photos->count() > 0)
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($maintenanceLog->photos as $photo)
                            <div class="relative">
                                <img src="{{ asset('storage/' . $photo->photo_path) }}" alt="Maintenance Photo" class="w-full h-auto rounded-lg">
                                <a href="{{ asset('storage/' . $photo->photo_path) }}" target="_blank" class="absolute bottom-2 right-2 bg-gray-800 bg-opacity-75 text-white p-2 rounded-full">
                                    <i class="fas fa-expand"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">Tidak ada foto.</p>
                @endif
            </x-card>
        </div>
    </div>
</div>
@endsection

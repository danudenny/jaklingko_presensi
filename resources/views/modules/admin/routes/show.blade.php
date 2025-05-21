@extends('modules.admin.layouts.main')

@section('title', 'Detail Rute')

@section('content')
<div class="container mx-auto py-6">
    <x-page-title>
        <x-slot name="title">Detail Rute</x-slot>
        <x-slot name="actions">
            <a href="{{ route('routes.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
            <a href="{{ route('routes.edit', $route->id) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-edit mr-2"></i>
                Edit
            </a>
        </x-slot>
    </x-page-title>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        <!-- Route Information -->
        <div class="md:col-span-1">
            <x-card>
                <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Rute</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Nomor Rute</h3>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ $route->route_number }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Nama Rute</h3>
                        <p class="mt-1 text-base font-semibold text-gray-900">{{ $route->name }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Status</h3>
                        @php
                            $statusColors = [
                                'aktif' => 'bg-green-100 text-green-800',
                                'nonaktif' => 'bg-red-100 text-red-800'
                            ];
                            $statusLabel = [
                                'aktif' => 'Aktif',
                                'nonaktif' => 'Non Aktif'
                            ];
                        @endphp
                        <span class="mt-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$route->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabel[$route->status] ?? ucfirst($route->status) }}
                        </span>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Tanggal Dibuat</h3>
                        <p class="mt-1 text-base text-gray-900">{{ $route->created_at->format('d M Y, H:i') }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500">Terakhir Diperbarui</h3>
                        <p class="mt-1 text-base text-gray-900">{{ $route->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                </div>
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
                                    <a href="{{ route('units.show', $unit->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
            </x-card>

            <!-- Schedules Associated with this Route -->
            <x-card class="mt-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-medium text-gray-900">Jadwal Terkait</h2>
                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $route->schedules()->count() }} Jadwal
                    </span>
                </div>
                
                <div id="schedules-container">
                    @include('modules.admin.routes.partials.schedules-table')
                </div>
            </x-card>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle pagination links
        $(document).on('click', '#schedules-container .pagination a', function(e) {
            e.preventDefault();
            
            var page = $(this).attr('href').split('page=')[1];
            fetchSchedules(page);
        });
        
        function fetchSchedules(page) {
            $.ajax({
                url: '{{ route("routes.show", $route->id) }}?page=' + page,
                success: function(data) {
                    $('#schedules-container').html(data);
                    // Scroll to the schedules section
                    $('html, body').animate({
                        scrollTop: $('#schedules-container').offset().top - 100
                    }, 200);
                }
            });
        }
    });
</script>
@endpush
        </div>
    </div>
</div>
@endsection
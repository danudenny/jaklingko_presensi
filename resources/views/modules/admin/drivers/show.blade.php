@extends('modules.admin.layouts.main')

@section('title', 'Driver Details')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Detail Pengemudi: {{ $driver->name }}</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Kembali
                </a>
                <a href="{{ route('drivers.edit', $driver) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-edit mr-1"></i>
                    Edit Pengemudi
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <div class="mt-6" x-data="{ activeTab: 'info' }">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'info'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'info', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'info'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-user mr-2"></i>
                    Informasi Pengemudi
                </button>
                <button @click="activeTab = 'routes'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'routes', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'routes'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-route mr-2"></i>
                    Rute
                </button>
                <button @click="activeTab = 'units'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'units', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'units'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-bus mr-2"></i>
                    Unit
                </button>
                <button @click="activeTab = 'schedules'" :class="{'border-indigo-500 text-indigo-600': activeTab === 'schedules', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'schedules'}" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-calendar-alt mr-2"></i>
                    Jadwal
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div>
            <!-- Driver Info Tab -->
            <div x-show="activeTab === 'info'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <x-card>
                    <div class="p-6">
                        <div class="flex items-center mb-6">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-white text-xl font-medium">{{ substr($driver->name, 0, 1) }}</span>
                            </div>
                            <div class="ml-6">
                                <h3 class="text-xl font-medium text-gray-900">{{ $driver->name }}</h3>
                            </div>
                        </div>

                        <div class="bg-white overflow-hidden">
                            <dl>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Tipe</dt>
                                    <dd class="text-sm text-gray-900">{{ ucfirst($driver->type) }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="text-sm text-gray-900">
                                        @php
                                            $statusColors = [
                                                'aktif' => 'bg-green-100 text-green-800',
                                                'nonaktif' => 'bg-red-100 text-red-800',
                                                'cuti' => 'bg-yellow-100 text-yellow-800'
                                            ];
                                            $statusLabel = [
                                                'aktif' => 'Aktif',
                                                'nonaktif' => 'Nonaktif',
                                                'cuti' => 'Cuti'
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$driver->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $statusLabel[$driver->status] ?? ucfirst($driver->status) }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">No KTP</dt>
                                    <dd class="text-sm text-gray-900">{{ $driver->ktp ?: 'N/A' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">No KPP</dt>
                                    <dd class="text-sm text-gray-900">{{ $driver->kpp ?: 'N/A' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">No KK</dt>
                                    <dd class="text-sm text-gray-900">{{ $driver->kk ?: 'N/A' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">No Rekening</dt>
                                    <dd class="text-sm text-gray-900">{{ $driver->rekening ?: 'N/A' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">No HP</dt>
                                    <dd class="text-sm text-gray-900">{{ $driver->phone ?: 'N/A' }}</dd>
                                </div>
                                <div class="py-3 flex justify-between">
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="text-sm text-gray-900">{{ $driver->email ?: 'N/A' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Routes Tab -->
            <div x-show="activeTab === 'routes'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <x-card>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Rute yang Aktif</h2>
                        </div>

                        @if($driver->routes->isEmpty())
                            <div class="py-8 flex flex-col items-center justify-center bg-gray-50 rounded-md border border-gray-200 border-dashed">
                                <i class="fas fa-route text-gray-400 text-2xl"></i>
                                <p class="mt-2 text-sm text-gray-500">Tidak ada rute yang aktif</p>
                            </div>
                        @else
                            <div class="border border-gray-200 rounded-md overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Rute</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($driver->routes as $route)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $route->route_number }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $route->name }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $route->status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ ucfirst($route->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </x-card>
            </div>

            <!-- Units Tab -->
            <div x-show="activeTab === 'units'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <x-card>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Unit yang Aktif</h2>
                        </div>

                        @if($driver->units->isEmpty())
                            <div class="py-8 flex flex-col items-center justify-center bg-gray-50 rounded-md border border-gray-200 border-dashed">
                                <i class="fas fa-bus text-gray-400 text-2xl"></i>
                                <p class="mt-2 text-sm text-gray-500">Tidak ada unit yang aktif</p>
                            </div>
                        @else
                            <div class="border border-gray-200 rounded-md overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Unit</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Plat</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($driver->units as $unit)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $unit->unit_number }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">{{ $unit->plate_number }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $unit->status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ ucfirst($unit->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </x-card>
            </div>

            <!-- Schedules Tab -->
            <div x-show="activeTab === 'schedules'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                <x-card>
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900">Jadwal</h2>
                            <a href="#" class="text-sm text-blue-600 hover:text-blue-800">Lihat Semua</a>
                        </div>

                        @if($driver->schedules->count() > 0)
                            <div class="border border-gray-200 rounded-md overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rute</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($driver->schedules->take(5) as $schedule)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-900">{{ $schedule->schedule_date->format('d M Y') }}</div>
                                                    <div class="text-xs uppercase text-gray-500 bg-green-200 rounded-xl px-2 py-0.5 inline-block">{{ $schedule->shift }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($schedule->route)
                                                        <div class="flex items-center">
                                                            <i class="fas fa-route text-blue-400 mr-1.5"></i>
                                                            {{ $schedule->route->route_number }} -
                                                            {{ $schedule->route->name }}
                                                        </div>
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($schedule->unit)
                                                        <div class="flex items-center">
                                                            <i class="fas fa-bus text-yellow-400 mr-1.5"></i>
                                                            {{ $schedule->unit->unit_number }}
                                                        </div>
                                                    @else
                                                        <span class="text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @php
                                                        $scheduleStatusColors = [
                                                            'scheduled' => 'bg-blue-100 text-blue-800',
                                                            'completed' => 'bg-green-100 text-green-800',
                                                            'missed' => 'bg-red-100 text-red-800',
                                                            'in_progress' => 'bg-yellow-100 text-yellow-800'
                                                        ];
                                                        $scheduleStatusLabels = [
                                                            'scheduled' => 'Terjadwal',
                                                            'completed' => 'Selesai',
                                                            'missed' => 'Dilewatkan',
                                                            'in_progress' => 'Sedang Berlangsung'
                                                        ];
                                                    @endphp
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $scheduleStatusColors[$schedule->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                        {{ $scheduleStatusLabels[$schedule->status] ?? ucfirst($schedule->status) }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="py-8 flex flex-col items-center justify-center bg-gray-50 rounded-md border border-gray-200 border-dashed">
                                <i class="fa-solid fa-calendar-xmark text-gray-400 text-2xl"></i>
                                <p class="mt-2 text-sm text-gray-500">Tidak ada jadwal</p>
                            </div>
                        @endif
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
</script>
@endpush

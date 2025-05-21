@extends('modules.admin.layouts.main')

@section('title', 'Units Management')

@push('styles')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" type="text/css" href="https://npmcdn.com/flatpickr/dist/themes/airbnb.css">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Courier+Prime&display=swap');

    .font-courier {
        font-family: 'Courier Prime', monospace;
    }

    .expiry-countdown {
        font-size: 0.65rem;
        display: block;
        margin-top: 2px;
        padding: 1px 5px;
        border-radius: 5px;
        width: fit-content;
    }

    .expiry-soon {
        color: #fff;
        background-color: #ef4444;
    }

    .expiry-warning {
        color: #fff;
        background-color: #f59e0b;
    }

    .expiry-ok {
        color: #fff;
        background-color: #10b981;
    }
</style>
@endpush

@section('content')
<div id="units-app" class="container mx-auto">
    <x-page-title>
        <x-slot name="title">List Unit</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('units.import.form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-file-import mr-2"></i>
                    Import
                </a>
                <a href="{{ route('units.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-plus mr-2"></i>
                    Tambahkan Unit
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-card>
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">Semua Unit</h2>
                </div>
            </div>
            
            <!-- Filter Form (Always Visible) -->
            <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <form method="GET" action="{{ route('units.index') }}">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-md font-medium text-gray-700">Filter Unit</h3>
                    </div>
                    <!-- 4 inputs in a row -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="unit_number" class="block text-sm font-medium text-gray-700">No Unit</label>
                            <input type="text" name="unit_number" id="unit_number" value="{{ request('unit_number') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="plate_number" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                            <input type="text" name="plate_number" id="plate_number" value="{{ request('plate_number') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="all">Semua Status</option>
                                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Non Aktif</option>
                                <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            </select>
                        </div>

                        <div>
                            <label for="is_pool" class="block text-sm font-medium text-gray-700">Tipe Unit</label>
                            <select name="is_pool" id="is_pool" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Tipe</option>
                                <option value="1" {{ request('is_pool') === '1' ? 'selected' : '' }}>Pool</option>
                                <option value="0" {{ request('is_pool') === '0' ? 'selected' : '' }}>Non-Pool</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-4">
                        <a href="{{ route('units.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Reset
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Apply Filter
                        </button>
                    </div>
                </form>
            </div>

            @if(request()->anyFilled(['unit_number', 'plate_number', 'status', 'route_id', 'expired_stnk_from', 'expired_stnk_to', 'expired_kir_from', 'expired_kir_to', 'expired_kp_from', 'expired_kp_to']))
                <div class="mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">Active Filters:</span>

                        @if(request('unit_number'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Unit: {{ request('unit_number') }}
                                <a href="{{ request()->fullUrlWithoutQuery(['unit_number']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('plate_number'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Plat: {{ request('plate_number') }}
                                <a href="{{ request()->fullUrlWithoutQuery(['plate_number']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('status') && request('status') !== 'all')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Status: {{ ucfirst(request('status')) }}
                                <a href="{{ request()->fullUrlWithoutQuery(['status']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('route_id'))
                            @php
                                $selectedRoute = $routes->firstWhere('id', request('route_id'));
                                $routeLabel = $selectedRoute ? $selectedRoute->route_number : request('route_id');
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Rute: {{ $routeLabel }}
                                <a href="{{ request()->fullUrlWithoutQuery(['route_id']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('expired_stnk_from') || request('expired_stnk_to'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                STNK:
                                {{ request('expired_stnk_from') ? date('d/m/Y', strtotime(request('expired_stnk_from'))) : 'Any' }}
                                -
                                {{ request('expired_stnk_to') ? date('d/m/Y', strtotime(request('expired_stnk_to'))) : 'Any' }}
                                <a href="{{ request()->fullUrlWithoutQuery(['expired_stnk_from', 'expired_stnk_to']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('expired_kir_from') || request('expired_kir_to'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                KIR:
                                {{ request('expired_kir_from') ? date('d/m/Y', strtotime(request('expired_kir_from'))) : 'Any' }}
                                -
                                {{ request('expired_kir_to') ? date('d/m/Y', strtotime(request('expired_kir_to'))) : 'Any' }}
                                <a href="{{ request()->fullUrlWithoutQuery(['expired_kir_from', 'expired_kir_to']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('expired_kp_from') || request('expired_kp_to'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                KP:
                                {{ request('expired_kp_from') ? date('d/m/Y', strtotime(request('expired_kp_from'))) : 'Any' }}
                                -
                                {{ request('expired_kp_to') ? date('d/m/Y', strtotime(request('expired_kp_to'))) : 'Any' }}
                                <a href="{{ request()->fullUrlWithoutQuery(['expired_kp_from', 'expired_kp_to']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        <a href="{{ route('units.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">
                            Clear All Filters
                        </a>
                    </div>
                </div>
            @endif

            <hr class="my-4">

            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Unit</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STNK</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KIR</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="units-table-body">
                        @foreach($units as $unit)
                            <tr class="unit-row {{ $unit->is_pool ? '' : 'bg-orange-50' }}" data-status="{{ $unit->status }}" data-unit-number="{{ strtolower($unit->unit_number) }}" data-plate-number="{{ strtolower($unit->plate_number ?? '') }}" data-is-pool="{{ $unit->is_pool ? 'true' : 'false' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    KWK-{{ $unit->unit_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="{{ $unit->is_pool ? 'bg-yellow-300' : 'bg-gray-200' }} border inline-block border-black rounded-md p-1 px-2 justify-center items-center max-w-xs shadow-md">
                                        <div class="text-black font-bold text-xs tracking-wider">
                                            @php
                                                $plateNumber = $unit->plate_number;
                                                $formattedPlate = substr($plateNumber, 0, 1) . ' ' . substr($plateNumber, 1, 4) . ' ' . substr($plateNumber, 5);
                                            @endphp
                                            {{ $formattedPlate }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($unit->status === 'aktif')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Aktif
                                        </span>
                                    @elseif($unit->status === 'maintenance')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Maintenance
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Non Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $batanganCount = $unit->drivers->where('type', 'batangan')->count();
                                        $cadanganCount = $unit->drivers->where('type', 'cadangan')->count();
                                    @endphp
                                    <div class="flex space-x-2">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800" title="Driver Batangan (Fixed)">
                                            <i class="fas fa-user-tie mr-1"></i> {{ $batanganCount }}
                                        </span>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800" title="Driver Cadangan (Non-Fixed)">
                                            <i class="fas fa-user mr-1"></i> {{ $cadanganCount }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($unit->expired_stnk)
                                        {{ \Carbon\Carbon::parse($unit->expired_stnk)->format('d M Y') }}
                                        @php
                                            $now = \Carbon\Carbon::now();
                                            $expiryDate = \Carbon\Carbon::parse($unit->expired_stnk);
                                            $diff = $now->diffInDays($expiryDate, false);
                                            $diffClass = $diff < 0 ? 'expiry-soon' : ($diff < 30 ? 'expiry-warning' : 'expiry-ok');

                                            if ($diff < 0) {
                                                $timeRemaining = 'Expired ' . abs($diff) . ' days ago';
                                            } else {
                                                if ($diff > 365) {
                                                    $years = floor($diff / 365);
                                                    $months = floor(($diff % 365) / 30);
                                                    $timeRemaining = $years . ' year' . ($years > 1 ? 's' : '') .
                                                                    ($months > 0 ? ', ' . $months . ' month' . ($months > 1 ? 's' : '') : '');
                                                } elseif ($diff > 30) {
                                                    $months = floor($diff / 30);
                                                    $days = $diff % 30;
                                                    $timeRemaining = $months . ' month' . ($months > 1 ? 's' : '') .
                                                                    ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
                                                } elseif ($diff > 7) {
                                                    $weeks = floor($diff / 7);
                                                    $days = $diff % 7;
                                                    $timeRemaining = $weeks . ' week' . ($weeks > 1 ? 's' : '') .
                                                                    ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
                                                } else {
                                                    $timeRemaining = $diff . ' day' . ($diff != 1 ? 's' : '');
                                                }
                                            }
                                        @endphp
                                        <span class="expiry-countdown {{ $diffClass }}">{{ $timeRemaining }}</span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($unit->expired_kir)
                                        {{ \Carbon\Carbon::parse($unit->expired_kir)->format('d M Y') }}
                                        @php
                                            $now = \Carbon\Carbon::now();
                                            $expiryDate = \Carbon\Carbon::parse($unit->expired_kir);
                                            $diff = $now->diffInDays($expiryDate, false);
                                            $diffClass = $diff < 0 ? 'expiry-soon' : ($diff < 30 ? 'expiry-warning' : 'expiry-ok');

                                            if ($diff < 0) {
                                                $timeRemaining = 'Expired ' . abs($diff) . ' days ago';
                                            } else {
                                                if ($diff > 365) {
                                                    $years = floor($diff / 365);
                                                    $months = floor(($diff % 365) / 30);
                                                    $timeRemaining = $years . ' year' . ($years > 1 ? 's' : '') .
                                                                    ($months > 0 ? ', ' . $months . ' month' . ($months > 1 ? 's' : '') : '');
                                                } elseif ($diff > 30) {
                                                    $months = floor($diff / 30);
                                                    $days = $diff % 30;
                                                    $timeRemaining = $months . ' month' . ($months > 1 ? 's' : '') .
                                                                    ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
                                                } elseif ($diff > 7) {
                                                    $weeks = floor($diff / 7);
                                                    $days = $diff % 7;
                                                    $timeRemaining = $weeks . ' week' . ($weeks > 1 ? 's' : '') .
                                                                    ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
                                                } else {
                                                    $timeRemaining = $diff . ' day' . ($diff != 1 ? 's' : '');
                                                }
                                            }
                                        @endphp
                                        <span class="expiry-countdown {{ $diffClass }}">{{ $timeRemaining }}</span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($unit->expired_kp)
                                        {{ \Carbon\Carbon::parse($unit->expired_kp)->format('d M Y') }}
                                        @php
                                            $now = \Carbon\Carbon::now();
                                            $expiryDate = \Carbon\Carbon::parse($unit->expired_kp);
                                            $diff = $now->diffInDays($expiryDate, false);
                                            $diffClass = $diff < 0 ? 'expiry-soon' : ($diff < 30 ? 'expiry-warning' : 'expiry-ok');

                                            if ($diff < 0) {
                                                $timeRemaining = 'Expired ' . abs($diff) . ' days ago';
                                            } else {
                                                if ($diff > 365) {
                                                    $years = floor($diff / 365);
                                                    $months = floor(($diff % 365) / 30);
                                                    $timeRemaining = $years . ' year' . ($years > 1 ? 's' : '') .
                                                                    ($months > 0 ? ', ' . $months . ' month' . ($months > 1 ? 's' : '') : '');
                                                } elseif ($diff > 30) {
                                                    $months = floor($diff / 30);
                                                    $days = $diff % 30;
                                                    $timeRemaining = $months . ' month' . ($months > 1 ? 's' : '') .
                                                                    ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
                                                } elseif ($diff > 7) {
                                                    $weeks = floor($diff / 7);
                                                    $days = $diff % 7;
                                                    $timeRemaining = $weeks . ' week' . ($weeks > 1 ? 's' : '') .
                                                                    ($days > 0 ? ', ' . $days . ' day' . ($days > 1 ? 's' : '') : '');
                                                } else {
                                                    $timeRemaining = $diff . ' day' . ($diff != 1 ? 's' : '');
                                                }
                                            }
                                        @endphp
                                        <span class="expiry-countdown {{ $diffClass }}">{{ $timeRemaining }}</span>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="{{ route('units.show', $unit->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('units.edit', $unit->id) }}" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteUnitConfirm({{ $unit->id }})" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $units->withQueryString()->links() }}
            </div>
        </div>
    </x-card>

    <!-- Bulk Renops Modal -->
    <div id="bulk-renops-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block w-full max-w-md px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button onclick="closeBulkRenopsModal()" type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div>
                    <div class="mt-3 text-center sm:mt-0 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900">
                            Bulk Renops Activation
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500">
                                Select units to activate or deactivate Renops status.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Renops Status</label>
                        <select id="bulk-renops-status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="1">Activate</option>
                            <option value="0">Deactivate</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Units</label>
                        <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-2">
                            <div class="mb-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" id="select-all-units" class="form-checkbox h-4 w-4 text-blue-600">
                                    <span class="ml-2 font-medium">Select All</span>
                                </label>
                            </div>
                            <div id="bulk-units-list" class="space-y-2">
                                @foreach($units as $unit)
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" class="bulk-unit-checkbox form-checkbox h-4 w-4 text-blue-600" value="{{ $unit->id }}">
                                        <span class="ml-2">{{ $unit->unit_number }} - {{ $unit->plate_number }}</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button type="button" id="apply-bulk-renops" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Apply
                    </button>
                    <button type="button" onclick="closeBulkRenopsModal()" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Unit Modal -->
    <div id="create-unit-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;

            <div class="inline-block w-full max-w-5xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">

                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button onclick="closeCreateModal()" type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                            Tambah Unit
                        </h3>
                    </div>
                </div>

                <div class="mt-4">
                    <form id="create-unit-form" method="POST" action="{{ route('units.store') }}">
                        @csrf
                        <div class="border-t border-gray-200 pt-4">
                            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                <div class="sm:col-span-3">
                                    <label for="unit_number" class="block text-sm font-medium text-gray-700">No Unit</label>
                                    <div class="mt-1">
                                        <input type="text" name="unit_number" id="unit_number" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="unit_number_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="plate_number" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                                    <div class="mt-1">
                                        <input type="text" name="plate_number" id="plate_number" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="plate_number_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="unit_reg" class="block text-sm font-medium text-gray-700">No Mesin</label>
                                    <div class="mt-1">
                                        <input type="text" name="unit_reg" id="unit_reg" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="unit_reg_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="serial_number" class="block text-sm font-medium text-gray-700">No Rangka</label>
                                    <div class="mt-1">
                                        <input type="text" name="serial_number" id="serial_number" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="serial_number_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="kir" class="block text-sm font-medium text-gray-700">KIR</label>
                                    <div class="mt-1">
                                        <input type="text" name="kir" id="kir" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="kir_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="expired_stnk" class="block text-sm font-medium text-gray-700">Tanggal Berakhir STNK</label>
                                    <div class="mt-1">
                                        <input type="text" name="expired_stnk" id="expired_stnk" required class="datepicker shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="expired_stnk_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="expired_kir" class="block text-sm font-medium text-gray-700">Tanggal Berakhir KIR</label>
                                    <div class="mt-1">
                                        <input type="text" name="expired_kir" id="expired_kir" required class="datepicker shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="expired_kir_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="expired_kp" class="block text-sm font-medium text-gray-700">Tanggal Berakhir KP</label>
                                    <div class="mt-1">
                                        <input type="text" name="expired_kp" id="expired_kp" required class="datepicker shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="expired_kp_error"></p>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <div class="mt-1">
                                        <select id="status" name="status" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                            <option value="aktif">Aktif</option>
                                            <option value="maintenance">Maintenance</option>
                                            <option value="nonaktif">Nonaktif</option>
                                        </select>
                                    </div>
                                    <p class="mt-1 text-sm text-red-600" id="status_error"></p>
                                </div>
                            </div>
                        </div>
                        <hr class="my-4">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900 mb-4">Pilih Rute</h2>

                            <div>
                                <div class="border border-gray-300 rounded-md overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-2 flex justify-between items-center border-b border-gray-300">
                                        <span class="text-sm font-medium text-gray-700">Rute yang Tersedia</span>
                                        <div>
                                            <button type="button" id="select-all-routes" class="text-xs text-blue-600 hover:text-blue-800">Pilih Semua</button>
                                            <span class="text-gray-400 mx-1">|</span>
                                            <button type="button" id="deselect-all-routes" class="text-xs text-blue-600 hover:text-blue-800">Batal Semua</button>
                                        </div>
                                    </div>
                                    <div class="p-4 max-h-60 overflow-y-auto">
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            @foreach($routes as $route)
                                                <div class="flex items-start">
                                                    <div class="flex items-center h-5">
                                                        <input id="route_{{ $route->id }}" name="route_ids[]" value="{{ $route->id }}" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                                    </div>
                                                    <div class="ml-3 text-sm flex flex-col">
                                                        <label for="route_{{ $route->id }}" class="font-medium text-gray-700">{{ $route->route_number }}</label>
                                                        <small class="text-xs bg-blue-600 text-white rounded-full px-2 py-0.5">{{ $route->name }}</small>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 sm:mt-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Simpan
                            </button>
                            <button onclick="closeCreateModal()" type="button" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals removed - using separate pages for show and edit -->
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js"></script>
<script>
    // Define global functions first
    window.createUnit = function() {
        document.getElementById('create-unit-modal').classList.remove('hidden');

        // Initialize Flatpickr for date inputs in the create modal
        flatpickr("#create-unit-modal .datepicker", {
            dateFormat: "Y-m-d",
            theme: "airbnb",
            allowInput: true
        });
    };

    window.closeCreateModal = function() {
        document.getElementById('create-unit-modal').classList.add('hidden');
    };

    // View and edit functions removed - using separate pages instead

    window.deleteUnitConfirm = function(unitId) {
        if (confirm('Are you sure you want to delete this unit?')) {
            fetch(`/units/${unitId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Unit deleted successfully');
                    window.location.reload();
                } else {
                    alert('Failed to delete unit');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the unit');
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Flatpickr for date inputs
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            theme: "airbnb",
        });
        
        // Initialize daterange pickers
        flatpickr("#expired_stnk_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            theme: "airbnb",
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const startDate = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                    const endDate = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                    document.getElementById('expired_stnk_from').value = startDate;
                    document.getElementById('expired_stnk_to').value = endDate;
                }
            }
        });
        
        flatpickr("#expired_kir_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            theme: "airbnb",
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const startDate = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                    const endDate = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                    document.getElementById('expired_kir_from').value = startDate;
                    document.getElementById('expired_kir_to').value = endDate;
                }
            }
        });
        
        flatpickr("#expired_kp_range", {
            mode: "range",
            dateFormat: "Y-m-d",
            theme: "airbnb",
            onChange: function(selectedDates, dateStr) {
                if (selectedDates.length === 2) {
                    const startDate = flatpickr.formatDate(selectedDates[0], "Y-m-d");
                    const endDate = flatpickr.formatDate(selectedDates[1], "Y-m-d");
                    document.getElementById('expired_kp_from').value = startDate;
                    document.getElementById('expired_kp_to').value = endDate;
                }
            }
        });

        // Create Unit form submission
        const createUnitForm = document.getElementById('create-unit-form');
        if (createUnitForm) {
            createUnitForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Unit created successfully');
                        window.location.reload();
                    } else {
                        alert('Failed to create unit');
                        // Display validation errors
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const errorElement = document.getElementById(`${field}_error`);
                                if (errorElement) {
                                    errorElement.textContent = data.errors[field][0];
                                }
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while creating the unit');
                });
            });
        }

        // Select/Deselect all routes
        const selectAllRoutesBtn = document.getElementById('select-all-routes');
        const deselectAllRoutesBtn = document.getElementById('deselect-all-routes');

        if (selectAllRoutesBtn) {
            selectAllRoutesBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('input[name="route_ids[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
            });
        }

        if (deselectAllRoutesBtn) {
            deselectAllRoutesBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('input[name="route_ids[]"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            });
        }

        // Search functionality
        const unitSearch = document.getElementById('unit-search');
        const statusFilter = document.getElementById('status-filter');
        const unitRows = document.querySelectorAll('.unit-row');

        // Toggle filter visibility
        const toggleFilter = document.getElementById('toggle-filter');
        const filterForm = document.getElementById('filter-form');

        toggleFilter.addEventListener('click', function() {
            filterForm.classList.toggle('hidden');
        });

        // Unit search functionality
        if (unitSearch) {
            unitSearch.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const unitRows = document.querySelectorAll('.unit-row');
                
                unitRows.forEach(row => {
                    const unitNumber = row.getAttribute('data-unit-number');
                    const plateNumber = row.getAttribute('data-plate-number');
                    const status = row.getAttribute('data-status');
                    
                    if (unitNumber.includes(searchTerm) || 
                        plateNumber.includes(searchTerm) || 
                        status.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                const statusValue = this.value;
                const unitRows = document.querySelectorAll('.unit-row');

                unitRows.forEach(row => {
                    const status = row.getAttribute('data-status');

                    if (statusValue === '' || status === statusValue) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }

        // Toggle renops status
        document.querySelectorAll('.toggle-renops').forEach(button => {
            button.addEventListener('click', function() {
                const unitId = this.dataset.unitId;
                const isRenops = this.dataset.isRenops === '1';

                fetch(`/units/${unitId}/toggle-renops`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the button text and data attribute
                        this.textContent = data.is_renops ? 'Remove from Renops' : 'Add to Renops';
                        this.dataset.isRenops = data.is_renops ? '1' : '0';

                        // Update the badge
                        const badge = this.closest('tr').querySelector('.renops-badge');
                        if (badge) {
                            if (data.is_renops) {
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }
                    } else {
                        alert('Failed to update renops status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating renops status');
                });
            });
        });
    });
</script>
@endpush

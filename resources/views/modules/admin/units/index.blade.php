@extends('modules.admin.layouts.main')

@section('title', 'Units Management')

@push('styles')
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
        border-radius: 10px;
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
            <button type="button" onclick="createUnit()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-plus mr-2"></i>
                Tambahkan Unit
            </button>
        </x-slot>
    </x-page-title>

    <x-card>
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">Semua Unit</h2>
                </div>
                <div>
                    <button type="button" id="toggle-filter" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </div>

            <!-- Advanced Filter Form -->
            <div id="filter-form" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200 hidden">
                <form method="GET" action="{{ route('units.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            <label for="route_id" class="block text-sm font-medium text-gray-700">Rute</label>
                            <select name="route_id" id="route_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Rute</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>
                                        {{ $route->route_number }} - {{ $route->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="expired_stnk_from" class="block text-sm font-medium text-gray-700">Expired STNK (Dari)</label>
                            <input type="date" name="expired_stnk_from" id="expired_stnk_from" value="{{ request('expired_stnk_from') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="expired_stnk_to" class="block text-sm font-medium text-gray-700">Expired STNK (Sampai)</label>
                            <input type="date" name="expired_stnk_to" id="expired_stnk_to" value="{{ request('expired_stnk_to') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="expired_kir_from" class="block text-sm font-medium text-gray-700">Expired KIR (Dari)</label>
                            <input type="date" name="expired_kir_from" id="expired_kir_from" value="{{ request('expired_kir_from') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="expired_kir_to" class="block text-sm font-medium text-gray-700">Expired KIR (Sampai)</label>
                            <input type="date" name="expired_kir_to" id="expired_kir_to" value="{{ request('expired_kir_to') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="expired_kp_from" class="block text-sm font-medium text-gray-700">Expired KP (Dari)</label>
                            <input type="date" name="expired_kp_from" id="expired_kp_from" value="{{ request('expired_kp_from') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="expired_kp_to" class="block text-sm font-medium text-gray-700">Expired KP (Sampai)</label>
                            <input type="date" name="expired_kp_to" id="expired_kp_to" value="{{ request('expired_kp_to') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
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

            <div class="flex items-center mb-4">
                <div class="relative">
                    <input type="text" id="unit-search" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pl-10 pr-4 py-2 text-sm" placeholder="Cari unit...">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <select id="status-filter" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        <option value="all">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Non Aktif</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Unit</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plat Nomor</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Mesin</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Rangka</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berakhir STNK</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berakhir KIR</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Berakhir KP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="units-table-body">
                        @foreach($units as $unit)
                            <tr class="unit-row" data-status="{{ $unit->status }}" data-unit-number="{{ strtolower($unit->unit_number) }}" data-plate-number="{{ strtolower($unit->plate_number ?? '') }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $unit->unit_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="bg-yellow-300 border border-black rounded-lg p-1 px-2 flex justify-center items-center max-w-xs shadow-md">
                                        <div class="text-black font-bold text-xs tracking-wider">
                                            @php
                                                $plateNumber = $unit->plate_number;
                                                $formattedPlate = substr($plateNumber, 0, 1) . ' ' . substr($plateNumber, 1, 4) . ' ' . substr($plateNumber, 5);
                                            @endphp
                                            {{ $formattedPlate }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-courier">
                                    {{ $unit->unit_reg ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-courier">
                                    {{ $unit->serial_number ?? 'N/A' }}
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewUnitDetails({{ $unit->id }})" type="button" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="editUnitDetails({{ $unit->id }})" type="button" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteUnitConfirm({{ $unit->id }})" type="button" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>`

            <!-- Pagination -->
            <div class="mt-4">
                {{ $units->withQueryString()->links() }}
            </div>
        </div>
    </x-card>

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
                    <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
                        Tambahkan Unit
                    </h3>
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
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="plate_number" class="block text-sm font-medium text-gray-700">Plat Nomor</label>
                                    <div class="mt-1">
                                        <input type="text" name="plate_number" id="plate_number" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="unit_reg" class="block text-sm font-medium text-gray-700">No. Mesin</label>
                                    <div class="mt-1">
                                        <input type="text" name="unit_reg" id="unit_reg" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="serial_number" class="block text-sm font-medium text-gray-700">No. Rangka</label>
                                    <div class="mt-1">
                                        <input type="text" name="serial_number" id="serial_number" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="kir" class="block text-sm font-medium text-gray-700">KIR</label>
                                    <div class="mt-1">
                                        <input type="text" name="kir" id="kir" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="expired_stnk" class="block text-sm font-medium text-gray-700">Tanggal Berakhir STNK</label>
                                    <div class="mt-1">
                                        <input type="date" name="expired_stnk" id="expired_stnk" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="expired_kir" class="block text-sm font-medium text-gray-700">Tanggal Berakhir KIR</label>
                                    <div class="mt-1">
                                        <input type="date" name="expired_kir" id="expired_kir" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="expired_kp" class="block text-sm font-medium text-gray-700">Tanggal Berakhir KP</label>
                                    <div class="mt-1">
                                        <input type="date" name="expired_kp" id="expired_kp" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                    <div class="mt-1">
                                        <select id="status" name="status" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                                            <option value="aktif">Aktif</option>
                                            <option value="nonaktif">Non Aktif</option>
                                        </select>
                                    </div>
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
                                            <button type="button" id="select-all-units" class="text-xs text-blue-600 hover:text-blue-800">Pilih Semua</button>
                                            <span class="text-gray-400 mx-1">|</span>
                                            <button type="button" id="deselect-all-units" class="text-xs text-blue-600 hover:text-blue-800">Batalkan Pilihan</button>
                                        </div>
                                    </div>
                                    <div class="border-b border-gray-300">
                                        <div class="px-4 py-2">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <i class="fas fa-search text-gray-400"></i>
                                                </div>
                                                <input type="text" id="unit-search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Search units...">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="max-h-60 overflow-y-auto p-2 bg-white">
                                        <div class="space-y-1" id="units-container">
                                            @foreach($routes as $route)
                                                <div class="flex items-center p-2 hover:bg-gray-50 rounded-md route-item">
                                                    <input
                                                        id="route-{{ $route->id }}"
                                                        type="checkbox"
                                                        name="route_ids[]"
                                                        value="{{ $route->id }}"
                                                        {{ in_array($route->id, old('route_ids', [])) ? 'checked' : '' }}
                                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                        data-route-number="{{ strtolower($route->route_number) }}"
                                                    >
                                                    <label for="route-{{ $route->id }}" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
                                                        <div>{{ $route->route_number }}</div>
                                                        <div>{{ $route->name }}</div>
                                                    </label>
                                                    <span class="ml-auto px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        {{ $route->status === 'aktif' ? 'bg-green-100 text-green-800' :
                                                          ($route->status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                        {{ ucfirst($route->status) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div id="no-units-found" class="hidden py-4 text-center text-sm text-gray-500">
                                            No units found matching your search.
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-2 border-t border-gray-300">
                                        <span class="text-xs text-gray-500">Selected: <span id="selected-units-count">0</span></span>
                                    </div>
                                </div>
                                <x-input-error :message="$errors->first('route_ids')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mt-6 sm:mt-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Simpan
                            </button>
                            <button type="button" onclick="closeCreateModal()" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Batalkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Unit Modal -->
    <div id="view-unit-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;

            <div class="inline-block w-full max-w-5xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="closeViewModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="view-unit-modal-content">
                    <div class="text-center py-10">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-600"></div>
                        <p class="mt-2 text-sm text-gray-600">Loading unit details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Unit Modal -->
    <div id="edit-unit-modal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;

            <div class="inline-block w-full max-w-5xl px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="edit-unit-modal-content">
                    <div class="text-center py-10">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-indigo-600"></div>
                        <p class="mt-2 text-sm text-gray-600">Loading unit details...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const unitSearch = document.getElementById('unit-search');
        const statusFilter = document.getElementById('status-filter');
        const unitRows = document.querySelectorAll('.unit-row');

        // Route selection functionality
        const routeCheckboxes = document.querySelectorAll('input[name="route_ids[]"]');
        const selectedRoutesCount = document.getElementById('selected-units-count');
        const selectAllRoutesBtn = document.getElementById('select-all-units');
        const deselectAllRoutesBtn = document.getElementById('deselect-all-units');
        const routeSearch = document.querySelector('#unit-search');

        // Update selected routes count
        function updateSelectedRoutesCount() {
            const checkedCount = document.querySelectorAll('input[name="route_ids[]"]:checked').length;
            if (selectedRoutesCount) {
                selectedRoutesCount.textContent = checkedCount;
            }
        }

        // Initialize selected routes count
        updateSelectedRoutesCount();

        // Add event listeners to route checkboxes
        routeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedRoutesCount);
        });

        // Select all routes
        if (selectAllRoutesBtn) {
            selectAllRoutesBtn.addEventListener('click', function() {
                routeCheckboxes.forEach(checkbox => {
                    const routeItem = checkbox.closest('.route-item');
                    if (routeItem && routeItem.style.display !== 'none') {
                        checkbox.checked = true;
                    }
                });
                updateSelectedRoutesCount();
            });
        }

        // Deselect all routes
        if (deselectAllRoutesBtn) {
            deselectAllRoutesBtn.addEventListener('click', function() {
                routeCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectedRoutesCount();
            });
        }

        // Filter functionality
        function filterUnits() {
            const searchTerm = unitSearch.value.toLowerCase();
            const statusFilter = document.getElementById('status-filter').value;

            unitRows.forEach(row => {
                const unitNumber = row.getAttribute('data-unit-number');
                const plateNumber = row.getAttribute('data-plate-number');
                const status = row.getAttribute('data-status');

                const matchesSearch = unitNumber.includes(searchTerm) || plateNumber.includes(searchTerm);
                const matchesStatus = statusFilter === 'all' || status === statusFilter;

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (unitSearch) {
            unitSearch.addEventListener('input', filterUnits);
        }

        if (statusFilter) {
            statusFilter.addEventListener('change', filterUnits);
        }

        // Route search functionality
        const routeSearchInput = document.getElementById('unit-search');
        const routeItems = document.querySelectorAll('.route-item');
        const noRoutesFound = document.getElementById('no-units-found');

        if (routeSearchInput) {
            routeSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                let foundRoutes = 0;

                routeItems.forEach(item => {
                    const routeNumber = item.querySelector('input[name="route_ids[]"]').getAttribute('data-route-number');
                    const routeName = item.querySelector('label div:nth-child(2)').textContent.toLowerCase();

                    if (routeNumber.includes(searchTerm) || routeName.includes(searchTerm)) {
                        item.style.display = '';
                        foundRoutes++;
                    } else {
                        item.style.display = 'none';
                    }
                });

                if (noRoutesFound) {
                    noRoutesFound.style.display = foundRoutes === 0 ? 'block' : 'none';
                }
            });
        }

        // Create and Edit Unit functions
        window.createUnit = function() {
            document.getElementById('create-unit-modal').classList.remove('hidden');
        }

        window.closeCreateModal = function() {
            document.getElementById('create-unit-modal').classList.add('hidden');
        }

        window.viewUnitDetails = function(unitId) {
            fetch(`/units/${unitId}?mode=view`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('view-unit-modal-content').innerHTML = data.html;
                    document.getElementById('view-unit-modal').classList.remove('hidden');
                } else {
                    alert('Failed to load unit details');
                }
            })
            .catch(error => {
                console.error('Error loading unit details:', error);
                alert('Failed to load unit details');
            });
        }

        window.closeViewModal = function() {
            document.getElementById('view-unit-modal').classList.add('hidden');
        }

        window.editUnitDetails = function(unitId) {
            fetch(`/units/${unitId}?mode=edit`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('edit-unit-modal-content').innerHTML = data.html;
                    document.getElementById('edit-unit-modal').classList.remove('hidden');

                    // Initialize edit form route selection functionality
                    const editRouteCheckboxes = document.querySelectorAll('#edit-unit-modal input[name="route_ids[]"]');
                    const editSelectedRoutesCount = document.getElementById('edit-selected-routes-count');
                    const editSelectAllRoutesBtn = document.getElementById('edit-select-all-routes');
                    const editDeselectAllRoutesBtn = document.getElementById('edit-deselect-all-routes');

                    // Update selected routes count for edit form
                    function updateEditSelectedRoutesCount() {
                        const checkedCount = document.querySelectorAll('#edit-unit-modal input[name="route_ids[]"]:checked').length;
                        if (editSelectedRoutesCount) {
                            editSelectedRoutesCount.textContent = checkedCount;
                        }
                    }

                    // Initialize edit form selected routes count
                    updateEditSelectedRoutesCount();

                    // Add event listeners to edit form route checkboxes
                    editRouteCheckboxes.forEach(checkbox => {
                        checkbox.addEventListener('change', updateEditSelectedRoutesCount);
                    });

                    // Select all routes in edit form
                    if (editSelectAllRoutesBtn) {
                        editSelectAllRoutesBtn.addEventListener('click', function() {
                            editRouteCheckboxes.forEach(checkbox => {
                                checkbox.checked = true;
                            });
                            updateEditSelectedRoutesCount();
                        });
                    }

                    // Deselect all routes in edit form
                    if (editDeselectAllRoutesBtn) {
                        editDeselectAllRoutesBtn.addEventListener('click', function() {
                            editRouteCheckboxes.forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            updateEditSelectedRoutesCount();
                        });
                    }
                } else {
                    alert('Failed to load unit details');
                }
            })
            .catch(error => {
                console.error('Error loading unit details:', error);
                alert('Failed to load unit details');
            });
        }

        window.closeEditModal = function() {
            document.getElementById('edit-unit-modal').classList.add('hidden');
        }

        window.deleteUnitConfirm = function(unitId) {
            if (confirm('Are you sure you want to delete this unit?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/units/${unitId}`;

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle filter form
        const toggleFilterBtn = document.getElementById('toggle-filter');
        const filterForm = document.getElementById('filter-form');

        if (toggleFilterBtn) {
            toggleFilterBtn.addEventListener('click', function() {
                filterForm.classList.toggle('hidden');
            });
        }
    });
</script>
@endpush

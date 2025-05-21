@extends('modules.admin.layouts.main')

@section('title', 'Detail Unit')

@push('styles')
<style>
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
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Detail Unit</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('units.edit', $unit->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Unit
                </a>
                <a href="{{ route('units.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Kembali
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-card>
        <div class="p-6">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Left column: Unit details -->
                <div class="w-full md:w-2/3">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Informasi Unit</h2>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex items-center mb-2">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-bus text-indigo-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">KWK-{{ $unit->unit_number }}</h3>
                                <p class="text-sm text-gray-500">{{ $unit->plate_number }}</p>
                            </div>
                            <div class="ml-auto">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $unit->status === 'aktif' ? 'bg-green-100 text-green-800' : ($unit->status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($unit->status) }}
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $unit->is_pool ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800' }} ml-2">
                                    {{ $unit->is_pool ? 'Pool' : 'Non-Pool' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Informasi Registrasi</h3>
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <dl class="divide-y divide-gray-200">
                                    <div class="py-2 flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Unit Reg</dt>
                                        <dd class="text-sm text-gray-900">{{ $unit->unit_reg ?? '-' }}</dd>
                                    </div>
                                    <div class="py-2 flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                                        <dd class="text-sm text-gray-900">{{ $unit->serial_number ?? '-' }}</dd>
                                    </div>
                                    <div class="py-2 flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">KIR</dt>
                                        <dd class="text-sm text-gray-900">{{ $unit->kir ?? '-' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Tanggal Kedaluwarsa</h3>
                            <div class="bg-white rounded-lg border border-gray-200 p-4">
                                <dl class="divide-y divide-gray-200">
                                    <div class="py-2 flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">STNK</dt>
                                        <dd class="text-sm text-gray-900">
                                            @if($unit->expired_stnk)
                                                {{ \Carbon\Carbon::parse($unit->expired_stnk)->format('d M Y') }}
                                                @php
                                                    $now = \Carbon\Carbon::now();
                                                    $expiredDate = \Carbon\Carbon::parse($unit->expired_stnk);
                                                    $diffInDays = $now->diffInDays($expiredDate, false);
                                                    
                                                    if ($diffInDays < 0) {
                                                        $expiryClass = 'expiry-soon';
                                                        $expiryText = 'Expired ' . abs($diffInDays) . ' days ago';
                                                    } elseif ($diffInDays <= 30) {
                                                        $expiryClass = 'expiry-soon';
                                                        $expiryText = 'Expires in ' . $diffInDays . ' days';
                                                    } elseif ($diffInDays <= 90) {
                                                        $expiryClass = 'expiry-warning';
                                                        $expiryText = 'Expires in ' . $diffInDays . ' days';
                                                    } else {
                                                        $expiryClass = 'expiry-ok';
                                                        $expiryText = 'Valid for ' . $diffInDays . ' days';
                                                    }
                                                @endphp
                                                <span class="expiry-countdown {{ $expiryClass }}">{{ $expiryText }}</span>
                                            @else
                                                -
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="py-2 flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">KIR</dt>
                                        <dd class="text-sm text-gray-900">
                                            @if($unit->expired_kir)
                                                {{ \Carbon\Carbon::parse($unit->expired_kir)->format('d M Y') }}
                                                @php
                                                    $now = \Carbon\Carbon::now();
                                                    $expiredDate = \Carbon\Carbon::parse($unit->expired_kir);
                                                    $diffInDays = $now->diffInDays($expiredDate, false);
                                                    
                                                    if ($diffInDays < 0) {
                                                        $expiryClass = 'expiry-soon';
                                                        $expiryText = 'Expired ' . abs($diffInDays) . ' days ago';
                                                    } elseif ($diffInDays <= 30) {
                                                        $expiryClass = 'expiry-soon';
                                                        $expiryText = 'Expires in ' . $diffInDays . ' days';
                                                    } elseif ($diffInDays <= 90) {
                                                        $expiryClass = 'expiry-warning';
                                                        $expiryText = 'Expires in ' . $diffInDays . ' days';
                                                    } else {
                                                        $expiryClass = 'expiry-ok';
                                                        $expiryText = 'Valid for ' . $diffInDays . ' days';
                                                    }
                                                @endphp
                                                <span class="expiry-countdown {{ $expiryClass }}">{{ $expiryText }}</span>
                                            @else
                                                -
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="py-2 flex justify-between">
                                        <dt class="text-sm font-medium text-gray-500">KP</dt>
                                        <dd class="text-sm text-gray-900">
                                            @if($unit->expired_kp)
                                                {{ \Carbon\Carbon::parse($unit->expired_kp)->format('d M Y') }}
                                                @php
                                                    $now = \Carbon\Carbon::now();
                                                    $expiredDate = \Carbon\Carbon::parse($unit->expired_kp);
                                                    $diffInDays = $now->diffInDays($expiredDate, false);
                                                    
                                                    if ($diffInDays < 0) {
                                                        $expiryClass = 'expiry-soon';
                                                        $expiryText = 'Expired ' . abs($diffInDays) . ' days ago';
                                                    } elseif ($diffInDays <= 30) {
                                                        $expiryClass = 'expiry-soon';
                                                        $expiryText = 'Expires in ' . $diffInDays . ' days';
                                                    } elseif ($diffInDays <= 90) {
                                                        $expiryClass = 'expiry-warning';
                                                        $expiryText = 'Expires in ' . $diffInDays . ' days';
                                                    } else {
                                                        $expiryClass = 'expiry-ok';
                                                        $expiryText = 'Valid for ' . $diffInDays . ' days';
                                                    }
                                                @endphp
                                                <span class="expiry-countdown {{ $expiryClass }}">{{ $expiryText }}</span>
                                            @else
                                                -
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    @if($unit->notes)
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Catatan</h3>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <p class="text-sm text-gray-700">{{ $unit->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right column: Routes and Drivers -->
                <div class="w-full md:w-1/3">
                    <!-- Routes -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Rute Terkait</h3>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            @if($unit->routes->count() > 0)
                                <div class="space-y-2">
                                    @foreach($unit->routes as $route)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <i class="fas fa-route text-indigo-600"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900">Rute {{ $route->route_number }}</p>
                                                    <p class="text-xs text-gray-500">{{ $route->name }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 italic">Tidak ada rute terkait</p>
                            @endif
                        </div>
                    </div>

                    <!-- Drivers -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Pengemudi Terkait</h3>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            @if($unit->drivers->count() > 0)
                                <div class="space-y-2">
                                    @foreach($unit->drivers as $driver)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <i class="fas fa-user text-blue-600"></i>
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-gray-900">{{ $driver->name }}</p>
                                                    <div class="flex items-center">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium {{ $driver->type === 'batangan' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }}">
                                                            {{ ucfirst($driver->type) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="{{ route('drivers.show', $driver->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 italic">Tidak ada pengemudi terkait</p>
                            @endif
                        </div>
                    </div>

                    <!-- Renops Status -->
                    <div class="mb-6">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Status Renops</h3>
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-700">Unit dalam Renops</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $unit->is_renops ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $unit->is_renops ? 'Ya' : 'Tidak' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-card>
</div>
@endsection

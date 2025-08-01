@extends('modules.admin.layouts.main')

@section('title', 'Add New Driver')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Tambah Pengemudi Baru</x-slot>
        <x-slot name="actions">
            <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-card>
        <form id="create-driver-form" method="POST" action="{{ route('drivers.store') }}" class="p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column: Driver Info -->
                <div>
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Umum</h2>

                    <div class="mb-4">
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus  />
                        <x-input-error :message="$errors->first('name')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="type" value="Type" />
                        <select id="type" name="type" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="batangan" {{ old('type') == 'batangan' ? 'selected' : '' }}>Batangan</option>
                            <option value="cadangan" {{ old('type') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                        </select>
                        <x-input-error :message="$errors->first('type')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="ktp" value="No KTP" />
                        <x-text-input id="ktp" class="block mt-1 w-full" type="text" name="ktp" :value="old('ktp')" required placeholder="e.g. 3201234567890001" />
                        <x-input-error :message="$errors->first('ktp')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="kpp" value="No KPP" />
                        <x-text-input id="kpp" class="block mt-1 w-full" type="text" name="kpp" :value="old('kpp')" placeholder="e.g. 3201234567890001" />
                        <x-input-error :message="$errors->first('kpp')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="kk" value="No KK" />
                        <x-text-input id="kk" class="block mt-1 w-full" type="text" name="kk" :value="old('kk')" placeholder="e.g. 3201234567890001" maxlength="16" />
                        <x-input-error :message="$errors->first('kk')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">Maximum 16 digits</p>
                    </div>

                    <div class="mb-4">
                        <x-input-label for="rekening" value="No Rekening" />
                        <x-text-input id="rekening" class="block mt-1 w-full" type="text" name="rekening" :value="old('rekening')" placeholder="e.g. 1234567890" maxlength="20" />
                        <x-input-error :message="$errors->first('rekening')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">Maximum 20 digits</p>
                    </div>

                    <div class="mb-4">
                        <x-input-label for="phone" value="Telepon" />
                        <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" placeholder="e.g. 08123456789" />
                        <x-input-error :message="$errors->first('phone')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="aktif" {{ old('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ old('status') == 'nonaktif' ? 'selected' : '' }}>Non Aktif</option>
                        </select>
                        <x-input-error :message="$errors->first('status')" class="mt-2" />
                    </div>
                </div>

                <!-- Right Column: Route and Unit Selection -->
                <div x-data="routeUnitSelector()">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Penugasan Rute & Unit</h2>

                    <!-- Routes Selection -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label for="routes" value="Rute" class="text-base font-medium" />
                            <span class="text-xs text-gray-500" x-text="selectedRoutes.length + ' rute dipilih'"></span>
                        </div>

                        <div class="border border-gray-300 rounded-md p-4 max-h-60 overflow-y-auto bg-white">
                            @foreach($routes as $route)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="route_{{ $route->id }}" value="{{ $route->id }}" name="routes[]" class="route-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" @change="handleRouteChange($event)" {{ in_array($route->id, old('routes', [])) ? 'checked' : '' }}>
                                        <label for="route_{{ $route->id }}" class="ml-2 block text-sm text-gray-900">
                                            {{ $route->route_number }} - {{ $route->name }}
                                        </label>
                                    </div>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $route->status == 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($route->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            <span x-show="driver_type === 'batangan'" class="text-amber-600">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Driver batangan hanya dapat ditugaskan ke 1 rute
                            </span>
                        </div>
                        <x-input-error :message="$errors->first('routes')" class="mt-2" />
                    </div>

                    <!-- Units Selection -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <x-input-label for="units" value="Unit" class="text-base font-medium" />
                            <span class="text-xs text-gray-500" x-text="selectedUnits.length + ' unit dipilih'"></span>
                        </div>

                        <div class="border border-gray-300 rounded-md p-4 max-h-72 overflow-y-auto bg-white">
                            <div x-show="loadingUnits" class="flex justify-center py-4">
                                <i class="fas fa-spinner fa-spin text-indigo-500"></i>
                                <span class="ml-2 text-sm text-gray-600">Memuat unit...</span>
                            </div>

                            <div x-show="!loadingUnits && availableUnits.length === 0" class="text-sm text-gray-500 py-4 text-center">
                                <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                Tidak ada unit yang tersedia
                            </div>

                            <div x-show="!loadingUnits && availableUnits.length > 0" class="mb-3">
                                <div class="flex justify-between items-center mb-2">
                                    <input type="text" x-model="unitSearch" placeholder="Cari unit..." class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 mr-2">
                                    <div class="whitespace-nowrap space-x-2">
                                        <button type="button" @click="selectAllUnits" class="text-xs text-blue-600 hover:underline">Pilih Semua</button>
                                        <button type="button" @click="clearUnitSelections" class="text-xs text-red-600 hover:underline">Bersihkan</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="!loadingUnits && availableUnits.length > 0" class="grid grid-cols-1 gap-2">
                                <template x-for="unit in filteredUnits" :key="unit.id">
                                    <div class="flex items-center p-2 hover:bg-gray-50 rounded border border-gray-100">
                                        <input type="checkbox" :id="'unit_' + unit.id" name="units[]" :value="unit.id" class="h-4 w-4 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 unit-checkbox" @change="handleUnitChange" :checked="selectedUnits.includes(unit.id.toString())">
                                        <label :for="'unit_' + unit.id" class="ml-3 text-sm text-gray-700 font-medium cursor-pointer flex-grow">
                                            <div class="font-medium" x-text="unit.unit_number"></div>
                                            <div class="text-xs text-gray-500" x-text="unit.plate_number || '-'"></div>
                                        </label>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="unit.status === 'aktif' ? 'bg-green-100 text-green-800' : (unit.status === 'nonaktif' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')" x-text="unit.status.charAt(0).toUpperCase() + unit.status.slice(1)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            <span class="text-blue-600">
                                <i class="fas fa-info-circle mr-1"></i> Rute akan otomatis ditentukan berdasarkan unit yang dipilih
                            </span>
                        </div>
                        <x-input-error :message="$errors->first('units')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div id="create-form-errors" class="mb-4 text-sm text-red-600 space-y-1 mt-4" style="display: none;"></div>

            <div class="flex justify-end mt-6">
                <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-500 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Batalkan
                </a>
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-save mr-2"></i>Simpan
                </button>
            </div>
        </form>
    </x-card>
</div>

@push('scripts')
<script>
    function routeUnitSelector() {
        return {
            unitSearch: '',
            driver_type: document.getElementById('type').value,
            availableUnits: [],
            selectedUnits: [],
            selectedRoutes: [],
            loadingUnits: false,
            
            init() {
                // Initialize selectedRoutes from any pre-checked route checkboxes
                this.selectedRoutes = Array.from(document.querySelectorAll('.route-checkbox:checked')).map(cb => cb.value);
                
                // Load units for any pre-selected routes
                if (this.selectedRoutes.length > 0) {
                    this.selectedRoutes.forEach(routeId => this.loadUnitsForRoute(routeId));
                }
            },
            
            async loadUnitsForRoute(routeId) {
                this.loadingUnits = true;
                try {
                    const url = '/drivers/get-units-for-route';
                    const response = await fetch(`${url}?route_id=${routeId}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Add new units to availableUnits if they're not already there
                        data.data.forEach(unit => {
                            if (!this.availableUnits.some(u => u.id === unit.id)) {
                                this.availableUnits.push(unit);
                            }
                        });
                    } else {
                        console.error('Error loading units for route:', data);
                    }
                } catch (error) {
                    console.error('Error loading units for route:', error);
                } finally {
                    this.loadingUnits = false;
                }
            },
            
            handleRouteChange(event) {
                const routeId = event.target.value;
                const isChecked = event.target.checked;
                
                if (isChecked) {
                    // For batangan drivers, only allow one route
                    if (this.driver_type === 'batangan' && this.selectedRoutes.length > 0) {
                        // Uncheck previously selected route
                        const previousRouteId = this.selectedRoutes[0];
                        const previousCheckbox = document.getElementById('route_' + previousRouteId);
                        if (previousCheckbox) {
                            previousCheckbox.checked = false;
                        }
                        
                        // Clear selectedRoutes array
                        this.selectedRoutes = [routeId];
                    } else {
                        // For cadangan drivers, allow multiple routes
                        if (!this.selectedRoutes.includes(routeId)) {
                            this.selectedRoutes.push(routeId);
                        }
                    }

                    // Load units for this route
                    this.loadUnitsForRoute(routeId);
                } else {
                    // Remove from selectedRoutes
                    this.selectedRoutes = this.selectedRoutes.filter(id => id !== routeId);

                    // Remove units associated with this route from availableUnits
                    // This is a simplified approach; in a real app, you'd need to track which units came from which route
                    // For now, we'll just reload all units for the remaining selected routes
                    this.availableUnits = [];
                    if (this.selectedRoutes.length > 0) {
                        this.selectedRoutes.forEach(id => this.loadUnitsForRoute(id));
                    }
                }
            },
            
            handleUnitChange(event) {
                const unitId = event.target.value;
                const isChecked = event.target.checked;
                
                // Update selectedUnits array
                if (isChecked) {
                    if (!this.selectedUnits.includes(unitId)) {
                        this.selectedUnits.push(unitId);
                    }
                } else {
                    this.selectedUnits = this.selectedUnits.filter(id => id !== unitId);
                }
            },

            selectAllUnits() {
                this.selectedUnits = this.filteredUnits.map(unit => unit.id.toString());
                
                // Update checkbox states in the DOM
                this.filteredUnits.forEach(unit => {
                    const checkbox = document.getElementById('unit_' + unit.id);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            },

            clearUnitSelections() {
                this.selectedUnits = [];
                
                // Update checkbox states in the DOM
                document.querySelectorAll('.unit-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
            },
            
            get filteredUnits() {
                return this.availableUnits.filter(unit => {
                    return unit.unit_number.toLowerCase().includes(this.unitSearch.toLowerCase()) || 
                           (unit.plate_number && unit.plate_number.toLowerCase().includes(this.unitSearch.toLowerCase()));
                });
            }
        }
    }
    
    // Type change handler
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('type');
        if (typeSelect) {
            typeSelect.addEventListener('change', function() {
                const routeSelector = document.querySelector('[x-data="routeUnitSelector()"]').__x.$data;
                routeSelector.driver_type = this.value;
                
                // If changing to batangan and multiple routes are selected, keep only the first one
                if (this.value === 'batangan' && routeSelector.selectedRoutes.length > 1) {
                    const firstRouteId = routeSelector.selectedRoutes[0];
                    
                    // Uncheck all route checkboxes except the first one
                    document.querySelectorAll('.route-checkbox:checked').forEach(checkbox => {
                        if (checkbox.value !== firstRouteId) {
                            checkbox.checked = false;
                        }
                    });
                    
                    // Update selectedRoutes array
                    routeSelector.selectedRoutes = [firstRouteId];
                    
                    // Show warning message
                    alert('Driver batangan hanya dapat ditugaskan ke 1 rute. Hanya rute pertama yang akan dipertahankan.');
                }
            });
        }
    });
</script>
@endpush

@endsection

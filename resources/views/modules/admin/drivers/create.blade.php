@extends('modules.admin.layouts.main')

@section('title', 'Add New Driver')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Tambah Driver Baru</x-slot>
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
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Informasi Driver</h2>

                    <div class="mb-4">
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus  />
                        <x-input-error :message="$errors->first('name')" class="mt-2" />
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
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" placeholder="e.g. example@mail.com" />
                        <x-input-error :message="$errors->first('email')" class="mt-2" />
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
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Route & Unit Assignment</h2>
                    
                    <div class="mb-4">
                        <div class="text-sm text-gray-600 mb-2">
                            <span class="font-medium">Note:</span>
                            <ul class="list-disc ml-5 mt-1">
                                <li>Driver <span class="font-medium">batangan</span> harus ditugaskan ke 1 rute</li>
                                <li>Driver <span class="font-medium">cadangan</span> dapat ditugaskan ke beberapa rute</li>
                                <li>Pilih rute terlebih dahulu, lalu pilih unit dari unit yang tersedia</li>
                                <li>Jika tidak ada unit yang ditugaskan ke rute, semua unit aktif akan ditampilkan</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Route Selection -->
                    <div class="mb-4">
                        <x-input-label for="routes" :value="__('Routes')" />
                        
                        <div class="border border-gray-300 rounded-md p-4 max-h-60 overflow-y-auto">
                            @foreach($routes as $route)
                                <div class="flex items-center mb-2">
                                    <input type="checkbox" id="route_{{ $route->id }}" name="routes[]" value="{{ $route->id }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 route-checkbox" @change="handleRouteChange($event)">
                                    <label for="route_{{ $route->id }}" class="ml-2 text-sm text-gray-700">{{ $route->route_number }} - {{ $route->name }}</label>
                                    <span class="ml-auto px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $route->status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($route->status) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <x-input-error :message="$errors->first('routes')" class="mt-2" />
                    </div>
                    
                    <!-- Unit Selection -->
                    <div class="mb-4">
                        <x-input-label for="units" :value="__('Units')" />
                        
                        <div class="border border-gray-300 rounded-md p-4 max-h-60 overflow-y-auto">
                            <div x-show="loadingUnits" class="flex justify-center py-4">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                            
                            <div x-show="!loadingUnits && availableUnits.length === 0" class="text-sm text-gray-500 py-2">
                                Pilih rute terlebih dahulu, lalu pilih unit dari unit yang tersedia.
                            </div>
                            
                            <template x-for="unit in availableUnits" :key="unit.id">
                                <div class="flex items-center unit-item">
                                    <input type="checkbox" :id="'unit_' + unit.id" name="units[]" :value="unit.id" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 unit-checkbox" @change="selectedUnits = [...document.querySelectorAll('.unit-checkbox:checked')].map(cb => cb.value)">
                                    <label :for="'unit_' + unit.id" class="ml-2 text-sm text-gray-700" x-text="unit.unit_number + (unit.plate_number ? ' - ' + unit.plate_number : '')"></label>
                                    <span class="ml-auto px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="unit.status === 'aktif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="unit.status.charAt(0).toUpperCase() + unit.status.slice(1)"></span>
                                </div>
                            </template>
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
            search: '', 
            selectedRoutes: [], 
            availableUnits: [],
            selectedUnits: [],
            loadingUnits: false,
            
            async loadUnitsForRoute(routeId) {
                if (!routeId) return;
                
                this.loadingUnits = true;
                try {
                    const url = '/drivers/get-units-for-route?route_id=' + routeId;
                    const response = await fetch(url, {
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
                        // Add units from this route to available units
                        this.availableUnits = [...this.availableUnits, ...data.data];
                        // Remove duplicates
                        this.availableUnits = this.availableUnits.filter((unit, index, self) =>
                            index === self.findIndex((u) => u.id === unit.id)
                        );
                    } else {
                        // Handle error silently
                        this.availableUnits = [];
                    }
                } catch (error) {
                    // Reset units on error
                    this.availableUnits = [];
                } finally {
                    this.loadingUnits = false;
                }
            },
            
            handleRouteChange(event) {
                const checkbox = event.target;
                const routeId = checkbox.value;
                
                if (checkbox.checked) {
                    // Check if driver is batangan and already has a route selected
                    if (document.getElementById('type').value === 'batangan' && this.selectedRoutes.length > 0) {
                        // Uncheck all other routes
                        document.querySelectorAll('.route-checkbox').forEach(cb => {
                            if (cb !== checkbox) {
                                cb.checked = false;
                            }
                        });
                        this.selectedRoutes = [routeId];
                        this.availableUnits = [];
                    } else {
                        this.selectedRoutes.push(routeId);
                    }
                    
                    // Load units for this route
                    this.loadUnitsForRoute(routeId);
                } else {
                    // Remove route from selected routes
                    this.selectedRoutes = this.selectedRoutes.filter(id => id !== routeId);
                    
                    // Reload all units for remaining selected routes
                    this.availableUnits = [];
                    this.selectedRoutes.forEach(id => this.loadUnitsForRoute(id));
                }
            }
        }
    }
</script>
@endpush

@endsection

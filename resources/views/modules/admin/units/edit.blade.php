@extends('modules.admin.layouts.main')

@section('title', 'Edit Unit')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edit Unit: {{ $unit->unit_number }}</h1>
        <a href="{{ route('units.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            <i class="fas fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <x-flash-message />

    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="{{ route('units.update', $unit->id) }}" method="POST" id="edit-unit-form">
            @csrf
            @method('PUT')
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Informasi Kepemilikan</h2>
                <div class="mb-4">
                    <label for="is_pool" class="block text-sm font-medium text-gray-700 mb-1">Status Kepemilikan Unit</label>
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <input type="radio" id="is_pool_yes" name="is_pool" value="1" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ $unit->is_pool ? 'checked' : '' }}>
                            <label for="is_pool_yes" class="ml-2 block text-sm text-gray-700">Unit Milik Sendiri (Pool)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="radio" id="is_pool_no" name="is_pool" value="0" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" {{ !$unit->is_pool ? 'checked' : '' }}>
                            <label for="is_pool_no" class="ml-2 block text-sm text-gray-700">Unit Bukan Milik Sendiri</label>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        Pilih "Unit Milik Sendiri" jika unit ini adalah milik perusahaan dan berada di pool.
                    </p>
                    @error('is_pool')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="unit_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Unit <span class="text-red-500">*</span></label>
                    <input type="text" name="unit_number" id="unit_number" value="{{ old('unit_number', $unit->unit_number) }}" 
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('unit_number') border-red-500 @enderror" required>
                    @error('unit_number')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="plate_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Plat <span class="text-red-500">*</span></label>
                    <input type="text" name="plate_number" id="plate_number" value="{{ old('plate_number', $unit->plate_number) }}" 
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('plate_number') border-red-500 @enderror" required>
                    @error('plate_number')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <div id="pool-unit-fields" class="mt-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Informasi Detail Unit</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="unit_reg" class="block text-sm font-medium text-gray-700 mb-1">Nomor Registrasi <span class="text-red-500 pool-required">*</span></label>
                        <input type="text" name="unit_reg" id="unit_reg" value="{{ old('unit_reg', $unit->unit_reg) }}" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('unit_reg') border-red-500 @enderror">
                        @error('unit_reg')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="serial_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Seri <span class="text-red-500 pool-required">*</span></label>
                        <input type="text" name="serial_number" id="serial_number" value="{{ old('serial_number', $unit->serial_number) }}" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('serial_number') border-red-500 @enderror">
                        @error('serial_number')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <label for="expired_stnk" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Expired STNK <span class="text-red-500 pool-required">*</span></label>
                        <input type="date" name="expired_stnk" id="expired_stnk" value="{{ old('expired_stnk', $unit->expired_stnk ? $unit->expired_stnk->format('Y-m-d') : '') }}" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('expired_stnk') border-red-500 @enderror">
                        @error('expired_stnk')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="expired_kir" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Expired KIR <span class="text-red-500 pool-required">*</span></label>
                        <input type="date" name="expired_kir" id="expired_kir" value="{{ old('expired_kir', $unit->expired_kir ? $unit->expired_kir->format('Y-m-d') : '') }}" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('expired_kir') border-red-500 @enderror">
                        @error('expired_kir')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="expired_kp" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Expired KP <span class="text-red-500 pool-required">*</span></label>
                        <input type="date" name="expired_kp" id="expired_kp" value="{{ old('expired_kp', $unit->expired_kp ? $unit->expired_kp->format('Y-m-d') : '') }}" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('expired_kp') border-red-500 @enderror">
                        @error('expired_kp')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="kir" class="block text-sm font-medium text-gray-700 mb-1">KIR <span class="text-red-500 pool-required">*</span></label>
                        <input type="text" name="kir" id="kir" value="{{ old('kir', $unit->kir) }}" 
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('kir') border-red-500 @enderror">
                        @error('kir')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500 pool-required">*</span></label>
                        <select name="status" id="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('status') border-red-500 @enderror">
                            <option value="aktif" {{ old('status', $unit->status) == 'aktif' ? 'selected' : '' }}>Aktif</option>
                            <option value="nonaktif" {{ old('status', $unit->status) == 'nonaktif' ? 'selected' : '' }}>Non-Aktif</option>
                            <option value="maintenance" {{ old('status', $unit->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                        @error('status')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">Rute <span class="text-red-500 pool-required">*</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3" id="route-cards-container">
                        @foreach($routes as $route)
                        <div class="route-card relative border rounded-md p-3 hover:border-indigo-500 cursor-pointer transition-all duration-200 @if(in_array($route->id, old('route_ids', $unit->routes->pluck('id')->toArray()))) selected bg-indigo-50 border-indigo-500 @endif" data-route-id="{{ $route->id }}">
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ $route->route_number }}</span>
                                <span class="text-xs text-gray-500">{{ $route->name }}</span>
                            </div>
                            <div class="absolute top-2 right-2 text-indigo-600 route-check-icon @if(!in_array($route->id, old('route_ids', $unit->routes->pluck('id')->toArray()))) hidden @endif">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div id="route-ids-container"></div>
                    <p class="mt-2 text-xs text-gray-500">Klik pada kartu untuk memilih rute</p>
                    @error('route_ids')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                

            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isPoolYes = document.getElementById('is_pool_yes');
        const isPoolNo = document.getElementById('is_pool_no');
        const poolUnitFields = document.getElementById('pool-unit-fields');
        const poolRequiredFields = document.querySelectorAll('.pool-required');
        const form = document.getElementById('edit-unit-form');
        
        // Function to toggle required fields based on is_pool value
        function toggleRequiredFields() {
            const isPool = isPoolYes.checked;
            
            // Show/hide the pool unit fields section
            poolUnitFields.style.display = isPool ? 'block' : 'none';
            
            // Toggle required attribute on fields
            document.querySelectorAll('#pool-unit-fields input, #pool-unit-fields select').forEach(field => {
                field.required = isPool;
            });
            
            // Show/hide the required asterisk
            poolRequiredFields.forEach(span => {
                span.style.display = isPool ? 'inline' : 'none';
            });
        }
        
        // Initial setup
        toggleRequiredFields();
        
        // Add event listeners
        isPoolYes.addEventListener('change', toggleRequiredFields);
        isPoolNo.addEventListener('change', toggleRequiredFields);
        
        // Make route cards interactive
        const routeCards = document.querySelectorAll('.route-card');
        const routeIdsContainer = document.getElementById('route-ids-container');
        
        // Function to update hidden input fields for selected routes
        function updateRouteInputs() {
            // Clear previous inputs
            routeIdsContainer.innerHTML = '';
            
            // Create hidden inputs for each selected route
            document.querySelectorAll('.route-card.selected').forEach(card => {
                const routeId = card.getAttribute('data-route-id');
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'route_ids[]';
                input.value = routeId;
                routeIdsContainer.appendChild(input);
            });
        }
        
        // Initialize hidden inputs for pre-selected routes
        updateRouteInputs();
        
        // Add click event listener to each route card
        routeCards.forEach(card => {
            card.addEventListener('click', function() {
                const checkIcon = this.querySelector('.route-check-icon');
                
                // Toggle selected state
                this.classList.toggle('selected');
                
                // Toggle visual styling
                if (this.classList.contains('selected')) {
                    this.classList.add('bg-indigo-50', 'border-indigo-500');
                    if (checkIcon) checkIcon.classList.remove('hidden');
                } else {
                    this.classList.remove('bg-indigo-50', 'border-indigo-500');
                    if (checkIcon) checkIcon.classList.add('hidden');
                }
                
                // Update hidden inputs
                updateRouteInputs();
            });
        });
        
        // Form validation before submit
        form.addEventListener('submit', function(e) {
            if (isPoolYes.checked) {
                // If unit is in pool, all fields are required
                let valid = true;
                document.querySelectorAll('#pool-unit-fields input[required], #pool-unit-fields select[required]').forEach(field => {
                    if (!field.value) {
                        valid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
                
                // Only validate routes if the unit is a pool unit
                if (isPoolYes.checked) {
                    // Check if at least one route is selected
                    const selectedRoutes = document.querySelectorAll('.route-card.selected');
                    if (selectedRoutes.length === 0) {
                        valid = false;
                        document.querySelector('.route-card').classList.add('border-red-500');
                        alert('Mohon pilih minimal satu rute');
                    }
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Mohon lengkapi semua field yang ditandai dengan tanda bintang (*)');
                }
            }
        });
    });
</script>
@endpush

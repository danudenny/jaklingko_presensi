<!-- Edit Unit Modal Content -->
<div class="sm:flex sm:items-start">
    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
        <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
        </svg>
    </div>
    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
            Edit Unit
        </h3>
    </div>
    <div class="absolute top-0 right-0 pt-4 pr-4">
        <button id="cancel-edit-unit" type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <span class="sr-only">Close</span>
            <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>

<div class="mt-4">
    <form id="edit-unit-form" method="POST" action="{{ route('units.update', $unit->id) }}">
        @csrf
        @method('PUT')
        <div class="border-t border-gray-200 pt-4">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="edit_unit_number" class="block text-sm font-medium text-gray-700">Unit Number</label>
                    <div class="mt-1">
                        <input type="text" name="unit_number" id="edit_unit_number" value="{{ $unit->unit_number }}" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_unit_number_error"></p>
                </div>

                <div class="sm:col-span-3">
                    <label for="edit_plate_number" class="block text-sm font-medium text-gray-700">Plate Number</label>
                    <div class="mt-1">
                        <input type="text" name="plate_number" id="edit_plate_number" value="{{ $unit->plate_number }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_plate_number_error"></p>
                </div>

                <div class="sm:col-span-3">
                    <label for="edit_unit_reg" class="block text-sm font-medium text-gray-700">Unit Registration</label>
                    <div class="mt-1">
                        <input type="text" name="unit_reg" id="edit_unit_reg" value="{{ $unit->unit_reg }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_unit_reg_error"></p>
                </div>

                <div class="sm:col-span-3">
                    <label for="edit_kir" class="block text-sm font-medium text-gray-700">KIR</label>
                    <div class="mt-1">
                        <input type="text" name="kir" id="edit_kir" value="{{ $unit->kir }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_kir_error"></p>
                </div>

                <div class="sm:col-span-3">
                    <label for="edit_expired_license" class="block text-sm font-medium text-gray-700">License Expiry Date</label>
                    <div class="mt-1">
                        <input type="date" name="expired_license" id="edit_expired_license" value="{{ $unit->expired_license ? $unit->expired_license->format('Y-m-d') : '' }}" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_expired_license_error"></p>
                </div>

                <div class="sm:col-span-3">
                    <label for="edit_status" class="block text-sm font-medium text-gray-700">Status</label>
                    <div class="mt-1">
                        <select id="edit_status" name="status" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                            <option value="aktif" {{ $unit->status === 'aktif' ? 'selected' : '' }}>Active</option>
                            <option value="maintenance" {{ $unit->status === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                            <option value="nonaktif" {{ $unit->status === 'nonaktif' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_status_error"></p>
                </div>

                <div class="sm:col-span-6">
                    <label for="edit_notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <div class="mt-1">
                        <textarea id="edit_notes" name="notes" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">{{ $unit->notes }}</textarea>
                    </div>
                    <p class="mt-1 text-sm text-red-600" id="edit_notes_error"></p>
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
                            <button type="button" id="edit-select-all-routes" class="text-xs text-blue-600 hover:text-blue-800">Pilih Semua</button>
                            <span class="text-gray-400 mx-1">|</span>
                            <button type="button" id="edit-deselect-all-routes" class="text-xs text-blue-600 hover:text-blue-800">Batalkan Pilihan</button>
                        </div>
                    </div>
                    <div class="border-b border-gray-300">
                        <div class="px-4 py-2">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" id="edit-route-search" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Cari rute...">
                            </div>
                        </div>
                    </div>
                    <div class="max-h-60 overflow-y-auto p-2 bg-white">
                        <div class="space-y-1" id="edit-routes-container">
                            @foreach($routes as $route)
                                <div class="flex items-center p-2 hover:bg-gray-50 rounded-md route-item">
                                    <input
                                        id="edit-route-{{ $route->id }}"
                                        type="checkbox"
                                        name="route_ids[]"
                                        value="{{ $route->id }}"
                                        {{ in_array($route->id, $unit->routes->pluck('id')->toArray()) ? 'checked' : '' }}
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                        data-route-number="{{ strtolower($route->route_number) }}"
                                    >
                                    <label for="edit-route-{{ $route->id }}" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer">
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
                        <div id="edit-no-routes-found" class="hidden py-4 text-center text-sm text-gray-500">
                            No routes found matching your search.
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-2 border-t border-gray-300">
                        <span class="text-xs text-gray-500">Selected: <span id="edit-selected-routes-count">{{ count($unit->routes) }}</span></span>
                    </div>
                </div>
                <p class="mt-1 text-sm text-red-600" id="edit_routes_error"></p>
            </div>
        </div>

        <div class="mt-6 sm:mt-6 sm:flex sm:flex-row-reverse">
            <button type="submit" class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                Update Unit
            </button>
            <button id="cancel-edit-unit" type="button" class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                Cancel
            </button>
        </div>
    </form>
</div>

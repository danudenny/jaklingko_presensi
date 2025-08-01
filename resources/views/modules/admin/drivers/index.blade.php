@extends('modules.admin.layouts.main')

@section('title', 'Drivers Management')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Pengemudi</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('drivers.create') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-plus"></i>
                    Tambah Pengemudi
                </a>
                <a href="{{ route('drivers.import') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-file-import"></i>
                    Import
                </a>
                <a href="{{ route('driver.schedule.settings') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-md hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-cog"></i>
                    Pengaturan Jadwal
                </a>
                <a href="{{ route('drivers.export') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-teal-600 border border-transparent rounded-md hover:bg-teal-500 active:bg-teal-700 focus:outline-none focus:border-teal-700 focus:ring ring-teal-300 disabled:opacity-25">
                    <i class="mr-2 fas fa-file-excel"></i>
                    Export Excel
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <!-- Drivers Table -->
    <x-card id="drivers-table-container">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Daftar Pengemudi</h2>
                    <p class="mt-1 text-sm text-gray-600">Total Pengemudi: {{ $drivers->total() }}</p>
                </div>
                <div class="flex space-x-2">
                    <button type="button" id="toggle-filter" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="mr-2 fas fa-filter"></i> Pencarian
                    </button>
                </div>
            </div>

            <!-- Advanced Filter Form -->
            <div id="filter-form" class="hidden p-4 mb-6 border border-gray-200 rounded-lg bg-gray-50">
                <form method="GET" action="{{ route('drivers.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
                            <input type="text" name="name" id="name" value="{{ request('name') }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="ktp" class="block text-sm font-medium text-gray-700">No KTP</label>
                            <input type="text" name="ktp" id="ktp" value="{{ request('ktp') }}" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Tipe</label>
                            <select name="type" id="type" class="block w-full px-3 py-2 mt-1 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Tipe</option>
                                <option value="batangan" {{ request('type') == 'batangan' ? 'selected' : '' }}>Batangan</option>
                                <option value="cadangan" {{ request('type') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="block w-full px-3 py-2 mt-1 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Status</option>
                                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Non Aktif</option>
                                <option value="cuti" {{ request('status') == 'cuti' ? 'selected' : '' }}>Cuti</option>
                            </select>
                        </div>

                        <div>
                            <label for="route" class="block text-sm font-medium text-gray-700">Rute</label>
                            <select name="route" id="route" class="block w-full px-3 py-2 mt-1 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Rute</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" {{ request('route') == $route->id ? 'selected' : '' }}>
                                        {{ $route->route_number }} - {{ $route->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div x-data="{ open: false, search: '', selected: '{{ request('unit') }}' }" class="relative">
                            <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
                            <input
                                type="text"
                                placeholder="Cari unit..."
                                class="block w-full mt-1 bg-white border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                x-model="search"
                                x-on:focus="open = true"
                                x-on:input="open = true"
                                autocomplete="off"
                                :value="selected ? ($refs.unitList.querySelector('[data-value=\'' + selected + '\']') ? $refs.unitList.querySelector('[data-value=\'' + selected + '\']').textContent : search) : search"
                            >
                            <input type="hidden" name="unit" :value="selected">
                            <div
                                x-show="open"
                                x-on:click.away="open = false"
                                class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-48"
                            >
                                <ul x-ref="unitList">
                                    <li
                                        x-show="search === ''"
                                        class="px-4 py-2 italic text-gray-400"
                                    >Pilih unit</li>
                                    @foreach($units as $unit)
                                        <li
                                            x-show="search === '' || '{{ strtolower($unit->unit_number . ' ' . $unit->plate_number) }}'.includes(search.toLowerCase())"
                                            @click="selected = '{{ $unit->id }}'; open = false"
                                            data-value="{{ $unit->id }}"
                                            class="px-4 py-2 cursor-pointer hover:bg-indigo-100 {{ request('unit') == $unit->id ? 'bg-indigo-50' : '' }}"
                                        >{{ $unit->unit_number }} @if($unit->plate_number) ({{ $unit->plate_number }})@endif</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('drivers.index') }}" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Reset
                        </a>
                        <button type="submit" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Apply Filter
                        </button>
                    </div>
                </form>
            </div>

            @if(request()->anyFilled(['name', 'ktp', 'type', 'status', 'unit', 'route']))
                <div class="mb-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-700">Active Filters:</span>

                        @if(request('name'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Nama: {{ request('name') }}
                                <a href="{{ request()->fullUrlWithoutQuery(['name']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('ktp'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                No KTP: {{ request('ktp') }}
                                <a href="{{ request()->fullUrlWithoutQuery(['ktp']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('type'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Tipe: {{ ucfirst(request('type')) }}
                                <a href="{{ request()->fullUrlWithoutQuery(['type']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('status'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Status: {{ ucfirst(request('status')) }}
                                <a href="{{ request()->fullUrlWithoutQuery(['status']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('unit'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Unit: {{ optional($units->firstWhere('id', request('unit')))->unit_number }}
                                <a href="{{ request()->fullUrlWithoutQuery(['unit']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif

                        @if(request('route'))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Rute: {{ optional($routes->firstWhere('id', request('route')))->route_number }} - {{ optional($routes->firstWhere('id', request('route')))->name }}
                                <a href="{{ request()->fullUrlWithoutQuery(['route']) }}" class="ml-1.5 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">No</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nama</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rute</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Unit</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Status</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Telepon</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="drivers-table-body">
                        @forelse ($drivers as $key => $driver)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $key + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 {{ $driver->type === 'batangan' ? 'bg-blue-300 text-blue-800' : 'bg-purple-300 text-purple-800' }} rounded-full">
                                        <span class="font-medium ">{{ collect(explode(' ', $driver->name))->map(fn($word) => substr($word, 0, 1))->implode('') }}
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $driver->name }}</div>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $driver->type === 'batangan' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                            {{ ucfirst($driver->type) }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($driver->routes && $driver->routes->count())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($driver->routes as $route)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $route->route_number }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($driver->units && $driver->units->count())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($driver->units as $unit)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                KWK-{{ $unit->unit_number }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'aktif' => 'bg-green-100 text-green-800',
                                        'nonaktif' => 'bg-red-100 text-red-800',
                                        'cuti' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $statusLabel = [
                                        'aktif' => 'Aktif',
                                        'nonaktif' => 'Non Aktif',
                                        'cuti' => 'Cuti'
                                    ];
                                @endphp
                                @if($driver->status === 'cuti')
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$driver->status] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $statusLabel[$driver->status] }}
                                    </span>
                                @else
                                    <div class="flex items-center">
                                        <div class="flex flex-col items-center">
                                            <label for="status-toggle-{{ $driver->id }}" class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" id="status-toggle-{{ $driver->id }}" data-driver-id="{{ $driver->id }}" class="sr-only status-toggle peer" {{ $driver->status === 'aktif' ? 'checked' : '' }}>
                                                <div class="w-11 h-6 bg-gray-200 rounded-full peer-focus:ring-4 peer-focus:ring-blue-300 peer-checked:bg-green-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                            </label>
                                            <span class="mt-1 text-xs text-gray-500 status-label">{{ $statusLabel[$driver->status] }}</span>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                @if($driver->phone)
                                    <div>{{ $driver->phone }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                <a href="{{ route('drivers.show', $driver) }}" class="mr-2 text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('drivers.edit', $driver) }}" class="mr-2 text-indigo-600 hover:text-indigo-900">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form class="inline-block delete-driver-form" method="POST" action="{{ route('drivers.destroy', $driver) }}" data-driver-id="{{ $driver->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-sm text-center text-gray-500 whitespace-nowrap">No drivers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="px-6 py-4 ">
                {{ $drivers->links() }}
            </div>
        </x-card>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete driver form submission
        const deleteForms = document.querySelectorAll('.delete-driver-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!confirm('Are you sure you want to delete this driver?')) {
                    return;
                }

                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        toastr.success(data.message);

                        // Refresh the table
                        refreshDriversTable();
                    } else {
                        // Show error message
                        toastr.error(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('An unexpected error occurred');
                });
            });
        });
        
        // Status toggle functionality
        const statusToggles = document.querySelectorAll('.status-toggle');
        statusToggles.forEach(toggle => {
            toggle.addEventListener('change', function() {
                const driverId = this.dataset.driverId;
                const isChecked = this.checked;
                const toggleContainer = this.closest('.flex-col');
                const statusLabel = toggleContainer ? toggleContainer.querySelector('.status-label') : null;
                
                // Create a form data object
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                
                // Send the request to toggle status
                fetch(`{{ url('/drivers') }}/${driverId}/toggle-status`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the status label
                        statusLabel.textContent = data.statusLabel;
                        
                        // Show success message
                        toastr.success(data.message);
                    } else {
                        // Revert the toggle if there was an error
                        this.checked = !isChecked;
                        
                        // Show error message
                        toastr.error(data.message || 'Failed to update status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Revert the toggle
                    this.checked = !isChecked;
                    
                    // Show error message
                    toastr.error('An unexpected error occurred');
                });
            });
        });

        // Helper functions
        function refreshDriversTable() {
            fetch('{{ route('drivers.index') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const tableContainer = doc.getElementById('drivers-table-container');

                if (tableContainer) {
                    document.getElementById('drivers-table-container').innerHTML = tableContainer.innerHTML;

                    // Reinitialize event listeners for new elements
                    initializeEventListeners();
                }
            })
            .catch(error => {
                console.error('Error refreshing table:', error);
            });
        }

        function initializeEventListeners() {
            // Re-attach delete event listeners
            document.querySelectorAll('.delete-driver-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    if (!confirm('Are you sure you want to delete this driver?')) {
                        return;
                    }

                    const formData = new FormData(this);

                    fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            toastr.success(data.message);
                            refreshDriversTable();
                        } else {
                            toastr.error(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                });
            });
        }

        // Toggle filter form
        document.getElementById('toggle-filter').addEventListener('click', function() {
            const filterForm = document.getElementById('filter-form');
            filterForm.classList.toggle('hidden');
        });
    });
    </script>
    @endpush

@endsection

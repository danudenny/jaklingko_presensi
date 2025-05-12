@extends('modules.admin.layouts.main')

@section('title', 'Drivers Management')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Pengemudi</x-slot>
        <x-slot name="actions">
            <div class="flex space-x-2">
                <a href="{{ route('drivers.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:border-indigo-700 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-plus mr-2"></i>
                    Tambah Pengemudi
                </a>
                <a href="{{ route('drivers.import') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <i class="fas fa-file-import mr-2"></i>
                    Import
                </a>
            </div>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <!-- Drivers Table -->
    <x-card id="drivers-table-container">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Daftar Pengemudi</h2>
                    <p class="mt-1 text-sm text-gray-600">Total Pengemudi: {{ $drivers->total() }}</p>
                </div>
                <div class="flex space-x-2">
                    <button type="button" id="toggle-filter" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-filter mr-2"></i> Pencarian
                    </button>
                </div>
            </div>

            <!-- Advanced Filter Form -->
            <div id="filter-form" class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200 hidden">
                <form method="GET" action="{{ route('drivers.index') }}" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nama</label>
                            <input type="text" name="name" id="name" value="{{ request('name') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="ktp" class="block text-sm font-medium text-gray-700">No KTP</label>
                            <input type="text" name="ktp" id="ktp" value="{{ request('ktp') }}" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Tipe</label>
                            <select name="type" id="type" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Tipe</option>
                                <option value="batangan" {{ request('type') == 'batangan' ? 'selected' : '' }}>Batangan</option>
                                <option value="cadangan" {{ request('type') == 'cadangan' ? 'selected' : '' }}>Cadangan</option>
                            </select>
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Semua Status</option>
                                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Non Aktif</option>
                                <option value="cuti" {{ request('status') == 'cuti' ? 'selected' : '' }}>Cuti</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('drivers.index') }}" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Reset
                        </a>
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Apply Filter
                        </button>
                    </div>
                </form>
            </div>

            @if(request()->anyFilled(['name', 'ktp', 'type', 'status']))
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
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No KTP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No KPP</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="drivers-table-body">
                        @forelse ($drivers as $key => $driver)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $key + 1 }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                        <span class="text-white font-medium">{{ substr($driver->name, 0, 1) }}</span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $driver->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $driver->ktp }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $driver->kpp }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $driver->type === 'batangan' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                    {{ ucfirst($driver->type) }}
                                </span>
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
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$driver->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ $statusLabel[$driver->status] ?? ucfirst($driver->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($driver->phone)
                                    <div>{{ $driver->phone }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('drivers.show', $driver) }}" class="text-blue-600 hover:text-blue-900 mr-2">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('drivers.edit', $driver) }}" class="text-indigo-600 hover:text-indigo-900 mr-2">
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
                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No drivers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Links -->
            <div class="px-6 py-4">
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
                        showFlashMessage('success', data.message);

                        // Refresh the table
                        refreshDriversTable();
                    } else {
                        // Show error message
                        showFlashMessage('error', data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFlashMessage('error', 'An error occurred while processing your request');
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

        function showFlashMessage(type, message) {
            // Create flash message element
            const flashContainer = document.createElement('div');
            const id = type === 'success' ? 'flash-success' : 'flash-error';
            const bgColor = type === 'success' ? 'bg-green-50' : 'bg-red-50';
            const textColor = type === 'success' ? 'text-green-800' : 'text-red-800';
            const iconColor = type === 'success' ? 'text-green-600' : 'text-red-600';
            const hoverColor = type === 'success' ? 'hover:text-green-900' : 'hover:text-red-900';

            flashContainer.id = id;
            flashContainer.className = `mb-4 rounded-lg ${bgColor} p-4 text-sm ${textColor} flex justify-between items-center`;
            flashContainer.setAttribute('role', 'alert');

            const contentDiv = document.createElement('div');
            contentDiv.className = 'flex items-center';

            const icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            icon.setAttribute('class', 'h-5 w-5 mr-2');
            icon.setAttribute('fill', 'none');
            icon.setAttribute('viewBox', '0 0 24 24');
            icon.setAttribute('stroke', 'currentColor');

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            if (type === 'success') {
                path.setAttribute('d', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z');
            } else {
                path.setAttribute('d', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');
            }
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-width', '2');

            icon.appendChild(path);

            const messageSpan = document.createElement('span');
            messageSpan.textContent = message;

            contentDiv.appendChild(icon);
            contentDiv.appendChild(messageSpan);

            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = `${iconColor} ${hoverColor}`;
            closeButton.onclick = function() {
                flashContainer.remove();
            };

            const closeIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            closeIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
            closeIcon.setAttribute('class', 'h-5 w-5');
            closeIcon.setAttribute('fill', 'none');
            closeIcon.setAttribute('viewBox', '0 0 24 24');
            closeIcon.setAttribute('stroke', 'currentColor');

            const closePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            closePath.setAttribute('d', 'M6 18L18 6M6 6l12 12');
            closePath.setAttribute('stroke-linecap', 'round');
            closePath.setAttribute('stroke-linejoin', 'round');
            closePath.setAttribute('stroke-width', '2');

            closeIcon.appendChild(closePath);
            closeButton.appendChild(closeIcon);

            flashContainer.appendChild(contentDiv);
            flashContainer.appendChild(closeButton);

            // Remove existing flash messages of the same type
            const existingFlash = document.getElementById(id);
            if (existingFlash) {
                existingFlash.remove();
            }

            // Add the flash message to the top of the page
            const firstChild = document.querySelector('.container').firstChild;
            document.querySelector('.container').insertBefore(flashContainer, firstChild);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (document.getElementById(id)) {
                    document.getElementById(id).remove();
                }
            }, 5000);
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
                            showFlashMessage('success', data.message);
                            refreshDriversTable();
                        } else {
                            showFlashMessage('error', data.message || 'An error occurred');
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

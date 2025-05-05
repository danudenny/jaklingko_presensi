@extends('modules.admin.layouts.main')

@section('title', 'Manajemen Rute')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Manajemen Rute</x-slot>
        <x-slot name="actions">
            <x-drawer-trigger id="create-route-drawer" title="Add New Route" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-plus mr-2"></i>
                Tambah Rute
            </x-drawer-trigger>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <!-- Routes Table -->
    <x-card id="routes-table-container">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Rute</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Rute</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="routes-table-body">
                    @forelse ($routes as $key => $route)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $key+1 }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $route->route_number }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-500 flex items-center justify-center">
                                    <span class="text-white font-medium">{{ substr($route->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $route->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'aktif' => 'bg-green-100 text-green-800',
                                    'nonaktif' => 'bg-red-100 text-red-800'
                                ];
                                $statusLabel = [
                                    'aktif' => 'Aktif',
                                    'nonaktif' => 'Non Aktif'
                                ];
                            @endphp
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$route->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabel[$route->status] ?? ucfirst($route->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                                type="button"
                                class="text-blue-600 hover:text-blue-900 mr-2"
                                x-data="{}"
                                @click="$dispatch('open-drawer', { id: 'view-route-drawer', title: 'View Route Details', routeId: {{ $route->id }} })"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                            <button
                                type="button"
                                class="text-indigo-600 hover:text-indigo-900 mr-2"
                                x-data="{}"
                                @click="$dispatch('open-drawer', { id: 'edit-route-drawer', title: 'Edit Route', routeId: {{ $route->id }} })"
                            >
                                <i class="fas fa-edit"></i>
                            </button>
                            <form class="inline-block delete-route-form" method="POST" action="{{ route('routes.destroy', $route) }}" data-route-id="{{ $route->id }}">
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
                        <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No routes found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
</div>

<!-- Create Route Drawer -->
<x-drawer id="create-route-drawer" max-width="md">
    <form id="create-route-form" method="POST" action="{{ route('routes.store') }}" class="space-y-4">
        @csrf
        <div>
            <x-input-label for="route_number" value="Route Number" />
            <x-text-input id="route_number" name="route_number" type="text" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" id="route_number_error" />
        </div>

        <div>
            <x-input-label for="name" value="Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" id="name_error" />
        </div>

        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                <option value="aktif">Aktif</option>
                <option value="nonaktif">Non Aktif</option>
            </select>
            <x-input-error class="mt-2" id="status_error" />
        </div>

        <div class="flex justify-end space-x-2">
            <x-secondary-button type="button" x-data="{}" @click="$dispatch('close-drawer', { id: 'create-route-drawer' })">
                Batal
            </x-secondary-button>
            <x-primary-button type="submit">
                Simpan
            </x-primary-button>
        </div>
    </form>
</x-drawer>

<!-- Edit Route Drawer -->
<x-drawer id="edit-route-drawer" max-width="md">
    <div id="edit-route-content" class="space-y-4">
        <div class="text-center py-10">
            <i class="fas fa-spinner fa-spin"></i>
            <p class="mt-2 text-sm text-gray-500">Loading route data...</p>
        </div>
    </div>
</x-drawer>

<!-- View Route Drawer -->
<x-drawer id="view-route-drawer" max-width="md">
    <div id="view-route-content" class="space-y-4">
        <div class="text-center py-10">
            <i class="fas fa-spinner fa-spin"></i>
            <p class="mt-2 text-sm text-gray-500">Loading route details...</p>
        </div>
    </div>
</x-drawer>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Create route form submission
    const createRouteForm = document.getElementById('create-route-form');
    if (createRouteForm) {
        createRouteForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close drawer
                    window.dispatchEvent(new CustomEvent('close-drawer', {
                        detail: { id: 'create-route-drawer' }
                    }));

                    // Show success message
                    showFlashMessage('success', data.message || 'Route created successfully');

                    // Refresh the table
                    refreshRoutesTable();

                    // Reset form
                    createRouteForm.reset();
                } else {
                    // Show validation errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const errorElement = document.getElementById(`${field}_error`);
                            if (errorElement) {
                                errorElement.textContent = data.errors[field][0];
                            }
                        });
                    } else {
                        showFlashMessage('error', data.message || 'An error occurred');
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFlashMessage('error', 'An error occurred while processing your request');
            });
        });
    }

    // Handle view route drawer open
    window.addEventListener('open-drawer', function(e) {
        if (e.detail.id === 'view-route-drawer' && e.detail.routeId) {
            loadRouteDetails(e.detail.routeId, 'view');
        } else if (e.detail.id === 'edit-route-drawer' && e.detail.routeId) {
            loadRouteDetails(e.detail.routeId, 'edit');
        }
    });

    // Load route details for view or edit
    function loadRouteDetails(routeId, mode) {
        fetch(`/routes/${routeId}?mode=${mode}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (mode === 'view') {
                    document.getElementById('view-route-content').innerHTML = data.html;
                } else if (mode === 'edit') {
                    document.getElementById('edit-route-content').innerHTML = data.html;

                    // Initialize edit form submission
                    const editForm = document.getElementById('edit-route-form');
                    if (editForm) {
                        editForm.addEventListener('submit', function(e) {
                            e.preventDefault();

                            const formData = new FormData(this);

                            fetch(this.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Close drawer
                                    window.dispatchEvent(new CustomEvent('close-drawer', {
                                        detail: { id: 'edit-route-drawer' }
                                    }));

                                    // Show success message
                                    showFlashMessage('success', data.message || 'Route updated successfully');

                                    // Refresh the table
                                    refreshRoutesTable();
                                } else {
                                    // Show validation errors
                                    if (data.errors) {
                                        Object.keys(data.errors).forEach(field => {
                                            const errorElement = document.getElementById(`edit_${field}_error`);
                                            if (errorElement) {
                                                errorElement.textContent = data.errors[field][0];
                                            }
                                        });
                                    } else {
                                        showFlashMessage('error', data.message || 'An error occurred');
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showFlashMessage('error', 'An error occurred while processing your request');
                            });
                        });
                    }
                }
            } else {
                showFlashMessage('error', data.message || 'Failed to load route details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFlashMessage('error', 'An error occurred while loading route details');
        });
    }

    // Delete route form submission
    const deleteForms = document.querySelectorAll('.delete-route-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to delete this route?')) {
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
                    refreshRoutesTable();
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
    function refreshRoutesTable() {
        fetch('{{ route('routes.index') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const tableContainer = doc.getElementById('routes-table-container');

            if (tableContainer) {
                document.getElementById('routes-table-container').innerHTML = tableContainer.innerHTML;

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

        let path;
        if (type === 'success') {
            path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('d', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z');
        } else {
            path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('stroke-linecap', 'round');
            path.setAttribute('stroke-linejoin', 'round');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('d', 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z');
        }

        icon.appendChild(path);

        const messageSpan = document.createElement('span');
        messageSpan.textContent = message;

        contentDiv.appendChild(icon);
        contentDiv.appendChild(messageSpan);

        const closeButton = document.createElement('button');
        closeButton.setAttribute('type', 'button');
        closeButton.className = `${iconColor} ${hoverColor}`;
        closeButton.addEventListener('click', function() {
            flashContainer.remove();
        });

        const closeIcon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        closeIcon.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        closeIcon.setAttribute('class', 'h-5 w-5');
        closeIcon.setAttribute('fill', 'none');
        closeIcon.setAttribute('viewBox', '0 0 24 24');
        closeIcon.setAttribute('stroke', 'currentColor');

        const closePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        closePath.setAttribute('stroke-linecap', 'round');
        closePath.setAttribute('stroke-linejoin', 'round');
        closePath.setAttribute('stroke-width', '2');
        closePath.setAttribute('d', 'M6 18L18 6M6 6l12 12');

        closeIcon.appendChild(closePath);
        closeButton.appendChild(closeIcon);

        flashContainer.appendChild(contentDiv);
        flashContainer.appendChild(closeButton);

        // Insert flash message at the top of the content
        const flashMessages = document.querySelector('x-flash-message') || document.querySelector('.container');
        if (flashMessages) {
            flashMessages.parentNode.insertBefore(flashContainer, flashMessages);
        }

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (document.getElementById(id)) {
                document.getElementById(id).remove();
            }
        }, 5000);
    }

    function initializeEventListeners() {
        // Re-attach delete event listeners
        document.querySelectorAll('.delete-route-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!confirm('Are you sure you want to delete this route?')) {
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
                        refreshRoutesTable();
                    } else {
                        showFlashMessage('error', data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFlashMessage('error', 'An error occurred while processing your request');
                });
            });
        });

        // Re-attach view and edit buttons
        document.querySelectorAll('[data-action="view-route"]').forEach(button => {
            button.addEventListener('click', function() {
                const routeId = this.getAttribute('data-route-id');
                window.dispatchEvent(new CustomEvent('open-drawer', {
                    detail: { id: 'view-route-drawer', title: 'View Route Details', routeId: routeId }
                }));
            });
        });

        document.querySelectorAll('[data-action="edit-route"]').forEach(button => {
            button.addEventListener('click', function() {
                const routeId = this.getAttribute('data-route-id');
                window.dispatchEvent(new CustomEvent('open-drawer', {
                    detail: { id: 'edit-route-drawer', title: 'Edit Route', routeId: routeId }
                }));
            });
        });
    }
});
</script>
@endpush

@endsection

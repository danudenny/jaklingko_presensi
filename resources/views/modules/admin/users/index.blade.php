@extends('modules.admin.layouts.main')

@section('title', 'Users Management')

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Users Management</x-slot>
        <x-slot name="actions">
            <button type="button" @click="$dispatch('open-modal', 'create-user-modal')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add User
            </button>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <!-- Users Table -->
    <x-card id="users-table-container">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created At</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $user)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium">{{ substr($user->name, 0, 1) }}</span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($user->is_superadmin)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Super Admin</span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Admin</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="$dispatch('open-modal', 'edit-user-modal-{{ $user->id }}')" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                            
                            @if ($user->id !== Auth::id())
                            <form class="inline-block delete-user-form" method="POST" action="{{ route('users.destroy', $user) }}" data-user-id="{{ $user->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                            </form>
                            @endif
                        </td>
                    </tr>

                    <!-- Edit User Modal -->
                    <x-modal id="edit-user-modal-{{ $user->id }}" maxWidth="md">
                        <div class="p-6">
                            <h2 class="text-lg font-medium text-gray-900 mb-4">
                                Edit User
                            </h2>
                            
                            <form id="edit-user-form-{{ $user->id }}" class="edit-user-form" method="POST" action="{{ route('users.update', $user) }}" data-user-id="{{ $user->id }}">
                                @csrf
                                @method('PATCH')
                                
                                <div class="mb-4">
                                    <x-input-label for="name-{{ $user->id }}" value="Name" />
                                    <x-text-input id="name-{{ $user->id }}" class="block mt-1 w-full" type="text" name="name" value="{{ $user->name }}" required autofocus />
                                    <x-input-error :message="$errors->first('name')" class="mt-2" />
                                </div>
                                
                                <div class="mb-4">
                                    <x-input-label for="email-{{ $user->id }}" value="Email" />
                                    <x-text-input id="email-{{ $user->id }}" class="block mt-1 w-full" type="email" name="email" value="{{ $user->email }}" required />
                                    <x-input-error :message="$errors->first('email')" class="mt-2" />
                                </div>
                                
                                <div class="mb-4">
                                    <x-input-label for="password-{{ $user->id }}" value="Password (leave blank to keep current)" />
                                    <x-text-input id="password-{{ $user->id }}" class="block mt-1 w-full" type="password" name="password" />
                                    <x-input-error :message="$errors->first('password')" class="mt-2" />
                                </div>
                                
                                <div class="mb-4">
                                    <x-input-label for="password_confirmation-{{ $user->id }}" value="Confirm Password" />
                                    <x-text-input id="password_confirmation-{{ $user->id }}" class="block mt-1 w-full" type="password" name="password_confirmation" />
                                </div>
                                
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_superadmin" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" {{ $user->is_superadmin ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-600">Super Admin Access</span>
                                    </label>
                                </div>
                                
                                <div id="edit-form-errors-{{ $user->id }}" class="mb-4 text-sm text-red-600 space-y-1" style="display: none;"></div>
                                
                                <div class="flex justify-end mt-6">
                                    <button type="button" @click="$dispatch('close-modal', 'edit-user-modal-{{ $user->id }}')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md mr-2">
                                        Cancel
                                    </button>
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">
                                        Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </x-modal>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No users found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <!-- Create User Modal -->
    <x-modal id="create-user-modal" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                Add New User
            </h2>
            
            <form id="create-user-form" method="POST" action="{{ route('users.store') }}">
                @csrf
                
                <div class="mb-4">
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                    <x-input-error :message="$errors->first('name')" class="mt-2" />
                </div>
                
                <div class="mb-4">
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required />
                    <x-input-error :message="$errors->first('email')" class="mt-2" />
                </div>
                
                <div class="mb-4">
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
                    <x-input-error :message="$errors->first('password')" class="mt-2" />
                </div>
                
                <div class="mb-4">
                    <x-input-label for="password_confirmation" value="Confirm Password" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required />
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_superadmin" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-600">Super Admin Access</span>
                    </label>
                </div>
                
                <div id="create-form-errors" class="mb-4 text-sm text-red-600 space-y-1" style="display: none;"></div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" @click="$dispatch('close-modal', 'create-user-modal')" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md mr-2">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </x-modal>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Create user form submission
    const createForm = document.getElementById('create-user-form');
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                
                if (data.success) {
                    // Close modal
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: 'create-user-modal' }));
                    
                    // Show success message
                    showFlashMessage('success', data.message);
                    
                    // Refresh the table
                    refreshUsersTable();
                    
                    // Reset the form
                    createForm.reset();
                } else {
                    // Show errors
                    const errorsDiv = document.getElementById('create-form-errors');
                    displayErrors(data.errors, errorsDiv);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.disabled = false;
            });
        });
    }
    
    // Edit user form submission
    const editForms = document.querySelectorAll('.edit-user-form');
    editForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const userId = this.getAttribute('data-user-id');
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                submitButton.disabled = false;
                
                if (data.success) {
                    // Close modal
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: `edit-user-modal-${userId}` }));
                    
                    // Show success message
                    showFlashMessage('success', data.message);
                    
                    // Refresh the table
                    refreshUsersTable();
                } else {
                    // Show errors
                    const errorsDiv = document.getElementById(`edit-form-errors-${userId}`);
                    displayErrors(data.errors, errorsDiv);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitButton.disabled = false;
            });
        });
    });
    
    // Delete user form submission
    const deleteForms = document.querySelectorAll('.delete-user-form');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to delete this user?')) {
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
                    refreshUsersTable();
                } else {
                    // Show error message
                    showFlashMessage('error', data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
    
    // Helper functions
    function refreshUsersTable() {
        fetch('{{ route('users.index') }}', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const tableContainer = doc.getElementById('users-table-container');
            
            if (tableContainer) {
                document.getElementById('users-table-container').innerHTML = tableContainer.innerHTML;
                
                // Reinitialize event listeners for new elements
                initializeEventListeners();
            }
        })
        .catch(error => {
            console.error('Error refreshing table:', error);
        });
    }
    
    function displayErrors(errors, container) {
        if (!container) return;
        
        container.style.display = 'block';
        container.innerHTML = '';
        
        if (typeof errors === 'object' && errors !== null) {
            const ul = document.createElement('ul');
            
            Object.keys(errors).forEach(field => {
                errors[field].forEach(message => {
                    const li = document.createElement('li');
                    li.textContent = message;
                    ul.appendChild(li);
                });
            });
            
            container.appendChild(ul);
        } else {
            container.textContent = 'An error occurred. Please try again.';
        }
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
        document.querySelectorAll('.delete-user-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to delete this user?')) {
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
                        refreshUsersTable();
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
});
</script>
@endpush

@endsection 
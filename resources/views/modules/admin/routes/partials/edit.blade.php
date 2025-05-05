<form id="edit-route-form" method="POST" action="{{ route('routes.update', $route) }}" class="space-y-4">
    @csrf
    @method('PUT')

    <div>
        <x-input-label for="edit_route_number" value="No. Rute" />
        <x-text-input id="edit_route_number" name="route_number" type="text" class="mt-1 block w-full" required value="{{ $route->route_number }}" />
        <x-input-error class="mt-2" id="edit_route_number_error" />
    </div>

    <div>
        <x-input-label for="edit_name" value="Nama Rute" />
        <x-text-input id="edit_name" name="name" type="text" class="mt-1 block w-full" required value="{{ $route->name }}" />
        <x-input-error class="mt-2" id="edit_name_error" />
    </div>

    <div>
        <x-input-label for="edit_status" value="Status" />
        <select id="edit_status" name="status" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
            <option value="aktif" {{ $route->status === 'aktif' ? 'selected' : '' }}>Aktif</option>
            <option value="nonaktif" {{ $route->status === 'nonaktif' ? 'selected' : '' }}>Non AKtif</option>
        </select>
        <x-input-error class="mt-2" id="edit_status_error" />
    </div>

    <div class="pt-5 mt-6 border-t border-gray-200 flex justify-end space-x-3">
        <x-secondary-button type="button" x-data="{}" @click="$dispatch('close-drawer', { id: 'edit-route-drawer' })">
            <i class="fas fa-times mr-2"></i>
            Kembali
        </x-secondary-button>
        <x-primary-button type="submit">
            <i class="fas fa-save mr-2"></i>
            Perbarui Data
        </x-primary-button>
    </div>
</form>

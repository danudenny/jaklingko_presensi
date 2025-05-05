<div class="space-y-6">
    <!-- Route Details -->
    <div>
        <h3 class="text-lg font-medium text-gray-900 mb-4">Informasi Rute</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500">No. Rute</p>
                <p class="mt-1 text-sm text-gray-900">{{ $route->route_number }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500">Nama Rute</p>
                <p class="mt-1 text-sm text-gray-900">{{ $route->name }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500">Status</p>
                <p class="mt-1">
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
                </p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-500">Dibuat</p>
                <p class="mt-1 text-sm text-gray-900">{{ $route->created_at->format('M d, Y H:i') }}</p>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="pt-5 mt-6 border-t border-gray-200 flex justify-end space-x-3">
        <x-secondary-button type="button" x-data="{}" @click="$dispatch('close-drawer', { id: 'view-route-drawer' })">
            <i class="fas fa-times mr-2"></i>
            Kembali
        </x-secondary-button>
    </div>
</div>

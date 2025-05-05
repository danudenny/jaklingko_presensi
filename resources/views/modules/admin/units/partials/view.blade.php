<!-- View Unit Modal Content -->
<div class="sm:flex sm:items-start">
    <div class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10">
        <svg class="w-6 h-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
    </div>
    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
        <h3 class="text-lg font-medium leading-6 text-gray-900" id="modal-title">
            Informasi Unit
        </h3>
    </div>
    <div class="absolute top-0 right-0 pt-4 pr-4">
        <button id="close-view-unit" type="button" class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <span class="sr-only">Tutup</span>
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<div class="mt-4">
    <div class="border-t border-gray-200">
        <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2">
            <div>
                <dl class="mt-2 border-t border-gray-200">
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">No Unit</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->unit_number }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">Plat Nomor</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->plate_number ?? 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">No Mesin</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->unit_reg ?? 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">No Rangka</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->serial_number ?? 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">KIR</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->kir ?? 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Berakhir STNK</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->expired_stnk ? $unit->expired_stnk->format('d M Y') : 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Berakhir KIR</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->expired_kir ? $unit->expired_kir->format('d M Y') : 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">Tanggal Berakhir KP</dt>
                        <dd class="text-sm text-gray-900 col-span-2">{{ $unit->expired_kp ? $unit->expired_kp->format('d M Y') : 'N/A' }}</dd>
                    </div>
                    <div class="py-3 grid grid-cols-3 gap-4 px-2 border-b">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="text-sm col-span-2">
                            @if($unit->status === 'aktif')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Aktif
                                </span>
                            @elseif($unit->status === 'maintenance')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Maintenance
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Non Aktif
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Routes Section -->
        <div class="mt-6">
            <h4 class="text-lg font-medium text-gray-900">Assigned Routes</h4>
            <div class="mt-2 border-t border-gray-200">
                @if($unit->routes->count() > 0)
                    <div class="grid grid-cols-1 gap-4 mt-4">
                        @foreach($unit->routes as $route)
                            <div class="flex items-center p-3 border border-gray-200 rounded-md">
                                <div class="flex-1">
                                    <div class="font-medium">{{ $route->route_number }}</div>
                                    <div class="text-sm text-gray-600">{{ $route->name }}</div>
                                </div>
                                <div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $route->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ ucfirst($route->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-4 text-center text-sm text-gray-500">
                        No routes assigned to this unit.
                    </div>
                @endif
            </div>
        </div>

        @if(isset($unit->schedules) && $unit->schedules->count() > 0)
            <div class="mt-6">
                <h4 class="text-lg font-medium text-gray-900">Schedules</h4>
                <div class="mt-2 border-t border-gray-200">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Route
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Driver
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($unit->schedules as $schedule)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $schedule->route ? $schedule->route->name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $schedule->driver ? $schedule->driver->name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $schedule->date }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $schedule->time }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($schedule->status === 'scheduled')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Scheduled
                                                </span>
                                            @elseif($schedule->status === 'completed')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Completed
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Cancelled
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <div class="mt-6 border-t border-gray-200 pt-4">
                <div class="py-4 px-2 text-center text-sm text-gray-500">
                    No schedules for this unit.
                </div>
            </div>
        @endif
    </div>
</div>

<div class="mt-5 sm:mt-6 sm:flex sm:flex-row-reverse">
    <button id="close-view-unit" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
        Close
    </button>
</div>

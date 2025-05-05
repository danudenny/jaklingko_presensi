@extends('modules.admin.layouts.main')

@section('title', 'Schedules')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* Override Select2 styles completely to fix the overlap issue */
        .select2-container {
            width: 100% !important;
        }

        .select2-container .select2-selection--multiple {
            min-height: 38px;
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            padding: 4px;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__rendered {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 0;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            background-color: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 0.25rem;
            margin: 0;
            padding: 2px 6px 2px 20px;
            position: relative;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
            position: absolute;
            left: 4px;
            top: 50%;
            transform: translateY(-50%);
            color: #4F46E5;
            margin: 0;
            padding: 0;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: bold;
        }

        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
            color: #312E81;
            background: none;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #6366F1;
            box-shadow: 0 0 0 1px rgba(99, 102, 241, 0.2);
        }

        .select2-container--default .select2-search--inline .select2-search__field {
            margin: 0;
            padding: 2px 4px;
            font-size: 0.875rem;
            line-height: 1.25rem;
            width: 100% !important;
        }

        .select2-dropdown {
            border-color: #D1D5DB;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto">
    <x-page-title>
        <x-slot name="title">Jadwal Pengemudi</x-slot>
        <x-slot name="actions">
            <a href="{{ route('schedules.generate.form') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 mr-2">
                <i class="fas fa-calendar-plus mr-2"></i>
                Auto-Generate
            </a>
            <a href="{{ route('schedules.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-plus mr-2"></i>
                Tambah Jadwal
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    @if(session('success'))
        <div class="p-4 mb-4 text-green-700 bg-green-100 border-l-4 border-green-500" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('generation_results'))
        <div class="p-4 mb-4 text-blue-700 bg-blue-100 border-l-4 border-blue-500" role="alert">
            <p class="font-bold">Schedule Generation Results</p>
            <p>Successfully created: {{ session('generation_results')['success'] }} schedules</p>
            <p>Failed: {{ session('generation_results')['failed'] }} schedules</p>

            @if(count(session('generation_results')['messages']) > 0)
                <button class="mt-2 text-blue-500 underline" onclick="toggleDetails()">
                    Show/Hide Details
                </button>
                <div id="generation-details" class="hidden mt-2 overflow-y-auto max-h-40">
                    <ul class="pl-5 list-disc">
                        @foreach(session('generation_results')['messages'] as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <!-- Filter Form -->
    <x-card class="mb-6">
        <form id="filter-form" action="{{ route('schedules.index') }}" method="GET">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Date Range Picker -->
                <div class="col-span-1 md:col-span-2">
                    <label for="date-range" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <div class="relative">
                        <div class="flex">
                            <div class="relative flex-1">
                                <input type="text" id="date-range" name="date_range" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Pilih Tanggal" autocomplete="off">
                                <input type="hidden" id="start_date" name="start_date" value="{{ $startDate }}">
                                <input type="hidden" id="end_date" name="end_date" value="{{ $endDate }}">
                            </div>
                        </div>
                    </div>
                    <div class="mt-2 flex space-x-2">
                        <button type="button" data-days="0" class="date-preset px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Hari Ini
                        </button>
                        <button type="button" data-days="7" class="date-preset px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            7 Hari Kedepan
                        </button>
                        <button type="button" data-days="15" class="date-preset px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            15 Hari Kedepan
                        </button>
                        <button type="button" data-days="30" class="date-preset px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            30 Hari Kedepan
                        </button>
                    </div>
                </div>

                <!-- Driver Selector -->
                <div>
                    <label for="driver_id" class="block text-sm font-medium text-gray-700 mb-1">Pengemudi</label>
                    <select id="driver_id" name="driver_id[]" class="js-example-basic-multiple block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" multiple="multiple">
                        @foreach($drivers as $driver)
                            <option value="{{ $driver->id }}" {{ in_array($driver->id, $driverIds) ? 'selected' : '' }}>
                                {{ $driver->name }} ({{ ucfirst($driver->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Unit Selector -->
                <div>
                    <label for="unit_id" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <select id="unit_id" name="unit_id[]" class="js-example-basic-multiple block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" multiple="multiple">
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ in_array($unit->id, $unitIds) ? 'selected' : '' }}>
                                {{ $unit->unit_number }} ({{ $unit->plate_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-filter mr-2"></i>
                    Cari
                </button>
                <a href="{{ route('schedules.index') }}" class="ml-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-times mr-2"></i>
                    Reset
                </a>
                @if(request('start_date') && request('end_date'))
                <a href="{{ route('schedules.export.excel', request()->all()) }}" class="ml-3 inline-flex items-center px-4 py-2 border border-green-500 rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-file-excel mr-2"></i>
                    Excel
                </a>
                <a href="{{ route('schedules.export.pdf', request()->all()) }}" class="ml-3 inline-flex items-center px-4 py-2 border border-red-500 rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-file-pdf mr-2"></i>
                    PDF
                </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Schedules Table -->
    <x-card>
        <div class="mb-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Daftar Jadwal</h3>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('schedules.index', ['start_date' => \Carbon\Carbon::parse($startDate)->subDay()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::parse($endDate)->subDay()->format('Y-m-d')]) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200">
                        <i class="fas fa-chevron-left mr-1"></i>
                        Hari Sebelumnya
                    </a>
                    <a href="{{ route('schedules.index', ['start_date' => \Carbon\Carbon::parse($startDate)->addDay()->format('Y-m-d'), 'end_date' => \Carbon\Carbon::parse($endDate)->addDay()->format('Y-m-d')]) }}" class="inline-flex items-center px-3 py-1 bg-gray-100 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200">
                        Hari Berikutnya
                        <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>

            <div class="mt-2 md:mt-0 flex flex-wrap gap-2">
                @if(count($selectedDrivers) > 0)
                    @foreach($selectedDrivers as $driver)
                        <div class="relative inline-block">
                            <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800 flex items-center">
                                <i class="fas fa-user mr-2"></i>
                                {{ $driver->name }}
                                <a href="{{ route('schedules.index', array_merge(
                                    request()->except('driver_id'),
                                    ['start_date' => $startDate, 'end_date' => $endDate, 'driver_id' => array_diff($driverIds, [$driver->id])]
                                )) }}" class="ml-2 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        </div>
                    @endforeach
                @endif

                @if(count($selectedUnits) > 0)
                    @foreach($selectedUnits as $unit)
                        <div class="relative inline-block">
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800 flex items-center">
                                <i class="fas fa-bus mr-2"></i>
                                {{ $unit->unit_number }}
                                <a href="{{ route('schedules.index', array_merge(
                                    request()->except('unit_id'),
                                    ['start_date' => $startDate, 'end_date' => $endDate, 'unit_id' => array_diff($unitIds, [$unit->id])]
                                )) }}" class="ml-2 text-green-500 hover:text-green-700">
                                    <i class="fas fa-times-circle"></i>
                                </a>
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">No.</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pengemudi</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rute</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center">Unit</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center">Shift</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center">Status</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center">Backup Pengemudi</th>
                        <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($schedules as $index => $schedule)
                        <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                            <td class="px-3 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900">
                                {{ $schedules->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $schedule->driver->name }}</div>
                                <div class="text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $schedule->driver->type == 'batangan' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $schedule->driver->type == 'batangan' ? 'Batangan' : 'Cadangan' }}
                                    </span>
                                    {{ $schedule->driver->employee_id ?? '' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $schedule->route->name }}</div>
                                <div class="text-sm text-gray-500">{{ $schedule->route->route_number }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900">{{ $schedule->unit->unit_number }}</div>
                                <div class="flex justify-center items-center">
                                    <div class="bg-yellow-300 border border-black rounded-lg p-1 px-2 flex justify-center items-center max-w-xs shadow-md">
                                        <div class="text-black font-bold text-xs tracking-wider">
                                            @php
                                                $plateNumber = $schedule->unit->plate_number;
                                                $formattedPlate = substr($plateNumber, 0, 1) . ' ' . substr($plateNumber, 1, 4) . ' ' . substr($plateNumber, 5);
                                            @endphp
                                            {{ $formattedPlate }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full {{ $schedule->shift == 'pagi' || $schedule->shift == 'morning' ? 'bg-yellow-100 text-yellow-800' : 'bg-indigo-100 text-indigo-800' }}">
                                    <i class="fa-solid fa-clock mr-2"></i>
                                    {{ $schedule->shift == 'pagi' || $schedule->shift == 'morning' ? 'Pagi' : 'Siang' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex items-center text-xs leading-5 font-semibold rounded-full {{
                                    ($schedule->status == 'scheduled' ? 'bg-green-100 text-green-800' :
                                    ($schedule->status == 'unavailable' ? 'bg-red-100 text-red-800' :
                                    ($schedule->status == 'on_leave' ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'))) }}">
                                    <i class="fa-solid fa-circle mr-2"></i>
                                    {{ ucfirst(str_replace('_', ' ', $schedule->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($schedule->backup_driver_id)
                                    <div class="text-sm text-gray-900">{{ $schedule->backupDriver->name }}</div>
                                @else
                                    <div class="text-sm text-gray-500">
                                        <i class="fa-solid fa-user mr-2"></i>
                                        -
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-center justify-center items-center flex ">
                                <div class="flex space-x-2">
                                    <a href="{{ route('schedules.show', $schedule) }}" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('schedules.edit', $schedule) }}" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($schedule->status == 'scheduled')
                                        <a href="{{ route('schedules.unavailable', $schedule) }}" class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-4 text-center text-gray-500 whitespace-nowrap
                                bg-yellow-50 text-sm font-medium">
                                <i class="fas fa-exclamation-circle mr-2"></i>
                                Tidak ada jadwal yang ditemukan.
                                <a href="{{ route('schedules.create') }}" class="text-blue-600 hover:text-blue-900">Buat jadwal baru</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($schedules->hasPages())
            <div class="mt-4 px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    @if($schedules->onFirstPage())
                        <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50">
                            Previous
                        </span>
                    @else
                        <a href="{{ $schedules->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                    @endif

                    @if($schedules->hasMorePages())
                        <a href="{{ $schedules->nextPageUrl() }}" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    @else
                        <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-400 bg-gray-50">
                            Next
                        </span>
                    @endif
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{ $schedules->firstItem() ?? 0 }}</span>
                            to
                            <span class="font-medium">{{ $schedules->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-medium">{{ $schedules->total() }}</span>
                            results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            {{-- Previous Page Link --}}
                            @if ($schedules->onFirstPage())
                                <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-300">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            @else
                                <a href="{{ $schedules->previousPageUrl() }}" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($schedules->onEachSide(1)->links()->elements[0] as $page => $url)
                                @if ($page == $schedules->currentPage())
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($schedules->hasMorePages())
                                <a href="{{ $schedules->nextPageUrl() }}" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            @else
                                <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-300">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            @endif
                        </nav>
                    </div>
                </div>
            </div>
        @endif
    </x-card>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Initialize everything when document is ready
    $(document).ready(function() {
        // Initialize Select2
        $('.js-example-basic-multiple').select2({
            placeholder: "Pilih",
            allowClear: true,
            width: '100%',
            closeOnSelect: false,
            // Add some custom styling to match Flatpickr
            templateSelection: function(data, container) {
                $(container).css('padding', '2px');
                return data.text;
            }
        });

        // Format date to YYYY-MM-DD for hidden inputs
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Format date for display in the input field
        function formatDisplayDate(date) {
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        }

        // Get initial dates from hidden inputs
        const initialStartDate = $("#start_date").val() ? new Date($("#start_date").val()) : new Date();
        const initialEndDate = $("#end_date").val() ? new Date($("#end_date").val()) : new Date();

        // Set initial display value
        if (initialStartDate && initialEndDate) {
            const formattedStartDate = formatDisplayDate(initialStartDate);
            const formattedEndDate = formatDisplayDate(initialEndDate);

            if (formattedStartDate === formattedEndDate) {
                $("#date-range").val(formattedStartDate);
            } else {
                $("#date-range").val(formattedStartDate + " - " + formattedEndDate);
            }
        }

        // Initialize Flatpickr with Airbnb theme
        const fp = flatpickr("#date-range", {
            mode: "range",
            dateFormat: "d/m/Y",
            defaultDate: [initialStartDate, initialEndDate],
            maxRange: 30, // Maximum range of 30 days
            disableMobile: true,
            allowInput: false,
            onChange: function(selectedDates, dateStr, instance) {
                // Update the display value immediately
                if (selectedDates.length === 1) {
                    const formattedDate = formatDisplayDate(selectedDates[0]);
                    $("#date-range").val(formattedDate);
                } else if (selectedDates.length === 2) {
                    const formattedStartDate = formatDisplayDate(selectedDates[0]);
                    const formattedEndDate = formatDisplayDate(selectedDates[1]);

                    if (formattedStartDate === formattedEndDate) {
                        $("#date-range").val(formattedStartDate);
                    } else {
                        $("#date-range").val(formattedStartDate + " - " + formattedEndDate);
                    }
                }
            },
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    // Calculate the difference in days
                    const diffTime = Math.abs(selectedDates[1] - selectedDates[0]);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

                    // If the range is more than 30 days, adjust the end date
                    if (diffDays > 30) {
                        const maxDate = new Date(selectedDates[0]);
                        maxDate.setDate(maxDate.getDate() + 30);
                        instance.setDate([selectedDates[0], maxDate]);
                        alert("Maximum date range is 30 days. End date has been adjusted.");
                        selectedDates[1] = maxDate;
                    }

                    // Update hidden inputs with YYYY-MM-DD format
                    $("#start_date").val(formatDate(selectedDates[0]));
                    $("#end_date").val(formatDate(selectedDates[1]));

                    // Auto-submit the form
                    $("#filter-form").submit();
                } else if (selectedDates.length === 1) {
                    // If only one date is selected, use it for both start and end
                    $("#start_date").val(formatDate(selectedDates[0]));
                    $("#end_date").val(formatDate(selectedDates[0]));

                    // Auto-submit the form
                    $("#filter-form").submit();
                }
            }
        });

        // Handle date preset buttons
        $('.date-preset').on('click', function(e) {
            e.preventDefault();
            const days = parseInt($(this).data('days'));

            // Ensure days doesn't exceed 30
            const adjustedDays = Math.min(days, 30);

            const today = new Date();
            const futureDate = new Date();
            futureDate.setDate(today.getDate() + adjustedDays);

            // Update hidden inputs
            $("#start_date").val(formatDate(today));
            $("#end_date").val(formatDate(futureDate));

            // Update the flatpickr instance
            fp.setDate([today, futureDate]);

            // Auto-submit the form
            $("#filter-form").submit();

            return false;
        });
    });
</script>
@endpush

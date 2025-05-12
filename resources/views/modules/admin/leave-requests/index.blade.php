@extends('modules.admin.layouts.main')

@section('title', 'Pengajuan Cuti')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .days-count {
            display: inline-flex;
            align-items: center;
            background-color: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 0.25rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #4F46E5;
        }

        .tab-button {
            padding: 0.75rem 1.25rem;
            font-weight: 500;
            border-bottom: 2px solid transparent;
            cursor: pointer;
        }

        .tab-button.active {
            border-bottom-color: #4F46E5;
            color: #4F46E5;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto py-6">
    <x-page-title>
        <x-slot name="title">Pengajuan Cuti</x-slot>
        <x-slot name="actions">
            <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-plus mr-2"></i>
                Tambah Pengajuan
            </a>
        </x-slot>
    </x-page-title>

    <x-card class="mb-6">
        <!-- Tab Navigation -->
        <div class="flex border-b border-gray-200">
            <div id="tab-pending" class="tab-button active" onclick="switchTab('pending')">
                <div class="flex items-center">
                    <span>Menunggu Persetujuan</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-800">
                        {{ $pendingRequests->count() }}
                    </span>
                </div>
            </div>
            <div id="tab-approved" class="tab-button" onclick="switchTab('approved')">
                <div class="flex items-center">
                    <span>Disetujui</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-green-100 text-green-800">
                        {{ $approvedRequests->count() }}
                    </span>
                </div>
            </div>
            <div id="tab-rejected" class="tab-button" onclick="switchTab('rejected')">
                <div class="flex items-center">
                    <span>Ditolak</span>
                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800">
                        {{ $rejectedRequests->count() }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Pending Requests Tab Content -->
        <div id="content-pending" class="tab-content active">
            @if($pendingRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">No.</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pengemudi</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tipe</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Alasan</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($pendingRequests as $index => $request)
                                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                    <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">{{ $request->driver->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @if($request->start_date->isSameDay($request->end_date))
                                                {{ $request->start_date->format('d M Y') }}
                                            @else
                                                {{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}
                                            @endif
                                        </div>
                                        <div class="days-count mt-1">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            {{ $request->start_date->diffInDays($request->end_date) + 1 }} hari
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($request->type == 'terencana') bg-blue-100 text-blue-800
                                            @elseif($request->type == 'sakit') bg-red-100 text-red-800
                                            @elseif($request->type == 'darurat') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($request->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">{{ $request->reason }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex space-x-2 justify-center">
                                            <a href="{{ route('leave-requests.show', $request) }}" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('leave-requests.edit', $request) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if(Auth::user()->isSuperAdmin())
                                            <form action="{{ route('leave-requests.approve', $request) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900" title="Setujui" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <button type="button" class="text-red-600 hover:text-red-900" title="Tolak" onclick="openRejectModal({{ $request->id }})">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @else
                                            <span class="text-gray-400 cursor-not-allowed px-2" title="Hanya superadmin yang dapat menyetujui atau menolak pengajuan cuti">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 px-4 text-center text-gray-500">
                    <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                    <p>Tidak ada pengajuan cuti yang menunggu persetujuan.</p>
                </div>
            @endif
        </div>

        <!-- Approved Requests Tab Content -->
        <div id="content-approved" class="tab-content">
            @if($approvedRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">No.</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pengemudi</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tipe</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Alasan</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($approvedRequests as $index => $request)
                                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                    <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">{{ $request->driver->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @if($request->start_date->isSameDay($request->end_date))
                                                {{ $request->start_date->format('d M Y') }}
                                            @else
                                                {{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}
                                            @endif
                                        </div>
                                        <div class="days-count mt-1">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            {{ $request->start_date->diffInDays($request->end_date) + 1 }} hari
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($request->type == 'terencana') bg-blue-100 text-blue-800
                                            @elseif($request->type == 'sakit') bg-red-100 text-red-800
                                            @elseif($request->type == 'darurat') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($request->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate">{{ $request->reason }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex space-x-2 justify-center">
                                            <a href="{{ route('leave-requests.show', $request) }}" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 px-4 text-center text-gray-500">
                    <i class="fas fa-info-circle text-blue-500 text-2xl mb-2"></i>
                    <p>Tidak ada pengajuan cuti yang disetujui dan masih aktif.</p>
                </div>
            @endif
        </div>

        <!-- Rejected Requests Tab Content -->
        <div id="content-rejected" class="tab-content">
            @if($rejectedRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">No.</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pengemudi</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tanggal</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tipe</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Alasan Penolakan</th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($rejectedRequests as $index => $request)
                                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                    <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="text-sm font-medium text-gray-900">{{ $request->driver->name }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @if($request->start_date->isSameDay($request->end_date))
                                                {{ $request->start_date->format('d M Y') }}
                                            @else
                                                {{ $request->start_date->format('d M Y') }} - {{ $request->end_date->format('d M Y') }}
                                            @endif
                                        </div>
                                        <div class="days-count mt-1">
                                            <i class="fas fa-calendar-day mr-1"></i>
                                            {{ $request->start_date->diffInDays($request->end_date) + 1 }} hari
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($request->type == 'terencana') bg-blue-100 text-blue-800
                                            @elseif($request->type == 'sakit') bg-red-100 text-red-800
                                            @elseif($request->type == 'darurat') bg-orange-100 text-orange-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ ucfirst($request->type) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs">{{ $request->admin_notes ?? 'Tidak ada catatan' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex space-x-2 justify-center">
                                            <a href="{{ route('leave-requests.show', $request) }}" class="text-blue-600 hover:text-blue-900" title="Lihat Detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-6 px-4 text-center text-gray-500">
                    <i class="fas fa-info-circle text-blue-500 text-2xl mb-2"></i>
                    <p>Tidak ada pengajuan cuti yang ditolak.</p>
                </div>
            @endif
        </div>
    </x-card>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <i class="fas fa-times text-red-600"></i>
            </div>
            <h3 class="text-lg leading-6 font-medium text-gray-900 mt-2">Tolak Pengajuan Cuti</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">
                    Masukkan alasan penolakan pengajuan cuti ini.
                </p>
                <form id="rejectForm" action="" method="POST" class="mt-3">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="rejected">
                    <textarea name="admin_notes" id="admin_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="Alasan penolakan..." required></textarea>
                    <div class="flex justify-between mt-4">
                        <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-200 text-gray-800 text-base font-medium rounded-md shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300">
                            Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openRejectModal(id) {
        document.getElementById('rejectForm').action = `/leave-requests/${id}`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
        document.getElementById('admin_notes').value = '';
    }

    function switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Show the selected tab content
        document.getElementById(`content-${tabName}`).classList.add('active');
        
        // Activate the selected tab button
        document.getElementById(`tab-${tabName}`).classList.add('active');
    }
</script>
@endpush

@endsection

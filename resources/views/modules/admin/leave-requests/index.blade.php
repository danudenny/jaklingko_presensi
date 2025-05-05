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

    <x-flash-message />

    <!-- Pending Requests -->
    <x-card class="mb-6">
        <div class="flex justify-between items-center border-b border-gray-200 p-4">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Pengajuan Menunggu Persetujuan</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Daftar pengajuan cuti yang memerlukan persetujuan</p>
            </div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                {{ $pendingRequests->count() }} pengajuan
            </span>
        </div>
        
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
                                        @if($request->type == 'planned') bg-blue-100 text-blue-800
                                        @elseif($request->type == 'sick') bg-red-100 text-red-800
                                        @elseif($request->type == 'emergency') bg-orange-100 text-orange-800
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
                                        <form action="{{ route('leave-requests.approve', $request) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-green-600 hover:text-green-900" title="Setujui" onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <button type="button" class="text-red-600 hover:text-red-900" title="Tolak" onclick="openRejectModal({{ $request->id }})">
                                            <i class="fas fa-times"></i>
                                        </button>
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
    </x-card>

    <!-- Approved Requests -->
    <x-card>
        <div class="flex justify-between items-center border-b border-gray-200 p-4">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Pengajuan Disetujui</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Daftar pengajuan cuti yang telah disetujui dan masih aktif</p>
            </div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                {{ $approvedRequests->count() }} pengajuan
            </span>
        </div>
        
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
                                        @if($request->type == 'planned') bg-blue-100 text-blue-800
                                        @elseif($request->type == 'sick') bg-red-100 text-red-800
                                        @elseif($request->type == 'emergency') bg-orange-100 text-orange-800
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
                <p>Tidak ada pengajuan cuti yang disetujui dan masih aktif.</p>
            </div>
        @endif
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
                    Berikan alasan penolakan pengajuan cuti ini.
                </p>
            </div>
            <form id="rejectForm" action="" method="POST">
                @csrf
                <div class="mt-2 px-7 py-3">
                    <textarea name="admin_notes" id="admin_notes" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Alasan penolakan..."></textarea>
                </div>
                <div class="items-center px-4 py-3">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-white text-gray-700 text-base font-medium rounded-md w-24 border border-gray-300 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-2">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openRejectModal(id) {
        document.getElementById('rejectForm').action = `/leave-requests/${id}/reject`;
        document.getElementById('rejectModal').classList.remove('hidden');
    }
    
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endpush
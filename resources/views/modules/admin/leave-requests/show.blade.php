@extends('modules.admin.layouts.main')

@section('title', 'Detail Pengajuan Cuti')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
    <style>
        .days-count {
            display: inline-flex;
            align-items: center;
            background-color: #EEF2FF;
            border: 1px solid #C7D2FE;
            border-radius: 0.25rem;
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4F46E5;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .detail-section {
            border-bottom: 1px solid #E5E7EB;
        }

        .detail-section:last-child {
            border-bottom: none;
        }
    </style>
@endpush

@section('content')
<div class="container mx-auto py-6">
    <x-page-title>
        <x-slot name="title">Detail Pengajuan Cuti</x-slot>
        <x-slot name="actions">
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:border-gray-700 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-arrow-left mr-2"></i>
                Kembali
            </a>
        </x-slot>
    </x-page-title>

    <x-flash-message />

    <!-- Leave Request Details -->
    <x-card class="mb-6">
        <div class="flex justify-between items-center border-b border-gray-200 p-4">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Informasi Pengajuan</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Detail pengajuan cuti #{{ $leaveRequest->id }}</p>
            </div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                @if($leaveRequest->status == 'requested') bg-yellow-100 text-yellow-800
                @elseif($leaveRequest->status == 'approved') bg-green-100 text-green-800
                @else bg-red-100 text-red-800 @endif">
                {{ ucfirst($leaveRequest->status) }}
            </span>
        </div>

        <dl>
            <div class="detail-section bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Pengemudi</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    {{ $leaveRequest->driver->name }}
                    <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                        {{ ucfirst($leaveRequest->driver->type) }}
                    </span>
                </dd>
            </div>
            <div class="detail-section bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Periode Cuti</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    <div class="flex items-center">
                        <div>
                            @if($leaveRequest->start_date->isSameDay($leaveRequest->end_date))
                                {{ $leaveRequest->start_date->format('d M Y') }}
                            @else
                                {{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}
                            @endif
                        </div>
                        <div class="days-count ml-3">
                            <i class="fas fa-calendar-day mr-1"></i>
                            {{ $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1 }} hari
                        </div>
                    </div>
                </dd>
            </div>
            <div class="detail-section bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Tipe Cuti</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                        @if($leaveRequest->type == 'terencana') bg-blue-100 text-blue-800
                        @elseif($leaveRequest->type == 'sakit') bg-red-100 text-red-800
                        @elseif($leaveRequest->type == 'darurat') bg-orange-100 text-orange-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ ucfirst($leaveRequest->type) }}
                    </span>
                </dd>
            </div>
            <div class="detail-section bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Alasan</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $leaveRequest->reason ?: '-' }}</dd>
            </div>

            @if($leaveRequest->documentation)
            <div class="detail-section bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Dokumentasi</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                    <div class="flex items-center">
                        <a href="{{ asset('storage/' . $leaveRequest->documentation) }}" target="_blank" class="inline-flex items-center">
                            <img src="{{ asset('storage/' . $leaveRequest->documentation) }}" alt="Dokumentasi" class="h-20 w-auto object-cover rounded mr-3">
                            <span class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Lihat Gambar
                            </span>
                        </a>
                    </div>
                </dd>
            </div>
            @endif

            @if($leaveRequest->status != 'requested')
                <div class="detail-section {{ $leaveRequest->documentation ? 'bg-white' : 'bg-gray-50' }} px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Catatan Admin</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $leaveRequest->admin_notes ?: '-' }}</dd>
                </div>
                <div class="detail-section {{ ($leaveRequest->documentation && $leaveRequest->status != 'requested') ? 'bg-gray-50' : 'bg-white' }} px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <dt class="text-sm font-medium text-gray-500">Disetujui/Ditolak Oleh</dt>
                    <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                        {{ $leaveRequest->approvedBy ? $leaveRequest->approvedBy->name : '-' }}
                    </dd>
                </div>
            @endif

            <div class="detail-section {{ $leaveRequest->status != 'requested' ? (($leaveRequest->documentation && $leaveRequest->status != 'requested') ? 'bg-white' : 'bg-gray-50') : ($leaveRequest->documentation ? 'bg-white' : 'bg-gray-50') }} px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                <dt class="text-sm font-medium text-gray-500">Tanggal Pengajuan</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $leaveRequest->created_at->format('d M Y H:i') }}</dd>
            </div>
        </dl>

        @if($leaveRequest->status == 'requested')
            <div class="px-4 py-4 sm:px-6 border-t border-gray-200 flex justify-end space-x-3">
                <a href="{{ route('leave-requests.edit', $leaveRequest) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-edit mr-2"></i>
                    Edit
                </a>
                <form action="{{ route('leave-requests.approve', $leaveRequest) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" {{ !$hasAllBackups ? 'disabled' : '' }} onclick="return confirm('Apakah Anda yakin ingin menyetujui pengajuan cuti ini?')">
                        <i class="fas fa-check mr-2"></i>
                        Setujui
                    </button>
                </form>
                <button type="button" onclick="openRejectModal({{ $leaveRequest->id }})" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <i class="fas fa-times mr-2"></i>
                    Tolak
                </button>
            </div>
        @endif
    </x-card>

    <!-- Affected Schedules -->
    <x-card id="backup-drivers-section">
        <div class="flex justify-between items-center border-b border-gray-200 p-4">
            <div>
                <h3 class="text-lg leading-6 font-medium text-gray-900">Jadwal Terdampak</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Jadwal pengemudi yang terdampak oleh pengajuan cuti ini</p>
            </div>
            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                {{ isset($affectedSchedules) ? $affectedSchedules->count() : 0 }} jadwal
            </span>
        </div>

        @if(isset($affectedSchedules) && $affectedSchedules->count() > 0)
            @if($leaveRequest->status == 'requested' && !$hasAllBackups)
                <div class="bg-yellow-50 p-4 border-b border-yellow-100">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                <strong>Perhatian:</strong> Tidak ada pengemudi pengganti yang tersedia untuk jadwal berikut:
                            </p>
                            @if(isset($schedulesWithoutBackup) && count($schedulesWithoutBackup) > 0)
                                <ul class="mt-2 list-disc pl-5 text-sm text-yellow-700">
                                    @foreach($schedulesWithoutBackup as $schedule)
                                        <li>
                                            <strong>{{ $schedule['date'] }}</strong> - 
                                            Unit: {{ $schedule['unit'] }}, 
                                            Shift: {{ $schedule['shift'] }}, 
                                            Rute: {{ $schedule['route'] ?? 'N/A' }}
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-2 text-sm text-yellow-700">
                                    Permohonan cuti tidak dapat disetujui sampai semua jadwal memiliki pengemudi pengganti yang tersedia. Sistem sudah mencoba mencari pengemudi batangan terlebih dahulu, lalu pengemudi cadangan.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">No.</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Shift</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Unit</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rute</th>
                            <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pengganti</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($affectedSchedules as $index => $schedule)
                            <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="px-3 py-4 whitespace-nowrap text-center text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $schedule->shift == 'pagi' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ ucfirst($schedule->shift) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $schedule->unit->unit_number ?? $schedule->unit->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $schedule->route ? $schedule->route->name : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($leaveRequest->status == 'requested')
                                        @if(isset($availableBackupDrivers[$schedule->id]) && $availableBackupDrivers[$schedule->id]->isNotEmpty())
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                {{ $availableBackupDrivers[$schedule->id]->count() }} pengemudi tersedia
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Tidak ada pengganti
                                            </span>
                                        @endif
                                    @else
                                        @if($schedule->backup_driver_id)
                                            <span class="text-sm text-gray-900">{{ $schedule->backupDriver->name }}</span>
                                        @else
                                            <span class="text-sm text-gray-500">-</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($leaveRequest->status == 'requested')
                <div id="manual-assignment" class="px-4 py-5 sm:px-6 border-t border-gray-200">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Pilih Pengemudi Pengganti</h3>
                    <form action="{{ route('leave-requests.assign-backup-drivers', $leaveRequest) }}" method="POST">
                        @csrf
                        @foreach($affectedSchedules as $schedule)
                            @if(!isset($availableBackupDrivers[$schedule->id]) || $availableBackupDrivers[$schedule->id]->isEmpty())
                                <div class="mb-4 p-4 bg-red-50 rounded-md">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                                        <span class="text-sm font-medium text-red-800">
                                            Tidak ada pengemudi pengganti tersedia untuk jadwal ini
                                        </span>
                                    </div>
                                    <div class="ml-6 text-sm text-red-700">
                                        <p>Tanggal: {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') }}</p>
                                        <p>Shift: {{ ucfirst($schedule->shift) }}</p>
                                        <p>Unit: {{ $schedule->unit->unit_number ?? $schedule->unit->name ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="mb-4 p-4 bg-gray-50 rounded-md">
                                    <div class="mb-2">
                                        <span class="text-sm font-medium text-gray-700">
                                            {{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d M Y') }} -
                                            {{ ucfirst($schedule->shift) }} -
                                            Unit: {{ $schedule->unit->unit_number ?? $schedule->unit->name ?? 'N/A' }}
                                        </span>
                                    </div>
                                    <div class="ml-0">
                                        <label for="backup_{{ $schedule->id }}" class="block text-sm font-medium text-gray-700">Pilih Pengganti:</label>
                                        <select id="backup_{{ $schedule->id }}" name="backup_assignments[{{ $schedule->id }}]" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                            @foreach($availableBackupDrivers[$schedule->id] as $driver)
                                                <option value="{{ $driver->id }}">{{ $driver->name }} ({{ ucfirst($driver->type) }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <i class="fas fa-save mr-2"></i>
                                Simpan & Setujui
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @else
            <div class="py-6 px-4 text-center text-gray-500">
                <p>Tidak ada jadwal yang terdampak oleh pengajuan cuti ini.</p>
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

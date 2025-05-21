@if($schedules->count() > 0)
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No.</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shift</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($schedules as $index => $schedule)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ($schedules->currentPage() - 1) * $schedules->perPage() + $index + 1 }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($schedule->date)->format('d M Y') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($schedule->shift) }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $schedule->unit->unit_number ?? 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $schedule->driver->name ?? 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">
    {{ $schedules->links() }}
</div>
@else
<div class="text-center py-8">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada jadwal terkait</h3>
    <p class="mt-1 text-sm text-gray-500">Belum ada jadwal yang terkait dengan rute ini.</p>
</div>
@endif

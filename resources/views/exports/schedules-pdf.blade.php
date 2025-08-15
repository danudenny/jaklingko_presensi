<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Jadwal</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            margin-top: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.summary {
            margin-bottom: 20px;
        }
        table.summary td {
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            width: 25%;
        }
        table.summary .label {
            font-weight: bold;
            font-size: 10px;
            color: #555;
        }
        table.summary .value {
            font-size: 14px;
            font-weight: bold;
        }
        table.data th {
            background-color: #f2f2f2;
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        table.data td {
            padding: 6px 8px;
            border: 1px solid #ddd;
            font-size: 11px;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            margin-top: 20px;
            color: #777;
        }
        .page-break {
            page-break-after: always;
        }
        .filters {
            margin-bottom: 15px;
            font-size: 11px;
        }
        .filters strong {
            display: inline-block;
            width: 100px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN JADWAL PENGEMUDI</h1>
        <p>
            @if($startDate == $endDate)
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
            @else
                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
            @endif
        </p>
    </div>
    
    <div class="filters">
        @if(isset($filters['drivers']) && count($filters['drivers']) > 0)
            <p><strong>Pengemudi:</strong> {{ implode(', ', $filters['drivers']) }}</p>
        @endif
        
        @if(isset($filters['units']) && count($filters['units']) > 0)
            <p><strong>Unit:</strong> {{ implode(', ', $filters['units']) }}</p>
        @endif
    </div>
    
    <table class="summary">
        <tr>
            <td>
                <div class="label">Total Jadwal</div>
                <div class="value">{{ $totalSchedules }}</div>
            </td>
            <td>
                <div class="label">Shift Pagi</div>
                <div class="value">{{ $morningCount }}</div>
            </td>
            <td>
                <div class="label">Shift Siang</div>
                <div class="value">{{ $eveningCount }}</div>
            </td>
            <td>
                <div class="label">Pengemudi Batangan</div>
                <div class="value">{{ $batanganCount }}</div>
            </td>
            <td>
                <div class="label">Pengemudi Cadangan</div>
                <div class="value">{{ $cadanganCount }}</div>
            </td>
        </tr>
    </table>
    
    @foreach($schedulesByUnit as $unitNumber => $unitSchedules)
        @if(!$loop->first)
            <div class="page-break"></div>
        @endif
        
        <!-- Unit Header -->
        @php
            $firstSchedule = $unitSchedules->first();
            $routeNumber = $firstSchedule && $firstSchedule->route ? $firstSchedule->route->route_number : 'N/A';
        @endphp
        <div style="margin: 20px 0 15px 0; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
            <h2 style="margin: 0; font-size: 16px; color: #007bff;">{{ $routeNumber }} : KWK-{{ $unitNumber }}</h2>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                Total Jadwal: {{ $unitSchedules->count() }} | 
                Pagi: {{ $unitSchedules->where('shift', 'pagi')->count() + $unitSchedules->where('shift', 'morning')->count() }} | 
                Siang: {{ $unitSchedules->where('shift', 'siang')->count() + $unitSchedules->where('shift', 'evening')->count() }}
            </p>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Pengemudi</th>
                    <th>Tipe</th>
                    <th>Plat Nomor</th>
                    <th>Shift</th>
                    <th>Status</th>
                    <th>Backup</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unitSchedules as $index => $schedule)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }}</td>
                        <td>{{ $schedule->driver ? $schedule->driver->name : '-' }}</td>
                        <td>{{ $schedule->driver ? ucfirst($schedule->driver->type) : '-' }}</td>
                        <td>{{ $schedule->unit ? $schedule->unit->plate_number : '-' }}</td>
                        <td>{{ ($schedule->shift == 'pagi' || $schedule->shift == 'morning') ? 'Pagi' : 'Siang' }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $schedule->status)) }}</td>
                        <td>{{ $schedule->backup_driver_id && $schedule->backupDriver ? $schedule->backupDriver->name : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        @if(!$loop->last)
            <div style="margin: 20px 0;"></div>
        @endif
    @endforeach
    
    <div class="footer">
        Laporan ini dicetak pada {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>

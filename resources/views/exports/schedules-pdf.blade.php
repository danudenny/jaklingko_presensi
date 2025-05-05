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
                <div class="value">{{ $schedules->count() }}</div>
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
    
    <table class="data">
        <thead>
            <tr>
                <th>No.</th>
                <th>Tanggal</th>
                <th>Pengemudi</th>
                <th>Tipe</th>
                <th>Rute</th>
                <th>Unit</th>
                <th>Plat Nomor</th>
                <th>Shift</th>
                <th>Status</th>
                <th>Backup</th>
            </tr>
        </thead>
        <tbody>
            @foreach($schedules as $index => $schedule)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($schedule->schedule_date)->format('d/m/Y') }}</td>
                    <td>{{ $schedule->driver->name }}</td>
                    <td>{{ ucfirst($schedule->driver->type) }}</td>
                    <td>{{ $schedule->route->name }} ({{ $schedule->route->route_number }})</td>
                    <td>{{ $schedule->unit->unit_number }}</td>
                    <td>{{ $schedule->unit->plate_number }}</td>
                    <td>{{ ($schedule->shift == 'pagi' || $schedule->shift == 'morning') ? 'Pagi' : 'Siang' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $schedule->status)) }}</td>
                    <td>{{ $schedule->backup_driver_id ? $schedule->backupDriver->name : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        Laporan ini dicetak pada {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>

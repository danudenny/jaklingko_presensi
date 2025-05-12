<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Jadwal Matrix</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            margin-bottom: 5px;
        }
        .header p {
            font-size: 14px;
            margin-top: 0;
        }
        .header .subtitle {
            font-size: 12px;
            color: #555;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.matrix {
            margin-bottom: 20px;
        }
        table.matrix th {
            background-color: #f2f2f2;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 8px;
            font-weight: bold;
        }
        table.matrix td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 8px;
            text-align: center;
        }
        table.matrix td.info-cell {
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        table.matrix td.unit-cell {
            background-color: #f9f9f9;
            font-weight: bold;
        }
        table.matrix td.route-cell {
            background-color: #f5f5f5;
        }
        table.matrix td.driver-cell {
            padding-left: 15px;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 15px;
            color: #777;
        }
        .page-break {
            page-break-after: always;
        }
        .checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            position: relative;
        }
        .checkbox.checked {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        .checkbox.backup {
            background-color: #FFC107;
            border-color: #FFC107;
        }
        .driver-type {
            font-size: 7px;
            color: #666;
            font-style: italic;
        }
        .page-number {
            text-align: right;
            font-size: 8px;
            margin-bottom: 5px;
        }
        .summary {
            margin-bottom: 15px;
            border: 1px solid #ddd;
            padding: 8px;
            background-color: #f9f9f9;
            font-size: 9px;
        }
        .summary p {
            margin: 3px 0;
        }
        .shift-pagi {
            color: #0066cc;
        }
        .shift-siang {
            color: #cc6600;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 5px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }
        .unassigned-drivers {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .unassigned-drivers-table {
            width: 100%;
            border-collapse: collapse;
        }
        .unassigned-drivers-table th {
            background-color: #f2f2f2;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 9px;
            font-weight: bold;
        }
        .unassigned-drivers-table td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        .unassigned-drivers-table td.driver-name {
            text-align: left;
        }
        .unassigned-drivers-table td.driver-type {
            text-align: center;
        }
        .total-column {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .legend {
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 9px;
        }
        .legend-item {
            display: inline-block;
            margin-right: 15px;
        }
        .legend-color {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin-right: 5px;
            border: 1px solid #000;
        }
        .legend-color.assigned {
            background-color: #4CAF50;
            border-color: #4CAF50;
        }
        .legend-color.backup {
            background-color: #FFC107;
            border-color: #FFC107;
        }
    </style>
</head>
<body>
    <div class="page-number">Halaman 1 dari 1</div>
    
    <div class="header">
        <h1>MATRIX JADWAL PENGEMUDI</h1>
        <p>{{ $periodText }}</p>
        <div class="subtitle">
            {{ $monthName }} {{ $year }}
        </div>
    </div>
    
    <div class="summary">
        <p><strong>Informasi Matrix:</strong></p>
        <p>Matrix ini menampilkan jadwal pengemudi untuk {{ $periodText }}.</p>
        <div class="legend">
            <div class="legend-item"><span class="legend-color assigned"></span> Pengemudi yang dijadwalkan</div>
            <div class="legend-item"><span class="legend-color backup"></span> Pengemudi cadangan</div>
        </div>
        <p>Shift Pagi ditampilkan dengan warna <span class="shift-pagi">biru</span> dan Shift Siang dengan warna <span class="shift-siang">oranye</span>.</p>
    </div>
    
    <div class="section-title">Pengemudi dengan Jadwal</div>
    
    <table class="matrix">
        <thead>
            <tr>
                <th style="width: 80px;">Unit</th>
                <th style="width: 80px;">Rute</th>
                <th style="width: 150px;">Pengemudi</th>
                <th style="width: 50px;">Shift</th>
                @foreach($dateHeaders as $date => $day)
                    <th>{{ $day }}</th>
                @endforeach
                <th style="width: 50px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($routeUnitDrivers as $index => $routeUnitDriver)
                @php
                    $unit = $routeUnitDriver['unit'];
                    $route = $routeUnitDriver['route'];
                    $driverShifts = $routeUnitDriver['driver_shifts'];
                    $isFirstInGroup = true;
                @endphp
                
                @foreach($driverShifts as $driverId => $shifts)
                    @foreach($shifts as $shiftName => $shiftData)
                        <tr>
                            <td class="info-cell unit-cell">
                                @if($isFirstInGroup)
                                    <strong>{{ $unit->unit_number }}</strong>
                                    @php $isFirstInGroup = false; @endphp
                                @else
                                    <span style="color: #999;">{{ $unit->unit_number }}</span>
                                @endif
                            </td>
                            <td class="info-cell route-cell">
                                @if($loop->parent->first && $loop->first)
                                    <strong>{{ $route->route_number }}</strong>
                                @else
                                    <span style="color: #999;">{{ $route->route_number }}</span>
                                @endif
                            </td>
                            
                            <td class="info-cell driver-cell">
                                {{ $shiftData['driver']->name }}
                                <div class="driver-type">({{ ucfirst($shiftData['driver']->type) }})</div>
                            </td>
                            
                            <td class="info-cell @if($shiftName == 'pagi') shift-pagi @else shift-siang @endif">
                                {{ ucfirst($shiftName) }}
                            </td>
                            
                            @foreach($dateRange as $date)
                                <td>
                                    @if(in_array($date, $shiftData['dates'] ?? []))
                                        <div class="checkbox checked"></div>
                                    @elseif(in_array($date, $shiftData['backup_dates'] ?? []))
                                        <div class="checkbox backup"></div>
                                    @else
                                        <div class="checkbox"></div>
                                    @endif
                                </td>
                            @endforeach
                            
                            <td class="total-column">
                                @php
                                    $totalAssigned = count($shiftData['dates'] ?? []);
                                    $totalBackup = count($shiftData['backup_dates'] ?? []);
                                    $total = $totalAssigned + $totalBackup;
                                @endphp
                                {{ $total }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
                
                @if(!$loop->last)
                    <tr class="spacer-row">
                        <td colspan="{{ 4 + count($dateRange) + 1 }}" style="padding: 2px; border: none;"></td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    
    @if($unassignedDrivers->count() > 0)
        <div class="section-title">Pengemudi Tanpa Jadwal ({{ $unassignedDrivers->count() }} orang)</div>
        
        <table class="matrix">
            <thead>
                <tr>
                    <th colspan="2">Informasi Pengemudi</th>
                    <th>Tipe</th>
                    @foreach($dateHeaders as $date => $day)
                        <th>{{ $day }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unassignedDrivers->chunk(10) as $driverChunk)
                    @foreach($driverChunk as $index => $driver)
                        <tr>
                            <td style="text-align: center; width: 5%;">{{ $index + 1 }}</td>
                            <td class="info-cell driver-cell" style="width: 25%;">{{ $driver->name }}</td>
                            <td style="text-align: center; width: 10%;">{{ ucfirst($driver->type) }}</td>
                            @foreach($dateRange as $date)
                                <td><div class="checkbox"></div></td>
                            @endforeach
                            <td class="total-column">0</td>
                        </tr>
                    @endforeach
                    
                    @if(!$loop->last)
                        <tr class="spacer-row">
                            <td colspan="{{ 3 + count($dateRange) + 1 }}" style="padding: 2px; border: none;"></td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif
    
    <div class="footer">
        Laporan ini dicetak pada {{ \Carbon\Carbon::now()->format('d M Y H:i:s') }}
    </div>
</body>
</html>

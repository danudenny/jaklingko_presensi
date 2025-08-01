<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .route-header {
            background-color: #e6e6e6;
            font-weight: bold;
        }
        .unit-header {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .subtotal-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .total-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .text-left {
            text-align: left;
        }
        .weekend {
            background-color: #fff8e6;
        }
        .holiday {
            background-color: #ffe6e6;
        }
    </style>
</head>
<body>
    <h2>{{ $title }}</h2>
    
    <table>
        <thead>
            <tr>
                <th>Driver</th>
                @foreach($dates as $date)
                    @php
                        $dateObj = \Carbon\Carbon::parse($date);
                        $isWeekend = $dateObj->isWeekend();
                        $isHoliday = isset($holidays[$date]);
                        $headerClass = $isHoliday ? 'holiday' : ($isWeekend ? 'weekend' : '');
                    @endphp
                    <th class="{{ $headerClass }}">
                        {{ $dateObj->format('d') }}<br>
                        {{ $dateObj->format('D') }}
                    </th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($routes as $route)
                <tr class="route-header">
                    <td colspan="{{ count($dates) + 2 }}" class="text-left">{{ $route->route_number }} - {{ $route->name }}</td>
                </tr>
                @foreach($route->units as $unit)
                    <tr class="unit-header">
                        <td colspan="{{ count($dates) + 2 }}" class="text-left">{{ $unit->unit_number }} - {{ $unit->plate_number }}</td>
                    </tr>
                    @if(isset($reportsByRouteUnitDriverDate[$route->id][$unit->id]) && count($reportsByRouteUnitDriverDate[$route->id][$unit->id]) > 0)
                        @foreach($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates)
                            <tr>
                                <td class="text-left">{{ $drivers[$driverId]->name ?? 'Unknown Driver' }}</td>
                                @foreach($dates as $date)
                                    @php
                                        $dateObj = \Carbon\Carbon::parse($date);
                                        $isWeekend = $dateObj->isWeekend();
                                        $isHoliday = isset($holidays[$date]);
                                        $cellClass = $isHoliday ? 'holiday' : ($isWeekend ? 'weekend' : '');
                                    @endphp
                                    <td class="{{ $cellClass }}">
                                        @if(isset($driverDates[$date]))
                                            {{ number_format($driverDates[$date]->kilometers, 1) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                @endforeach
                                <td>{{ isset($routeDriverTotals[$route->id][$driverId]) ? number_format($routeDriverTotals[$route->id][$driverId], 1) : '0.0' }}</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td class="text-left">Subtotal Unit</td>
                            @foreach($dates as $date)
                                @php
                                    $unitDateTotal = 0;
                                    foreach ($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates) {
                                        if (isset($driverDates[$date])) {
                                            $unitDateTotal += $driverDates[$date]->original_kilometers;
                                        }
                                    }
                                @endphp
                                <td>{{ $unitDateTotal > 0 ? number_format($unitDateTotal, 1) : '-' }}</td>
                            @endforeach
                            <td>{{ isset($routeUnitTotals[$route->id][$unit->id]) ? number_format($routeUnitTotals[$route->id][$unit->id], 1) : '0.0' }}</td>
                        </tr>
                    @else
                        <tr>
                            <td class="text-left">Tidak ada pengemudi</td>
                            @foreach($dates as $date)
                                <td>-</td>
                            @endforeach
                            <td>0.0</td>
                        </tr>
                    @endif
                @endforeach
                <tr class="subtotal-row">
                    <td class="text-left">Subtotal Rute</td>
                    @foreach($dates as $date)
                        @php
                            $routeDateTotal = 0;
                            foreach ($route->units as $unit) {
                                if (isset($reportsByRouteUnitDriverDate[$route->id][$unit->id])) {
                                    foreach ($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates) {
                                        if (isset($driverDates[$date])) {
                                            $routeDateTotal += $driverDates[$date]->original_kilometers;
                                        }
                                    }
                                }
                            }
                        @endphp
                        <td>{{ $routeDateTotal > 0 ? number_format($routeDateTotal, 1) : '-' }}</td>
                    @endforeach
                    <td>{{ isset($routeTotals[$route->id]) ? number_format($routeTotals[$route->id], 1) : '0.0' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <th class="text-left">Total Keseluruhan</th>
                @foreach($dates as $date)
                    <th>{{ isset($dateTotals[$date]) ? number_format($dateTotals[$date], 1) : '0.0' }}</th>
                @endforeach
                <th>{{ number_format($grandTotal, 1) }}</th>
            </tr>
        </tfoot>
    </table>
    
    <div style="margin-top: 20px; font-size: 8px; text-align: right;">
        Dicetak pada: {{ \Carbon\Carbon::now()->format('d F Y H:i') }}
    </div>
</body>
</html>
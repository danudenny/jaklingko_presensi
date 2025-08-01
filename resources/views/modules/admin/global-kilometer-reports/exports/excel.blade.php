<table>
    <thead>
        <tr>
            <th>{{ $title }}</th>
            @foreach($dates as $date)
                @php
                    $dateObj = \Carbon\Carbon::parse($date);
                    $isWeekend = $dateObj->isWeekend();
                    $isHoliday = isset($holidays[$date]);
                @endphp
                <th>{{ $dateObj->format('d') }} {{ $dateObj->format('D') }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($routes as $route)
            <tr>
                <td colspan="{{ count($dates) + 2 }}">{{ $route->route_number }} - {{ $route->name }}</td>
            </tr>
            @foreach($route->units as $unit)
                <tr>
                    <td colspan="{{ count($dates) + 2 }}">{{ $unit->unit_number }} - {{ $unit->plate_number }}</td>
                </tr>
                @if(isset($reportsByRouteUnitDriverDate[$route->id][$unit->id]) && count($reportsByRouteUnitDriverDate[$route->id][$unit->id]) > 0)
                    @foreach($reportsByRouteUnitDriverDate[$route->id][$unit->id] as $driverId => $driverDates)
                        <tr>
                            <td>{{ $drivers[$driverId]->name ?? 'Unknown Driver' }}</td>
                            @foreach($dates as $date)
                                <td>
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
                    <tr>
                        <td>Subtotal Unit</td>
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
                        <td>Tidak ada pengemudi</td>
                        @foreach($dates as $date)
                            <td>-</td>
                        @endforeach
                        <td>0.0</td>
                    </tr>
                @endif
            @endforeach
            <tr>
                <td>Subtotal Rute</td>
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
        <tr>
            <th>Total Keseluruhan</th>
            @foreach($dates as $date)
                <th>{{ isset($dateTotals[$date]) ? number_format($dateTotals[$date], 1) : '0.0' }}</th>
            @endforeach
            <th>{{ number_format($grandTotal, 1) }}</th>
        </tr>
    </tfoot>
</table>
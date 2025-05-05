<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Kilometer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
        }
        .page-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .page-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px;
            font-size: 10px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .route-header {
            background-color: #e6e6e6;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }
        .km-cell {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="page-title">{{ $title }}</div>
    </div>

    @foreach($data as $routeData)
        <table>
            <thead>
                <tr class="route-header">
                    <th colspan="{{ count($dates) + 3 }}">Rute: {{ $routeData['route']->name }} ({{ $routeData['route']->route_number }})</th>
                </tr>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">Unit</th>
                    @foreach($formattedDates as $date)
                        <th style="width: {{ 80 / count($dates) }}%;">{{ $date }}</th>
                    @endforeach
                    <th style="width: 10%;">Total KM</th>
                </tr>
            </thead>
            <tbody>
                @foreach($routeData['units'] as $unitData)
                    <tr>
                        <td class="text-center">{{ $unitData['no'] }}</td>
                        <td>{{ $unitData['unit']->unit_number }}</td>
                        @foreach($dates as $date)
                            <td class="km-cell">{{ number_format($unitData['kilometers'][$date], 0, ',', '.') }}</td>
                        @endforeach
                        <td class="km-cell total-row">{{ number_format($unitData['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div style="margin-top: 30px; text-align: right;">
        <p>Dicetak pada: {{ now()->format('d F Y H:i') }}</p>
    </div>
</body>
</html>

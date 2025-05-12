<div class="flex justify-between items-center mb-4">
    <div class="period-tabs">
        <div class="period-tab active" data-period="1">Periode 1 (1-15)</div>
        <div class="period-tab" data-period="2">Periode 2 (16-{{ Carbon\Carbon::now()->endOfMonth()->format('d') }})</div>
    </div>
    <div class="flex items-end space-x-4">
        <div>
            <label for="matrix-month" class="block mb-1 text-sm font-medium text-gray-700">Bulan</label>
            <select id="matrix-month" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @foreach(range(1, 12) as $month)
                    <option value="{{ $month }}" {{ Carbon\Carbon::now()->month == $month ? 'selected' : '' }}>
                        {{ Carbon\Carbon::create(null, $month)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="matrix-year" class="block mb-1 text-sm font-medium text-gray-700">Tahun</label>
            <select id="matrix-year" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @foreach(range(Carbon\Carbon::now()->year - 2, Carbon\Carbon::now()->year + 2) as $year)
                    <option value="{{ $year }}" {{ Carbon\Carbon::now()->year == $year ? 'selected' : '' }}>
                        {{ $year }}
                    </option>
                @endforeach
            </select>
        </div>
        <button id="apply-matrix-filter" type="button" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-blue-600 border border-transparent rounded-md hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring ring-blue-300 disabled:opacity-25">
            <i class="mr-2 fas fa-filter"></i>
            Filter
        </button>
    </div>
</div>

<div class="period-content active" id="period-1">
    <div class="matrix-view-header">
        <h3 class="text-lg font-medium text-gray-900">
            Periode 1: Tanggal 1-15 <span id="period1-month-year">{{ Carbon\Carbon::now()->format('F Y') }}</span>
        </h3>
        <div>
            <button type="button" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md save-matrix hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring ring-green-300 disabled:opacity-25">
                <i class="mr-2 fas fa-save"></i>
                Simpan
            </button>
        </div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200 matrix-table">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase sticky-left">Unit</th>
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase sticky-left">Pengemudi</th>
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase sticky-left">Shift</th>
                
                @for($day = 1; $day <= 15; $day++)
                    @php
                        $date = \Carbon\Carbon::createFromDate(\Carbon\Carbon::now()->year, \Carbon\Carbon::now()->month, $day);
                        $isWeekend = $date->isWeekend();
                        $isHoliday = false; // Need to implement holiday check
                        $cellClass = $isHoliday ? 'holiday' : ($isWeekend ? 'weekend' : '');
                    @endphp
                    <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center {{ $cellClass }}">
                        {{ $date->format('d') }}
                        <div class="text-xxs">{{ $date->format('D') }}</div>
                    </th>
                @endfor
                
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase bg-gray-100">
                    Total
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <tr>
                <td colspan="20" class="px-6 py-4 text-sm text-center text-gray-500">
                    <i class="mr-2 fas fa-spinner fa-spin"></i>
                    Loading schedule data...
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="period-content" id="period-2">
    <div class="matrix-view-header">
        <h3 class="text-lg font-medium text-gray-900">
            Periode 2: Tanggal 16-<span id="end-day">{{ Carbon\Carbon::now()->endOfMonth()->format('d') }}</span> <span id="period2-month-year">{{ Carbon\Carbon::now()->format('F Y') }}</span>
        </h3>
        <div>
            <button type="button" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md save-matrix hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring ring-green-300 disabled:opacity-25">
                <i class="mr-2 fas fa-save"></i>
                Simpan
            </button>
        </div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200 matrix-table">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase sticky-left">Unit</th>
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase sticky-left">Pengemudi</th>
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase sticky-left">Shift</th>
                
                @php 
                    $lastDay = \Carbon\Carbon::now()->endOfMonth()->day; 
                    $currentYear = \Carbon\Carbon::now()->year;
                    $currentMonth = \Carbon\Carbon::now()->month;
                @endphp
                @for($day = 16; $day <= $lastDay; $day++)
                    @php
                        $date = \Carbon\Carbon::createFromDate($currentYear, $currentMonth, $day);
                        $isWeekend = $date->isWeekend();
                        $isHoliday = false; // Need to implement holiday check
                        $cellClass = $isHoliday ? 'holiday' : ($isWeekend ? 'weekend' : '');
                    @endphp
                    <th scope="col" class="px-3 py-3 text-xs font-medium tracking-wider text-gray-500 uppercase text-center {{ $cellClass }}">
                        {{ $date->format('d') }}
                        <div class="text-xxs">{{ $date->format('D') }}</div>
                    </th>
                @endfor
                
                <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase bg-gray-100">
                    Total
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <tr>
                <td colspan="20" class="px-6 py-4 text-sm text-center text-gray-500">
                    <i class="mr-2 fas fa-spinner fa-spin"></i>
                    Loading schedule data...
                </td>
            </tr>
        </tbody>
    </table>
</div>

<script>
// Define loadMatrixData in the global scope
function loadMatrixData(forceReload = false) {
    console.log('loadMatrixData called with forceReload:', forceReload);
    
    // Get selected month and year from the dropdowns
    const month = parseInt($('#matrix-month').val());
    const year = parseInt($('#matrix-year').val());
    
    console.log('Loading matrix data for month:', month, 'year:', year);
    
    // Update the month and year display
    updateMatrixMonthYear();
    
    const period1Tbody = $('#period-1 tbody');
    const period2Tbody = $('#period-2 tbody');
    
    period1Tbody.html(`
        <tr>
            <td colspan="20" class="px-6 py-4 text-sm text-center text-gray-500">
                <i class="mr-2 fas fa-spinner fa-spin"></i>
                Loading schedule data...
            </td>
        </tr>
    `);
    period2Tbody.html(period1Tbody.html());
    
    fetch(`{{ route('schedules.matrix-data') }}?month=${month}&year=${year}&_=${Date.now()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                $('#matrix-view').data('loaded', true);
                
                updateMatrixView(data.period1, 1, month, year);
                updateMatrixView(data.period2, 2, month, year);
            } else {
                period1Tbody.html(`
                    <tr>
                        <td colspan="20" class="px-6 py-4 text-sm font-medium text-center text-red-500">
                            <i class="mr-2 fas fa-exclamation-circle"></i>
                            Failed to load schedule data: ${data.message || 'Unknown error'}
                        </td>
                    </tr>
                `);
                period2Tbody.html(period1Tbody.html());
            }
        })
        .catch(error => {
            console.error('Error loading matrix data:', error);
            period1Tbody.html(`
                <tr>
                    <td colspan="20" class="px-6 py-4 text-sm font-medium text-center text-red-500">
                        <i class="mr-2 fas fa-exclamation-circle"></i>
                        Failed to load schedule data. Please try again later.
                    </td>
                </tr>
            `);
            period2Tbody.html(period1Tbody.html());
        });
}

// Also expose it as a window property for backward compatibility
window.loadMatrixData = loadMatrixData;

$(document).ready(function() {
    // Filter button click handler
    $('#apply-matrix-filter').on('click', function() {
        console.log('Filter button clicked');
        updateMatrixMonthYear();
        loadMatrixData(true);
    });
    
    // Period Tabs click handler
    $('.period-tab').on('click', function() {
        // Remove active class from all tabs
        $('.period-tab').removeClass('active');
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Hide all content
        $('.period-content').removeClass('active');
        // Show content for selected period
        const period = $(this).data('period');
        $('#period-' + period).addClass('active');
    });
    
    // Save matrix button click handler
    $('.save-matrix').on('click', function() {
        const activePeriod = $('.period-tab.active').data('period');
        const matrix = [];
        
        $('#period-' + activePeriod + ' .matrix-checkbox:checked').each(function() {
            matrix.push({
                date: $(this).data('date'),
                driver_id: $(this).data('driver-id'),
                unit_id: $(this).data('unit-id'),
                shift: $(this).data('shift'),
                route_id: $(this).data('route-id')
            });
        });
        
        if (matrix.length === 0) {
            alert('No schedules selected to save.');
            return;
        }
        
        // Disable save button and show loading state
        $(this).prop('disabled', true);
        const originalText = $(this).html();
        $(this).html('<i class="mr-2 fas fa-spinner fa-spin"></i> Saving...');
        
        fetch('{{ route('schedules.save-matrix') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({ schedules: matrix })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Schedules saved successfully!');
            } else {
                alert('Failed to save schedules: ' + (data.message || 'Unknown error'));
            }
            
            // Re-enable save button
            $(this).prop('disabled', false);
            $(this).html(originalText);
        })
        .catch(error => {
            console.error('Error saving matrix:', error);
            alert('Failed to save schedules. Please try again later.');
            
            // Re-enable save button
            $(this).prop('disabled', false);
            $(this).html(originalText);
        });
    });
});

// Function to update the month and year display in headers
function updateMatrixMonthYear() {
    const monthSelect = $('#matrix-month');
    const yearSelect = $('#matrix-year');
    const month = monthSelect.find('option:selected').text();
    const year = yearSelect.val();
    const monthIndex = parseInt(monthSelect.val());
    
    console.log('Updating matrix month/year display:', month, year);
    
    // Update month and year in headers
    $('#period1-month-year').text(month + ' ' + year);
    $('#period2-month-year').text(month + ' ' + year);
    
    // Update the end day in period 2 tab
    const lastDayOfMonth = new Date(year, monthIndex, 0).getDate();
    $('#end-day').text(lastDayOfMonth);
    
    // Update weekend classes in period 1
    $('#period-1 thead th:not(.sticky-left):not(:last-child)').each(function(index) {
        const day = index + 1;
        const date = new Date(year, monthIndex - 1, day);
        const isWeekend = date.getDay() === 0 || date.getDay() === 6; // 0 = Sunday, 6 = Saturday
        
        // Update day and weekday
        const dayElement = $(this).contents().first();
        if (dayElement.length) dayElement.replaceWith(document.createTextNode(day));
        
        const weekdayElement = $(this).find('.text-xxs');
        if (weekdayElement.length) {
            const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            weekdayElement.text(weekdays[date.getDay()]);
        }
        
        // Update class
        $(this).removeClass('weekend');
        if (isWeekend) $(this).addClass('weekend');
    });
    
    // Update weekend classes in period 2
    $('#period-2 thead th:not(.sticky-left):not(:last-child)').each(function(index) {
        const day = index + 16;
        if (day <= lastDayOfMonth) {
            const date = new Date(year, monthIndex - 1, day);
            const isWeekend = date.getDay() === 0 || date.getDay() === 6;
            
            // Update day and weekday
            const dayElement = $(this).contents().first();
            if (dayElement.length) dayElement.replaceWith(document.createTextNode(day));
            
            const weekdayElement = $(this).find('.text-xxs');
            if (weekdayElement.length) {
                const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                weekdayElement.text(weekdays[date.getDay()]);
            }
            
            // Update class
            $(this).removeClass('weekend');
            if (isWeekend) $(this).addClass('weekend');
            
            // Make visible
            $(this).show();
        } else {
            // Hide columns beyond the last day of the month
            $(this).hide();
        }
    });
}

// Function to update the matrix view with data from the server
function updateMatrixView(data, period, month, year) {
    console.log('Updating matrix view for period:', period, 'with data:', data);
    
    const tbody = $('#period-' + period + ' tbody');
    tbody.empty();
    
    const routeGroups = {};
    
    if (data && data.length > 0) {
        // Group data by route_number
        data.forEach(function(row) {
            const routeNumber = row.route?.route_number || 'Unknown Route';
            if (!routeGroups[routeNumber]) {
                routeGroups[routeNumber] = {
                    routeName: row.route?.name || 'Unknown',
                    rows: []
                };
            }
            routeGroups[routeNumber].rows.push(row);
        });
        
        // Process each route group
        Object.keys(routeGroups).sort().forEach(function(routeNumber) {
            const routeGroup = routeGroups[routeNumber];
            
            // Add route header row
            const startDay = period === 1 ? 1 : 16;
            const endDay = period === 1 ? 15 : new Date(year, month, 0).getDate();
            
            routeGroup.rows.forEach(function(row) {
                const tr = $('<tr>').addClass('border-t border-gray-200 hover:bg-gray-50');
                
                // Add fixed columns
                tr.append(`
                    <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap sticky-left">${row.unit?.unit_number || '-'}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap sticky-left">${row.driver?.name || '-'}</td>
                    <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap sticky-left">${row.shift === 'pagi' || row.shift === 'morning' ? 'Pagi' : 'Siang'}</td>
                `);
                
                // Add day columns
                let totalDays = 0;
                
                for (let day = startDay; day <= endDay; day++) {
                    const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const dateObj = new Date(date);
                    
                    // Check if this date is a weekend
                    const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6; // 0 = Sunday, 6 = Saturday
                    const cellClass = isWeekend ? 'bg-orange-50' : '';
                    
                    // Check if driver is scheduled for this day
                    const isScheduled = row.dates && row.dates.includes(date);
                    
                    const td = $('<td>')
                        .addClass(`px-3 py-2 text-sm text-center ${cellClass}`)
                        .attr('data-date', date)
                        .attr('data-driver-id', row.driver?.id || '')
                        .attr('data-unit-id', row.unit?.id || '')
                        .attr('data-shift', row.shift || '');
                    
                    const checkbox = $('<input>')
                        .attr('type', 'checkbox')
                        .addClass('matrix-checkbox')
                        .prop('checked', isScheduled)
                        .attr('data-date', date)
                        .attr('data-driver-id', row.driver?.id || '')
                        .attr('data-unit-id', row.unit?.id || '')
                        .attr('data-shift', row.shift || '')
                        .attr('data-route-id', row.route?.id || '');
                    
                    td.append(checkbox);
                    tr.append(td);
                    
                    if (isScheduled) totalDays++;
                }
                
                // Add total column
                const totalTd = $('<td>')
                    .addClass('px-4 py-2 text-sm font-medium text-center bg-gray-100')
                    .text(totalDays);
                tr.append(totalTd);
                
                tbody.append(tr);
            });
        });
    } else {
        // No data message
        const colCount = period === 1 ? 20 : 20 + (new Date(year, month, 0).getDate() - 15);
        tbody.html(`
            <tr>
                <td colspan="${colCount}" class="px-6 py-4 text-sm font-medium text-center text-gray-500 whitespace-nowrap bg-yellow-50">
                    <i class="mr-2 fas fa-exclamation-circle"></i>
                    Tidak ada jadwal yang ditemukan.
                </td>
            </tr>
        `);
    }
}
</script>

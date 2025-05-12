<div class="flex justify-between items-center mb-4">
    <div class="period-tabs">
        <div class="period-tab active" data-period="1">Periode 1 (1-15)</div>
        <div class="period-tab" data-period="2">Periode 2 (16-{{ Carbon\Carbon::now()->endOfMonth()->format('d') }})</div>
    </div>
    <div class="flex items-end space-x-4">
        <div class="flex space-x-2">
            <button id="prev-month" type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button id="apply-matrix-filter" type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button id="next-month" type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<div class="period-content active" id="period-1">
    <div class="matrix-view-header">
        <h3 class="text-lg font-medium text-gray-900">
            Periode 1: Tanggal 1-15 <span id="period1-month-year">{{ Carbon\Carbon::now()->format('F Y') }}</span>
        </h3>
    </div>

    <table class="min-w-full divide-y divide-gray-200 matrix-table">
        <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">UNIT</th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">PENGEMUDI</th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">SHIFT</th>

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
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">UNIT</th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">PENGEMUDI</th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">SHIFT</th>

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

@push('scripts')
    <script>
        // Constants and configuration
        const MATRIX_SELECTORS = {
            month: '#matrix-month',
            year: '#matrix-year',
            period1: '#period-1',
            period2: '#period-2',
            matrixView: '#matrix-view',
            loadingHtml: `
                <tr>
                    <td colspan="20" class="px-6 py-4 text-sm font-medium text-center text-gray-500 whitespace-nowrap">
                        <i class="mr-2 fas fa-spinner fa-spin"></i>
                        Memuat data jadwal...
                    </td>
                </tr>
            `,
            errorHtml: (message = 'Terjadi kesalahan saat memuat data. Silakan coba lagi.') => `
                <tr>
                    <td colspan="20" class="px-6 py-4 text-sm font-medium text-center text-red-500 whitespace-nowrap bg-red-50">
                        <i class="mr-2 fas fa-exclamation-circle"></i>
                        ${message}
                    </td>
                </tr>
            `
        };

        // Matrix data loading and rendering
        function loadMatrixData(forceReload = false) {
            // Get the selected month and year values directly from the DOM elements
            const monthElement = document.getElementById('matrix-month');
            const yearElement = document.getElementById('matrix-year');

            // Ensure we're getting the current values from the DOM
            const month = parseInt(monthElement.value);
            const year = parseInt(yearElement.value);

            console.log('Selected values - month:', month, 'year:', year);

            // Update month/year display in headers
            updateMatrixMonthYear(month, year);

            const periodTbodies = [
                $(`${MATRIX_SELECTORS.period1} tbody`),
                $(`${MATRIX_SELECTORS.period2} tbody`)
            ];

            periodTbodies.forEach(tbody => tbody.html(MATRIX_SELECTORS.loadingHtml));

            // Make sure we're sending the correct month and year values
            const url = `{{ route('schedules.matrix-data') }}?month=${month}&year=${year}&_=${Date.now()}`;

            // Use XMLHttpRequest instead of fetch for better debugging
            const xhr = new XMLHttpRequest();
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        handleMatrixData(data, month, year);
                    } catch (e) {
                        handleMatrixError(e, periodTbodies);
                    }
                } else {
                    handleMatrixError(new Error(`Request failed: ${xhr.status}`), periodTbodies);
                }
            };
            xhr.onerror = function() {
                handleMatrixError(new Error('Network error'), periodTbodies);
            };
            xhr.send();
        }

        function updateMatrixMonthYear(month, year) {
            const monthSelect = $(MATRIX_SELECTORS.month);
            const yearSelect = $(MATRIX_SELECTORS.year);
            const monthText = monthSelect.find('option:selected').text();

            // Update headers
            $('[id$="-month-year"]').text(`${monthText} ${year}`);

            // Update period columns
            updatePeriodColumns(1, 1, 15, month, year);
            updatePeriodColumns(2, 16, new Date(year, month, 0).getDate(), month, year);
        }

        function updateMatrixView(data, period, month, year) {
            const tbody = $(`#period-${period} tbody`);
            tbody.empty();

            if (!data || data.length === 0) {
                tbody.html(noDataHtml(period, month, year));
                return;
            }

            // Group data by route
            const routeGroups = {};

            data.forEach(function(row) {
                const routeId = row.route?.id || 'unknown';
                const routeNumber = row.route?.route_number || '';
                const routeName = row.route?.name || 'Unknown Route';
                const routeKey = `${routeId}`;

                if (!routeGroups[routeKey]) {
                    routeGroups[routeKey] = {
                        id: routeId,
                        routeNumber: routeNumber,
                        routeName: routeName,
                        rows: []
                    };
                }
                routeGroups[routeKey].rows.push(row);
            });

            // Sort routes by route number
            const sortedRouteKeys = Object.keys(routeGroups).sort((a, b) => {
                const numA = routeGroups[a].routeNumber || '';
                const numB = routeGroups[b].routeNumber || '';
                return numA.localeCompare(numB);
            });

            // Process each route group
            sortedRouteKeys.forEach(function(routeKey) {
                const routeGroup = routeGroups[routeKey];

                // Add route header row
                const routeHeaderRow = $('<tr>').addClass('bg-gray-100');
                const routeHeaderText = routeGroup.routeNumber ?
                    `${routeGroup.routeNumber} - ${routeGroup.routeName}` :
                    routeGroup.routeName;

                routeHeaderRow.append(`
            <td colspan="3" class="px-4 py-2 text-sm font-bold text-gray-900 whitespace-nowrap bg-gray-100">
                ${routeHeaderText}
            </td>
        `);

                // Add empty cells for dates
                const daysInPeriod = period === 1 ? 15 : (new Date(year, month, 0).getDate() - 15);
                for (let i = 0; i < daysInPeriod; i++) {
                    routeHeaderRow.append('<td class="px-4 py-2 bg-gray-100"></td>');
                }

                // Add empty cell for total
                routeHeaderRow.append('<td class="px-4 py-2 bg-gray-100"></td>');

                tbody.append(routeHeaderRow);

                // Sort rows by unit number and shift
                routeGroup.rows.sort(function(a, b) {
                    if (a.unit?.unit_number !== b.unit?.unit_number) {
                        return (a.unit?.unit_number || '').localeCompare(b.unit?.unit_number || '');
                    }
                    return (a.shift || '').localeCompare(b.shift || '');
                });

                // Add each driver row
                routeGroup.rows.forEach(function(row) {
                    tbody.append(createMatrixRow(row, period, month, year));
                });
            });
        }

        function handleMatrixData(data, month, year) {
            if (data.success) {
                $(MATRIX_SELECTORS.matrixView).data('loaded', true);

                // Update the month and year selectors to match the returned data
                if (data.month && data.year) {
                    // Update the month dropdown to match the returned month
                    const monthElement = document.getElementById('matrix-month');
                    monthElement.value = data.month;

                    // Update the year dropdown to match the returned year
                    const yearElement = document.getElementById('matrix-year');
                    yearElement.value = data.year;

                    // Update the month/year display with the returned values
                    updateMatrixMonthYear(data.month, data.year);

                    // Use the returned month and year for rendering
                    month = data.month;
                    year = data.year;
                }

                updateMatrixView(data.period1, 1, month, year);
                updateMatrixView(data.period2, 2, month, year);
            } else {
                const periodTbodies = [
                    $(`${MATRIX_SELECTORS.period1} tbody`),
                    $(`${MATRIX_SELECTORS.period2} tbody`)
                ];
                periodTbodies.forEach(tbody =>
                    tbody.html(MATRIX_SELECTORS.errorHtml(data.message))
                );
            }
        }

        function updatePeriodColumns(period, startDay, endDay, month, year) {
            const headerSelector = `#period-${period} thead th:not(:first-child):not(:nth-child(2)):not(:nth-child(3)):not(:last-child)`;
            const lastDayOfMonth = new Date(year, month, 0).getDate();

            $(headerSelector).each(function(index) {
                const day = startDay + index;
                const $header = $(this);

                if (day > endDay || day > lastDayOfMonth) {
                    $header.hide();
                    return;
                }

                const date = new Date(year, month - 1, day);
                const isWeekend = date.getDay() === 0 || date.getDay() === 6; // Sunday (0) or Saturday (6)
                const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

                $header
                    .toggleClass('weekend', isWeekend)
                    .toggleClass('bg-orange-50', isWeekend)
                    .show()
                    .contents().first().replaceWith(day)
                    .end()
                    .find('.text-xxs').text(weekdays[date.getDay()]);
            });
        }

        function createMatrixRow(row, period, month, year) {
            const startDay = period === 1 ? 1 : 16;
            const endDay = period === 1 ? 15 : new Date(year, month, 0).getDate();
            const tr = $('<tr>').addClass('hover:bg-gray-50');
            let totalDays = 0;

            // Add unit column - only show unit number
            tr.append(`
                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                    ${row.unit?.unit_number || '-'}
                </td>
            `);

            // Add driver name column
            tr.append(`
                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                    <p>${row.driver?.name || '-'}</p>
                    <p class="text-xs inline-block rounded-full px-2 py-0.5 ${row.driver?.type.toLowerCase() === 'batangan' ? 'bg-green-500 text-white' : 'bg-orange-500 text-white'} italic text-xs">${row.driver?.type.toLowerCase() || '-'}</p>
                </td>
            `);

            // Add shift column with badge
            tr.append(`
        <td class="px-4 py-2 text-sm text-center whitespace-nowrap">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.shift === 'pagi' || row.shift === 'morning' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'}">
                ${row.shift === 'pagi' || row.shift === 'morning' ? 'Pagi' : 'Siang'}
            </span>
        </td>
    `);

            // Count total scheduled days for this period
            if (row.schedules && row.schedules.length > 0) {
                for (const schedule of row.schedules) {
                    if (schedule.schedule_date) {
                        const scheduleDateObj = new Date(schedule.schedule_date);
                        const scheduleDay = scheduleDateObj.getDate();
                        if (scheduleDay >= startDay && scheduleDay <= endDay) {
                            totalDays++;
                        }
                    }
                }
            }

            // Add day columns
            for (let day = startDay; day <= endDay; day++) {
                const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dateObj = new Date(year, month - 1, day);
                const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6; // Sunday (0) or Saturday (6)

                tr.append(createDayCell(date, row, isWeekend, month, year));
            }

            // Add total column
            tr.append(`
        <td class="px-4 py-2 text-sm font-medium text-center bg-gray-100">
            ${totalDays}
        </td>
    `);

            return tr;
        }

        function createDayCell(date, row, isWeekend, month, year) {
            // Check if this date is scheduled
            let isDateScheduled = false;

            if (row.schedules && row.schedules.length > 0) {
                // Loop through schedules to find a match
                for (const schedule of row.schedules) {
                    if (schedule.schedule_date) {
                        // Extract just the date part (YYYY-MM-DD) from the schedule_date
                        const scheduleDatePart = schedule.schedule_date.substring(0, 10);
                        if (scheduleDatePart === date) {
                            isDateScheduled = true;
                            break;
                        }
                    }
                }
            }

            return $(`
        <td class="px-3 py-2 text-sm text-center ${isWeekend ? 'bg-orange-50' : ''}"
            data-date="${date}"
            data-driver-id="${row.driver?.id || ''}"
            data-unit-id="${row.unit?.id || ''}"
            data-shift="${row.shift || ''}">
            <input type="checkbox"
                class="matrix-checkbox"
                ${isDateScheduled ? 'checked' : ''}
                data-date="${date}"
                data-driver-id="${row.driver?.id || ''}"
                data-unit-id="${row.unit?.id || ''}"
                data-shift="${row.shift || ''}"
                data-route-id="${row.route?.id || ''}"
                onchange="handleCheckboxChange(this)">
        </td>
    `);
        }

        function noDataHtml(period, month, year) {
            const colCount = period === 1 ? 20 : 20 + (new Date(year, month, 0).getDate() - 15);
            return `
        <tr>
            <td colspan="${colCount}" class="px-6 py-4 text-sm font-medium text-center text-gray-500 whitespace-nowrap bg-yellow-50">
                <i class="mr-2 fas fa-exclamation-circle"></i>
                Tidak ada jadwal yang ditemukan.
            </td>
        </tr>
    `;
        }

        // Event handlers
        function handleFilterClick() {
            // Force reload with the current values
            loadMatrixData(true);
        }

        function handlePrevMonthClick() {
            const monthElement = document.getElementById('matrix-month');
            const yearElement = document.getElementById('matrix-year');

            let month = parseInt(monthElement.value);
            let year = parseInt(yearElement.value);

            // Go to previous month
            month--;

            // If we go before January, go to December of previous year
            if (month < 1) {
                month = 12;
                year--;
            }

            // Update the selects
            monthElement.value = month;
            yearElement.value = year;

            // Reload data
            loadMatrixData(true);
        }

        function handleNextMonthClick() {
            const monthElement = document.getElementById('matrix-month');
            const yearElement = document.getElementById('matrix-year');

            let month = parseInt(monthElement.value);
            let year = parseInt(yearElement.value);

            // Go to next month
            month++;

            // If we go past December, go to January of next year
            if (month > 12) {
                month = 1;
                year++;
            }

            // Update the selects
            monthElement.value = month;
            yearElement.value = year;

            // Reload data
            loadMatrixData(true);
        }

        function handlePeriodTabClick() {
            const $this = $(this);
            $('.period-tab').removeClass('active');
            $this.addClass('active');

            const period = $this.data('period');
            $('.period-content').removeClass('active');
            $(`#period-${period}`).addClass('active');
        }

        async function handleSaveMatrixClick() {
            const $button = $(this);
            const activePeriod = $('.period-tab.active').data('period');
            const matrix = getSelectedSchedules(activePeriod);

            if (matrix.length === 0) {
                alert('No schedules selected to save.');
                return;
            }

            try {
                toggleSaveButton($button, true);
                const response = await saveMatrixData(matrix);
                handleSaveResponse(response, $button);
            } catch (error) {
                handleSaveError(error, $button);
            }
        }

        function handleCheckboxChange(checkbox) {
            const $checkbox = $(checkbox);
            const isChecked = $checkbox.prop('checked');

            // Get the data from the checkbox
            const scheduleData = {
                date: $checkbox.data('date'),
                driver_id: $checkbox.data('driver-id'),
                unit_id: $checkbox.data('unit-id'),
                shift: $checkbox.data('shift'),
                route_id: $checkbox.data('route-id'),
                checked: isChecked
            };

            // Show loading indicator on the cell
            const $cell = $checkbox.closest('td');
            const originalContent = $cell.html();
            $cell.html('<i class="fas fa-spinner fa-spin text-blue-500"></i>');

            // Save the individual schedule
            saveIndividualSchedule(scheduleData)
                .then(response => {
                    // Restore the checkbox with the correct state
                    $cell.html(originalContent);

                    // If the save was successful, make sure the checkbox state matches the response
                    if (response.success) {
                        $checkbox.prop('checked', response.checked);

                        // Show a brief success indicator
                        const $indicator = $('<span class="absolute top-0 right-0 text-green-500"><i class="fas fa-check-circle"></i></span>');
                        $cell.css('position', 'relative').append($indicator);
                        setTimeout(() => $indicator.fadeOut('fast', function() { $(this).remove(); }), 1500);
                    } else {
                        // If there was an error, show an error indicator and restore original state
                        const $indicator = $('<span class="absolute top-0 right-0 text-red-500"><i class="fas fa-exclamation-circle"></i></span>');
                        $cell.css('position', 'relative').append($indicator);
                        setTimeout(() => $indicator.fadeOut('fast', function() { $(this).remove(); }), 1500);

                        // Show error message
                        if (response.message) {
                            console.error('Schedule update error:', response.message);
                            alert(`Failed to update schedule: ${response.message}`);
                        }
                    }
                })
                .catch(error => {
                    // Restore the original content
                    $cell.html(originalContent);

                    // Show error indicator
                    const $indicator = $('<span class="absolute top-0 right-0 text-red-500"><i class="fas fa-exclamation-circle"></i></span>');
                    $cell.css('position', 'relative').append($indicator);
                    setTimeout(() => $indicator.fadeOut('fast', function() { $(this).remove(); }), 1500);

                    console.error('Error saving schedule:', error);
                    alert('Failed to update schedule. Please try again.');
                });
        }

        async function saveIndividualSchedule(scheduleData) {
            const response = await fetch('{{ route('schedules.save-individual') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify(scheduleData)
            });

            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }

            return response.json();
        }

        // Initialize on document ready
        $(document).ready(function() {
            // Add event listeners
            $('#apply-matrix-filter').on('click', handleFilterClick);
            $('#prev-month').on('click', handlePrevMonthClick);
            $('#next-month').on('click', handleNextMonthClick);
            $('.period-tab').on('click', handlePeriodTabClick);
            $('.save-matrix').on('click', handleSaveMatrixClick);

            // Also update when month or year is changed directly
            $(MATRIX_SELECTORS.month).on('change', function() {
                // Auto-apply filter when month changes
                loadMatrixData(true);
            });

            $(MATRIX_SELECTORS.year).on('change', function() {
                // Auto-apply filter when year changes
                loadMatrixData(true);
            });

            // Load initial data
            loadMatrixData();

            // Add CSS for weekend columns
            $('<style>')
                .text('.weekend { background-color: rgba(251, 146, 60, 0.1); }')
                .appendTo('head');
        });
    </script>
@endpush

<div class="flex justify-between items-center mb-4">
    <div class="period-tabs">
        <div class="period-tab active" data-period="1">Periode 1 (1-15)</div>
        <div class="period-tab" data-period="2">Periode 2 (16-{{ Carbon\Carbon::now()->endOfMonth()->format('d') }})</div>
        <div class="period-tab" data-period="unassigned">Pengemudi Belum Terjadwal</div>
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
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tanggal</th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">UNIT</th>

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
    </div>

    <table class="min-w-full divide-y divide-gray-200 matrix-table">
        <thead class="bg-gray-50">
        <tr>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">UNIT</th>
            <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">PENGEMUDI</th>

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

<!-- Unassigned Drivers Section -->
<div class="period-content" id="period-unassigned">
    <div class="matrix-view-header">
        <h3 class="text-lg font-medium text-gray-900">
            Pengemudi Belum Terjadwal <span id="unassigned-month-year">{{ Carbon\Carbon::now()->format('F Y') }}</span>
        </h3>
        <div>
            <button type="button" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out bg-green-600 border border-transparent rounded-md save-assignment hover:bg-green-500 active:bg-green-700 focus:outline-none focus:ring ring-green-300 disabled:opacity-25">
                <i class="mr-2 fas fa-save"></i>
                Simpan Penugasan
            </button>
        </div>
    </div>

    <!-- Assignment Form -->
    <div class="bg-white rounded-lg shadow-md p-4 mb-4">
        <h4 class="text-md font-medium text-gray-900 mb-2">Buat Penugasan Baru</h4>
        <form id="assignment-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="assignment-date" class="block text-sm font-medium text-gray-700">Tanggal</label>
                <input type="date" id="assignment-date" name="assignment-date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
            </div>
            <div>
                <label for="assignment-shift" class="block text-sm font-medium text-gray-700">Shift</label>
                <select id="assignment-shift" name="assignment-shift" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Pilih Shift</option>
                    <option value="pagi">Pagi</option>
                    <option value="siang">Siang</option>
                </select>
            </div>
            <div>
                <label for="assignment-route" class="block text-sm font-medium text-gray-700">Rute</label>
                <select id="assignment-route" name="assignment-route" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Pilih Rute</option>
                    <!-- Routes will be populated via JavaScript -->
                </select>
            </div>
            <div>
                <label for="assignment-unit" class="block text-sm font-medium text-gray-700">Unit</label>
                <select id="assignment-unit" name="assignment-unit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Pilih Unit</option>
                    <!-- Units will be populated via JavaScript -->
                </select>
            </div>
            <div>
                <label for="assignment-driver" class="block text-sm font-medium text-gray-700">Pengemudi</label>
                <select id="assignment-driver" name="assignment-driver" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    <option value="">Pilih Pengemudi</option>
                    <!-- Drivers will be populated via JavaScript -->
                </select>
            </div>
            <div class="flex items-end">
                <button type="button" id="add-assignment" class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-plus mr-2"></i> Tambah Penugasan
                </button>
            </div>
        </form>
    </div>

    <!-- Unassigned Drivers List -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h4 class="text-md font-medium text-gray-900 mb-2">Daftar Pengemudi Belum Terjadwal</h4>
        <div class="mb-4">
            <input type="text" id="unassigned-driver-search" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Cari pengemudi...">
        </div>
        <div id="unassigned-drivers-container" class="max-h-96 overflow-y-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Nama</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tipe</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rute</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Unit</th>
                    </tr>
                </thead>
                <tbody id="unassigned-drivers-list" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-sm text-center text-gray-500">
                            <i class="mr-2 fas fa-spinner fa-spin"></i>
                            Memuat data pengemudi...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pending Assignments List -->
    <div class="bg-white rounded-lg shadow-md p-4 mt-4">
        <h4 class="text-md font-medium text-gray-900 mb-2">Penugasan Baru</h4>
        <div id="pending-assignments-container">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Tanggal</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Shift</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Pengemudi</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Rute</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">Unit</th>
                        <th scope="col" class="px-4 py-3 text-xs font-medium tracking-wider text-center text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="pending-assignments-list" class="bg-white divide-y divide-gray-200">
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-sm text-center text-gray-500">
                            Belum ada penugasan baru
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script>
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

        function loadMatrixData(forceReload = false) {
            let month = $(MATRIX_SELECTORS.month).val();
            let year = $(MATRIX_SELECTORS.year).val();

            updateMatrixMonthYear(month, year);

            const periodTbodies = [
                $(`${MATRIX_SELECTORS.period1} tbody`),
                $(`${MATRIX_SELECTORS.period2} tbody`)
            ];

            periodTbodies.forEach(tbody => tbody.html(MATRIX_SELECTORS.loadingHtml));

            const url = `{{ route('schedules.matrix-data') }}?month=${month}&year=${year}&_=${Date.now()}`;

            const xhr = new XMLHttpRequest();
            xhr.open('GET', url);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const data = JSON.parse(xhr.responseText);
                        if (data && data.success) {
                            handleMatrixData(data, month, year);
                            
                            // Also load unassigned drivers
                            if (data.unassignedDrivers) {
                                loadUnassignedDrivers(data);
                            }
                        } else {
                            const errorMessage = data.message || 'Invalid data format received from server';
                            handleMatrixError(new Error(errorMessage), periodTbodies);
                        }
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

        function handleMatrixError(error, periodTbodies) {
            console.error('Matrix data error:', error);
            
            periodTbodies.forEach(tbody => {
                tbody.html(`
                    <tr>
                        <td colspan="20" class="px-6 py-4 text-sm text-center text-red-500">
                            <i class="mr-2 fas fa-exclamation-triangle"></i>
                            Error loading schedule data: ${error.message || 'Unknown error'}
                        </td>
                    </tr>
                `);
            });
        }
        
        function noDataHtml(period, month, year) {
            const periodLabel = period === 1 ? '1-15' : 
                               (period === 2 ? '16-' + new Date(year, month, 0).getDate() : 'unassigned');
            return `
                <tr>
                    <td colspan="20" class="px-6 py-4 text-sm text-center text-gray-500">
                        <i class="mr-2 fas fa-info-circle"></i>
                         Tidak ada jadwal yang ditemukan ${periodLabel}
                    </td>
                </tr>
            `;
        }

        function updateMatrixMonthYear(month, year) {
            const monthSelect = $(MATRIX_SELECTORS.month);
            const yearSelect = $(MATRIX_SELECTORS.year);
            const monthText = monthSelect.find('option:selected').text();

            $('[id$="-month-year"]').text(`${monthText} ${year}`);

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

            const sortedRouteKeys = Object.keys(routeGroups).sort((a, b) => {
                const numA = routeGroups[a].routeNumber || '';
                const numB = routeGroups[b].routeNumber || '';
                return numA.localeCompare(numB);
            });

            sortedRouteKeys.forEach(function(routeKey) {
                const routeGroup = routeGroups[routeKey];

                const routeHeaderRow = $('<tr>').addClass('bg-gray-100');
                const routeHeaderText = routeGroup.routeNumber ?
                    `${routeGroup.routeNumber} - ${routeGroup.routeName}` :
                    routeGroup.routeName;

                routeHeaderRow.append(`
            <td colspan="3" class="px-4 py-2 text-sm font-bold text-gray-900 whitespace-nowrap bg-gray-100">
                ${routeHeaderText}
            </td>
        `);

                const daysInPeriod = period === 1 ? 15 : (new Date(year, month, 0).getDate() - 15);
                for (let i = 0; i < daysInPeriod; i++) {
                    routeHeaderRow.append('<td class="px-4 py-2 bg-gray-100"></td>');
                }

                routeHeaderRow.append('<td class="px-4 py-2 bg-gray-100"></td>');

                tbody.append(routeHeaderRow);
                routeGroup.rows.sort(function(a, b) {
                    if (a.unit?.unit_number !== b.unit?.unit_number) {
                        return (a.unit?.unit_number || '').localeCompare(b.unit?.unit_number || '');
                    }
                    return (a.shift || '').localeCompare(b.shift || '');
                });

                routeGroup.rows.forEach(function(row) {
                    tbody.append(createMatrixRow(row, period, month, year));
                });
            });
        }

        function handleMatrixData(data, month, year) {
            if (data.success) {
                $(MATRIX_SELECTORS.matrixView).data('loaded', true);

                if (data.month && data.year) {
                    const monthElement = document.getElementById('matrix-month');
                    monthElement.value = data.month;

                    const yearElement = document.getElementById('matrix-year');
                    yearElement.value = data.year;

                    updateMatrixMonthYear(data.month, data.year);
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
            const dayCount = endDay - startDay + 1;
            const lastDayOfMonth = new Date(year, month, 0).getDate();
            
            const $headerRow = $(`#period-${period} thead tr`);
            const $headers = $headerRow.find('th');
            
            for (let i = 0; i < dayCount; i++) {
                const columnIndex = i + 2; // +2 because we have 2 fixed columns before the date columns
                const day = startDay + i;
                const $header = $headers.eq(columnIndex);
                
                if (day > endDay || day > lastDayOfMonth) {
                    $header.hide();
                    continue;
                }
                
                const date = new Date(year, month - 1, day);
                const isWeekend = date.getDay() === 0 || date.getDay() === 6; // Sunday (0) or Saturday (6)
                const dayOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][date.getDay()];
                
                $header.empty()
                    .append(document.createTextNode(day))
                    .append($('<div class="text-xxs"></div>').text(dayOfWeek))
                    .toggleClass('weekend', isWeekend)
                    .toggleClass('bg-orange-50', isWeekend)
                    .show();
            }
            
            for (let i = dayCount; i < 31; i++) { // Assuming max 31 days
                const columnIndex = i + 2;
                if (columnIndex < $headers.length - 1) { // -1 to exclude the total column
                    $headers.eq(columnIndex).hide();
                }
            }
        }

        function createMatrixRow(row, period, month, year) {
            const startDay = period === 1 ? 1 : 16;
            const endDay = period === 1 ? 15 : new Date(year, month, 0).getDate();
            const tr = $('<tr>').addClass('hover:bg-gray-50');
            let totalDays = 0;

            tr.append(`
                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                    ${row.unit?.unit_number || '-'}
                </td>
            `);

            tr.append(`
                <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                    <p>${row.driver?.name || '-'}</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        <span class="text-xs inline-block rounded-full px-2.5 py-0.5 ${row.driver?.type.toLowerCase() === 'batangan' ? 'bg-green-500 text-white' : 'bg-orange-500 text-white'} italic">
                            ${row.driver?.type.toLowerCase() || '-'}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${row.shift === 'pagi' || row.shift === 'morning' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'}">
                            ${row.shift === 'pagi' || row.shift === 'morning' ? 'Pagi' : 'Siang'}
                        </span>
                        ${row.is_backup ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Backup</span>' : ''}
                    </div>
                </td>
            `);

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

            for (let day = startDay; day <= endDay; day++) {
                const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dateObj = new Date(year, month - 1, day);
                const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6;

                tr.append(createDayCell(date, row, isWeekend, month, year));
            }

            tr.append(`
                <td class="px-4 py-2 text-sm font-medium text-center bg-gray-100">
                    ${totalDays}
                </td>
            `);

            return tr;
        }

        function createDayCell(date, row, isWeekend, month, year) {
            let isDateScheduled = false;
            let hasBackupDriver = false;
            let backupDriverId = null;
            let currentSchedule = null;
            let isOnLeave = false;
            
            if (row.on_leave_dates && row.on_leave_dates.includes(date)) {
                isOnLeave = true;
            }
            
            const isBackupDriver = row.is_backup === true;

            if (row.schedules && row.schedules.length > 0) {
                for (const schedule of row.schedules) {
                    if (schedule.schedule_date) {
                        const scheduleDatePart = schedule.schedule_date.substring(0, 10);
                        if (scheduleDatePart === date) {
                            isDateScheduled = true;
                            currentSchedule = schedule;
                            
                            if (schedule.backup_driver_id) {
                                hasBackupDriver = true;
                                backupDriverId = schedule.backup_driver_id;
                            }
                            break;
                        }
                    }
                }
            }

            let cellClass = isWeekend ? 'bg-orange-50' : '';
            if (isOnLeave) {
                cellClass += ' bg-red-50';
            } else if (isBackupDriver) {
                cellClass += ' bg-blue-50';
            }
            
            const cell = $(`
                <td class="px-3 py-2 text-sm text-center ${cellClass}"
                    data-date="${date}"
                    data-driver-id="${row.driver?.id || ''}"
                    data-unit-id="${row.unit?.id || ''}"
                    data-shift="${row.shift || ''}"
                    data-route-id="${row.route?.id || ''}">
                    <div class="flex flex-col items-center justify-center">
                        <input type="checkbox"
                            class="matrix-checkbox mb-1"
                            ${isDateScheduled && !isOnLeave ? 'checked' : ''}
                            ${isOnLeave ? 'disabled' : ''}
                            data-date="${date}"
                            data-driver-id="${row.driver?.id || ''}"
                            data-unit-id="${row.unit?.id || ''}"
                            data-shift="${row.shift || ''}"
                            data-route-id="${row.route?.id || ''}"
                            onchange="handleCheckboxChange(this)">
                    </div>
                </td>
            `);

            if (isBackupDriver) {
                const backupBadge = $(`
                    <span class="ml-1 text-xs bg-blue-100 text-blue-800 font-medium px-1 py-0.5 rounded" title="Backup driver for ${row.primary_driver_id}">
                        Backup
                    </span>
                `);
                cell.find('.flex').append(backupBadge);
            } else if (hasBackupDriver && !isOnLeave) {
                const indicator = $(`
                    <span class="ml-1 text-xs text-blue-600" title="This schedule has a backup driver (ID: ${backupDriverId})">
                        <i class="fas fa-exchange-alt"></i>
                    </span>
                `);
                cell.find('.flex').append(indicator);
            }
            
            if (isOnLeave) {
                const leaveIndicator = $(`
                    <span class="ml-1 text-xs text-red-600" title="Driver is on leave on this date">
                        <i class="fas fa-user-times"></i>
                    </span>
                `);
                cell.find('.flex').append(leaveIndicator);
            }

            return cell;
        }
        
        function saveIndividualSchedule(scheduleData) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: '{{ route("schedules.save-individual") }}',
                    type: 'POST',
                    data: scheduleData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        resolve(response);
                        toastr.success('Jadwal berhasil disimpan', 'Sukses');
                        loadMatrixData(true);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving schedule:', error);
                        reject(error);
                    }
                });
            });
        }

        function handleCheckboxChange(checkbox) {
            const $checkbox = $(checkbox);
            const isChecked = $checkbox.prop('checked');

            const scheduleData = {
                date: $checkbox.data('date'),
                driver_id: $checkbox.data('driver-id'),
                unit_id: $checkbox.data('unit-id'),
                shift: $checkbox.data('shift'),
                route_id: $checkbox.data('route-id'),
                checked: isChecked
            };

            const $cell = $checkbox.closest('td');
            const originalContent = $cell.html();
            $cell.html('<i class="fas fa-spinner fa-spin text-blue-500"></i>');
            saveIndividualSchedule(scheduleData)
                .then(response => {
                    $cell.html(originalContent);
                    if (response.success) {
                        $checkbox.prop('checked', response.checked);
                        const $indicator = $('<span class="absolute top-0 right-0 text-green-500"><i class="fas fa-check-circle"></i></span>');
                        $cell.css('position', 'relative').append($indicator);
                        setTimeout(() => $indicator.fadeOut('fast', function() { $(this).remove(); }), 1500);
                    } else {
                        const $indicator = $('<span class="absolute top-0 right-0 text-red-500"><i class="fas fa-exclamation-circle"></i></span>');
                        $cell.css('position', 'relative').append($indicator);
                        setTimeout(() => $indicator.fadeOut('fast', function() { $(this).remove(); }), 1500);
                        if (response.message) {
                            console.error('Schedule update error:', response.message);
                            alert(`Failed to update schedule: ${response.message}`);
                        }
                    }
                })
                .catch(error => {
                    $cell.html(originalContent);
                    const $indicator = $('<span class="absolute top-0 right-0 text-red-500"><i class="fas fa-exclamation-circle"></i></span>');
                    $cell.css('position', 'relative').append($indicator);
                    setTimeout(() => $indicator.fadeOut('fast', function() { $(this).remove(); }), 1500);

                    console.error('Error saving schedule:', error);
                    alert('Failed to update schedule. Please try again.');
                });
        }

        function handleFilterClick() {
            loadMatrixData(true);
        }

        function handlePrevMonthClick() {
            const monthElement = document.getElementById('matrix-month');
            const yearElement = document.getElementById('matrix-year');

            let month = parseInt(monthElement.value);
            let year = parseInt(yearElement.value);

            month--;

            if (month < 1) {
                month = 12;
                year--;
            }

            monthElement.value = month;
            yearElement.value = year;

            loadMatrixData(true);
        }

        function handleNextMonthClick() {
            const monthElement = document.getElementById('matrix-month');
            const yearElement = document.getElementById('matrix-year');

            let month = parseInt(monthElement.value);
            let year = parseInt(yearElement.value);

            month++;

            if (month > 12) {
                month = 1;
                year++;
            }

            monthElement.value = month;
            yearElement.value = year;

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

        function handleSaveAssignmentClick() {
            if (pendingAssignments.length === 0) {
                alert('Tidak ada penugasan baru untuk disimpan');
                return;
            }
            
            const $button = $(this);
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...');
            
            const assignments = pendingAssignments.map(a => ({
                date: a.date,
                shift: a.shift,
                route_id: a.route_id,
                unit_id: a.unit_id,
                driver_id: a.driver_id
            }));
            
            $.ajax({
                url: '/schedules/save-individual-batch',
                type: 'POST',
                data: JSON.stringify({ assignments }),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        const successCount = response.results.filter(r => r.success).length;
                        alert(`Berhasil menyimpan ${successCount} penugasan baru`);
                        
                        pendingAssignments = [];
                        renderPendingAssignments();
                        
                        loadMatrixData(true);
                        
                        loadUnassignedDrivers();
                    } else {
                        const failedResults = response.results ? response.results.filter(r => !r.success) : [];
                        let errorMessage = 'Gagal menyimpan penugasan: ' + response.message;
                        
                        if (failedResults.length > 0) {
                            errorMessage += '\n\nDetail kegagalan:';
                            failedResults.forEach((result, index) => {
                                const driver = pendingAssignments.find(a => a.id === result.index)?.driver?.name || 'Unknown';
                                const date = pendingAssignments.find(a => a.id === result.index)?.date || 'Unknown';
                                errorMessage += `\n${index + 1}. ${driver} (${date}): ${result.message}`;
                            });
                        }
                        
                        alert(errorMessage);
                    }
                    $button.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Simpan Penugasan');
                },
                error: function(error) {
                    console.error('Error saving assignments:', error);
                    let errorMessage = 'Gagal menyimpan penugasan. Silakan coba lagi.';
                    
                    if (error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                        
                        if (error.responseJSON.errors) {
                            errorMessage += '\n\nDetail validasi:';
                            Object.entries(error.responseJSON.errors).forEach(([field, messages]) => {
                                errorMessage += `\n- ${field}: ${messages.join(', ')}`;
                            });
                        }
                    }
                    
                    alert(errorMessage);
                    $button.prop('disabled', false).html('<i class="fas fa-save mr-2"></i> Simpan Penugasan');
                }
            });
        }

        $(document).ready(function() {
            $('#apply-matrix-filter').on('click', handleFilterClick);
            $('#prev-month').on('click', handlePrevMonthClick);
            $('#next-month').on('click', handleNextMonthClick);
            $('.period-tab').on('click', handlePeriodTabClick);
            $('.save-matrix').on('click', handleSaveMatrixClick);

            $(MATRIX_SELECTORS.month).on('change', function() {
                loadMatrixData(true);
            });

            $(MATRIX_SELECTORS.year).on('change', function() {
                loadMatrixData(true);
            });

            loadMatrixData();

            $('<style>')
                .text('.weekend { background-color: rgba(251, 146, 60, 0.1); }')
                .appendTo('head');
        });

        let unassignedDrivers = [];
        let routes = [];
        let units = [];
        let pendingAssignments = [];

        function loadUnassignedDrivers(data) {
            if (!data) {
                const monthElement = document.getElementById('matrix-month');
                const yearElement = document.getElementById('matrix-year');
                const month = parseInt(monthElement.value);
                const year = parseInt(yearElement.value);
                
                const url = `{{ route('schedules.matrix-data') }}?month=${month}&year=${year}&unassigned_only=1&_=${Date.now()}`;
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.unassignedDrivers) {
                            unassignedDrivers = response.unassignedDrivers;
                            renderUnassignedDrivers();
                            
                            loadRoutes();
                        }
                    },
                    error: function(error) {
                        console.error('Error loading unassigned drivers:', error);
                    }
                });
                return;
            }

            if (!data.unassignedDrivers) {
                return;
            }

            unassignedDrivers = data.unassignedDrivers;
            renderUnassignedDrivers();
            
            loadRoutes();
        }

        function renderUnassignedDrivers() {
            const $container = $('#unassigned-drivers-list');
            $container.empty();

            if (unassignedDrivers.length === 0) {
                $container.html(`
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-sm text-center text-gray-500">
                            Semua pengemudi sudah terjadwal
                        </td>
                    </tr>
                `);
                return;
            }

            unassignedDrivers.forEach(driver => {
                const driverRoutes = driver.routes ? driver.routes.map(r => r.route_number ? `${r.route_number} - ${r.name}` : r.name).join(', ') : '-';
                const driverUnits = driver.units ? driver.units.map(u => u.unit_number).join(', ') : '-';
                
                $container.append(`
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${driver.name}
                        </td>
                        <td class="px-4 py-2 text-sm whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${driver.type.toLowerCase() === 'batangan' ? 'bg-green-500 text-white' : 'bg-orange-500 text-white'} italic text-xs">${driver.type}</span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${driverRoutes}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${driverUnits}
                        </td>
                    </tr>
                `);
            });
        }

        function filterUnassignedDrivers() {
            const searchTerm = $('#unassigned-driver-search').val().toLowerCase();
            $('#unassigned-drivers-list tr').each(function() {
                const driverName = $(this).find('td:first').text().toLowerCase();
                if (driverName.includes(searchTerm) || searchTerm === '') {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function loadRoutes() {
            $.ajax({
                url: '/api/routes/list',
                type: 'GET',
                success: function(data) {
                    routes = data;
                    const $routeSelect = $('#assignment-route');
                    $routeSelect.find('option:not(:first)').remove();
                    
                    routes.forEach(route => {
                        const routeText = route.route_number ? 
                            `${route.route_number} - ${route.name}` : 
                            route.name;
                        
                        $routeSelect.append(`<option value="${route.id}">${routeText}</option>`);
                    });
                },
                error: function(error) {
                    console.error('Error loading routes:', error);
                }
            });
        }

        function loadUnitsForRoute() {
            const routeId = $('#assignment-route').val();
            if (!routeId) {
                $('#assignment-unit').find('option:not(:first)').remove();
                return;
            }

            $.ajax({
                url: `/api/schedules/routes/${routeId}/units`,
                type: 'GET',
                success: function(unitIds) {
                    $.ajax({
                        url: '/api/units/list',
                        type: 'GET',
                        success: function(allUnits) {
                            units = allUnits.filter(unit => unitIds.includes(unit.id));
                            const $unitSelect = $('#assignment-unit');
                            $unitSelect.find('option:not(:first)').remove();
                            
                            units.forEach(unit => {
                                $unitSelect.append(`<option value="${unit.id}">${unit.unit_number} (${unit.plate_number})</option>`);
                            });
                            
                            updateDriverSelect();
                        },
                        error: function(error) {
                            console.error('Error loading units:', error);
                        }
                    });
                },
                error: function(error) {
                    console.error('Error loading units for route:', error);
                }
            });
        }

        function updateDriverSelect() {
            const routeId = $('#assignment-route').val();
            const unitId = $('#assignment-unit').val();
            
            if (!routeId || !unitId) {
                return;
            }
            
            const $driverSelect = $('#assignment-driver');
            $driverSelect.find('option:not(:first)').remove();
            $driverSelect.prop('disabled', true);
            
            $driverSelect.append(`<option value="" disabled>Loading drivers...</option>`);
            
            $.ajax({
                url: `/api/schedules/qualified-drivers/${routeId}/${unitId}`,
                type: 'GET',
                success: function(data) {
                    $driverSelect.find('option[disabled]').remove();
                    $driverSelect.prop('disabled', false);
                    
                    if (data.qualified && data.qualified.length > 0) {
                        $driverSelect.append(`<optgroup label="Qualified Drivers">`);
                        data.qualified.forEach(driver => {
                            $driverSelect.append(`<option value="${driver.id}">${driver.name} (${driver.type})</option>`);
                        });
                        $driverSelect.append(`</optgroup>`);
                    }
                    
                    if (data.routeOnly && data.routeOnly.length > 0) {
                        $driverSelect.append(`<optgroup label="Route Only Qualified">`);
                        data.routeOnly.forEach(driver => {
                            $driverSelect.append(`<option value="${driver.id}">${driver.name} (Route Only)</option>`);
                        });
                        $driverSelect.append(`</optgroup>`);
                    }
                    
                    if (data.unitOnly && data.unitOnly.length > 0) {
                        $driverSelect.append(`<optgroup label="Unit Only Qualified">`);
                        data.unitOnly.forEach(driver => {
                            $driverSelect.append(`<option value="${driver.id}">${driver.name} (Unit Only)</option>`);
                        });
                        $driverSelect.append(`</optgroup>`);
                    }
                    
                    if (data.unqualified && data.unqualified.length > 0) {
                        $driverSelect.append(`<optgroup label="Unqualified Drivers">`);
                        data.unqualified.forEach(driver => {
                            $driverSelect.append(`<option value="${driver.id}">${driver.name} (No Qualifications)</option>`);
                        });
                        $driverSelect.append(`</optgroup>`);
                    }
                },
                error: function(error) {
                    console.error('Error loading qualified drivers:', error);
                    $driverSelect.find('option[disabled]').remove();
                    $driverSelect.prop('disabled', false);
                    $driverSelect.append(`<option value="" disabled>Error loading drivers</option>`);
                }
            });
        }

        function addNewAssignment() {
            const date = $('#assignment-date').val();
            const shift = $('#assignment-shift').val();
            const routeId = $('#assignment-route').val();
            const unitId = $('#assignment-unit').val();
            const driverId = $('#assignment-driver').val();
            
            if (!date || !shift || !routeId || !unitId || !driverId) {
                alert('Silakan lengkapi semua field');
                return;
            }
            
            const route = routes.find(r => r.id == routeId);
            const unit = units.find(u => u.id == unitId);
            const driver = unassignedDrivers.find(d => d.id == driverId);
            
            if (!route || !unit || !driver) {
                alert('Data tidak valid');
                return;
            }
            
            const isDuplicate = pendingAssignments.some(a => 
                a.date === date && 
                a.shift === shift && 
                a.unit_id === unitId
            );
            
            if (isDuplicate) {
                alert('Unit sudah dijadwalkan pada tanggal dan shift yang sama');
                return;
            }
            
            const isDriverAssigned = pendingAssignments.some(a => 
                a.date === date && 
                a.shift === shift && 
                a.driver_id === driverId
            );
            
            if (isDriverAssigned) {
                if (!confirm('Pengemudi sudah dijadwalkan pada tanggal dan shift yang sama. Tetap lanjutkan?')) {
                    return;
                }
            }
            
            const assignment = {
                id: Date.now(),
                date: date,
                shift: shift,
                route_id: routeId,
                unit_id: unitId,
                driver_id: driverId,
                route: route,
                unit: unit,
                driver: driver
            };
            
            pendingAssignments.push(assignment);
            
            renderPendingAssignments();
            
            $('#assignment-form')[0].reset();
        }
        


        function renderPendingAssignments() {
            const $container = $('#pending-assignments-list');
            $container.empty();
            
            if (pendingAssignments.length === 0) {
                $container.html(`
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-sm text-center text-gray-500">
                            Belum ada penugasan baru
                        </td>
                    </tr>
                `);
                return;
            }
            
            pendingAssignments.forEach(assignment => {
                const formattedDate = new Date(assignment.date).toLocaleDateString('id-ID', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                
                const routeText = assignment.route.route_number ? 
                    `${assignment.route.route_number} - ${assignment.route.name}` : 
                    assignment.route.name;
                
                $container.append(`
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${formattedDate}
                        </td>
                        <td class="px-4 py-2 text-sm whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${assignment.shift === 'pagi' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'}">
                                ${assignment.shift === 'pagi' ? 'Pagi' : 'Siang'}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${assignment.driver.name}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${routeText}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">
                            ${assignment.unit.unit_number}
                        </td>
                        <td class="px-4 py-2 text-sm text-center whitespace-nowrap">
                            <button type="button" class="text-red-600 hover:text-red-900" onclick="removeAssignment(${assignment.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        }

        function removeAssignment(id) {
            pendingAssignments = pendingAssignments.filter(a => a.id !== id);
            renderPendingAssignments();
        }
    </script>
@endpush

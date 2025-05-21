@extends('modules.admin.layouts.main')

@section('title', 'Jadwal Pengemudi')

@push('styles')
<style>
    .tooltip-holiday {
        position: absolute;
        transform: translateX(-50%);
        z-index: 100;
        padding: 6px 10px;
        background-color: #dc2626;
        color: white;
        border-radius: 4px;
        font-size: 0.875rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        max-width: 250px;
        text-align: center;
    }
    
    .tooltip-holiday:before {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        border-width: 8px 8px 0;
        border-style: solid;
        border-color: #dc2626 transparent transparent transparent;
    }
    
    /* Holiday indicator triangle in the corner */
    .highlight-holiday {
        position: relative;
        transition: all 0.2s;
    }
    
    .highlight-holiday:after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0 12px 12px 0;
        border-color: transparent #ef4444 transparent transparent;
        z-index: 10;
        box-shadow: -1px 1px 3px rgba(239, 68, 68, 0.4);
    }
    
    .highlight-holiday:hover {
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.4);
    }
    
    /* Highlighted days */
    .highlight-saturday, .highlight-sunday {
        background-color: #fefce8 !important; /* yellow-50 - lighter yellow */
    }
    
    .highlight-holiday {
        background-color: #fef2f2 !important; /* red-50 - lighter red */
    }
    
    /* Cadangan driver checkmark */
    .cadangan-checkmark {
        background-color: #e9d5ff !important; /* purple-200 */
        color: #7e22ce !important; /* purple-700 */
    }
    
    /* Renops indicator */
    .renops-indicator {
        background-color: #1e40af !important; /* blue-800 */
        color: #ffffff !important; /* white */
        border: 2px solid #93c5fd !important; /* blue-300 */
    }
    
    /* Collapsible sections */
    .route-header, .unit-header {
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .route-header:hover, .unit-header:hover {
        background-color: rgba(0, 0, 0, 0.05);
    }
    
    .route-content, .unit-content {
        transition: all 0.3s ease-in-out;
    }
    
    .route-header .toggle-icon, .unit-header .toggle-icon {
        transition: transform 0.3s ease;
    }
    
    .route-header.collapsed .toggle-icon, .unit-header.collapsed .toggle-icon {
        transform: rotate(-90deg);
    }
</style>
@endpush

@section('content')
@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips for holidays
        const holidayCells = document.querySelectorAll('[data-tooltip]');
        holidayCells.forEach(cell => {
            cell.addEventListener('mouseenter', function() {
                // Remove any existing tooltips first
                document.querySelectorAll('.tooltip-holiday').forEach(el => el.remove());
                
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-holiday fixed z-50 px-3 py-2 text-sm text-white bg-red-600 rounded-md shadow-lg';
                tooltip.innerHTML = this.getAttribute('data-tooltip');
                
                // Position tooltip
                const rect = this.getBoundingClientRect();
                tooltip.style.top = (rect.top - 40) + 'px';
                tooltip.style.left = (rect.left + (rect.width/2)) + 'px';
                
                document.body.appendChild(tooltip);
            });
            
            cell.addEventListener('mouseleave', function() {
                document.querySelectorAll('.tooltip-holiday').forEach(el => el.remove());
            });
        });
        
        // Setup for dropdown menus
        const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const menu = this.nextElementSibling;
                menu.classList.toggle('hidden');
            });
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            dropdownToggles.forEach(toggle => {
                const dropdown = toggle.parentElement;
                const menu = toggle.nextElementSibling;
                
                if (!dropdown.contains(event.target) && !menu.classList.contains('hidden')) {
                    menu.classList.add('hidden');
                }
            });
        });
        
        // Initialize collapsible sections
        // By default, keep all route and unit sections expanded
        window.toggleRouteContent = function(routeId) {
            const header = document.querySelector(`.route-header[data-route-id="${routeId}"]`);
            const content = document.querySelector(`.route-content[data-route-content="${routeId}"]`);
            
            // Check if the click was on a toggle icon or the header itself
            event.stopPropagation();
            
            // Toggle the content visibility
            if (content.style.display === 'none') {
                content.style.display = 'table-row-group';
                header.classList.remove('collapsed');
            } else {
                content.style.display = 'none';
                header.classList.add('collapsed');
            }
        };
        
        window.toggleUnitContent = function(unitId) {
            const header = document.querySelector(`.unit-header[data-unit-id="${unitId}"]`);
            const content = document.querySelector(`.unit-content[data-unit-content="${unitId}"]`);
            
            // Check if the click was on a toggle icon or the header itself
            event.stopPropagation();
            
            // Toggle the content visibility
            if (content.style.display === 'none') {
                content.style.display = 'table-row-group';
                header.classList.remove('collapsed');
            } else {
                content.style.display = 'none';
                header.classList.add('collapsed');
            }
        };
        
        // Add click event listeners to prevent event propagation when clicking on links inside headers
        document.querySelectorAll('.route-header a, .unit-header a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        // Edit mode functionality
        let isEditMode = false;
        const editButton = document.getElementById('edit-schedule-btn');
        const editBtnText = document.getElementById('edit-btn-text');
        const scheduleTable = document.querySelector('table');
        const saveChangesRow = document.createElement('tr');
        
        if (editButton) {
            editButton.addEventListener('click', function() {
                isEditMode = !isEditMode;
                
                // Toggle edit mode
                if (isEditMode) {
                    editBtnText.textContent = 'Batal Edit';
                    editButton.classList.remove('bg-gradient-to-r', 'from-orange-600', 'to-orange-700', 'hover:from-orange-500', 'hover:to-orange-600');
                    editButton.classList.add('bg-gradient-to-r', 'from-red-600', 'to-red-700', 'hover:from-red-500', 'hover:to-red-600');
                    
                    // Show save changes button at the top of the table
                    if (scheduleTable) {
                        const tfoot = document.createElement('tfoot');
                        saveChangesRow.innerHTML = `
                            <td colspan="100%" class="px-4 py-3 bg-gray-100">
                                <div class="flex justify-end">
                                    <button id="save-changes-btn" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-green-600 to-green-700 hover:from-green-500 hover:to-green-600">
                                        <i class="mr-2 fas fa-save"></i>
                                        Simpan Perubahan
                                    </button>
                                </div>
                            </td>
                        `;
                        tfoot.appendChild(saveChangesRow);
                        scheduleTable.appendChild(tfoot);
                        
                        // Add event listener to save button
                        document.getElementById('save-changes-btn').addEventListener('click', function() {
                            // Here you would implement the save functionality
                            alert('Perubahan berhasil disimpan!');
                            toggleEditMode();
                        });
                    }
                    
                    // Show edit mode legend
                    document.getElementById('edit-mode-legend').classList.remove('hidden');
                    
                    // Convert all schedule indicators to checkboxes
                    convertToCheckboxes();
                    
                    // Initialize toggle all button
                    initToggleAllButton();
                } else {
                    toggleEditMode();
                }
            });
        }
        
        function toggleEditMode() {
            isEditMode = false;
            editBtnText.textContent = 'Edit Jadwal';
            editButton.classList.remove('bg-gradient-to-r', 'from-red-600', 'to-red-700', 'hover:from-red-500', 'hover:to-red-600');
            editButton.classList.add('bg-gradient-to-r', 'from-orange-600', 'to-orange-700', 'hover:from-orange-500', 'hover:to-orange-600');
            
            // Remove save changes button
            if (saveChangesRow.parentNode) {
                saveChangesRow.parentNode.removeChild(saveChangesRow);
            }
            
            // Hide edit mode legend
            document.getElementById('edit-mode-legend').classList.add('hidden');
            
            // Convert back to normal view
            convertToNormalView();
        }
        
        function convertToCheckboxes() {
            // Find all schedule cells (assigned and backup)
            const scheduleCells = document.querySelectorAll('td span.inline-flex.items-center.justify-center.w-6.h-6');
            const emptyCells = document.querySelectorAll('td span.inline-block.w-6.h-6');
            
            // Create a map to track checkboxes by unit and date
            const scheduleMap = new Map();
            
            scheduleCells.forEach(cell => {
                const isAssigned = cell.classList.contains('text-green-800');
                const isBackup = cell.classList.contains('text-amber-800');
                const parentTd = cell.closest('td');
                
                // Store original HTML for reverting later
                parentTd.setAttribute('data-original-html', parentTd.innerHTML);
                
                // Get unit and date information
                const row = parentTd.closest('tr');
                const unitRow = findUnitRow(row);
                const unitId = unitRow ? unitRow.getAttribute('data-unit-id') || getUnitIdFromText(unitRow.textContent) : 'unknown';
                const dateIndex = Array.from(row.cells).indexOf(parentTd);
                const dateCell = row.closest('table').querySelector('thead tr:last-child th:nth-child(' + (dateIndex + 1) + ')');
                const dateValue = dateCell ? dateCell.getAttribute('data-date') || dateCell.textContent.trim() : 'unknown';
                const shift = getShiftFromRow(row);
                
                // Create checkbox
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = isAssigned || isBackup;
                checkbox.className = 'w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500';
                checkbox.setAttribute('data-type', isAssigned ? 'assigned' : 'backup');
                checkbox.setAttribute('data-unit-id', unitId);
                checkbox.setAttribute('data-date', dateValue);
                checkbox.setAttribute('data-shift', shift);
                
                // Store in map for validation
                const mapKey = `${unitId}-${dateValue}-${shift}`;
                if (!scheduleMap.has(mapKey)) {
                    scheduleMap.set(mapKey, []);
                }
                scheduleMap.get(mapKey).push(checkbox);
                
                // Add change event listener for validation
                checkbox.addEventListener('change', function() {
                    validateScheduleConflicts(this, scheduleMap);
                });
                
                // Replace cell content with checkbox
                parentTd.innerHTML = '';
                parentTd.appendChild(checkbox);
            });
            
            emptyCells.forEach(cell => {
                const parentTd = cell.closest('td');
                parentTd.setAttribute('data-original-html', parentTd.innerHTML);
                
                // Get unit and date information
                const row = parentTd.closest('tr');
                const unitRow = findUnitRow(row);
                const unitId = unitRow ? unitRow.getAttribute('data-unit-id') || getUnitIdFromText(unitRow.textContent) : 'unknown';
                const dateIndex = Array.from(row.cells).indexOf(parentTd);
                const dateCell = row.closest('table').querySelector('thead tr:last-child th:nth-child(' + (dateIndex + 1) + ')');
                const dateValue = dateCell ? dateCell.getAttribute('data-date') || dateCell.textContent.trim() : 'unknown';
                const shift = getShiftFromRow(row);
                
                // Create checkbox
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.checked = false;
                checkbox.className = 'w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500';
                checkbox.setAttribute('data-type', 'empty');
                checkbox.setAttribute('data-unit-id', unitId);
                checkbox.setAttribute('data-date', dateValue);
                checkbox.setAttribute('data-shift', shift);
                
                // Store in map for validation
                const mapKey = `${unitId}-${dateValue}-${shift}`;
                if (!scheduleMap.has(mapKey)) {
                    scheduleMap.set(mapKey, []);
                }
                scheduleMap.get(mapKey).push(checkbox);
                
                // Add change event listener for validation
                checkbox.addEventListener('change', function() {
                    validateScheduleConflicts(this, scheduleMap);
                });
                
                // Replace cell content with checkbox
                parentTd.innerHTML = '';
                parentTd.appendChild(checkbox);
            });
        }
        
        function convertToNormalView() {
            // Find all cells with checkboxes
            const checkboxCells = document.querySelectorAll('td input[type="checkbox"]');
            
            checkboxCells.forEach(checkbox => {
                const parentTd = checkbox.closest('td');
                const originalHtml = parentTd.getAttribute('data-original-html');
                
                if (originalHtml) {
                    parentTd.innerHTML = originalHtml;
                }
            });
        }
        
        function initToggleAllButton() {
            const toggleAllBtn = document.getElementById('toggle-all-btn');
            if (toggleAllBtn) {
                toggleAllBtn.addEventListener('click', function() {
                    const checkboxes = document.querySelectorAll('td input[type="checkbox"]');
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    
                    // If all are checked, uncheck all; otherwise, check all
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = !allChecked;
                        // Trigger validation for each checkbox
                        const event = new Event('change');
                        checkbox.dispatchEvent(event);
                    });
                });
            }
        }
        
        // Helper function to find the unit row for a given row
        function findUnitRow(row) {
            let currentRow = row;
            while (currentRow) {
                if (currentRow.classList.contains('bg-blue-50')) {
                    return currentRow;
                }
                currentRow = currentRow.previousElementSibling;
            }
            return null;
        }
        
        // Helper function to extract unit ID from text
        function getUnitIdFromText(text) {
            const unitMatch = text.match(/Unit\s+(\d+)/i);
            return unitMatch ? unitMatch[1] : 'unknown';
        }
        
        // Helper function to get shift from row
        function getShiftFromRow(row) {
            const shiftCell = row.querySelector('td:nth-child(4)');
            if (shiftCell) {
                if (shiftCell.textContent.includes('Pagi')) {
                    return 'pagi';
                } else if (shiftCell.textContent.includes('Siang')) {
                    return 'siang';
                }
            }
            return 'unknown';
        }
        
        // Validate schedule conflicts
        function validateScheduleConflicts(checkbox, scheduleMap) {
            if (!checkbox.checked) {
                // If unchecking, no conflict possible
                checkbox.style.outline = '';
                return;
            }
            
            const unitId = checkbox.getAttribute('data-unit-id');
            const date = checkbox.getAttribute('data-date');
            const shift = checkbox.getAttribute('data-shift');
            const mapKey = `${unitId}-${date}-${shift}`;
            
            // Get all checkboxes for this unit, date, and shift
            const relatedCheckboxes = scheduleMap.get(mapKey) || [];
            
            // Count how many are checked
            const checkedCount = relatedCheckboxes.filter(cb => cb.checked).length;
            
            if (checkedCount > 1) {
                // Conflict detected - more than one driver assigned to the same unit, date, and shift
                relatedCheckboxes.forEach(cb => {
                    if (cb.checked) {
                        cb.style.outline = '2px solid red';
                        
                        // Add tooltip to indicate conflict
                        const tooltip = document.createElement('div');
                        tooltip.className = 'text-xs text-red-600 font-medium mt-1';
                        tooltip.textContent = 'Konflik jadwal';
                        
                        // Remove any existing tooltips
                        const existingTooltip = cb.parentNode.querySelector('.text-red-600');
                        if (existingTooltip) {
                            cb.parentNode.removeChild(existingTooltip);
                        }
                        
                        cb.parentNode.appendChild(tooltip);
                    }
                });
                
                // Show warning toast
                showWarningToast('Konflik jadwal terdeteksi! Unit yang sama tidak dapat memiliki lebih dari satu pengemudi pada tanggal dan shift yang sama.');
            } else {
                // No conflict, reset styles
                relatedCheckboxes.forEach(cb => {
                    cb.style.outline = '';
                    
                    // Remove any existing tooltips
                    const existingTooltip = cb.parentNode.querySelector('.text-red-600');
                    if (existingTooltip) {
                        cb.parentNode.removeChild(existingTooltip);
                    }
                });
            }
        }
        
        // Show warning toast
        function showWarningToast(message) {
            // Remove any existing toasts
            const existingToasts = document.querySelectorAll('.toast-warning');
            existingToasts.forEach(toast => toast.remove());
            
            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast-warning fixed top-4 right-4 bg-amber-100 border-l-4 border-amber-500 text-amber-700 p-4 rounded shadow-md z-50 max-w-md';
            toast.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-amber-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button class="inline-flex text-amber-500 focus:outline-none focus:text-amber-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            
            // Add close button functionality
            toast.querySelector('button').addEventListener('click', () => {
                toast.remove();
            });
            
            // Add to document
            document.body.appendChild(toast);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    toast.remove();
                }
            }, 5000);
        }
    });
</script>
@endpush
<div class="w-full px-4 container-fluid">
    <x-page-title>
        <x-slot name="title">
            <div class="flex items-center">
                <i class="mr-3 text-2xl text-indigo-500 fas fa-calendar-alt"></i>
                <div>
                    <h1 class="text-2xl font-bold">Jadwal Pengemudi</h1>
                    <p class="text-sm font-thin text-gray-500">Manajemen jadwal pengemudi</p>
                </div>
            </div>
        </x-slot>
        <x-slot name="actions">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('schedules.generate.form') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600">
                    <i class="mr-2 fas fa-calendar-plus"></i>
                    Buat Jadwal
                </a>
                <button id="edit-schedule-btn" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-500 hover:to-orange-600">
                    <i class="mr-2 fas fa-edit"></i>
                    <span id="edit-btn-text">Edit Jadwal</span>
                </button>
                <div class="relative dropdown">
                    <button class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow dropdown-toggle bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600" type="button">
                        <i class="mr-2 fas fa-file-export"></i>
                        Export
                        <i class="ml-2 text-xs fas fa-chevron-down"></i>
                    </button>
                    <div class="absolute right-0 z-50 hidden w-48 p-2 mt-2 space-y-1 bg-white rounded-md shadow-lg dropdown-menu ring-1 ring-black ring-opacity-5">
                        <a href="{{ route('schedules.export.excel', ['month' => $month, 'year' => $year, 'period' => $period, 'route' => $selectedRoute, 'driver' => $selectedDriver, 'unit' => $selectedUnit]) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900">
                            <i class="mr-2 text-green-500 fas fa-file-excel"></i>
                            Excel
                        </a>
                        <a href="{{ route('schedules.export.pdf', ['month' => $month, 'year' => $year, 'period' => $period, 'route' => $selectedRoute, 'driver' => $selectedDriver, 'unit' => $selectedUnit]) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900">
                            <i class="mr-2 text-red-500 fas fa-file-pdf"></i>
                            PDF
                        </a>
                        <a href="{{ route('schedules.export.matrix-pdf', ['month' => $month, 'year' => $year, 'period' => $period, 'route' => $selectedRoute, 'driver' => $selectedDriver, 'unit' => $selectedUnit]) }}" class="block px-4 py-2 text-xs font-medium text-gray-700 rounded-md hover:bg-gray-100 hover:text-gray-900">
                            <i class="mr-2 text-purple-500 fas fa-table"></i>
                            Matrix PDF
                        </a>
                        @if(app()->environment('local'))
                        <div class="border-t border-gray-100 my-1"></div>
                        <form action="{{ route('schedules.reset-all') }}" method="POST" onsubmit="return confirm('PERINGATAN: Semua data jadwal akan dihapus. Apakah Anda yakin?');">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 rounded-md hover:bg-red-50 hover:text-red-800">
                                <i class="mr-2 fas fa-trash-alt"></i>
                                Reset Data Jadwal
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </x-slot>
    </x-page-title>
    
    <div class="p-6 bg-white rounded-lg shadow-md">
        <!-- Statistics -->
        <div class="mb-6">
            <h3 class="mb-3 text-lg font-medium text-gray-700">
                <i class="mr-2 text-gray-500 fas fa-chart-bar"></i>Statistik Jadwal
            </h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div class="p-4 transition-all border border-blue-200 rounded-lg shadow-sm bg-gradient-to-r from-blue-50 to-blue-100 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 text-white bg-blue-500 rounded-lg shadow-md">
                            <i class="text-lg fas fa-calendar-check"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-blue-900">Total Jadwal</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-blue-700">{{ $totalAssignments }}</p>
                                <p class="ml-2 text-xs text-blue-500">penugasan</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 transition-all border rounded-lg shadow-sm bg-gradient-to-r from-emerald-50 to-emerald-100 border-emerald-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 text-white rounded-lg shadow-md bg-emerald-500">
                            <i class="text-lg fas fa-user-check"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-emerald-900">Total Pengemudi</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-emerald-700">{{ $uniqueDriversCount }}</p>
                                <p class="ml-2 text-xs text-emerald-500">pengemudi</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 transition-all border rounded-lg shadow-sm bg-gradient-to-r from-amber-50 to-amber-100 border-amber-200 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 text-white rounded-lg shadow-md bg-amber-500">
                            <i class="text-lg fas fa-bus"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-amber-900">Total Unit</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-amber-700">{{ $uniqueUnitsCount }}</p>
                                <p class="ml-2 text-xs text-amber-500">kendaraan</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="p-4 transition-all border border-indigo-200 rounded-lg shadow-sm bg-gradient-to-r from-indigo-50 to-indigo-100 hover:shadow-md">
                    <div class="flex items-center">
                        <div class="p-3 text-white bg-indigo-500 rounded-lg shadow-md">
                            <i class="text-lg fas fa-calendar-alt"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-indigo-900">Periode Aktif</p>
                            <p class="text-xl font-semibold text-indigo-700">{{ $period == 1 ? '1-15' : '16-'.$endDate }}</p>
                            <p class="text-xs text-indigo-600">{{ $monthName }} {{ $year }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="p-4 mb-6 border border-gray-200 rounded-lg shadow-sm bg-gradient-to-r from-gray-50 to-gray-100">
            <h3 class="mb-3 text-lg font-medium text-gray-700">
                <i class="mr-2 text-indigo-500 fas fa-filter"></i>Filter Jadwal
            </h3>
            
            <form id="filter-form" method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
                <div>
                    <x-input-label for="month" value="Bulan" class="font-medium text-gray-700" />
                    <div class="relative">
                        <select id="month" name="month" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ Carbon\Carbon::create(null, $m, 1)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 pointer-events-none">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div>
                    <x-input-label for="year" value="Tahun" class="font-medium text-gray-700" />
                    <div class="relative">
                        <select id="year" name="year" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            @foreach(range(date('Y')-2, date('Y')+1) as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 pointer-events-none">
                            <i class="fas fa-calendar-year"></i>
                        </div>
                    </div>
                </div>
                
                <div>
                    <x-input-label for="route" value="Rute" class="font-medium text-gray-700" />
                    <div class="relative">
                        <select id="route" name="route" class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            <option value="">-- Semua Rute --</option>
                            @foreach($routes as $route)
                                <option value="{{ $route->id }}" {{ $selectedRoute == $route->id ? 'selected' : '' }}>
                                    {{ $route->route_number }} - {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 pointer-events-none">
                            <i class="fas fa-route"></i>
                        </div>
                    </div>
                </div>
                
                <div>
                    <x-input-label for="driver" value="Pengemudi" class="font-medium text-gray-700" />
                    <div class="relative" 
                         x-data="{ 
                            open: false, 
                            search: '{{ $selectedDriver ? ($drivers->firstWhere('id', $selectedDriver)->name) : '-- Semua Pengemudi --' }}', 
                            selectedOption: '{{ $selectedDriver }}',
                            defaultSearch: '{{ $selectedDriver ? ($drivers->firstWhere('id', $selectedDriver)->name) : '-- Semua Pengemudi --' }}',
                            isFirstInput: true,
                            filterDrivers(term) {
                                // Clear default text on first input
                                if (this.isFirstInput && term === this.defaultSearch) {
                                    this.search = '';
                                    this.isFirstInput = false;
                                    return;
                                }
                                
                                // Skip filtering if empty or default text
                                if (!term) return;
                                
                                document.querySelectorAll('[data-driver-item]').forEach(el => {
                                    const driverName = el.getAttribute('data-driver-name').toLowerCase();
                                    el.style.display = driverName.includes(term.toLowerCase()) ? '' : 'none';
                                });
                            }
                         }">
                        <div class="relative">
                            <input
                                type="text"
                                placeholder="Ketik untuk mencari pengemudi..."
                                class="block w-full pl-10 pr-10 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                x-on:focus="open = true"
                                x-on:click="open = true"
                                x-model="search"
                                x-on:input="filterDrivers(search)"
                                autocomplete="off"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="text-indigo-500 fas fa-search"></i>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="flex items-center justify-center w-6 h-6 text-gray-100 bg-indigo-500 rounded-full">
                                    <i class="text-xs fas fa-user"></i>
                                </span>
                            </div>
                            <input type="hidden" name="driver" :value="selectedOption" />
                        </div>
                        
                        <div 
                            x-show="open" 
                            x-on:click.away="open = false"
                            class="absolute z-40 w-full mt-1 overflow-auto bg-white rounded-md shadow-lg max-h-60"
                            style="display: none;"
                        >
                            <div class="sticky top-0 p-2 border-b border-gray-200 bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-medium text-gray-600">Pilih pengemudi</div>
                                    <button type="button" @click="open = false" class="text-gray-500 hover:text-gray-700">
                                        <i class="text-xs fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <div class="py-1 text-sm cursor-pointer hover:bg-gray-50" 
                                     x-on:click="selectedOption = ''; open = false; search = '-- Semua Pengemudi --'">
                                    <span class="flex items-center block px-4 py-2">
                                        <i class="mr-2 text-gray-500 fas fa-globe-asia"></i>
                                        -- Semua Pengemudi --
                                    </span>
                                </div>
                                
                                <div class="py-8 text-center" 
                                     x-show="search !== defaultSearch && !Array.from(document.querySelectorAll('[data-driver-item]')).some(el => el.style.display !== 'none')"
                                     style="display: none;">
                                    <i class="mb-2 text-2xl text-gray-300 fas fa-search"></i>
                                    <p class="text-sm text-gray-500">Tidak ditemukan pengemudi yang cocok</p>
                                </div>
                                
                                <div class="py-1 border-t border-gray-100">
                                    <div class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 sticky top-[41px]">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center justify-center w-5 h-5 mr-2 rounded-full bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-user-tie text-xxs"></i>
                                            </span>
                                            Pengemudi Tetap (Batangan)
                                        </div>
                                    </div>
                                    @foreach($drivers->where('type', 'batangan') as $driver)
                                        <div class="text-sm cursor-pointer hover:bg-gray-50" 
                                             x-on:click="selectedOption = '{{ $driver->id }}'; open = false; search = '{{ $driver->name }}'"
                                             data-driver-item
                                             data-driver-name="{{ $driver->name }}"
                                        >
                                            <span class="block px-4 py-2 {{ $selectedDriver == $driver->id ? 'text-indigo-600 font-medium bg-indigo-50' : 'text-gray-700' }}">
                                                {{ $driver->name }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="py-1 border-t border-gray-100">
                                    <div class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 sticky top-[41px]">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center justify-center w-5 h-5 mr-2 text-gray-700 bg-gray-100 rounded-full">
                                                <i class="fas fa-user text-xxs"></i>
                                            </span>
                                            Pengemudi Cadangan
                                        </div>
                                    </div>
                                    @foreach($drivers->where('type', 'cadangan') as $driver)
                                        <div class="text-sm cursor-pointer hover:bg-gray-50" 
                                             x-on:click="selectedOption = '{{ $driver->id }}'; open = false; search = '{{ $driver->name }}'"
                                             data-driver-item
                                             data-driver-name="{{ $driver->name }}"
                                        >
                                            <span class="block px-4 py-2 {{ $selectedDriver == $driver->id ? 'text-indigo-600 font-medium bg-indigo-50' : 'text-gray-700' }}">
                                                {{ $driver->name }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div>
                    <x-input-label for="unit" value="Unit" class="font-medium text-gray-700" />
                    <div class="relative" 
                         x-data="{ 
                            open: false, 
                            search: '{{ $selectedUnit ? ($units->firstWhere('id', $selectedUnit)->unit_number . ($units->firstWhere('id', $selectedUnit)->plate_number ? ' ('.$units->firstWhere('id', $selectedUnit)->plate_number.')' : '')) : '-- Semua Unit --' }}', 
                            selectedOption: '{{ $selectedUnit }}',
                            defaultSearch: '{{ $selectedUnit ? ($units->firstWhere('id', $selectedUnit)->unit_number . ($units->firstWhere('id', $selectedUnit)->plate_number ? ' ('.$units->firstWhere('id', $selectedUnit)->plate_number.')' : '')) : '-- Semua Unit --' }}',
                            isFirstInput: true,
                            filterUnits(term) {
                                // Clear default text on first input
                                if (this.isFirstInput && term === this.defaultSearch) {
                                    this.search = '';
                                    this.isFirstInput = false;
                                    return;
                                }
                                
                                // Skip filtering if empty
                                if (!term) return;
                                
                                document.querySelectorAll('[data-unit-item]').forEach(el => {
                                    const unitInfo = el.getAttribute('data-unit-info').toLowerCase();
                                    el.style.display = unitInfo.includes(term.toLowerCase()) ? '' : 'none';
                                });
                            }
                         }">
                        <div class="relative">
                            <input
                                type="text"
                                placeholder="Ketik untuk mencari unit..."
                                class="block w-full pl-10 pr-10 mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                x-on:focus="open = true"
                                x-on:click="open = true"
                                x-model="search"
                                x-on:input="filterUnits(search)"
                                autocomplete="off"
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="text-indigo-500 fas fa-search"></i>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <span class="flex items-center justify-center w-6 h-6 text-gray-100 bg-blue-500 rounded-full">
                                    <i class="text-xs fas fa-bus"></i>
                                </span>
                            </div>
                            <input type="hidden" name="unit" :value="selectedOption" />
                        </div>
                        
                        <div 
                            x-show="open" 
                            x-on:click.away="open = false"
                            class="absolute z-40 w-full mt-1 overflow-auto bg-white rounded-md shadow-lg max-h-60"
                            style="display: none;"
                        >
                            <div class="sticky top-0 p-2 border-b border-gray-200 bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="text-xs font-medium text-gray-600">Pilih unit</div>
                                    <button type="button" @click="open = false" class="text-gray-500 hover:text-gray-700">
                                        <i class="text-xs fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <div class="py-1 text-sm cursor-pointer hover:bg-gray-50" 
                                     x-on:click="selectedOption = ''; open = false; search = '-- Semua Unit --'">
                                    <span class="flex items-center block px-4 py-2">
                                        <i class="mr-2 text-gray-500 fas fa-globe-asia"></i>
                                        -- Semua Unit --
                                    </span>
                                </div>
                                
                                <div class="py-8 text-center" 
                                     x-show="search !== defaultSearch && !Array.from(document.querySelectorAll('[data-unit-item]')).some(el => el.style.display !== 'none')"
                                     style="display: none;">
                                    <i class="mb-2 text-2xl text-gray-300 fas fa-search"></i>
                                    <p class="text-sm text-gray-500">Tidak ditemukan unit yang cocok</p>
                                </div>
                                
                                <div class="py-1 border-t border-gray-100">
                                    <div class="px-3 py-1 text-xs font-medium text-gray-600 bg-gray-50 sticky top-[41px]">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center justify-center w-5 h-5 mr-2 text-blue-700 bg-blue-100 rounded-full">
                                                <i class="fas fa-bus text-xxs"></i>
                                            </span>
                                            Unit Kendaraan
                                        </div>
                                    </div>
                                    @foreach($units as $unit)
                                        @php 
                                            $unitDisplay = $unit->unit_number . ($unit->plate_number ? ' ('.$unit->plate_number.')' : '');
                                            $unitSearchValue = strtolower($unit->unit_number . ' ' . $unit->plate_number);
                                        @endphp
                                        <div class="text-sm cursor-pointer hover:bg-gray-50" 
                                             x-on:click="selectedOption = '{{ $unit->id }}'; open = false; search = '{{ $unitDisplay }}'"
                                             data-unit-item
                                             data-unit-info="{{ $unitSearchValue }}"
                                        >
                                            <span class="block px-4 py-2 {{ $selectedUnit == $unit->id ? 'text-indigo-600 font-medium bg-indigo-50' : 'text-gray-700' }}">
                                                <span class="font-medium">{{ $unit->unit_number }}</span>
                                                @if($unit->plate_number)
                                                    <span class="text-gray-500">{{ ' ('.$unit->plate_number.')' }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex flex-wrap items-center pt-2 space-x-4 md:col-span-2 lg:col-span-5">
                    <button type="submit" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition duration-150 ease-in-out border border-transparent rounded-md shadow-sm bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 focus:outline-none focus:border-indigo-700 focus:ring focus:ring-indigo-200 active:from-indigo-700 active:to-indigo-800">
                        <i class="mr-2 fas fa-search"></i>Terapkan Filter
                    </button>
                    <a href="{{ route('schedules.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase transition duration-150 ease-in-out bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 hover:text-gray-900 focus:outline-none focus:ring focus:ring-gray-200 focus:border-gray-400 active:bg-gray-100">
                        <i class="mr-2 fas fa-times"></i>Reset
                    </a>
                    <div class="ml-2 text-xs text-gray-500">
                        <i class="mr-1 fas fa-info-circle"></i>
                        Tips: Filter dapat dikombinasikan untuk menyaring jadwal
                    </div>
                </div>
            </form>
        </div>

        <!-- Period Tabs -->
        <div class="mb-6">
            <h3 class="mb-3 text-lg font-medium text-gray-700">
                <i class="mr-2 text-indigo-500 fas fa-calendar-week"></i>Pilih Periode
            </h3>
            <div class="flex max-w-md p-1 space-x-1 bg-gray-100 border border-gray-200 rounded-lg shadow-sm">
                <a href="{{ route('schedules.index', array_merge(request()->query(), ['period' => 1])) }}" 
                    class="w-1/2 py-2 px-4 text-center rounded-md transition-all duration-200 {{ $period == 1 ? 'bg-white shadow-sm font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i class="mr-2 fas fa-calendar-day"></i>
                    Periode 1
                    <div class="text-xs {{ $period == 1 ? 'text-indigo-500' : 'text-gray-500' }}">1-15 {{ $monthName }}</div>
                </a>
                <a href="{{ route('schedules.index', array_merge(request()->query(), ['period' => 2])) }}" 
                    class="w-1/2 py-2 px-4 text-center rounded-md transition-all duration-200 {{ $period == 2 ? 'bg-white shadow-sm font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50' }}">
                    <i class="mr-2 fas fa-calendar-day"></i>
                    Periode 2
                    <div class="text-xs {{ $period == 2 ? 'text-indigo-500' : 'text-gray-500' }}">16-{{ Carbon\Carbon::parse($endDate)->format('d') }} {{ $monthName }}</div>
                </a>
            </div>
        </div>

        <!-- Schedule Matrix Table -->
        <div class="max-w-full overflow-x-auto">
            <div class="w-full align-middle bg-white border border-gray-200 rounded-lg shadow-sm">
                <table class="w-full divide-y divide-gray-200 table-auto">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-route"></i> Rute
                                </div>
                            </th>
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-bus"></i> Unit
                                </div>
                            </th>
                            <th scope="col" class="w-32 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-user"></i> Pengemudi
                                </div>
                            </th>
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                                <div class="flex items-center">
                                    <i class="mr-1 text-indigo-400 fas fa-clock"></i> Shift
                                </div>
                            </th>
                            @foreach($dateRange as $date)
                                @php
                                    $date = $date;
                                    $dayName = Carbon\Carbon::parse($date)->format('l');
                                    $day = substr($dayName, 0, 3);
                                    $isHoliday = isset($holidays[$date]);
                                    $holidayName = $isHoliday ? $holidays[$date] : '';
                                    
                                    // Set classes based on day of week
                                    if ($dayName === 'Sunday') {
                                        $bgClass = 'bg-red-50';
                                        $textClass = 'text-red-700';
                                        $subTextClass = 'text-red-600';
                                    } elseif ($dayName === 'Saturday') {
                                        $bgClass = 'bg-orange-50';
                                        $textClass = 'text-orange-700';
                                        $subTextClass = 'text-orange-600';
                                    } else {
                                        $bgClass = 'bg-gray-50';
                                        $textClass = 'text-gray-700';
                                        $subTextClass = 'text-gray-500';
                                    }
                                    
                                    // Format the date for comparison with the holidays array
                                    $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                    
                                    // Add highlight classes for specific days
                                    $highlightClass = '';
                                    if ($dayName === 'Saturday' || $dayName === 'Sunday') {
                                        $highlightClass = 'highlight-saturday';
                                    }
                                    
                                    // Add holiday highlight class - takes precedence
                                    if ($isHoliday) {
                                        $highlightClass = 'highlight-holiday';
                                    }
                                @endphp
                                <th scope="col" class="w-8 px-1 py-3 text-xs font-medium tracking-wider text-center {{ $textClass }} uppercase {{ $bgClass }} {{ $highlightClass }} relative" 
                                    @if($isHoliday) title="{{ $holidayName }}" data-tooltip="{{ $holidayName }}" @endif>
                                    {{ Carbon\Carbon::parse($date)->format('d') }}
                                    <div class="{{ $subTextClass }} text-xxs">
                                        {{ $day }}
                                        @if($isHoliday)
                                            <div class="mt-1 text-red-600 text-xxs">
                                                <i class="fas fa-star-of-life"></i>
                                            </div>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                            <th scope="col" class="w-16 px-3 py-3 text-xs font-medium tracking-wider text-center text-gray-700 uppercase bg-gray-50">
                                <div class="flex items-center justify-center">
                                    <i class="mr-1 text-indigo-400 fas fa-calculator"></i> Total
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @if(count($routeUnitDrivers) === 0)
                            <tr>
                                <td colspan="{{ 4 + count($dateRange) + 1 }}" class="px-3 py-8 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <i class="mb-3 text-5xl text-gray-300 fas fa-calendar-times"></i>
                                        <p class="text-lg text-gray-500">Tidak ada jadwal untuk periode ini.</p>
                                        <p class="mt-1 text-sm text-gray-400">Silakan coba filter lain atau buat jadwal baru.</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @foreach($routeUnitDrivers as $routeGroup)
                                <tr class="bg-indigo-50 route-header" data-route-id="{{ $routeGroup['route']->id }}" onclick="toggleRouteContent({{ $routeGroup['route']->id }})">
                                    <td colspan="{{ 4 + count($dateRange) + 1 }}" class="px-3 py-3 font-medium text-indigo-900 border-b border-indigo-100">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex items-center justify-center w-6 h-6 mr-2 text-white bg-indigo-600 rounded-full">
                                                    <i class="text-xs fas fa-route"></i>
                                                </div>
                                                <span class="font-semibold">Rute {{ $routeGroup['route']->route_number }}</span>
                                                <span class="mx-2">-</span>
                                                <span>{{ $routeGroup['route']->name }}</span>
                                            </div>
                                            <div class="toggle-icon">
                                                <i class="fas fa-chevron-down text-indigo-500"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tbody class="route-content" data-route-content="{{ $routeGroup['route']->id }}">


                                @foreach($routeGroup['units'] as $unitGroup)
                                    <tr class="bg-blue-50 unit-header" data-unit-id="{{ $unitGroup['unit']->id }}" onclick="toggleUnitContent({{ $unitGroup['unit']->id }})">
                                        <td class="px-3 py-2 text-right"></td>
                                        <td colspan="{{ 3 + count($dateRange) + 1 }}" class="px-3 py-2 font-medium text-blue-800 border-b border-blue-100">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center">
                                                    <div class="flex items-center justify-center w-5 h-5 mr-2 text-white bg-blue-500 rounded-full">
                                                        <i class="text-xs fas fa-bus"></i>
                                                    </div>
                                                    Unit {{ $unitGroup['unit']->unit_number }} 
                                                    @if($unitGroup['unit']->plate_number)
                                                        <span class="text-xs text-blue-600 ml-1 bg-blue-100 px-2 py-0.5 rounded-full">({{ $unitGroup['unit']->plate_number }})</span>
                                                    @endif
                                                </div>
                                                <div class="toggle-icon">
                                                    <i class="fas fa-chevron-down text-blue-500"></i>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tbody class="unit-content" data-unit-content="{{ $unitGroup['unit']->id }}">


                                    @foreach($unitGroup['drivers'] as $driverInfo)
                                        @foreach(['pagi', 'siang'] as $shift)
                                            @if(!empty($driverInfo['shifts'][$shift]['dates']) || !empty($driverInfo['shifts'][$shift]['backup_dates']))
                                                <tr class="transition-colors hover:bg-gray-50">
                                                    <td class="px-3 py-2 text-xs text-right text-gray-500"></td>
                                                    <td class="px-3 py-2 text-xs text-right text-gray-500"></td>
                                                    <td class="px-3 py-3 text-sm">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0">
                                                                <span class="inline-flex items-center justify-center h-7 w-7 rounded-full {{ $driverInfo['driver']->type == 'batangan' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                                                    <i class="text-xs fas fa-user"></i>
                                                                </span>
                                                            </div>
                                                            <div class="ml-3">
                                                                <a href="{{ route('drivers.show', $driverInfo['driver']->id) }}" class="font-medium text-gray-800 hover:text-indigo-600 hover:underline">{{ $driverInfo['driver']->name }}</a>
                                                                <p class="text-xs {{ $driverInfo['driver']->type == 'batangan' ? 'text-emerald-600' : 'text-gray-500' }}">
                                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $driverInfo['driver']->type == 'batangan' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800' }} text-xxs font-medium">
                                                                        {{ ucfirst($driverInfo['driver']->type) }}
                                                                    </span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-3 py-3 text-sm">
                                                        @if($shift == 'pagi')
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                <i class="mr-1 text-blue-600 fas fa-sun"></i>
                                                                Pagi
                                                            </span>
                                                        @else
                                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                                <i class="mr-1 fas fa-moon text-amber-600"></i>
                                                                Siang
                                                            </span>
                                                        @endif
                                                    </td>

                                                    @foreach($dateRange as $date)
                                                        @php
                                                            $isAssigned = in_array($date, $driverInfo['shifts'][$shift]['dates']);
                                                            $isBackup = in_array($date, $driverInfo['shifts'][$shift]['backup_dates']);
                                                            
                                                            // Determine day type for highlighting
                                                            $dayName = \Carbon\Carbon::parse($date)->format('l');
                                                            
                                                            // Format the date for comparison with the holidays array
                                                            $formattedDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                                            
                                                            // Check if this date is a holiday using the holidays array from the controller
                                                            $isHoliday = isset($holidays[$formattedDate]);
                                                            
                                                            // Set highlight class based on day type
                                                            $cellHighlightClass = '';
                                                            if ($dayName === 'Saturday' || $dayName === 'Sunday') {
                                                                $cellHighlightClass = 'highlight-saturday';
                                                            }
                                                            
                                                            // Holiday takes precedence over weekend
                                                            if ($isHoliday) {
                                                                $cellHighlightClass = 'highlight-holiday';
                                                            }
                                                        @endphp
                                                        <td class="px-1 py-2 text-center {{ $cellHighlightClass }}">
                                                            @php
                                                                // Check if this unit is in renops for this date
                                                                $isUnitInRenops = isset($unitRenops[$formattedDate]) && isset($unitRenops[$formattedDate][$unitGroup['unit']->id]);
                                                            @endphp
                                                            
                                                            @if($isUnitInRenops)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full renops-indicator" title="Unit Tidak Beroperasi (Renops)">
                                                                    <i class="text-sm fas fa-exclamation"></i>
                                                                </span>
                                                            @elseif($isAssigned)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full hover:bg-green-200 {{ $driverInfo['driver']->type == 'batangan' ? 'bg-green-100 text-green-800' : 'cadangan-checkmark' }}" title="Pengemudi Dijadwalkan">
                                                                    <i class="text-sm fas fa-check"></i>
                                                                </span>
                                                            @elseif($isBackup)
                                                                <span class="inline-flex items-center justify-center w-6 h-6 transition-all rounded-full bg-amber-100 text-amber-800 hover:bg-amber-200" title="Pengemudi Cadangan">
                                                                    <i class="text-sm fas fa-question"></i>
                                                                </span>
                                                            @else
                                                                <span class="inline-block w-6 h-6 transition-all border border-gray-200 rounded-full hover:bg-gray-100"></span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                    
                                                    <td class="px-3 py-3 text-sm font-medium text-center bg-gray-50">
                                                        @php
                                                            $totalAssigned = count($driverInfo['shifts'][$shift]['dates']);
                                                            $totalBackup = count($driverInfo['shifts'][$shift]['backup_dates']);
                                                            $total = $totalAssigned + $totalBackup;
                                                        @endphp
                                                        <div class="flex flex-col items-center justify-center">
                                                            <span class="text-lg font-semibold {{ $total > 0 ? 'text-indigo-600' : 'text-gray-400' }}">{{ $total }}</span>
                                                            @if($totalAssigned > 0 && $totalBackup > 0)
                                                                <span class="text-gray-500 text-xxs">({{ $totalAssigned }}+{{ $totalBackup }})</span>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                </tbody> <!-- Close unit-content -->
                                @endforeach
                                </tbody> <!-- Close route-content -->
                                
                                <!-- Spacer row between route groups -->
                                <tr class="h-4">
                                    <td colspan="{{ 4 + count($dateRange) + 1 }}" class="border-b"></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Legend -->
        <div class="p-4 mt-4 rounded-md bg-gray-50">
            <h3 class="text-lg font-medium text-gray-700">
                <i class="mr-2 text-gray-500 fas fa-info-circle"></i>Keterangan:
            </h3>
            <div class="flex flex-wrap gap-4 mt-3">
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 mr-2 text-green-800 bg-green-100 rounded-full">
                        <i class="text-sm fas fa-check"></i>
                    </span>
                    <span class="text-sm text-gray-700">Pengemudi Batangan</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full cadangan-checkmark">
                        <i class="text-sm fas fa-check"></i>
                    </span>
                    <span class="text-sm text-gray-700">Pengemudi Cadangan</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full bg-amber-100 text-amber-800">
                        <i class="text-sm fas fa-question"></i>
                    </span>
                    <span class="text-sm text-gray-700">Pengemudi Backup</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded-full renops-indicator">
                        <i class="text-sm fas fa-exclamation"></i>
                    </span>
                    <span class="text-sm text-gray-700">Unit Tidak Beroperasi (Renops)</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center px-2 py-1 mr-2 text-xs font-medium text-blue-600 bg-blue-100 rounded">
                        <i class="mr-1 fas fa-sun"></i>Pagi
                    </span>
                    <span class="text-sm text-gray-700">Shift Pagi</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center px-2 py-1 mr-2 text-xs font-medium rounded text-amber-600 bg-amber-100">
                        <i class="mr-1 fas fa-moon"></i>Siang
                    </span>
                    <span class="text-sm text-gray-700">Shift Siang</span>
                </div>
                
                <div class="w-full pt-2 mt-2 border-t border-gray-200"></div>
                
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 mr-2 text-red-700 rounded bg-red-50">
                        <i class="text-xs fas fa-star-of-life"></i>
                    </span>
                    <span class="text-sm text-gray-700">Hari Libur Nasional</span>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center justify-center w-6 h-6 mr-2 rounded bg-amber-50 text-amber-700">
                        <span class="text-xs font-medium">Sab</span>
                    </span>
                    <span class="text-sm text-gray-700">Hari Libur Akhir Pekan</span>
                </div>
                
                <div class="w-full pt-2 mt-2 border-t border-gray-200"></div>
                
                <div id="edit-mode-legend" class="hidden w-full">
                    <h4 class="mt-2 mb-2 text-sm font-medium text-gray-700">Mode Edit:</h4>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" checked class="w-5 h-5 mr-2 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">Centang untuk menjadwalkan pengemudi</span>
                        </div>
                        <div class="flex items-center">
                            <button id="toggle-all-btn" class="px-3 py-1 text-xs font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Toggle Semua
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
                
    </div>
@endsection

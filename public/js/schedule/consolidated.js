window.filterDriversByType = function (driverType) {
    const driverItems = document.querySelectorAll("[data-driver-item]");

    driverItems.forEach((item) => {
        const itemType = item.getAttribute("data-driver-type");

        if (!driverType || driverType === "") {
            item.style.display = "";
        } else if (itemType === driverType) {
            item.style.display = "";
        } else {
            item.style.display = "none";
        }
    });
};

document.addEventListener("DOMContentLoaded", function () {
    const holidayCells = document.querySelectorAll("[data-tooltip]");
    holidayCells.forEach((cell) => {
        cell.addEventListener("mouseenter", function () {
            document
                .querySelectorAll(".tooltip-holiday")
                .forEach((el) => el.remove());

            const tooltip = document.createElement("div");
            tooltip.className = "tooltip-holiday";
            tooltip.innerHTML = this.getAttribute("data-tooltip");

            const rect = this.getBoundingClientRect();
            tooltip.style.top = window.scrollY + rect.top - 40 + "px";
            tooltip.style.left =
                window.scrollX + rect.left + rect.width / 2 + "px";

            document.body.appendChild(tooltip);
        });

        cell.addEventListener("mouseleave", function () {
            document
                .querySelectorAll(".tooltip-holiday")
                .forEach((el) => el.remove());
        });
    });

    const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener("click", function () {
            const menu = this.nextElementSibling;
            menu.classList.toggle("hidden");
        });
    });
    
    // Drawer Functionality (Common functions for both drawers)
    function openDrawer(drawer, overlay) {
        drawer.classList.remove('translate-x-full');
        overlay.classList.remove('hidden');
        setTimeout(() => {
            overlay.classList.add('opacity-100');
            overlay.classList.remove('opacity-0');
        }, 50);
        document.body.classList.add('overflow-hidden');
    }
    
    function closeDrawer(drawer, overlay) {
        drawer.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        overlay.classList.remove('opacity-100');
        setTimeout(() => {
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }, 300);
    }
    
    // Legends Drawer Functionality
    const showLegendsBtn = document.getElementById('show-legends-btn');
    const closeLegendsBtn = document.getElementById('close-legends-btn');
    const legendsDrawer = document.getElementById('legends-drawer');
    const legendsOverlay = document.getElementById('legends-overlay');
    
    // Event listeners for opening and closing the legends drawer
    if (showLegendsBtn) {
        showLegendsBtn.addEventListener('click', () => openDrawer(legendsDrawer, legendsOverlay));
    }
    
    if (closeLegendsBtn) {
        closeLegendsBtn.addEventListener('click', () => closeDrawer(legendsDrawer, legendsOverlay));
    }
    
    if (legendsOverlay) {
        legendsOverlay.addEventListener('click', () => closeDrawer(legendsDrawer, legendsOverlay));
    }
    
    // Unassigned Drivers Drawer Functionality
    const showUnassignedBtn = document.getElementById('show-unassigned-btn');
    const closeUnassignedBtn = document.getElementById('close-unassigned-btn');
    const unassignedDrawer = document.getElementById('unassigned-drivers-drawer');
    const unassignedOverlay = document.getElementById('unassigned-overlay');
    
    // Event listeners for opening and closing the unassigned drivers drawer
    if (showUnassignedBtn) {
        showUnassignedBtn.addEventListener('click', () => openDrawer(unassignedDrawer, unassignedOverlay));
    }
    
    if (closeUnassignedBtn) {
        closeUnassignedBtn.addEventListener('click', () => closeDrawer(unassignedDrawer, unassignedOverlay));
    }
    
    if (unassignedOverlay) {
        unassignedOverlay.addEventListener('click', () => closeDrawer(unassignedDrawer, unassignedOverlay));
    }
    
    // Stats Drawer Functionality
    const showStatsBtn = document.getElementById('show-stats-btn');
    const closeStatsBtn = document.getElementById('close-stats-btn');
    const statsDrawer = document.getElementById('stats-drawer');
    const statsOverlay = document.getElementById('stats-overlay');
    
    // Event listeners for opening and closing the stats drawer
    if (showStatsBtn) {
        showStatsBtn.addEventListener('click', () => openDrawer(statsDrawer, statsOverlay));
    }
    
    if (closeStatsBtn) {
        closeStatsBtn.addEventListener('click', () => closeDrawer(statsDrawer, statsOverlay));
    }
    
    if (statsOverlay) {
        statsOverlay.addEventListener('click', () => closeDrawer(statsDrawer, statsOverlay));
    }
    
    // Filter functionality for unassigned drivers
    const unassignedFilterType = document.getElementById('unassigned-filter-type');
    if (unassignedFilterType) {
        unassignedFilterType.addEventListener('change', function() {
            const selectedType = this.value;
            const batanganSection = document.getElementById('unassigned-batangan-list').closest('div.mb-5');
            const cadanganSection = document.getElementById('unassigned-cadangan-list').closest('div.mb-5');
            
            if (selectedType === '') {
                // Show all
                batanganSection.style.display = 'block';
                cadanganSection.style.display = 'block';
            } else if (selectedType === 'batangan') {
                // Show only batangan
                batanganSection.style.display = 'block';
                cadanganSection.style.display = 'none';
            } else if (selectedType === 'cadangan') {
                // Show only cadangan
                batanganSection.style.display = 'none';
                cadanganSection.style.display = 'block';
            }
        });
    }
    
    // Close drawer with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !legendsDrawer.classList.contains('translate-x-full')) {
            closeLegendsDrawer();
        }
    });

    document.addEventListener("click", function (event) {
        dropdownToggles.forEach((toggle) => {
            const dropdown = toggle.parentElement;
            const menu = toggle.nextElementSibling;

            if (
                !dropdown.contains(event.target) &&
                !menu.classList.contains("hidden")
            ) {
                menu.classList.add("hidden");
            }
        });
    });

    window.toggleRouteContent = function (routeId, event) {
        const header = document.querySelector(
            `.route-header[data-route-id="${routeId}"]`
        );
        const content = document.querySelector(
            `.route-content[data-route-content="${routeId}"]`
        );

        if (event) event.stopPropagation();

        if (content.style.display === "none") {
            content.style.display = "table-row-group";
            header.classList.remove("collapsed");
        } else {
            content.style.display = "none";
            header.classList.add("collapsed");
        }
    };

    window.toggleUnitContent = function (unitId, event) {
        const header = document.querySelector(
            `.unit-header[data-unit-id="${unitId}"]`
        );
        const content = document.querySelector(
            `.unit-content[data-unit-content="${unitId}"]`
        );

        if (event) event.stopPropagation();

        if (content.style.display === "none") {
            content.style.display = "table-row-group";
            header.classList.remove("collapsed");
        } else {
            content.style.display = "none";
            header.classList.add("collapsed");
        }
    };

    document
        .querySelectorAll(".route-header a, .unit-header a")
        .forEach((link) => {
            link.addEventListener("click", function (e) {
                e.stopPropagation();
            });
        });

    let isEditMode = false;
    const editButton = document.getElementById("edit-schedule-btn");
    const editBtnText = document.getElementById("edit-btn-text");
    const scheduleTable = document.querySelector("table");
    const saveChangesRow = document.createElement("tr");

    if (editButton) {
        editButton.addEventListener("click", function () {
            isEditMode = !isEditMode;

            if (isEditMode) {
                editBtnText.textContent = "Batal Edit";
                editButton.classList.remove(
                    "bg-gradient-to-r",
                    "from-orange-600",
                    "to-orange-700",
                    "hover:from-orange-500",
                    "hover:to-orange-600"
                );
                editButton.classList.add(
                    "bg-gradient-to-r",
                    "from-red-600",
                    "to-red-700",
                    "hover:from-red-500",
                    "hover:to-red-600"
                );

                if (scheduleTable) {
                    const tfoot = document.createElement("tfoot");
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

                    document
                        .getElementById("save-changes-btn")
                        .addEventListener("click", function () {
                            // Collect all checked checkboxes
                            const checkedBoxes = document.querySelectorAll('input[type="checkbox"]:checked');
                            const changes = [];
                            
                            checkedBoxes.forEach(checkbox => {
                                const unitId = checkbox.getAttribute('data-unit-id');
                                const date = checkbox.getAttribute('data-date');
                                const shift = checkbox.getAttribute('data-shift');
                                const type = checkbox.getAttribute('data-type');
                                
                                // Determine the driver type based on the checkbox type
                                let driverType = 'batangan';
                                if (type === 'cadangan' || type === 'empty-cadangan') {
                                    driverType = 'cadangan';
                                }
                                
                                changes.push({
                                    unitId,
                                    date,
                                    shift,
                                    driverType
                                });
                            });
                            
                            // Here you would typically send these changes to the server
                            console.log('Schedule changes:', changes);
                            
                            alert("Perubahan berhasil disimpan!");
                            toggleEditMode();
                        });
                }

                document
                    .getElementById("edit-mode-legend")
                    .classList.remove("hidden");
                convertToCheckboxes();
                initToggleAllButton();
            } else {
                toggleEditMode();
            }
        });
    }

    function toggleEditMode() {
        isEditMode = false;
        editBtnText.textContent = "Edit Jadwal";
        editButton.classList.remove(
            "bg-gradient-to-r",
            "from-red-600",
            "to-red-700",
            "hover:from-red-500",
            "hover:to-red-600"
        );
        editButton.classList.add(
            "bg-gradient-to-r",
            "from-orange-600",
            "to-orange-700",
            "hover:from-orange-500",
            "hover:to-orange-600"
        );

        if (saveChangesRow.parentNode) {
            saveChangesRow.parentNode.removeChild(saveChangesRow);
        }
        document.getElementById("edit-mode-legend").classList.add("hidden");
        convertToNormalView();
    }

    function convertToCheckboxes() {
        // Get all date cells (cells that can potentially have a schedule)
        // This will include all cells in the date columns, whether they have content or not
        const dateCells = document.querySelectorAll('tbody td:nth-child(n+5):not(:last-child)');
        
        const scheduleMap = new Map();

        dateCells.forEach((parentTd) => {
            // Skip cells that don't have a parent row
            if (!parentTd || !parentTd.closest('tr')) {
                return;
            }
            
            // Skip cells in header rows
            if (parentTd.closest('tr.route-header') || parentTd.closest('tr.unit-header')) {
                return;
            }
            
            // Skip the total column
            if (parentTd.cellIndex === parentTd.parentElement.cells.length - 1) {
                return;
            }
            
            // Save original HTML for reverting later
            parentTd.setAttribute("data-original-html", parentTd.innerHTML);

            const row = parentTd.closest("tr");
            const unitRow = findUnitRow(row);
            const unitId = unitRow
                ? unitRow.getAttribute("data-unit-id") ||
                  getUnitIdFromText(unitRow.textContent)
                : "unknown";
            const dateIndex = Array.from(row.cells).indexOf(parentTd);
            const dateCell = row
                .closest("table")
                .querySelector(
                    "thead tr:last-child th:nth-child(" + (dateIndex + 1) + ")"
                );
            const dateValue = dateCell
                ? dateCell.getAttribute("data-date") ||
                  dateCell.textContent.trim()
                : "unknown";
            const shift = getShiftFromRow(row);
            
            // Check if this unit is in renops for this date
            const isUnitInRenops = checkIfUnitInRenops(unitId, dateValue);
            
            // Check if the driver is on leave for this date
            const isDriverOnLeave = checkIfDriverOnLeave(row, dateValue);
            
            // Check if the unit is in maintenance for this date
            const isUnitInMaintenance = checkIfUnitInMaintenance(unitId, dateValue);
            
            // Check current cell status
            const hasAssignedIndicator = parentTd.querySelector('.text-green-800, .cadangan-checkmark');
            const hasBackupIndicator = parentTd.querySelector('.text-amber-800');
            const hasRenopsIndicator = parentTd.querySelector('.renops-indicator');
            const hasMaintenanceIndicator = parentTd.querySelector('.text-teal-800');
            const hasOnLeaveIndicator = parentTd.querySelector('.text-red-800');
            
            // If the unit is in renops, maintenance, or driver is on leave, we should disable the checkbox
            if (isUnitInRenops || isUnitInMaintenance || isDriverOnLeave || 
                hasRenopsIndicator || hasMaintenanceIndicator || hasOnLeaveIndicator) {
                // For renops/maintenance/leave units, we'll display a disabled checkbox without any icon
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.checked = false;
                checkbox.disabled = true;
                checkbox.className =
                    "w-5 h-5 text-gray-400 border-gray-300 rounded focus:ring-gray-300 cursor-not-allowed opacity-50";
                checkbox.setAttribute("data-type", "disabled");
                checkbox.setAttribute("data-unit-id", unitId);
                checkbox.setAttribute("data-date", dateValue);
                checkbox.setAttribute("data-shift", shift);
                
                parentTd.innerHTML = "";
                parentTd.appendChild(checkbox);
                return;
            }

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = hasAssignedIndicator || hasBackupIndicator;
            
            // Determine driver type
            const driverTypeCell = row.querySelector('td:nth-child(3)');
            const isCadangan = driverTypeCell && driverTypeCell.textContent.toLowerCase().includes('cadangan') || 
                              parentTd.querySelector('.cadangan-checkmark');
            
            if (hasAssignedIndicator) {
                if (isCadangan) {
                    // Purple for cadangan drivers
                    checkbox.className = "w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500";
                    checkbox.setAttribute("data-type", "cadangan");
                } else {
                    // Green for batangan drivers
                    checkbox.className = "w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500";
                    checkbox.setAttribute("data-type", "assigned");
                }
            } else if (hasBackupIndicator) {
                // Amber/orange for backup drivers
                checkbox.className = "w-5 h-5 text-amber-600 border-gray-300 rounded focus:ring-amber-500";
                checkbox.setAttribute("data-type", "backup");
            } else {
                // Empty cell - style based on driver type
                if (isCadangan) {
                    // Purple for cadangan drivers
                    checkbox.className = "w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500";
                    checkbox.setAttribute("data-type", "empty-cadangan");
                } else {
                    // Green for batangan drivers
                    checkbox.className = "w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500";
                    checkbox.setAttribute("data-type", "empty-assigned");
                }
            }
            
            checkbox.setAttribute("data-unit-id", unitId);
            checkbox.setAttribute("data-date", dateValue);
            checkbox.setAttribute("data-shift", shift);

            const mapKey = `${unitId}-${dateValue}-${shift}`;
            if (!scheduleMap.has(mapKey)) {
                scheduleMap.set(mapKey, []);
            }
            scheduleMap.get(mapKey).push(checkbox);

            checkbox.addEventListener("change", function () {
                validateScheduleConflicts(this, scheduleMap);
            });
            parentTd.innerHTML = "";
            parentTd.appendChild(checkbox);
        });
    }

    function convertToNormalView() {
        const checkboxCells = document.querySelectorAll(
            'td input[type="checkbox"]'
        );

        checkboxCells.forEach((checkbox) => {
            const parentTd = checkbox.closest("td");
            const originalHtml = parentTd.getAttribute("data-original-html");

            if (originalHtml) {
                parentTd.innerHTML = originalHtml;
            }
        });
    }

    function initToggleAllButton() {
        const toggleAllBtn = document.getElementById("toggle-all-btn");
        if (toggleAllBtn) {
            toggleAllBtn.addEventListener("click", function () {
                const checkboxes = document.querySelectorAll(
                    'td input[type="checkbox"]'
                );
                const allChecked = Array.from(checkboxes).every(
                    (cb) => cb.checked
                );

                checkboxes.forEach((checkbox) => {
                    checkbox.checked = !allChecked;
                    const event = new Event("change");
                    checkbox.dispatchEvent(event);
                });
            });
        }
    }

    function findUnitRow(row) {
        if (!row) return null;
        
        let current = row;
        while (current) {
            // Check for both class name and bg-blue-50 class which is used for unit headers
            if (current.classList && 
                (current.classList.contains("unit-header") || current.classList.contains("bg-blue-50"))) {
                return current;
            }
            current = current.previousElementSibling;
        }
        return null;
    }
    
    function getUnitIdFromText(text) {
        if (!text) return "unknown";
        
        const match = text.match(/Unit\s+#?(\d+)/i);
        return match ? match[1] : "unknown";
    }
    
    function getShiftFromRow(row) {
        if (!row) return "unknown";
        
        // Try to get the shift from the text content of the shift column
        const shiftCell = row.querySelector('td:nth-child(4)');
        if (shiftCell) {
            const shiftText = shiftCell.textContent.trim().toLowerCase();
            if (shiftText.includes('pagi')) return 'pagi';
            if (shiftText.includes('siang')) return 'siang';
        }
        
        // If that fails, try to infer from the row's position or other attributes
        return row.classList && row.classList.contains('morning-shift') ? 'pagi' : 'siang';
    }

    // Helper function to check if a unit is in renops for a specific date
    function checkIfUnitInRenops(unitId, dateValue) {
        if (!unitId || !dateValue) return false;
        
        // Find all renops indicators in the table
        const renopsIndicators = document.querySelectorAll('.renops-indicator');
        if (!renopsIndicators || renopsIndicators.length === 0) return false;
        
        // Check if any of them are for this unit and date
        for (const indicator of renopsIndicators) {
            if (!indicator) continue;
            
            const cell = indicator.closest('td');
            if (!cell) continue;
            
            const row = cell.closest('tr');
            if (!row) continue;
            
            const unitRow = findUnitRow(row);
            if (!unitRow) continue;
            
            const indicatorUnitId = unitRow.getAttribute('data-unit-id') || getUnitIdFromText(unitRow.textContent);
            if (!indicatorUnitId) continue;
            
            // If this indicator is for the same unit
            if (indicatorUnitId === unitId) {
                // Check if it's for the same date
                if (!row.cells) continue;
                
                const dateIndex = Array.from(row.cells).indexOf(cell);
                const table = row.closest('table');
                if (!table) continue;
                
                const dateCell = table.querySelector(`thead tr:last-child th:nth-child(${dateIndex + 1})`);
                if (!dateCell) continue;
                
                const indicatorDate = dateCell.getAttribute('data-date') || dateCell.textContent.trim();
                
                if (indicatorDate === dateValue) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    // Helper function to check if a driver is on leave for a specific date
    function checkIfDriverOnLeave(row, dateValue) {
        if (!row || !dateValue) return false;
        
        // Find all on-leave indicators in this row
        const onLeaveIndicators = row.querySelectorAll('.text-red-800');
        if (!onLeaveIndicators || onLeaveIndicators.length === 0) return false;
        
        // Check if any of them are for this date
        for (const indicator of onLeaveIndicators) {
            if (!indicator) continue;
            
            const cell = indicator.closest('td');
            if (!cell) continue;
            
            if (!row.cells) continue;
            
            const dateIndex = Array.from(row.cells).indexOf(cell);
            const table = row.closest('table');
            if (!table) continue;
            
            const dateCell = table.querySelector(`thead tr:last-child th:nth-child(${dateIndex + 1})`);
            if (!dateCell) continue;
            
            const indicatorDate = dateCell.getAttribute('data-date') || dateCell.textContent.trim();
            
            if (indicatorDate === dateValue) {
                return true;
            }
        }
        
        return false;
    }
    
    // Helper function to check if a unit is in maintenance for a specific date
    function checkIfUnitInMaintenance(unitId, dateValue) {
        if (!unitId || !dateValue) return false;
        
        // Find all maintenance indicators in the table
        const maintenanceIndicators = document.querySelectorAll('.text-teal-800');
        if (!maintenanceIndicators || maintenanceIndicators.length === 0) return false;
        
        // Check if any of them are for this unit and date
        for (const indicator of maintenanceIndicators) {
            if (!indicator) continue;
            
            const cell = indicator.closest('td');
            if (!cell) continue;
            
            const row = cell.closest('tr');
            if (!row) continue;
            
            const unitRow = findUnitRow(row);
            if (!unitRow) continue;
            
            const indicatorUnitId = unitRow.getAttribute('data-unit-id') || getUnitIdFromText(unitRow.textContent);
            if (!indicatorUnitId) continue;
            
            // If this indicator is for the same unit
            if (indicatorUnitId === unitId) {
                // Check if it's for the same date
                if (!row.cells) continue;
                
                const dateIndex = Array.from(row.cells).indexOf(cell);
                const table = row.closest('table');
                if (!table) continue;
                
                const dateCell = table.querySelector(`thead tr:last-child th:nth-child(${dateIndex + 1})`);
                if (!dateCell) continue;
                
                const indicatorDate = dateCell.getAttribute('data-date') || dateCell.textContent.trim();
                
                if (indicatorDate === dateValue) {
                    return true;
                }
            }
        }
        
        return false;
    }

    function validateScheduleConflicts(checkbox, scheduleMap) {
        if (!checkbox.checked) {
            checkbox.style.outline = "";
            return;
        }

        const unitId = checkbox.getAttribute("data-unit-id");
        const date = checkbox.getAttribute("data-date");
        const shift = checkbox.getAttribute("data-shift");
        const mapKey = `${unitId}-${date}-${shift}`;

        const relatedCheckboxes = scheduleMap.get(mapKey) || [];

        const checkedCount = relatedCheckboxes.filter(
            (cb) => cb.checked
        ).length;

        if (checkedCount > 1) {
            relatedCheckboxes.forEach((cb) => {
                if (cb.checked) {
                    cb.style.outline = "2px solid red";
                    const tooltip = document.createElement("div");
                    tooltip.className = "text-xs text-red-600 font-medium mt-1";
                    tooltip.textContent = "Konflik jadwal";

                    const existingTooltip =
                        cb.parentNode.querySelector(".text-red-600");
                    if (existingTooltip) {
                        cb.parentNode.removeChild(existingTooltip);
                    }

                    cb.parentNode.appendChild(tooltip);
                }
            });

            showWarningToast(
                "Konflik jadwal terdeteksi! Unit yang sama tidak dapat memiliki lebih dari satu pengemudi pada tanggal dan shift yang sama."
            );
        } else {
            relatedCheckboxes.forEach((cb) => {
                cb.style.outline = "";

                const existingTooltip =
                    cb.parentNode.querySelector(".text-red-600");
                if (existingTooltip) {
                    cb.parentNode.removeChild(existingTooltip);
                }
            });
        }
    }

    function showWarningToast(message) {
        const existingToasts = document.querySelectorAll(".toast-warning");
        existingToasts.forEach((toast) => toast.remove());

        const toast = document.createElement("div");
        toast.className =
            "toast-warning fixed top-4 right-4 bg-amber-100 border-l-4 border-amber-500 text-amber-700 p-4 rounded shadow-md z-50 max-w-md";
        toast.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-amber-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="pl-3 ml-auto">
                        <button class="inline-flex text-amber-500 focus:outline-none focus:text-amber-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;

        toast.querySelector("button").addEventListener("click", () => {
            toast.remove();
        });
        document.body.appendChild(toast);

        setTimeout(() => {
            if (document.body.contains(toast)) {
                toast.remove();
            }
        }, 5000);
    }
});

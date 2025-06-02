function convertToCheckboxes() {
    // Get all date cells (cells that can potentially have a schedule)
    // This will include all cells in the date columns, whether they have content or not
    const dateCells = document.querySelectorAll(
        "tbody td:nth-child(n+5):not(:last-child)"
    );

    const scheduleMap = new Map();

    dateCells.forEach((parentTd) => {
        // Skip cells that don't have a parent row
        if (!parentTd || !parentTd.closest("tr")) {
            return;
        }

        // Skip cells in header rows
        if (
            parentTd.closest("tr.route-header") ||
            parentTd.closest("tr.unit-header")
        ) {
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
            ? dateCell.getAttribute("data-date") || dateCell.textContent.trim()
            : "unknown";
        const shift = getShiftFromRow(row);

        // Check if this unit is in renops for this date
        const isUnitInRenops = checkIfUnitInRenops(unitId, dateValue);

        // Check if the driver is on leave for this date
        const isDriverOnLeave = checkIfDriverOnLeave(row, dateValue);

        // Check if the unit is in maintenance for this date
        const isUnitInMaintenance = checkIfUnitInMaintenance(unitId, dateValue);

        // Check current cell status
        const hasAssignedIndicator = parentTd.querySelector(
            ".text-green-800, .cadangan-checkmark"
        );
        const hasBackupIndicator = parentTd.querySelector(".text-amber-800");
        const hasRenopsIndicator = parentTd.querySelector(".renops-indicator");
        const hasMaintenanceIndicator =
            parentTd.querySelector(".text-teal-800");
        const hasOnLeaveIndicator = parentTd.querySelector(".text-red-800");

        // If the unit is in renops, maintenance, or driver is on leave, we should disable the checkbox
        if (
            isUnitInRenops ||
            isUnitInMaintenance ||
            isDriverOnLeave ||
            hasRenopsIndicator ||
            hasMaintenanceIndicator ||
            hasOnLeaveIndicator
        ) {
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
        const driverTypeCell = row.querySelector("td:nth-child(3)");
        const isCadangan =
            (driverTypeCell &&
                driverTypeCell.textContent
                    .toLowerCase()
                    .includes("cadangan")) ||
            parentTd.querySelector(".cadangan-checkmark");

        if (hasAssignedIndicator) {
            if (isCadangan) {
                // Purple for cadangan drivers
                checkbox.className =
                    "w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500";
                checkbox.setAttribute("data-type", "cadangan");
            } else {
                // Green for batangan drivers
                checkbox.className =
                    "w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500";
                checkbox.setAttribute("data-type", "assigned");
            }
        } else if (hasBackupIndicator) {
            // Amber/orange for backup drivers
            checkbox.className =
                "w-5 h-5 text-amber-600 border-gray-300 rounded focus:ring-amber-500";
            checkbox.setAttribute("data-type", "backup");
        } else {
            // Empty cell - style based on driver type
            if (isCadangan) {
                // Purple for cadangan drivers
                checkbox.className =
                    "w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500";
                checkbox.setAttribute("data-type", "empty-cadangan");
            } else {
                // Green for batangan drivers
                checkbox.className =
                    "w-5 h-5 text-green-600 border-gray-300 rounded focus:ring-green-500";
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

function saveUnitChanges(unitId) {
    const unitContent = document.querySelector(
        `.unit-content[data-unit-content="${unitId}"]`
    );
    if (!unitContent) return;

    // Get current month, year, and period from the URL
    // Initialize this at the beginning of the function so it's available everywhere
    const urlParams = new URLSearchParams(window.location.search);
    const month = urlParams.get("month") || new Date().getMonth() + 1;
    const year = urlParams.get("year") || new Date().getFullYear();
    const period = urlParams.get("period") || 1;

    // Get all checkboxes, both checked and unchecked
    const allCheckboxes = unitContent.querySelectorAll(
        'input[type="checkbox"]:not([disabled])'
    );
    const changes = [];
    const removals = [];

    // Debug the checkboxes
    console.log(
        `Found ${allCheckboxes.length} total checkboxes for unit ${unitId}`
    );

    allCheckboxes.forEach((checkbox) => {
        let date = checkbox.getAttribute("data-date");
        const shift = checkbox.getAttribute("data-shift");
        const driverId = checkbox.getAttribute("data-driver-id");
        const isChecked = checkbox.checked;
        const originallyChecked =
            checkbox.getAttribute("data-originally-checked") === "true";

        // Format the date properly for MySQL (YYYY-MM-DD)
        // First, check if the date is already in the correct format
        if (date && !date.match(/^\d{4}-\d{2}-\d{2}$/)) {
            // Try to extract the day number from formats like "03 Tue"
            const dayMatch = date.match(/^(\d{1,2})/);
            if (dayMatch) {
                const day = dayMatch[1].padStart(2, "0");
                const currentMonth =
                    urlParams.get("month") || new Date().getMonth() + 1;
                const currentYear =
                    urlParams.get("year") || new Date().getFullYear();
                // Format as YYYY-MM-DD
                date = `${currentYear}-${currentMonth
                    .toString()
                    .padStart(2, "0")}-${day}`;
            }
        }

        // Debug each checkbox data
        console.log(
            `Checkbox data: date=${date}, shift=${shift}, driverId=${driverId}, checked=${isChecked}, originallyChecked=${originallyChecked}`
        );

        // Only process if all required data is present
        if (date && shift && driverId) {
            // Ensure driverId is properly parsed as an integer
            const parsedDriverId = parseInt(driverId, 10);
            
            if (isChecked) {
                // Add to changes if checkbox is checked
                changes.push({
                    date,
                    shift,
                    driverId: parsedDriverId,
                    action: "add",
                });
            } else if (originallyChecked) {
                // Add to removals if checkbox was originally checked but now unchecked
                removals.push({
                    date,
                    shift,
                    driverId: parsedDriverId, // Include driverId in removals
                    action: "remove",
                });
            }
        } else {
            console.warn(
                `Missing data for checkbox: date=${date}, shift=${shift}, driverId=${driverId}`
            );
        }
    });

    // Combine changes and removals
    const allChanges = [...changes, ...removals];

    // If no valid changes or removals, show warning and return
    if (allChanges.length === 0) {
        showWarningToast(
            "Tidak ada perubahan jadwal yang valid untuk disimpan"
        );
        return;
    }

    // Debug the data being sent
    console.log("Sending data to server:", {
        unitId,
        additions: changes.filter(item => item.action === "add"),
        removals: removals,
        month,
        year,
        period,
    });

    // Show loading state
    const saveButton = unitContent.querySelector(".unit-save-btn");
    if (saveButton) {
        saveButton.disabled = true;
        saveButton.innerHTML =
            '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';
    }

    // Send the changes to the server
    fetch("/schedules/update", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            unitId,
            additions: changes.filter(item => item.action === "add"),
            removals: removals,
            month,
            year,
            period,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                showSuccessToast(data.message);

                // Show a brief message before reloading
                const reloadMessage = document.createElement("div");
                reloadMessage.className =
                    "fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50";
                reloadMessage.innerHTML = `
                        <div class="bg-white p-6 rounded-lg shadow-xl text-center">
                            <i class="fas fa-sync-alt text-3xl text-indigo-500 animate-spin mb-3"></i>
                            <p class="text-lg font-medium">Memperbarui jadwal...</p>
                            <p class="text-sm text-gray-500 mt-1">Halaman akan dimuat ulang dalam 2 detik</p>
                        </div>
                    `;
                document.body.appendChild(reloadMessage);

                // Wait 2 seconds before reloading to show the success message
                setTimeout(() => {
                    // Reload the page to show updated data
                    window.location.reload();
                }, 2000);
            } else {
                showWarningToast(
                    data.message || "Terjadi kesalahan saat menyimpan perubahan"
                );
                // Re-enable save button
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.innerHTML =
                        '<i class="mr-2 fas fa-save"></i> Simpan Perubahan';
                }
            }
        })
        .catch((error) => {
            console.error("Error saving changes:", error);
            showWarningToast("Terjadi kesalahan saat menyimpan perubahan");
            // Re-enable save button
            if (saveButton) {
                saveButton.disabled = false;
                saveButton.innerHTML =
                    '<i class="mr-2 fas fa-save"></i> Simpan Perubahan';
            }
        });
}

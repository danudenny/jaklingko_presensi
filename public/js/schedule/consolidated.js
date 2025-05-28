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
        const scheduleCells = document.querySelectorAll(
            "td span.inline-flex.items-center.justify-center.w-6.h-6"
        );
        const emptyCells = document.querySelectorAll(
            "td span.inline-block.w-6.h-6"
        );

        const scheduleMap = new Map();

        scheduleCells.forEach((cell) => {
            const isAssigned = cell.classList.contains("text-green-800");
            const isBackup = cell.classList.contains("text-amber-800");
            const parentTd = cell.closest("td");

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

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = isAssigned || isBackup;
            checkbox.className =
                "w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500";
            checkbox.setAttribute(
                "data-type",
                isAssigned ? "assigned" : "backup"
            );
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

        emptyCells.forEach((cell) => {
            const parentTd = cell.closest("td");
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

            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = false;
            checkbox.className =
                "w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500";
            checkbox.setAttribute("data-type", "empty");
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
        let currentRow = row;
        while (currentRow) {
            if (currentRow.classList.contains("bg-blue-50")) {
                return currentRow;
            }
            currentRow = currentRow.previousElementSibling;
        }
        return null;
    }

    function getUnitIdFromText(text) {
        const unitMatch = text.match(/Unit\s+(\d+)/i);
        return unitMatch ? unitMatch[1] : "unknown";
    }

    function getShiftFromRow(row) {
        const shiftCell = row.querySelector("td:nth-child(4)");
        if (shiftCell) {
            if (shiftCell.textContent.includes("Pagi")) {
                return "pagi";
            } else if (shiftCell.textContent.includes("Siang")) {
                return "siang";
            }
        }
        return "unknown";
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

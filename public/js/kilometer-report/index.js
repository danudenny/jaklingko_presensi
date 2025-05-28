function kilometerReport() {
    return {
        editMode: false,
        showImportModal: false,
        showDownloadModal: false,
        downloadMonth: new Date().getMonth() + 1, // Current month (1-12)
        downloadYear: new Date().getFullYear(), // Current year
        downloadPeriod: 1,
        downloadGroup: 'all',
        toggleImportModal() {
            this.showImportModal = !this.showImportModal;
        },
        openDownloadModal() {
            // Close import modal and open download modal
            this.showImportModal = false;
            // Set current values from the URL or defaults
            const urlParams = new URLSearchParams(window.location.search);
            this.downloadPeriod = urlParams.get('period') || 1;
            this.downloadGroup = urlParams.get('group') || 'all';
            this.downloadMonth = urlParams.get('month') || new Date().getMonth() + 1;
            this.downloadYear = urlParams.get('year') || new Date().getFullYear();
            this.showDownloadModal = true;
        },
        toggleEditMode() {
            if (this.editMode) {
                this.editMode = false;
                this.refreshTableData();
            } else {
                this.editMode = true;
            }
        },
        refreshTableData() {
            window.dispatchEvent(
                new CustomEvent("toast", {
                    detail: {
                        message: "Memperbarui data...",
                        type: "info",
                        duration: 2000,
                    },
                })
            );

            fetch(window.location.href)
                .then((response) => response.text())
                .then((html) => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, "text/html");
                    const newTable = doc.getElementById("km-report-table");

                    if (newTable) {
                        document.getElementById("km-report-table").innerHTML =
                            newTable.innerHTML;

                        window.dispatchEvent(
                            new CustomEvent("toast", {
                                detail: {
                                    message: "Data berhasil diperbarui",
                                    type: "success",
                                    duration: 3000,
                                },
                            })
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error refreshing data:", error);

                    window.dispatchEvent(
                        new CustomEvent("toast", {
                            detail: {
                                message: "Gagal memperbarui data",
                                type: "error",
                                duration: 3000,
                            },
                        })
                    );
                });
        },
        saveKilometers(event, unitId, routeId, date) {
            const kilometers = event.target
                .closest("td")
                .querySelector("input").value;

            if (!kilometers || kilometers <= 0) {
                window.dispatchEvent(
                    new CustomEvent("toast", {
                        detail: {
                            message: "Masukkan jumlah kilometer yang valid",
                            type: "error",
                            duration: 3000,
                        },
                    })
                );
                return;
            }

            const saveButton = event.target.closest("button");
            const originalHTML = saveButton.innerHTML;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            saveButton.disabled = true;

            // Get the CSRF token from the meta tag
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/kilometer-reports', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify({
                    unit_id: unitId,
                    route_id: routeId,
                    date: date,
                    kilometers: kilometers,
                }),
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.json();
                })
                .then((data) => {
                    if (data.success) {
                        const td = event.target.closest("td");
                        td.__x.$data.isEditing = false;

                        window.dispatchEvent(
                            new CustomEvent("toast", {
                                detail: {
                                    message: "Data kilometer berhasil disimpan",
                                    type: "success",
                                    duration: 3000,
                                },
                            })
                        );

                        const valueDisplay = td.querySelector("div > span");
                        if (valueDisplay) {
                            valueDisplay.textContent =
                                parseFloat(kilometers).toFixed(1);
                        }
                    } else {
                        window.dispatchEvent(
                            new CustomEvent("toast", {
                                detail: {
                                    message: "Error: " + data.message,
                                    type: "error",
                                    duration: 3000,
                                },
                            })
                        );
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);

                    window.dispatchEvent(
                        new CustomEvent("toast", {
                            detail: {
                                message:
                                    "Mungkin tersimpan, refresh untuk melihat",
                                type: "warning",
                                duration: 3000,
                            },
                        })
                    );

                    const td = event.target.closest("td");
                    td.__x.$data.isEditing = false;
                })
                .finally(() => {
                    saveButton.innerHTML = originalHTML;
                    saveButton.disabled = false;
                });
        },
        submitImportForm() {
            const form = document.getElementById("import-form");
            form.submit();
        },
        downloadTemplate() {
            // Construct the download URL with all parameters
            const baseUrl = document.getElementById("download-template-form").action;
            const url = new URL(baseUrl, window.location.origin);
            
            // Add parameters
            url.searchParams.append('period', this.downloadPeriod);
            url.searchParams.append('group', this.downloadGroup);
            url.searchParams.append('month', this.downloadMonth);
            url.searchParams.append('year', this.downloadYear);
            
            // Navigate to the URL to download the file
            window.location.href = url.toString();
            
            // Close the modal after initiating download
            this.showDownloadModal = false;
        },
    };
}

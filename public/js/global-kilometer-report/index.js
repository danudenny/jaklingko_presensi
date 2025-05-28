function globalKilometerReport() {
    return {
        showGenerateModal: false,
        // Other state properties can be added here
        
        // Initialize the component
        init() {
            // Ensure modal is closed on page load
            this.showGenerateModal = false;
        },
        
        // Method to open the modal
        openGenerateModal() {
            this.showGenerateModal = true;
        },
        
        // Method to close the modal
        closeGenerateModal() {
            this.showGenerateModal = false;
        }
    };
}

document.addEventListener("DOMContentLoaded", function () {
    // Initialize scroll controls
    const tableContainer = document.getElementById("global-km-report-table");
    const scrollLeftBtn = document.getElementById("scroll-left");
    const scrollRightBtn = document.getElementById("scroll-right");

    // Calculate a dynamic scroll step size based on table width (about 25% of visible width)
    const calculateScrollStep = () => {
        return Math.max(150, Math.floor(tableContainer.clientWidth * 0.25));
    };

    // Update scroll button visibility based on scroll position
    const updateScrollButtonVisibility = () => {
        const maxScrollLeft =
            tableContainer.scrollWidth - tableContainer.clientWidth;

        // Hide left button if at leftmost position
        if (tableContainer.scrollLeft <= 0) {
            scrollLeftBtn.style.opacity = "0.5";
        } else {
            scrollLeftBtn.style.opacity = "0.9";
        }

        // Hide right button if at rightmost position
        if (tableContainer.scrollLeft >= maxScrollLeft - 10) {
            scrollRightBtn.style.opacity = "0.5";
        } else {
            scrollRightBtn.style.opacity = "0.9";
        }
    };

    if (scrollLeftBtn && scrollRightBtn && tableContainer) {
        // Initial visibility check
        updateScrollButtonVisibility();

        // Left scroll button click handler
        scrollLeftBtn.addEventListener("click", function () {
            tableContainer.scrollBy({
                left: -calculateScrollStep(),
                behavior: "smooth",
            });
        });

        // Right scroll button click handler
        scrollRightBtn.addEventListener("click", function () {
            tableContainer.scrollBy({
                left: calculateScrollStep(),
                behavior: "smooth",
            });
        });

        // Update button visibility when scrolling
        tableContainer.addEventListener("scroll", updateScrollButtonVisibility);

        // Also allow keyboard navigation
        document.addEventListener("keydown", function (event) {
            if (
                document.activeElement.tagName !== "INPUT" &&
                document.activeElement.tagName !== "TEXTAREA"
            ) {
                if (event.key === "ArrowLeft") {
                    tableContainer.scrollBy({
                        left: -calculateScrollStep(),
                        behavior: "smooth",
                    });
                    event.preventDefault();
                } else if (event.key === "ArrowRight") {
                    tableContainer.scrollBy({
                        left: calculateScrollStep(),
                        behavior: "smooth",
                    });
                    event.preventDefault();
                }
            }
        });
    }

    // Initialize tooltips for holidays
    const holidayCells = document.querySelectorAll("[data-tooltip]");
    holidayCells.forEach((cell) => {
        cell.addEventListener("mouseenter", function () {
            // Remove any existing tooltips first
            document
                .querySelectorAll(".tooltip-holiday")
                .forEach((el) => el.remove());

            const tooltip = document.createElement("div");
            tooltip.className = "tooltip-holiday";
            tooltip.innerHTML = this.getAttribute("data-tooltip");

            // Position tooltip
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

    // Initialize dropdown menu
    const dropdownToggles = document.querySelectorAll(".dropdown-toggle");

    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener("click", function () {
            const menu = this.nextElementSibling;
            menu.classList.toggle("hidden");
        });
    });

    // Close dropdowns when clicking outside
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
});

// Function to toggle collapsible groups (route groups and unit groups)
function toggleGroup(elementId) {
    const content = document.getElementById(elementId);
    const header = content.previousElementSibling;
    const icon = header.querySelector(".group-toggle");

    if (content.style.display === "none") {
        content.style.display = "table-row";
        header.classList.remove("collapsed");
        icon.classList.remove("transform", "rotate-180");
    } else {
        content.style.display = "none";
        header.classList.add("collapsed");
        icon.classList.add("transform", "rotate-180");
    }
}

// Initialize all groups - collapsed by default
document.addEventListener("DOMContentLoaded", function () {
    // Initialize all nested content sections
    const nestedContents = document.querySelectorAll(
        ".nested-content[id]"
    );

    // Only hide unit groups by default, keep route groups expanded
    nestedContents.forEach((content) => {
        if (content.id.startsWith("unit-")) {
            content.style.display = "none";
            const header = content.previousElementSibling;
            if (header) {
                header.classList.add("collapsed");
                const icon = header.querySelector(".group-toggle");
                if (icon) {
                    icon.classList.add("transform", "rotate-180");
                }
            }
        }
    });
});

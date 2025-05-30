function showSuccessToast(message) {
    const existingToasts = document.querySelectorAll(".toast-success");
    existingToasts.forEach((toast) => toast.remove());

    const toast = document.createElement("div");
    toast.className =
        "toast-success fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-md z-50 max-w-md";
    toast.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="pl-3 ml-auto">
                        <button class="inline-flex text-green-500 focus:outline-none focus:text-green-700">
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

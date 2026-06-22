/**
 * Main Javascript Helper File
 * POS Kasir Website
 */

document.addEventListener("DOMContentLoaded", function () {
    // Sidebar responsive toggle handler
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.getElementById("sidebar");
    const mainContent = document.getElementById("mainContent");

    if (sidebarToggle && sidebar && mainContent) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            sidebar.classList.toggle("active");
            mainContent.classList.toggle("active");
        });
    }
});

/**
 * Format number representation as Rupiah currency string
 * @param {number|string} number 
 * @param {string} prefix 
 * @returns {string}
 */
function formatRupiah(number, prefix = 'Rp ') {
    if (number === undefined || number === null || isNaN(number)) {
        return prefix + '0';
    }
    const val = parseFloat(number);
    const parts = val.toFixed(0).split(".");
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    return prefix + parts.join(",");
}

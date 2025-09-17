// Immediate theme initialization (before DOM is ready)
(function () {
    const theme = localStorage.getItem("theme") || "dark";
    document.documentElement.className = theme;
    document.documentElement.setAttribute("data-theme", theme);
})();

// Theme management after Turbo load
document.addEventListener("turbo:load", function () {
    const themeSelect = document.getElementById("theme-select");
    const html = document.documentElement;

    // LocalStorage
    const currentTheme = localStorage.getItem("theme") || "dark";

    // Initial theme (ensure it's set correctly)
    setTheme(currentTheme);
    if (themeSelect) {
        themeSelect.value = currentTheme;
    }

    // Theme changes
    if (themeSelect) {
        themeSelect.addEventListener("change", function () {
            const newTheme = this.value;
            setTheme(newTheme);
            localStorage.setItem("theme", newTheme);
        });
    }

    function setTheme(theme) {
        html.className = theme;
        html.setAttribute("data-theme", theme);
    }
});

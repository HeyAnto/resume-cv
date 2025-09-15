document.addEventListener("DOMContentLoaded", function () {
    const themeSelect = document.getElementById("theme-select");
    const html = document.documentElement;

    // LocalStorage
    const currentTheme = localStorage.getItem("theme") || "dark";

    // Initial theme
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

// Theme management after Turbo load
document.addEventListener("turbo:load", function () {
    const themeSelect = document.getElementById("theme-select");
    const html = document.documentElement;

    // Get current theme from localStorage or default to dark
    const currentTheme = localStorage.getItem("theme") || "dark";

    // Ensure theme is correctly applied (theme should already be set by inline script in base.html.twig)
    // But we double-check in case something went wrong
    if (!html.className || html.className !== currentTheme) {
        setTheme(currentTheme);
    }

    // Set the select value to match current theme
    if (themeSelect) {
        themeSelect.value = currentTheme;
    }

    // Handle theme changes
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

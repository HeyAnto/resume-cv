document.addEventListener("turbo:load", function () {
    const burgerBtn = document.getElementById("burger-btn");
    const sidebar = document.getElementById("sidebar");
    const editSidebar = document.getElementById("edit-sidebar");
    const overlay = document.getElementById("mobile-overlay");

    function toggleMenu() {
        const isActive = sidebar.classList.contains("active");

        if (isActive) {
            // Close menus
            sidebar.classList.remove("active");
            if (editSidebar) {
                editSidebar.classList.remove("active");
            }
            overlay.classList.remove("active");
            burgerBtn.classList.remove("active");
        } else {
            // Open menus
            sidebar.classList.add("active");
            if (editSidebar) {
                editSidebar.classList.add("active");
            }
            overlay.classList.add("active");
            burgerBtn.classList.add("active");
        }
    }

    // Burger
    if (burgerBtn) {
        burgerBtn.addEventListener("click", toggleMenu);
    }

    // Overlay
    if (overlay) {
        overlay.addEventListener("click", function () {
            if (sidebar.classList.contains("active")) {
                toggleMenu();
            }
        });
    }

    // Close menu -> lick click
    const sidebarLinks = document.querySelectorAll(
        ".sidebar .nav-button, .edit-sidebar .btn-menu"
    );
    sidebarLinks.forEach((link) => {
        link.addEventListener("click", function () {
            setTimeout(() => {
                if (sidebar.classList.contains("active")) {
                    toggleMenu();
                }
            }, 100);
        });
    });
});

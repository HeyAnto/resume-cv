document.addEventListener("DOMContentLoaded", function () {
    const scrollContainers = document.querySelectorAll(".project-img");

    scrollContainers.forEach((container) => {
        let isDown = false;
        let startX;
        let scrollLeft;

        container.addEventListener("mousedown", (e) => {
            isDown = true;
            container.style.cursor = "grabbing";
            startX = e.pageX - container.offsetLeft;
            scrollLeft = container.scrollLeft;
            e.preventDefault();
        });

        container.addEventListener("mouseleave", () => {
            isDown = false;
            container.style.cursor = "grab";
        });

        container.addEventListener("mouseup", () => {
            isDown = false;
            container.style.cursor = "grab";
        });

        container.addEventListener("mousemove", (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const walk = (x - startX) * 2; //scroll speed
            container.scrollLeft = scrollLeft - walk;
        });
    });
});

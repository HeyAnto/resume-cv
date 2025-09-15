document.addEventListener("DOMContentLoaded", function () {
    const modal = document.createElement("div");
    modal.className = "image-modal";
    modal.innerHTML = `
        <div class="modal-overlay">
            <div class="modal-content">
                <img class="modal-image" src="" alt="">
                <button class="modal-close btn btn-secondary-xs text-14">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const modalOverlay = modal.querySelector(".modal-overlay");
    const modalImage = modal.querySelector(".modal-image");
    const closeButton = modal.querySelector(".modal-close");

    // Open modal
    function openModal(imageSrc, imageAlt) {
        modalImage.src = imageSrc;
        modalImage.alt = imageAlt;
        modal.style.display = "block";
        document.body.style.overflow = "hidden";
    }

    // Close modal
    function closeModal() {
        modal.style.display = "none";
        document.body.style.overflow = "auto";
    }

    // Click events
    document.addEventListener("click", function (e) {
        if (
            e.target.matches(".project-img img") ||
            e.target.matches(".post-img")
        ) {
            e.stopPropagation();
            openModal(e.target.src, e.target.alt);
        }
    });

    // Set cursor
    const projectImages = document.querySelectorAll(".project-img img");
    const postImages = document.querySelectorAll(".post-img");

    projectImages.forEach((img) => {
        img.style.cursor = "pointer";
    });

    postImages.forEach((img) => {
        img.style.cursor = "pointer";
    });

    // Close handlers
    closeButton.addEventListener("click", closeModal);
    modalOverlay.addEventListener("click", function (e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal.style.display === "block") {
            closeModal();
        }
    });
});

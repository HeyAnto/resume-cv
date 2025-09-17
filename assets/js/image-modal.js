document.addEventListener("turbo:load", function () {
    const modal = document.createElement("div");
    modal.className = "image-modal";
    modal.innerHTML = `
        <div class="modal-overlay">
            <div class="modal-content">
                <img class="modal-image" src="" alt="">
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const modalOverlay = modal.querySelector(".modal-overlay");
    const modalContent = modal.querySelector(".modal-content");
    const modalImage = modal.querySelector(".modal-image");

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
    modalContent.addEventListener("click", closeModal);

    // Prevent image click from closing modal
    modalImage.addEventListener("click", function (e) {
        e.stopPropagation();
    });

    // Escape key
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal.style.display === "block") {
            closeModal();
        }
    });
});

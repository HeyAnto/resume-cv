document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.querySelector(
        'input[type="file"][accept*="image"]'
    );

    if (fileInput) {
        fileInput.addEventListener("change", function (event) {
            const file = event.target.files[0];
            const previewContainer = document.getElementById(
                "image-preview-container"
            );
            const existingImage = document.querySelector(".img-edit-postform");

            // Remove existing preview if any
            const existingPreview = document.querySelector(".image-preview");
            if (existingPreview) {
                existingPreview.remove();
            }

            if (file && file.type.startsWith("image/")) {
                const allowedTypes = [
                    "image/jpeg",
                    "image/jpg",
                    "image/png",
                    "image/webp",
                    "image/gif",
                ];
                if (!allowedTypes.includes(file.type)) {
                    return;
                }

                // Validate file size (5MB max)
                const maxSize = 5 * 1024 * 1024;
                if (file.size > maxSize) {
                    return;
                }

                const reader = new FileReader();

                reader.onload = function (e) {
                    // Hide existing image
                    if (existingImage) {
                        existingImage.style.display = "none";
                    }

                    // Create preview image
                    const preview = document.createElement("img");
                    preview.src = e.target.result;
                    preview.alt = "Preview";
                    preview.className = "image-preview img-edit-postform";

                    // Add to preview container
                    previewContainer.appendChild(preview);
                };

                reader.readAsDataURL(file);
            } else {
                if (existingImage) {
                    existingImage.style.display = "block";
                }
            }
        });
    }
});

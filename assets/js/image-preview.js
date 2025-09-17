document.addEventListener("DOMContentLoaded", function () {
    const fileInputs = document.querySelectorAll(
        'input[type="file"][accept*="image"]'
    );

    fileInputs.forEach(function (fileInput, index) {
        fileInput.addEventListener("change", function (event) {
            const file = event.target.files[0];

            // Preview by ID
            let previewContainerId = "image-preview-container";
            if (fileInput.id.includes("imagePath2")) {
                previewContainerId = "image-preview-container-2";
            } else if (fileInput.id.includes("imagePath3")) {
                previewContainerId = "image-preview-container-3";
            } else if (fileInput.id.includes("imagePath")) {
                previewContainerId = "image-preview-container-1";
            }

            const previewContainer =
                document.getElementById(previewContainerId);
            if (!previewContainer) {
                return;
            }

            const existingImage =
                previewContainer.querySelector(".img-edit-postform");

            // Remove existing preview if any
            const existingPreview =
                previewContainer.querySelector(".image-preview");
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
                    preview.className =
                        "image-preview img-edit-postform post-img";
                    preview.style.cursor = "pointer";

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
    });
});

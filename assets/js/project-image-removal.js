document.addEventListener("turbo:load", function () {
    // Function to remove project images from server (for existing projects)
    window.removeProjectImage = function (username, projectId, imageField) {
        if (!confirm("Are you sure you want to remove this image?")) {
            return;
        }

        const url = `/profile/${username}/project/${projectId}/remove-image/${imageField}`;

        fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        })
            .then((response) => {
                if (response.ok) {
                    // Find and remove the image wrapper instead of reloading the page
                    const imageContainer = document.querySelector(
                        `#image-preview-container-${getImageNumber(
                            imageField
                        )} .image-preview-wrapper`
                    );
                    if (imageContainer) {
                        imageContainer.remove();
                    }

                    // Show success message (optional)
                    showFlashMessage("Image removed successfully!", "success");
                } else {
                    showFlashMessage("Error removing image", "error");
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                showFlashMessage("Error removing image", "error");
            });
    };

    // Function to remove preview images (before submit)
    window.removePreviewImage = function (imageField) {
        if (!confirm("Are you sure you want to remove this image preview?")) {
            return;
        }

        const imageNumber = getImageNumber(imageField);
        const container = document.querySelector(
            `#image-preview-container-${imageNumber}`
        );
        const fileInput = document.querySelector(`input[id*="${imageField}"]`);

        if (container && fileInput) {
            // Clear file input
            fileInput.value = "";

            // Remove the preview wrapper (identified by data-preview attribute)
            const previewWrapper = container.querySelector(
                '.image-preview-wrapper[data-preview="true"]'
            );
            if (previewWrapper) {
                previewWrapper.remove();
            }

            // Show existing image if any (the one without data-preview attribute)
            const existingWrapper = container.querySelector(
                ".image-preview-wrapper:not([data-preview])"
            );
            if (existingWrapper) {
                existingWrapper.style.display = "block";
            }

            showFlashMessage("Image preview removed!", "success");
        }
    };

    // Enhanced image preview functionality with remove buttons
    const fileInputs = document.querySelectorAll(
        'input[type="file"][accept*="image"]'
    );

    fileInputs.forEach(function (fileInput) {
        fileInput.addEventListener("change", function (event) {
            const file = event.target.files[0];

            // Preview by ID
            let previewContainerId = "image-preview-container";
            let imageField = "imagePath";

            if (fileInput.id.includes("imagePath2")) {
                previewContainerId = "image-preview-container-2";
                imageField = "imagePath2";
            } else if (fileInput.id.includes("imagePath3")) {
                previewContainerId = "image-preview-container-3";
                imageField = "imagePath3";
            } else if (fileInput.id.includes("imagePath")) {
                previewContainerId = "image-preview-container-1";
                imageField = "imagePath";
            }

            const previewContainer =
                document.getElementById(previewContainerId);
            if (!previewContainer) {
                return;
            }

            const existingWrapper = previewContainer.querySelector(
                ".image-preview-wrapper"
            );

            // Remove existing preview if any (but keep existing server images)
            const existingPreview = previewContainer.querySelector(
                '.image-preview-wrapper[data-preview="true"]'
            );
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
                    // Hide existing image wrapper if any
                    if (existingWrapper) {
                        existingWrapper.style.display = "none";
                    }

                    // Create preview wrapper with data attribute to identify it as preview
                    const previewWrapper = document.createElement("div");
                    previewWrapper.className = "image-preview-wrapper";
                    previewWrapper.setAttribute("data-preview", "true");

                    // Create preview image
                    const preview = document.createElement("img");
                    preview.src = e.target.result;
                    preview.alt = "Preview";
                    preview.className = "image-preview img-edit-postform";
                    preview.style.cursor = "pointer";

                    // Create remove button for preview
                    const removeBtn = document.createElement("button");
                    removeBtn.type = "button";
                    removeBtn.className = "image-remove-btn";
                    removeBtn.title = "Remove preview";
                    removeBtn.onclick = function () {
                        removePreviewImage(imageField);
                    };

                    // Add SVG icon
                    removeBtn.innerHTML = `
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    `;

                    // Assemble preview wrapper
                    previewWrapper.appendChild(preview);
                    previewWrapper.appendChild(removeBtn);

                    // Add to preview container
                    previewContainer.appendChild(previewWrapper);
                };

                reader.readAsDataURL(file);
            } else {
                // If no file selected, remove preview and show existing image if any
                const previewWrapper = previewContainer.querySelector(
                    '.image-preview-wrapper[data-preview="true"]'
                );
                if (previewWrapper) {
                    previewWrapper.remove();
                }

                if (existingWrapper) {
                    existingWrapper.style.display = "block";
                }
            }
        });
    });

    // Helper function to get image number from field name
    function getImageNumber(imageField) {
        switch (imageField) {
            case "imagePath":
                return "1";
            case "imagePath2":
                return "2";
            case "imagePath3":
                return "3";
            default:
                return "1";
        }
    }

    // Helper function to show flash messages
    function showFlashMessage(message, type) {
        // Create flash message element
        const flashMessage = document.createElement("div");
        flashMessage.className = `alert alert-${
            type === "success" ? "success" : "danger"
        }`;
        flashMessage.textContent = message;

        // Insert at the top of the container
        const container = document.querySelector(
            ".section-profile-edit .container"
        );
        if (container) {
            const firstChild = container.querySelector("div");
            container.insertBefore(flashMessage, firstChild.nextSibling);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                flashMessage.remove();
            }, 3000);
        }
    }
});

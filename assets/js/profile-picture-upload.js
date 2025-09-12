document.addEventListener("DOMContentLoaded", function () {
    const uploadBtn = document.getElementById("upload-btn");
    const removeBtn = document.getElementById("remove-btn");
    const profileInput = document.querySelector(
        'input[type="file"][name*="profilePicture"]'
    );
    const profilePreview = document.getElementById("profile-preview");

    if (uploadBtn && profileInput) {
        // Open file selector
        uploadBtn.addEventListener("click", function () {
            profileInput.click();
        });

        // Preview selected image
        profileInput.addEventListener("change", function (event) {
            const file = event.target.files[0];

            if (file) {
                // Validate file type
                const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
                if (!allowedTypes.includes(file.type)) {
                    alert("Please select a valid image file (JPEG, PNG, WebP)");
                    return;
                }

                // Validate file size
                if (file.size > 2 * 1024 * 1024) {
                    alert("File size must be less than 2MB");
                    return;
                }

                // Create image preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);

                // Auto submit form
                const form = profileInput.closest("form");
                if (form) {
                    form.submit();
                }
            }
        });
    }

    // Handle profile removal
    if (removeBtn) {
        removeBtn.addEventListener("click", function () {
            if (
                confirm("Are you sure you want to remove your profile picture?")
            ) {
                const removeUrl = removeBtn.getAttribute("data-remove-url");

                // Create temporary form
                const form = document.createElement("form");
                form.method = "POST";
                form.action = removeUrl;
                form.style.display = "none";

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});

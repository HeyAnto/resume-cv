document.addEventListener("DOMContentLoaded", function () {
    // Handle verify/unverify buttons
    const verifyBtns = document.querySelectorAll(".admin-verify-btn");
    verifyBtns.forEach(function (verifyBtn) {
        verifyBtn.addEventListener("click", function () {
            const userId = this.getAttribute("data-user-id");

            fetch(`/admin/users/${userId}/toggle-verified`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Update button & data
                        this.textContent = data.isVerified
                            ? "Unverify"
                            : "Verify";
                        this.setAttribute(
                            "data-verified",
                            data.isVerified ? 1 : 0
                        );

                        // Show success message
                        alert(data.message);
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert(
                        "An error occurred while updating user verification status."
                    );
                });
        });
    });

    // Handle delete buttons
    const deleteBtns = document.querySelectorAll(".admin-delete-btn");
    deleteBtns.forEach(function (deleteBtn) {
        deleteBtn.addEventListener("click", function () {
            const username = this.getAttribute("data-username");

            if (
                confirm(
                    `Are you sure you want to delete the account "${username}"? This action cannot be undone.`
                )
            ) {
                // Create a form and submit it
                const form = document.createElement("form");
                form.method = "POST";
                form.action = `/profile/${username}/admin-delete`;

                // Add CSRF token if available
                const csrfToken = document.querySelector(
                    'meta[name="csrf-token"]'
                );
                if (csrfToken) {
                    const csrfInput = document.createElement("input");
                    csrfInput.type = "hidden";
                    csrfInput.name = "_token";
                    csrfInput.value = csrfToken.getAttribute("content");
                    form.appendChild(csrfInput);
                }

                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});

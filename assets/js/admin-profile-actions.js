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

    // Handle admin role buttons
    const adminRoleBtns = document.querySelectorAll(".admin-role-btn");
    adminRoleBtns.forEach(function (roleBtn) {
        roleBtn.addEventListener("click", function () {
            const userId = this.getAttribute("data-user-id");
            const isAdmin = this.getAttribute("data-is-admin") === "1";

            const action = isAdmin ? "remove" : "add";
            const confirmMessage = isAdmin
                ? "Are you sure you want to remove admin role from this user?"
                : "Are you sure you want to make this user an admin?";

            if (confirm(confirmMessage)) {
                fetch(`/admin/users/${userId}/toggle-admin-role`, {
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
                            this.textContent = data.isAdmin
                                ? "Remove Admin"
                                : "Make Admin";
                            this.setAttribute(
                                "data-is-admin",
                                data.isAdmin ? 1 : 0
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
                            "An error occurred while updating user admin role."
                        );
                    });
            }
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
                // Create a form -> submit
                const form = document.createElement("form");
                form.method = "POST";
                form.action = `/profile/${username}/admin-delete`;

                // Add CSRF token
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

    // Handle post visibility
    const postVisibilityBtns = document.querySelectorAll(
        ".admin-post-visibility-btn"
    );
    postVisibilityBtns.forEach(function (visibilityBtn) {
        visibilityBtn.addEventListener("click", function () {
            const postId = this.getAttribute("data-post-id");

            fetch(`/admin/posts/${postId}/toggle-visibility`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        // Update button text
                        this.textContent = data.isVisible
                            ? "Hide Post"
                            : "Show Post";
                        alert(data.message);
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch((error) => {
                    console.error("Error:", error);
                    alert("An error occurred while updating post visibility.");
                });
        });
    });

    // Handle post delete
    const postDeleteBtns = document.querySelectorAll(".admin-post-delete-btn");
    postDeleteBtns.forEach(function (deleteBtn) {
        deleteBtn.addEventListener("click", function () {
            const postId = this.getAttribute("data-post-id");

            if (
                confirm(
                    "Are you sure you want to delete this post? This action cannot be undone."
                )
            ) {
                fetch(`/admin/posts/${postId}/delete`, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.success) {
                            // Remove the post
                            this.closest(".admin-post-card").remove();
                            alert(data.message);
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch((error) => {
                        console.error("Error:", error);
                        alert("An error occurred while deleting the post.");
                    });
            }
        });
    });
});

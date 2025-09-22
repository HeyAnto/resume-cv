document.addEventListener("turbo:load", function () {
    const forms = document.querySelectorAll("form");

    forms.forEach(function (form) {
        form.addEventListener("submit", function () {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
            }
        });
    });

    // Username to lowercase
    const usernameInputs = document.querySelectorAll('input[id*="username"]');
    usernameInputs.forEach(function (input) {
        input.addEventListener("input", function () {
            this.value = this.value.toLowerCase();
        });
    });

    // Auto-submit search type
    const typeSelect = document.getElementById("type");
    if (typeSelect) {
        typeSelect.addEventListener("change", function () {
            const form = this.closest("form");
            if (form) {
                form.submit();
            }
        });
    }
});

document.addEventListener("turbo:load", function () {
    function updateCounter(input) {
        const counterId = input.getAttribute("data-counter");
        const maxLength = parseInt(input.getAttribute("data-max"));
        const counter = document.querySelector(`#${counterId}`);

        if (counter) {
            const currentLength = input.value.length;
            counter.textContent = `${currentLength} of ${maxLength}`;

            // Visual feedback
            if (currentLength >= maxLength) {
                counter.style.color = "#ff6b6b";
            } else if (currentLength > maxLength * 0.9) {
                counter.style.color = "#ff6b6b";
            } else if (currentLength > maxLength * 0.7) {
                counter.style.color = "#ffa726";
            } else {
                counter.style.color = "";
            }
        }
    }

    // Handle input
    function handleInput(input) {
        const maxLength = parseInt(input.getAttribute("data-max"));

        // Truncate if needed
        if (input.value.length > maxLength) {
            input.value = input.value.substring(0, maxLength);
        }

        updateCounter(input);
    }

    // Handle keypress
    function handleKeypress(e, input) {
        const maxLength = parseInt(input.getAttribute("data-max"));
        const currentLength = input.value.length;

        // Allowed keys
        const allowedKeys = [8, 9, 37, 38, 39, 40, 46];

        if (allowedKeys.includes(e.keyCode) || e.ctrlKey || e.metaKey) {
            return true;
        }

        // Prevent typing
        if (currentLength >= maxLength) {
            e.preventDefault();
            return false;
        }

        return true;
    }

    // Handle paste
    function handlePaste(e, input) {
        const maxLength = parseInt(input.getAttribute("data-max"));

        setTimeout(() => {
            if (input.value.length > maxLength) {
                input.value = input.value.substring(0, maxLength);
            }
            updateCounter(input);
        }, 10);
    }

    // Initialize counters
    const inputs = document.querySelectorAll(
        "input[data-counter], textarea[data-counter]"
    );

    inputs.forEach(function (input) {
        // Set maxlength
        const maxLength = parseInt(input.getAttribute("data-max"));
        input.setAttribute("maxlength", maxLength);

        // Initial update
        updateCounter(input);

        // Event listeners
        input.addEventListener("input", function () {
            handleInput(this);
        });

        input.addEventListener("keypress", function (e) {
            handleKeypress(e, this);
        });

        input.addEventListener("paste", function (e) {
            handlePaste(e, this);
        });
    });
});

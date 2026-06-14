

document.addEventListener("DOMContentLoaded", () => {
    const sliders = document.querySelectorAll(".slider");

    // Sentiment palette: Deep Red → Gold → Sage Green
    const colors = {
        low:  "#C0392B",   // Negative
        mid:  "#B08D57",   // Neutral / brand gold
        high: "#4E7040"    // Positive / luxury green
    };

    // Updates one slider's visual state based on its current value
    const updateSentiment = (slider) => {
        const val = slider.value;
        const emoji = slider.parentElement.querySelector(".emoji");

        // 1. Pick the track color band
        let currentColor;
        if (val < 50) {
            currentColor = colors.low;
        } else if (val < 80) {
            currentColor = colors.mid;
        } else {
            currentColor = colors.high;
        }

        // 2. Paint the filled portion of the slider with that color
        slider.style.background = `linear-gradient(to right, ${currentColor} ${val}%, #E8E2D9 ${val}%)`;

        // 3. Swap the emoji + apply scale/filter for affect
        if (val < 35) {
            emoji.textContent = emoji.getAttribute("data-sad") || "😡";
            emoji.style.transform = "scale(0.9)";
            emoji.style.filter = "grayscale(0.4)";
        } else if (val < 75) {
            emoji.textContent = emoji.getAttribute("data-neutral") || "😐";
            emoji.style.transform = "scale(1.1)";
            emoji.style.filter = "grayscale(0)";
        } else {
            emoji.textContent = emoji.getAttribute("data-happy") || "😍";
            emoji.style.transform = "scale(1.3)";
            emoji.style.filter = "drop-shadow(0 0 5px rgba(176, 141, 87, 0.3))";
        }
    };

    sliders.forEach(slider => {
        // Render the initial state on page load
        updateSentiment(slider);

        // Re-render on every drag input — adds a quick "pulse" class for animation
        slider.addEventListener("input", () => {
            updateSentiment(slider);

            const emoji = slider.parentElement.querySelector(".emoji");
            emoji.classList.add("active");
            clearTimeout(slider.pulseTimeout);
            slider.pulseTimeout = setTimeout(() => {
                emoji.classList.remove("active");
            }, 150);
        });
    });
});

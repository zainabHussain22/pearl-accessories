


// Show an error message inside a span/div with the given id
function showError(elementId, message) {
    var el = document.getElementById(elementId);
    if (el) el.textContent = message;
}

// Clear an error message
function clearError(elementId) {
    var el = document.getElementById(elementId);
    if (el) el.textContent = '';
}

// Validate email format — quick regex (not RFC-perfect, just good enough
// for catching typos like "abc" or "abc@" before the form hits the server).
function isValidEmail(email) {
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(email);
}

// Validate that a value is a positive number (used for prices, quantities)
function isPositiveNumber(value) {
    return !isNaN(value) && parseFloat(value) > 0;
}

// Screen-reader announcement helper for accessibility
// Creates a visually-hidden ARIA live region so blind users get audio feedback
function announceMessage(message) {
    var alertDiv = document.createElement('div');
    alertDiv.setAttribute('role', 'alert');
    alertDiv.setAttribute('aria-live', 'polite');
    alertDiv.textContent = message;
    alertDiv.style.position = 'absolute';
    alertDiv.style.left = '-9999px';
    document.body.appendChild(alertDiv);
    setTimeout(function() {
        document.body.removeChild(alertDiv);
    }, 3000);
}

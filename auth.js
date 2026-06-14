/* ============================================================================
 * FILE:  js/auth.js
 * ROLE:  Legacy helper for swapping between login/register card faces on
 *        old auth pages. Kept for backward compatibility — new auth pages
 *        use their own inline scripts with show/hide eye toggles instead.
 * ============================================================================
 */
// Student Name: Zainab

// Hide the login card and show the register card
function showRegister(){
    document.querySelector(".login").style.display="none"
    document.querySelector(".register").style.display="flex"
}

// Hide the register card and show the login card
function showLogin(){
    document.querySelector(".register").style.display="none"
    document.querySelector(".login").style.display="flex"
}

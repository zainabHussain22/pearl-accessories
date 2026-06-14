<?php
/**
 * ============================================================================
 * FILE:  contact.php
 * ROLE:  Static contact information page. Shows the shop's address, phone,
 *        email, and working hours, plus an embedded Google Maps iframe
 *        pointing to Al Khobar in the Eastern Province.
 *
 * NOTES:
 *   • No database access required — pure presentation.
 *   • The Google Maps iframe is the textbook example from the project
 *     brief (Task 11) — easy to swap for a real shop address later.
 * ============================================================================
 */
// Student Name: Najd

include 'includes/header.php';
?>

<h1 class="page-title">📍 Contact Us</h1>

<div class="contact-info">
    <div class="contact-details">
        <h2>🦪 Pearl Accessories Shop</h2>
        <br>
        <p><strong>📍 Address:</strong></p>
        <p>King Faisal Road, Al Khobar</p>
        <p>Eastern Province, Saudi Arabia</p>
        <br>
        <p><strong>📞 Phone:</strong> +966 13 123 4567</p>
        <p><strong>📧 Email:</strong> info@pearlaccessories.com</p>
        <p><strong>🕐 Working Hours:</strong></p>
        <p>Saturday - Thursday: 9:00 AM - 10:00 PM</p>
        <p>Friday: 4:00 PM - 10:00 PM</p>
        <br>
        <p><strong>🌐 Follow Us:</strong></p>
        <p>Instagram: @pearl_accessories</p>
        <p>Twitter: @pearl_shop</p>
    </div>

    <div class="contact-map">
        <!-- Task 11: Google Map -->
        <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3576.789!2d50.208!3d26.304!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjbCsDE4JzE0LjQiTiA1MMKwMTInMjguOCJF!5e0!3m2!1sen!2ssa!4v1234567890"
            width="100%" 
            height="450" 
            style="border:0;" 
            allowfullscreen="" 
            loading="lazy"
            title="Pearl Accessories Shop Location"
            aria-label="Google Map showing shop location">
        </iframe>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
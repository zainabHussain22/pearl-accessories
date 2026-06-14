<?php
/**
 * ============================================================================
 * FILE:  review.php
 * ROLE:  Renders the review form for a specific completed order. The
 *        actual database insert happens in save_review.php (separate file
 *        so the form action is clean).
 *
 * URL: review.php?order_id={id}
 *
 * INPUT VALIDATION:
 *   • Admin → blocked (admins don't write reviews).
 *   • Non-logged-in → redirected to login.
 *   • Missing/invalid order_id → friendly error page (not a crash).
 *
 * UX:
 *   Two sentiment sliders (Product Quality + Customer Service) with
 *   live emoji feedback handled in js/review.js — the emoji morphs
 *   from 😡 → 😐 → 😍 as the user drags the slider.
 * ============================================================================
 */
// Student Name: Wajeha

session_start();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] != true) {
    header("Location: login.php");
    exit;
}

// REJECT if no valid order_id
if ($order_id <= 0) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invalid Order</title>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div style="max-width: 600px; margin: 100px auto; padding: 40px; text-align: center; background: #fff3cd; border: 2px solid #ffc107; border-radius: 12px;">
            <h2 style="color: #856404; margin: 0 0 16px 0;">❌ Invalid Order ID</h2>
            <p style="color: #856404; margin: 0 0 24px 0;">Please select a valid order to review.</p>
            <a href="customer_panel.php" style="display: inline-block; padding: 12px 28px; background: #ffc107; color: #000; text-decoration: none; border-radius: 6px; font-weight: 600; transition: background 0.3s;">Back to My Orders</a>
        </div>
    </body>
    </html>';
    exit;
}

include 'includes/header.php'; 
?>

<div class="review-wrapper">
    <div class="review-card">
        <h2 class="review-title">⭐ Rate Your Experience</h2>
        
        <p style="color: #B08D57; font-weight: 600; margin-bottom: 16px;">
            📦 You are reviewing <strong>Order #<?php echo $order_id; ?></strong>
        </p>
        
        <p class="review-subtitle">Help us improve by sharing your feedback</p>
        <form action="save_review.php" method="POST" class="review-form">
            
            <!-- REQUIRED: order_id must be passed -->
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

            <div class="rating-box">
                <div class="rating-item">
                    <label>Product Quality</label>
                    <div class="slider-container">
                        <span class="emoji" data-sad="😡" data-neutral="😐" data-happy="😍">😐</span>
                        <input type="range" name="quality" min="0" max="100" value="50" class="slider">
                    </div>
                </div>

                <div class="rating-item">
                    <label>Customer Service</label>
                    <div class="slider-container">
                        <span class="emoji" data-sad="😡" data-neutral="😐" data-happy="😍">😐</span>
                        <input type="range" name="service" min="0" max="100" value="50" class="slider">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Additional Comments (Optional)</label>
                <textarea name="comments" rows="4" placeholder="Share your experience with us..."></textarea>
            </div>

            <div class="review-buttons">
                <button type="submit" class="btn btn-gold">Submit Review</button>
                <a href="customer_panel.php" class="btn btn-primary">Back to Orders</a>
            </div>
        </form>
    </div>
</div>

<script src="js/review.js"></script>
<?php include 'includes/footer.php'; ?>
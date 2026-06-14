<?php
/**
 * ============================================================================
 * FILE:  save_review.php
 * ROLE:  Receives the review form POST from review.php, validates ownership
 *        of the order, then inserts a new row into the `ratings` table.
 *
 * SECURITY (CRITICAL — ownership check):
 *   Before inserting, we run a SELECT to confirm that the order_id
 *   actually belongs to the logged-in user. Without this, anyone could
 *   forge a POST with another user's order_id and inject fake reviews.
 *   This was a real vulnerability fix during development.
 *
 * POST-INSERT REDIRECT:
 *   On success → index.php?rating_success=1 (triggers a toast banner).
 *   On failure → back to review.php with an error flag in the query string.
 * ============================================================================
 */
// Student Name: Wajeha

session_start();
include 'db.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $user_id  = intval($_SESSION['user_id']);
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

    // If order_id is missing or invalid, reject
    if ($order_id <= 0) {
        header("Location: review.php?error=no_order");
        exit;
    }

    $product_quality  = intval($_POST['quality']  ?? 50);
    $customer_service = intval($_POST['service']  ?? 50);
    $comment          = $_POST['comments'] ?? '';

    try {
        // LISTING 14.17 — Verify the order belongs to the logged-in user (security check)
        $sql = "SELECT order_id FROM orders WHERE order_id = :oid AND user_id = :uid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':oid', $order_id);
        $statement->bindValue(':uid', $user_id);
        $statement->execute();
        $ownsOrder = $statement->fetch();

        if (!$ownsOrder) {
            header("Location: customer_panel.php?error=not_your_order");
            exit;
        }

        // LISTING 14.17 — Prepared statement for INSERT
        $sql = "INSERT INTO ratings 
                    (user_id, order_id, product_quality, customer_service, comment, created_at)
                VALUES
                    (:user_id, :order_id, :quality, :service, :comment, NOW())";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':user_id',  $user_id);
        $statement->bindValue(':order_id', $order_id);
        $statement->bindValue(':quality',  $product_quality);
        $statement->bindValue(':service',  $customer_service);
        $statement->bindValue(':comment',  $comment);

        if ($statement->execute()) {
            $pdo = null; // LISTING 14.15 — close connection
            header("Location: index.php?rating_success=1");
            exit;
        } else {
            $pdo = null;
            header("Location: review.php?error=db_error&order_id=$order_id");
            exit;
        }
    }
    catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        header("Location: review.php?error=db_error&order_id=$order_id");
        exit;
    }
}

// If not POST request
header("Location: index.php");
exit;
?>

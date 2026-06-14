<?php
/**
 * ============================================================================
 * FILE:  update_profile.php
 * ROLE:  Handles the "Edit Profile" form submitted from customer_panel.php.
 *        Updates first_name, last_name, phone, address, city, and country
 *        for the currently logged-in user, then redirects back to the
 *        customer panel with a success/error flag.
 *
 * SECURITY:
 *   • Top-of-file login check — non-users redirected to login.
 *   • Single prepared UPDATE statement (LISTING 14.17) — no SQL injection.
 *   • WHERE clause uses session user_id, so a user can ONLY update their
 *     own row even if they tamper with the form.
 * ============================================================================
 */
// Student Name: Zainab

session_start();
include 'db.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$user_id    = $_SESSION['user_id'];
$first_name = $_POST['first_name'] ?? '';
$last_name  = $_POST['last_name']  ?? '';
$phone      = $_POST['phone']      ?? '';
$address    = $_POST['address']    ?? '';
$city       = $_POST['city']       ?? '';
$country    = $_POST['country']    ?? '';

try {
    // LISTING 14.17 — Prepared statement for UPDATE
    $sql = "UPDATE users SET 
                first_name = :first_name,
                last_name  = :last_name,
                phone      = :phone,
                address    = :address,
                city       = :city,
                country    = :country
            WHERE user_id = :user_id";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':first_name', $first_name);
    $statement->bindValue(':last_name',  $last_name);
    $statement->bindValue(':phone',      $phone);
    $statement->bindValue(':address',    $address);
    $statement->bindValue(':city',       $city);
    $statement->bindValue(':country',    $country);
    $statement->bindValue(':user_id',    $user_id);

    if ($statement->execute()) {
        $pdo = null; // LISTING 14.15 — close connection
        header("Location: customer_panel.php?success=1");
        exit;
    } else {
        $pdo = null;
        header("Location: customer_panel.php?error=1");
        exit;
    }
}
catch (PDOException $e) {
    die($e->getMessage());
}
?>

<?php
/**
 * ============================================================================
 * FILE:  includes/header.php
 * ROLE:  Shared site header — included at the top of every public page.
 *        Provides:
 *          • <html>, <head>, fonts, and CSS link
 *          • The fixed top navigation (Home / Products / Contact + auth icons)
 *          • The cart badge with a live count of items in $_SESSION['cart']
 *
 * SESSION HANDLING:
 *   We call session_start() ONLY if a session isn't already active —
 *   this prevents "session already started" notices on pages that begin
 *   their own session before including this file.
 *
 * ROLE-AWARE NAV (LISTING 15.8 — check session before access):
 *   • Admin     → top-right shows Admin Panel + Logout (no cart icon).
 *   • Customer  → top-right shows My Account + Logout + cart.
 *   • Guest     → top-right shows Login + Register + cart.
 * ============================================================================
 */
// Student Name: Najd & Emtenan

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title> Pearl Luxury | Elegant Jewelry </title>

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
<?php include 'css/style.css'; ?>
</style>

</head>

<body>

<header class="site-header">

<nav class="main-nav">

<div class="nav-links-left">

<a href="index.php">Home</a>

<a href="index.php#products">Products</a>

<a href="contact.php">Contact</a>

</div>

<div class="nav-center-logo">

<a href="index.php">

<img src="images/logo.png" alt="Pearl Logo" class="nav-logo-img">

</a>

</div>

<div class="nav-links-right">

<?php
// Determine where the user icon should go based on session
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $userLinkHref  = 'admin_panel.php?tab=profile';
    $userLinkTitle = 'Admin Profile';
} else if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    $userLinkHref  = 'customer_panel.php';
    $userLinkTitle = 'My Account';
} else {
    $userLinkHref  = 'login.php';
    $userLinkTitle = 'Login';
}
?>

<a href="<?php echo $userLinkHref; ?>" class="nav-icon-link" title="<?php echo $userLinkTitle; ?>">
    <img src="images/user.png" class="nav-icon" alt="User Account">
</a>

<?php if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true): ?>

<a href="admin_panel.php" class="icon-link">Admin</a>

<a href="logout.php" class="icon-link">Logout</a>

<?php elseif(isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>

<a href="logout.php" class="icon-link">Logout</a>

<?php else: ?>

<a href="login.php" class="icon-link">Login</a>

<a href="register.php" class="icon-link">Register</a>

<?php endif; ?>

<?php if (!(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true)): ?>
<a href="cart.php" class="icon-link cart-link">

<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">

<path d="M6 6h15l-1.5 9h-13z"/>

<circle cx="9" cy="20" r="1"/>

<circle cx="18" cy="20" r="1"/>

</svg>

<?php if(isset($_SESSION['cart']) && count($_SESSION['cart'])>0): ?>

<span class="cart-badge"><?php echo count($_SESSION['cart']); ?></span>

<?php endif; ?>

</a>
<?php endif; ?>

</div>

</nav>

</header>

<main>
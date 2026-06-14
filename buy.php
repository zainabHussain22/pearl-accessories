<?php

session_start();
include 'db.php';

// LISTING 15.8 — check for existence of session value before accessing
// Block admins — they cannot purchase
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// Must be logged in to purchase
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);

try {
    // LISTING 14.17 — Fetch fresh user info from DB
    $sql = "SELECT username, email, address, city, country, phone FROM users WHERE user_id = :uid";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':uid', $user_id);
    $statement->execute();
    $user = $statement->fetch();

    if (!$user) {
        header("Location: login.php");
        exit;
    }

    $customer_name    = $user['username'];
    $customer_email   = $user['email'];
    $customer_address = ($user['address'] ?? '') . ', ' . ($user['city'] ?? '');

    // ---- STOCK CHECK: verify every item has enough stock BEFORE inserting order ----
    foreach ($_SESSION['cart'] as $item) {
        $sql = "SELECT stock, name FROM products WHERE product_id = :pid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':pid', $item['product_id']);
        $statement->execute();
        $prod = $statement->fetch();

        if (!$prod || $prod['stock'] < $item['quantity']) {
            $_SESSION['cart_error'] = 'Not enough stock for "' . htmlspecialchars($item['name']) . '". Only ' . ($prod['stock'] ?? 0) . ' available.';
            header("Location: cart.php");
            exit;
        }
    }

    // Calculate total
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['total'];
    }

    // ---- TRANSACTION: all-or-nothing order creation ----
    $pdo->beginTransaction();

    // LISTING 14.17 — Prepared statement for INSERT order
    $sql = "INSERT INTO orders (user_id, customer_name, customer_email, customer_address, total_amount)
            VALUES (:user_id, :name, :email, :address, :total)";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':user_id', $user_id);
    $statement->bindValue(':name',    $customer_name);
    $statement->bindValue(':email',   $customer_email);
    $statement->bindValue(':address', $customer_address);
    $statement->bindValue(':total',   $total_amount);
    $statement->execute();

    $order_id = $pdo->lastInsertId();

    // Loop through cart items
    foreach ($_SESSION['cart'] as $item) {
        $product_id = $item['product_id'];
        $quantity   = $item['quantity'];
        $price      = $item['price'];

        // LISTING 14.17 — Prepared statement for stock UPDATE
        $sql = "UPDATE products SET stock = stock - :qty WHERE product_id = :pid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':qty', $quantity);
        $statement->bindValue(':pid', $product_id);
        $statement->execute();

        // LISTING 14.17 — Prepared statement for INSERT order item
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (:oid, :pid, :qty, :price)";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':oid',   $order_id);
        $statement->bindValue(':pid',   $product_id);
        $statement->bindValue(':qty',   $quantity);
        $statement->bindValue(':price', $price);
        $statement->execute();
    }

    $pdo->commit();
}
catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error creating order: " . $e->getMessage());
}

// --- Save recent purchases to SESSION (clears on logout, user-specific) ---
if (!isset($_SESSION['past_purchases']) || !is_array($_SESSION['past_purchases'])) {
    $_SESSION['past_purchases'] = [];
}

foreach ($_SESSION['cart'] as $item) {
    // Add new purchase to the FRONT so newest shows first
    array_unshift($_SESSION['past_purchases'], [
        'name'     => $item['name'],
        'quantity' => $item['quantity'],
        'total'    => $item['total']
    ]);
}

// Keep only the last 5 purchases
if (count($_SESSION['past_purchases']) > 5) {
    $_SESSION['past_purchases'] = array_slice($_SESSION['past_purchases'], 0, 5);
}

// Clear cart
$_SESSION['cart'] = [];

$pdo = null; // LISTING 14.15 — close connection

// Redirect directly to order details page
header("Location: order_details.php?id=$order_id");
exit;
?>

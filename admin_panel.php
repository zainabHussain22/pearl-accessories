<?php

session_start();
include 'db.php';
include_once 'currency_helper.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$message = '';

/**
 * Helper: process a multi-file upload field.
 * Returns array of saved filenames.
 */
function processMultipleImages($field) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $saved   = [];

    if (!isset($_FILES[$field]) || empty($_FILES[$field]['name'][0])) {
        return $saved;
    }

    $count = count($_FILES[$field]['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($_FILES[$field]['error'][$i] !== 0) continue;

        $origName = basename($_FILES[$field]['name'][$i]);
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        // Unique filename: timestamp + index + random + original
        $newName = time() . '_' . $i . '_' . mt_rand(1000, 9999) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '', $origName);
        if (move_uploaded_file($_FILES[$field]['tmp_name'][$i], 'images/' . $newName)) {
            $saved[] = $newName;
        }
    }
    return $saved;
}

// ---- Add New Product ----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name     = $_POST['prod_name'];
    $desc     = $_POST['prod_desc'];
    $price    = floatval($_POST['prod_price']);
    $stock    = intval($_POST['prod_qty']);
    $category = $_POST['prod_category'];

    $uploaded    = processMultipleImages('prod_image');
    $imageString = implode(',', $uploaded);
    $adminId     = intval($_SESSION['admin_id']);

    try {
        // LISTING 14.17 — Prepared statement for INSERT
        $sql = "INSERT INTO products (name, description, price, stock, category, image, added_by)
                VALUES (:name, :desc, :price, :stock, :category, :image, :admin_id)";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':name',     $name);
        $statement->bindValue(':desc',     $desc);
        $statement->bindValue(':price',    $price);
        $statement->bindValue(':stock',    $stock);
        $statement->bindValue(':category', $category);
        $statement->bindValue(':image',    $imageString);
        $statement->bindValue(':admin_id', $adminId);
        $statement->execute();
        $message = '<div class="msg success">✅ Product added successfully! (' . count($uploaded) . ' image(s) uploaded)</div>';
    }
    catch (PDOException $e) {
        $message = '<div class="msg error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// ---- Delete Product ----
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    try {
        // LISTING 14.17 — Prepared statement for DELETE
        $sql = "DELETE FROM products WHERE product_id = :pid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':pid', $deleteId);
        $statement->execute();
        $message = '<div class="msg success">✅ Product deleted successfully!</div>';
    }
    catch (PDOException $e) {
        $message = '<div class="msg error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// ---- Modify Product ----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['modify_product'])) {
    $modId       = intval($_POST['mod_id']);
    $modName     = $_POST['mod_name'];
    $modDesc     = $_POST['mod_desc'];
    $modPrice    = floatval($_POST['mod_price']);
    $modStock    = intval($_POST['mod_qty']);
    $modCategory = $_POST['mod_category'];

    // 1) Images the admin chose to keep (existing ones not removed in the modal)
    $keptImages = [];
    if (isset($_POST['mod_existing_images']) && is_array($_POST['mod_existing_images'])) {
        foreach ($_POST['mod_existing_images'] as $img) {
            $img = trim($img);
            if ($img !== '') $keptImages[] = $img;
        }
    }

    // 2) New uploaded images
    $newImages = processMultipleImages('mod_image');

    // 3) Combine
    $allImages   = array_merge($keptImages, $newImages);
    $imageString = implode(',', $allImages);

    try {
        // LISTING 14.17 — Prepared statement for UPDATE
        $sql = "UPDATE products SET 
                    name = :name, description = :desc, price = :price,
                    stock = :stock, category = :category, image = :image
                WHERE product_id = :pid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':name',     $modName);
        $statement->bindValue(':desc',     $modDesc);
        $statement->bindValue(':price',    $modPrice);
        $statement->bindValue(':stock',    $modStock);
        $statement->bindValue(':category', $modCategory);
        $statement->bindValue(':image',    $imageString);
        $statement->bindValue(':pid',      $modId);
        $statement->execute();
        $message = '<div class="msg success">✅ Product updated successfully!</div>';
    }
    catch (PDOException $e) {
        $message = '<div class="msg error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// ---- Update Order Status ----
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order'])) {
    $orderId   = intval($_POST['order_id']);
    $newStatus = $_POST['order_status'];
    $adminId   = intval($_SESSION['admin_id']);

    try {
        // LISTING 14.17 — Get old status with prepared statement
        $sql = "SELECT status FROM orders WHERE order_id = :oid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':oid', $orderId);
        $statement->execute();
        $oldRow = $statement->fetch();
        $oldStatus = $oldRow['status'];

        // Update order status
        $sql = "UPDATE orders SET status = :status WHERE order_id = :oid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':status', $newStatus);
        $statement->bindValue(':oid',    $orderId);
        $statement->execute();

        // Insert into status log
        $sql = "INSERT INTO order_status_log (order_id, admin_id, old_status, new_status)
                VALUES (:oid, :aid, :old, :new)";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':oid', $orderId);
        $statement->bindValue(':aid', $adminId);
        $statement->bindValue(':old', $oldStatus);
        $statement->bindValue(':new', $newStatus);
        $statement->execute();

        $message = '<div class="msg success">✅ Order status updated!</div>';
    }
    catch (PDOException $e) {
        $message = '<div class="msg error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// ---- Delete Order ----
if (isset($_GET['delete_order'])) {
    $orderId = intval($_GET['delete_order']);
    try {
        // LISTING 14.17 — Prepared statements for DELETE
        $sql = "DELETE FROM order_items WHERE order_id = :oid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':oid', $orderId);
        $statement->execute();

        $sql = "DELETE FROM orders WHERE order_id = :oid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':oid', $orderId);
        $statement->execute();

        $message = '<div class="msg success">✅ Order deleted!</div>';
    }
    catch (PDOException $e) {
        $message = '<div class="msg error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

// ---- Fetch Products ----
$search = isset($_GET['search']) ? $_GET['search'] : '';
try {
    if ($search) {
        // LISTING 14.17 — Prepared statement with LIKE
        $sql = "SELECT * FROM products WHERE name LIKE :search OR category LIKE :search ORDER BY product_id DESC";
        $productsStmt = $pdo->prepare($sql);
        $productsStmt->bindValue(':search', '%' . $search . '%');
        $productsStmt->execute();
    } else {
        $productsStmt = $pdo->query("SELECT * FROM products ORDER BY product_id DESC");
    }
}
catch (PDOException $e) {
    die($e->getMessage());
}
// ──────── ADMIN PROFILE: password change + info fetch ────────
$profile_message      = '';
$profile_message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $perrors = [];

    // Verify current password matches DB
    try {
        // LISTING 14.17 — Prepared statement
        $sql = "SELECT password FROM admins WHERE admin_id = :aid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':aid', $_SESSION['admin_id']);
        $statement->execute();
        $row = $statement->fetch();

        if (!$row || !(password_verify($current_password, $row['password']) || $current_password === $row['password'])) {
            $perrors[] = "Current password is incorrect.";
        }
    } catch (PDOException $e) {
        $perrors[] = "Database error.";
    }

    // Validate new password (Ch 12 slide concepts only — for loop)
    if (empty($new_password)) {
        $perrors[] = "New password is required.";
    } else if (strlen($new_password) < 8) {
        $perrors[] = "New password must be at least 8 characters.";
    } else if ($new_password[0] >= '0' && $new_password[0] <= '9') {
        $perrors[] = "New password cannot start with a number.";
    } else {
        // LISTING 12.11 — for loop checking each character
        $hasUpper = false;
        $hasDigit = false;
        for ($i = 0; $i < strlen($new_password); $i++) {
            if ($new_password[$i] >= 'A' && $new_password[$i] <= 'Z') {
                $hasUpper = true;
            }
            if ($new_password[$i] >= '0' && $new_password[$i] <= '9') {
                $hasDigit = true;
            }
        }
        if ($hasUpper == false) {
            $perrors[] = "New password must contain at least one uppercase letter.";
        } else if ($hasDigit == false) {
            $perrors[] = "New password must contain at least one number.";
        }
    }

    if ($new_password != $confirm_password) {
        $perrors[] = "New password and confirmation do not match.";
    }

    if (empty($perrors)) {
        try {
            // Hash the new password before storing
            $hashedNewPassword = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE admins SET password = :pwd WHERE admin_id = :aid";
            $statement = $pdo->prepare($sql);
            $statement->bindValue(':pwd', $hashedNewPassword);
            $statement->bindValue(':aid', $_SESSION['admin_id']);
            $statement->execute();
            $profile_message      = "✅ Password updated successfully!";
            $profile_message_type = 'success';
        } catch (PDOException $e) {
            $profile_message      = "Failed to update password.";
            $profile_message_type = 'error';
        }
    } else {
        $profile_message      = implode("<br>", $perrors);
        $profile_message_type = 'error';
    }
}

// Fetch admin info for display
try {
    $sql = "SELECT * FROM admins WHERE admin_id = :aid";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':aid', $_SESSION['admin_id']);
    $statement->execute();
    $adminInfo = $statement->fetch();

    // Count products this admin added
    $sql = "SELECT COUNT(*) FROM products WHERE added_by = :aid";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':aid', $_SESSION['admin_id']);
    $statement->execute();
    $myProductsCount = $statement->fetchColumn();
} catch (PDOException $e) {
    $adminInfo = null;
    $myProductsCount = 0;
}

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'products';

include 'includes/header.php';
?>

<style>
.admin-wrapper {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px 60px;
    font-family: 'Georgia', serif;
}
.admin-topbar {
    background: linear-gradient(135deg, #3d1c02, #7a3b1e);
    color: #fff;
    padding: 18px 30px;
    border-radius: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(61,28,2,0.3);
}
.admin-topbar h1 { margin: 0; font-size: 1.5rem; letter-spacing: 1px; }
.admin-topbar span { font-size: 0.9rem; opacity: 0.85; }
.btn-logout {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.3);
    padding: 8px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.85rem;
    transition: background 0.2s;
}
.btn-logout:hover { background: rgba(255,255,255,0.25); }
.msg { padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; }
.msg.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.msg.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.admin-tabs { display: flex; gap: 4px; margin-bottom: 28px; border-bottom: 2px solid #e8ddd4; }
.tab-btn {
    padding: 11px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    font-size: 0.95rem;
    color: #888;
    font-family: 'Georgia', serif;
    transition: all 0.2s;
}
.tab-btn:hover { color: #7a3b1e; }
.tab-btn.active { color: #7a3b1e; border-bottom-color: #7a3b1e; font-weight: bold; }
.tab-panel { display: none; }
.tab-panel.active { display: block; }
.card {
    background: #fff;
    border: 1px solid #e8ddd4;
    border-radius: 12px;
    padding: 28px;
    margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.card h2 { margin: 0 0 22px; font-size: 1.15rem; color: #3d1c02; padding-bottom: 12px; border-bottom: 1px solid #e8ddd4; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid .full { grid-column: 1 / -1; }
.form-group label { display: block; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: #666; margin-bottom: 6px; }
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    max-width: 100%;
    padding: 10px 14px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 0.95rem;
    font-family: inherit;
    box-sizing: border-box;
    transition: border-color 0.2s;
    background: #fafafa;
}
.form-group textarea { resize: vertical; min-height: 80px; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus { outline: none; border-color: #b08d57; background: #fff; }
.error-text { color: #c0392b; font-size: 0.8rem; display: block; margin-top: 3px; }

/* ── Image Thumbnails ── */
.image-thumbs {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
    margin-bottom: 8px;
    min-height: 0;
}
.image-thumbs:empty { display: none; }
.img-thumb {
    position: relative;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: visible;
    border: 1px solid #e8ddd4;
    background: #fafafa;
}
.img-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 7px;
    display: block;
}
.img-thumb .remove-btn {
    position: absolute;
    top: -7px;
    right: -7px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #fff;
    background: #e74c3c;
    color: #fff;
    cursor: pointer;
    font-size: 11px;
    line-height: 1;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    transition: transform 0.15s;
}
.img-thumb .remove-btn:hover { transform: scale(1.15); background: #c0392b; }
.img-thumb .badge-main {
    position: absolute;
    bottom: 4px;
    left: 4px;
    background: rgba(176, 141, 87, 0.95);
    color: #fff;
    font-size: 9px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: bold;
    letter-spacing: 0.5px;
}
.upload-hint {
    font-size: 0.78rem;
    color: #888;
    margin-top: 4px;
    display: block;
}

/* Image count badge in product table */
.img-count-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    background: linear-gradient(135deg, #b08d57, #9a7a45);
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: bold;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.btn {
    padding: 9px 20px;
    border: none;
    border-radius: 22px;
    cursor: pointer;
    font-size: 0.88rem;
    font-weight: 600;
    font-family: inherit;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s ease;
}
.btn-primary  { background: #b08d57; color: #fff; }
.btn-primary:hover  { background: #9a7a45; }
.btn-success  { background: linear-gradient(135deg, #2ecc71, #27ae60); color: #fff; box-shadow: 0 2px 8px rgba(39,174,96,0.3); }
.btn-success:hover  { background: linear-gradient(135deg, #27ae60, #1e8449); transform: translateY(-1px); }
.btn-warning  { background: linear-gradient(135deg, #f39c12, #e67e22); color: #fff; box-shadow: 0 2px 8px rgba(230,126,34,0.35); }
.btn-warning:hover  { background: linear-gradient(135deg, #e67e22, #d35400); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(230,126,34,0.45); }
.btn-danger   { background: linear-gradient(135deg, #e74c3c, #c0392b); color: #fff; box-shadow: 0 2px 8px rgba(192,57,43,0.35); }
.btn-danger:hover   { background: linear-gradient(135deg, #c0392b, #922b21); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(192,57,43,0.45); }
.btn-sm { padding: 7px 16px; font-size: 0.82rem; }

.search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
.search-bar input { flex: 1; padding: 10px 16px; border: 1px solid #ddd; border-radius: 22px; font-size: 0.95rem; }
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #fff; border: 1px solid #e8ddd4; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.stat-card .stat-num { font-size: 2rem; font-weight: bold; color: #7a3b1e; line-height: 1; }
.stat-card .stat-label { font-size: 0.8rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 6px; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.data-table th { background: #f5ebe0; color: #3d1c02; padding: 12px 14px; text-align: left; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.5px; }
.data-table td { padding: 12px 14px; border-bottom: 1px solid #f0e8df; vertical-align: middle; }
.data-table tr:hover td { background: #fdf8f4; }
.data-table img { width: 50px; height: 50px; object-fit: cover; border-radius: 6px; }
.action-cell { display: flex; gap: 8px; align-items: center; flex-wrap: nowrap; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
.badge-pending   { background: #fff3cd; color: #856404; }
.badge-shipped   { background: #cce5ff; color: #004085; }
.badge-delivered { background: #d4edda; color: #155724; }
.badge-cancelled { background: #f8d7da; color: #721c24; }
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; }
.modal-overlay.open { display: flex; }
.modal-box { background: #fff; border-radius: 14px; padding: 32px; width: 100%; max-width: 700px; max-height: 90vh; overflow-y: auto; overflow-x: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.2); box-sizing: border-box; }
.modal-box h2 { margin: 0 0 20px; color: #3d1c02; font-size: 1.2rem; }
.modal-box .form-grid { width: 100%; }
.modal-box form { width: 100%; }
.modal-box textarea { resize: vertical; max-width: 100%; }
.modal-box input[type="file"] { max-width: 100%; }
.modal-close { float: right; background: none; border: none; font-size: 1.4rem; cursor: pointer; color: #888; line-height: 1; }
@media (max-width: 768px) {
    .form-grid { grid-template-columns: 1fr; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    .data-table { font-size: 0.8rem; }
}
</style>

<div class="admin-wrapper">

    <div class="admin-topbar">
        <div>
            <h1>⚙️ Admin Panel</h1>
            <span>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong></span>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <?php echo $message; ?>

    <?php
    try {
        // LISTING 14.9 — Executing SELECT queries with PDO
        $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

        // Check if orders table exists
        $tableCheck   = $pdo->query("SHOW TABLES LIKE 'orders'");
        $ordersExist  = $tableCheck->rowCount() > 0;

        $totalOrders   = $ordersExist ? $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() : 0;
        $pendingOrders = $ordersExist ? $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn() : 0;
        $totalRevenue  = $ordersExist ? ($pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders")->fetchColumn() ?? 0) : 0;
    }
    catch (PDOException $e) {
        die($e->getMessage());
    }
    ?>

    <div class="stats-row">
        <div class="stat-card"><div class="stat-num"><?php echo $totalProducts; ?></div><div class="stat-label">Products</div></div>
        <div class="stat-card"><div class="stat-num"><?php echo $totalOrders; ?></div><div class="stat-label">Total Orders</div></div>
        <div class="stat-card"><div class="stat-num"><?php echo $pendingOrders; ?></div><div class="stat-label">Pending</div></div>
        <div class="stat-card"><div class="stat-num">$<?php echo number_format($totalRevenue, 0); ?></div><div class="stat-label">Revenue</div></div>
    </div>

    <div class="admin-tabs">
        <button class="tab-btn <?php echo $activeTab=='products'?'active':''; ?>" onclick="switchTab('products', this)">📦 Products</button>
        <button class="tab-btn <?php echo $activeTab=='add'?'active':''; ?>"      onclick="switchTab('add', this)">➕ Add Product</button>
        <button class="tab-btn <?php echo $activeTab=='orders'?'active':''; ?>"   onclick="switchTab('orders', this)">🛒 Orders</button>
        <button class="tab-btn <?php echo $activeTab=='ratings'?'active':''; ?>"  onclick="switchTab('ratings', this)">⭐ Ratings</button>
        <button class="tab-btn <?php echo $activeTab=='profile'?'active':''; ?>"  onclick="switchTab('profile', this)">👤 Profile</button>
    </div>

    <!-- PRODUCTS TAB -->
    <div id="tab-products" class="tab-panel <?php echo $activeTab=='products'?'active':''; ?>">
    <div class="card">
        <h2>📦 All Products</h2>
        <form method="GET" class="search-bar">
            <input type="hidden" name="tab" value="products">
            <input type="text" name="search" placeholder="Search by name or category..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">🔍 Search</button>
            <?php if ($search): ?><a href="admin_panel.php?tab=products" class="btn btn-warning">✕ Clear</a><?php endif; ?>
        </form>
        <?php
        // LISTING 14.11 — Loop through results using fetch()
        $products = $productsStmt->fetchAll();
        if (count($products) > 0): ?>
        <table class="data-table">
            <thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($products as $prod): ?>
                <?php
                // Parse images for this product
                $prodImgs   = array_values(array_filter(array_map('trim', explode(',', $prod['image']))));
                $firstImg   = !empty($prodImgs) ? $prodImgs[0] : '';
                $imgCount   = count($prodImgs);
                ?>
            <tr>
                <td>
                    <div style="position:relative; display:inline-block;">
                        <img src="images/<?php echo htmlspecialchars($firstImg); ?>" onerror="this.src='https://via.placeholder.com/50x50/f5ebe0/b08d57?text=P'">
                        <?php if ($imgCount > 1): ?>
                            <span class="img-count-badge"><?php echo $imgCount; ?></span>
                        <?php endif; ?>
                    </div>
                </td>
                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                <td><?php echo htmlspecialchars($prod['category'] ?? '—'); ?></td>
                <td><?php echo formatPrice($prod['price']); ?></td>
                <td><?php echo intval($prod['stock']); ?></td>
                <td>
                    <div class="action-cell">
                        <button class="btn btn-warning btn-sm"
                                onclick='openEditModal(<?php echo json_encode([
                                    "id"          => intval($prod["product_id"]),
                                    "name"        => $prod["name"],
                                    "description" => $prod["description"],
                                    "price"       => floatval($prod["price"]),
                                    "stock"       => intval($prod["stock"]),
                                    "category"    => $prod["category"] ?? "",
                                    "images"      => $prodImgs
                                ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>Edit</button>
                        <a href="admin_panel.php?delete_id=<?php echo $prod['product_id']; ?>&tab=products" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:#888; text-align:center; padding:30px;">No products found.</p>
        <?php endif; ?>
    </div>
    </div>

    <!-- ADD PRODUCT TAB -->
    <div id="tab-add" class="tab-panel <?php echo $activeTab=='add'?'active':''; ?>">
        <div class="card">
            <h2>➕ Add New Product</h2>
            <form method="POST" enctype="multipart/form-data" id="addProductForm" onsubmit="return validateAddProduct()">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" name="prod_name" id="prod_name" placeholder="e.g. Classic Pearl Necklace" required>
                        <span id="prodNameError" class="error-text"></span>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="prod_category" id="prod_category" required>
                            <option value="">Select Category</option>
                            <option value="Necklaces">Necklaces</option>
                            <option value="Earrings">Earrings</option>
                            <option value="Bracelets">Bracelets</option>
                            <option value="Rings">Rings</option>
                            <option value="Anklets">Anklets</option>
                            <option value="Pendants">Pendants</option>
                        </select>
                        <span id="prodCategoryError" class="error-text"></span>
                    </div>
                    <div class="form-group">
                        <label>Price (SAR):</label>
                        <input type="number" name="prod_price" id="prod_price" step="0.01" min="0.01" placeholder="0.00" required>
                        <span id="prodPriceError" class="error-text"></span>
                    </div>
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="prod_qty" id="prod_qty" min="0" placeholder="0" required>
                        <span id="prodQtyError" class="error-text"></span>
                    </div>
                    <div class="form-group full">
                        <label>Description</label>
                        <textarea name="prod_desc" id="prod_desc" rows="3" placeholder="Product description..." required></textarea>
                        <span id="prodDescError" class="error-text"></span>
                    </div>
                    <div class="form-group full">
                        <label>Product Images <small style="font-weight:normal; text-transform:none; color:#888;">(you can select multiple — first one will be the main image)</small></label>
                        <input type="file" name="prod_image[]" id="prod_image" accept="image/*" multiple>
                        <small class="upload-hint">📌 Hold Ctrl (Windows) or Cmd (Mac) to select multiple files</small>
                        <div class="image-thumbs" id="prod_image_preview"></div>
                    </div>
                </div>
                <br>
                <button type="submit" name="add_product" class="btn btn-success">➕ Add Product</button>
            </form>
        </div>
    </div>

    <!-- ORDERS TAB -->
    <div id="tab-orders" class="tab-panel <?php echo $activeTab=='orders'?'active':''; ?>">
        <div class="card">
            <h2>🛒 Order Management</h2>
            <?php
            try {
                // LISTING 14.9 — Executing a SELECT query with PDO
                $ordersStmt2 = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
                $allOrders   = $ordersStmt2->fetchAll();
            }
            catch (PDOException $e) {
                $allOrders = [];
            }
            if (count($allOrders) > 0):
            ?>
            <table class="data-table">
                <thead><tr><th>#</th><th>Customer</th><th>Email</th><th>Total</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($allOrders as $order): ?>
                <tr>
                    <td>#<?php echo $order['order_id']; ?></td>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                    <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                    <td>
                        <?php
                        $s = $order['status'] ?? 'Pending';
                        $bc = match(strtolower($s)) {
                            'shipped' => 'badge-shipped', 'delivered' => 'badge-delivered',
                            'cancelled' => 'badge-cancelled', default => 'badge-pending'
                        };
                        ?>
                        <span class="badge <?php echo $bc; ?>"><?php echo htmlspecialchars($s); ?></span>
                    </td>
                    <td>
                        <div class="action-cell">
                            <form method="POST" style="display:inline-flex; gap:6px; align-items:center;">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <select name="order_status" style="padding:5px 8px; border:1px solid #ddd; border-radius:6px; font-size:0.8rem;">
                                    <option <?php if(($order['status']??'')=='Pending')   echo 'selected'; ?>>Pending</option>
                                    <option <?php if(($order['status']??'')=='Shipped')   echo 'selected'; ?>>Shipped</option>
                                    <option <?php if(($order['status']??'')=='Delivered') echo 'selected'; ?>>Delivered</option>
                                    <option <?php if(($order['status']??'')=='Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_order" class="btn btn-warning btn-sm">Update</button>
                            </form>
                            <a href="admin_panel.php?delete_order=<?php echo $order['order_id']; ?>&tab=orders" class="btn btn-danger btn-sm" onclick="return confirm('Delete this order?')">Delete</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p style="color:#888; text-align:center; padding:30px;">No orders yet.</p>
            <?php endif; ?>
        </div>
    </div>

<!-- RATINGS TAB -->
<div id="tab-ratings" class="tab-panel <?php echo $activeTab=='ratings'?'active':''; ?>">
    <div class="card">
        <h2>⭐ Customer Ratings & Reviews</h2>
        <?php
        try {
            // LISTING 14.9 — Executing a SELECT query with PDO
            $sql = "SELECT r.*, u.username, u.email FROM ratings r 
                    LEFT JOIN users u ON r.user_id = u.user_id 
                    ORDER BY r.created_at DESC";
            $ratingsStmt = $pdo->query($sql);
            $allRatings  = $ratingsStmt->fetchAll();
        }
        catch (PDOException $e) {
            $allRatings = [];
        }

        if (count($allRatings) > 0):
        ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Order ID</th>
                    <th>Quality</th>
                    <th>Service</th>
                    <th>Comments</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($allRatings as $rating): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($rating['username'] ?? 'Guest'); ?></strong><br>
                    <small style="color: #888;"><?php echo htmlspecialchars($rating['email'] ?? 'N/A'); ?></small>
                </td>
                <td>#<?php echo $rating['order_id']; ?></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 80px; height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                            <div style="width: <?php echo $rating['product_quality']; ?>%; height: 100%; background: linear-gradient(90deg, #ff4d4d, #ffb347, #4CAF50);"></div>
                        </div>
                        <span style="font-weight: bold; font-size: 0.9rem;"><?php echo $rating['product_quality']; ?>%</span>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div style="width: 80px; height: 8px; background: #f0f0f0; border-radius: 4px; overflow: hidden;">
                            <div style="width: <?php echo $rating['customer_service']; ?>%; height: 100%; background: linear-gradient(90deg, #ff4d4d, #ffb347, #4CAF50);"></div>
                        </div>
                        <span style="font-weight: bold; font-size: 0.9rem;"><?php echo $rating['customer_service']; ?>%</span>
                    </div>
                </td>
                <td>
                    <small><?php echo htmlspecialchars(substr($rating['comment'], 0, 50)); ?><?php echo strlen($rating['comment']) > 50 ? '...' : ''; ?></small>
                </td>
                <td><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="color:#888; text-align:center; padding:30px;">No ratings yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- PROFILE TAB -->
<div id="tab-profile" class="tab-panel <?php echo $activeTab=='profile'?'active':''; ?>">
    <div class="card">
        <h2>👤 Admin Profile</h2>

        <?php if ($profile_message != ''): ?>
            <div class="msg <?php echo htmlspecialchars($profile_message_type); ?>" style="padding:12px; border-radius:6px; margin-bottom:16px; <?php echo $profile_message_type=='success' ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;'; ?>">
                <?php echo $profile_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($adminInfo): ?>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
            <div style="padding:14px 18px; background:#faf6f0; border-left:3px solid #b08d57; border-radius:6px;">
                <div style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px;">Admin ID</div>
                <div style="font-size:18px; font-weight:600; color:#2a2a2a; margin-top:4px;">#<?php echo htmlspecialchars($adminInfo['admin_id']); ?></div>
            </div>
            <div style="padding:14px 18px; background:#faf6f0; border-left:3px solid #b08d57; border-radius:6px;">
                <div style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px;">Username</div>
                <div style="font-size:18px; font-weight:600; color:#2a2a2a; margin-top:4px;"><?php echo htmlspecialchars($adminInfo['username']); ?></div>
            </div>
            <div style="padding:14px 18px; background:#faf6f0; border-left:3px solid #b08d57; border-radius:6px;">
                <div style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px;">Member Since</div>
                <div style="font-size:18px; font-weight:600; color:#2a2a2a; margin-top:4px;">
                    <?php echo !empty($adminInfo['created_at']) ? date('M d, Y', strtotime($adminInfo['created_at'])) : 'N/A'; ?>
                </div>
            </div>
            <div style="padding:14px 18px; background:#faf6f0; border-left:3px solid #b08d57; border-radius:6px;">
                <div style="font-size:12px; color:#888; text-transform:uppercase; letter-spacing:1px;">Products Added</div>
                <div style="font-size:18px; font-weight:600; color:#2a2a2a; margin-top:4px;"><?php echo intval($myProductsCount); ?> products</div>
            </div>
        </div>
        <?php endif; ?>

        <h3 style="margin-top:20px;">🔒 Change Password</h3>
        <form method="POST" action="admin_panel.php?tab=profile" id="adminPwForm" style="max-width:600px;" novalidate>
            <input type="hidden" name="change_password" value="1">

            <div class="form-group" style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:6px; font-weight:600;">Current Password</label>
                <div style="position:relative;">
                    <input type="password" id="apw_current" name="current_password" required style="width:100%; padding:10px 44px 10px 12px; border:1px solid #ddd; border-radius:6px;">
                    <button type="button" onclick="toggleAdminPw('apw_current', this)" aria-label="Show password"
                            style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; padding:8px; color:#7A6250; display:flex; align-items:center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:6px; font-weight:600;">New Password</label>
                    <div style="position:relative;">
                        <input type="password" id="apw_new" name="new_password" required style="width:100%; padding:10px 44px 10px 12px; border:1px solid #ddd; border-radius:6px;">
                        <button type="button" onclick="toggleAdminPw('apw_new', this)" aria-label="Show password"
                                style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; padding:8px; color:#7A6250; display:flex; align-items:center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <span id="adminNewPwError" class="error-text" style="color:#7A2E2E; font-size:12px; display:block; margin-top:6px; min-height:14px;"></span>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label style="display:block; margin-bottom:6px; font-weight:600;">Confirm New Password</label>
                    <div style="position:relative;">
                        <input type="password" id="apw_confirm" name="confirm_password" required style="width:100%; padding:10px 44px 10px 12px; border:1px solid #ddd; border-radius:6px;">
                        <button type="button" onclick="toggleAdminPw('apw_confirm', this)" aria-label="Show password"
                                style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; padding:8px; color:#7A6250; display:flex; align-items:center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <span id="adminConfirmPwError" class="error-text" style="color:#7A2E2E; font-size:12px; display:block; margin-top:6px; min-height:14px;"></span>
                </div>
            </div>

            <!-- Password requirement chips -->
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 4px 0 20px 0;">
                <span class="pw-req" id="admin-req-length" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:#50412A; letter-spacing:0.3px; transition: all 0.2s;">
                    <span class="dot" style="width:6px; height:6px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                    8+ characters
                </span>
                <span class="pw-req" id="admin-req-upper" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:#50412A; letter-spacing:0.3px; transition: all 0.2s;">
                    <span class="dot" style="width:6px; height:6px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                    1 uppercase letter
                </span>
                <span class="pw-req" id="admin-req-digit" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:#50412A; letter-spacing:0.3px; transition: all 0.2s;">
                    <span class="dot" style="width:6px; height:6px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                    1 number
                </span>
                <span class="pw-req" id="admin-req-start" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:#50412A; letter-spacing:0.3px; transition: all 0.2s;">
                    <span class="dot" style="width:6px; height:6px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                    Doesn't start with number
                </span>
            </div>

            <button type="submit" class="btn btn-primary">🔒 Update Password</button>
        </form>
    </div>
</div>

<script>
// Toggle password visibility — swaps eye SVG to eye-off SVG
function toggleAdminPw(inputId, btn) {
    var input = document.getElementById(inputId);
    var isPwd = input.type === 'password';
    input.type = isPwd ? 'text' : 'password';
    btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
    btn.innerHTML = isPwd
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

// Live password requirement chips + submit validation
(function() {
    var form     = document.getElementById('adminPwForm');
    if (!form) return;

    var newPw    = document.getElementById('apw_new');
    var confPw   = document.getElementById('apw_confirm');

    var reqLen   = document.getElementById('admin-req-length');
    var reqUpper = document.getElementById('admin-req-upper');
    var reqDigit = document.getElementById('admin-req-digit');
    var reqStart = document.getElementById('admin-req-start');

    function setReq(chip, passed) {
        var dot = chip.querySelector('.dot');
        if (passed) {
            chip.style.background = 'rgba(78, 112, 64, 0.12)';
            chip.style.borderColor = 'rgba(78, 112, 64, 0.35)';
            chip.style.color = '#3B5530';
            dot.style.background = '#4E7040';
        } else {
            chip.style.background = 'rgba(176, 141, 87, 0.08)';
            chip.style.borderColor = 'rgba(176, 141, 87, 0.22)';
            chip.style.color = '#50412A';
            dot.style.background = '#7A6250';
        }
    }

    newPw.addEventListener('input', function() {
        var v = newPw.value;
        setReq(reqLen,   v.length >= 8);
        setReq(reqUpper, /[A-Z]/.test(v));
        setReq(reqDigit, /[0-9]/.test(v));
        setReq(reqStart, v.length > 0 && !/^[0-9]/.test(v));
    });

    // Submit-time validation
    form.addEventListener('submit', function(e) {
        var newVal   = newPw.value;
        var confVal  = confPw.value;
        var newErr   = document.getElementById('adminNewPwError');
        var confErr  = document.getElementById('adminConfirmPwError');
        var valid = true;

        newErr.textContent  = '';
        confErr.textContent = '';

        if (newVal.length < 8) {
            newErr.textContent = 'Password must be at least 8 characters.';
            valid = false;
        } else if (/^[0-9]/.test(newVal)) {
            newErr.textContent = 'Password cannot start with a number.';
            valid = false;
        } else if (!/[A-Z]/.test(newVal)) {
            newErr.textContent = 'Password must contain at least one uppercase letter.';
            valid = false;
        } else if (!/[0-9]/.test(newVal)) {
            newErr.textContent = 'Password must contain at least one number.';
            valid = false;
        }

        if (newVal !== confVal) {
            confErr.textContent = 'Passwords do not match.';
            valid = false;
        }

        if (!valid) {
            e.preventDefault();
        }
    });
})();
</script>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeEditModal()">✕</button>
        <h2>✏️ Edit Product</h2>
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateModifyProduct(this)">
            <input type="hidden" name="mod_id" id="mod_id">
            <div class="form-grid">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="mod_name" id="mod_name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="mod_category" id="mod_category" required>
                        <option value="Necklaces">Necklaces</option>
                        <option value="Earrings">Earrings</option>
                        <option value="Bracelets">Bracelets</option>
                        <option value="Rings">Rings</option>
                        <option value="Anklets">Anklets</option>
                        <option value="Pendants">Pendants</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (SAR)</label>
                    <input type="number" name="mod_price" id="mod_price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="mod_qty" id="mod_qty" required>
                </div>
                <div class="form-group full">
                    <label>Description</label>
                    <textarea name="mod_desc" id="mod_desc" rows="3" required></textarea>
                </div>

                <div class="form-group full">
                    <label>Current Images <small style="font-weight:normal; text-transform:none; color:#888;">(click ✕ to remove)</small></label>
                    <div class="image-thumbs" id="mod_current_images"></div>
                </div>

                <div class="form-group full">
                    <label>Add New Images <small style="font-weight:normal; text-transform:none; color:#888;">(optional — append to existing)</small></label>
                    <input type="file" name="mod_image[]" id="mod_image" accept="image/*" multiple>
                    <small class="upload-hint">📌 Hold Ctrl/Cmd to select multiple files</small>
                    <div class="image-thumbs" id="mod_image_preview"></div>
                </div>
            </div>
            <br>
            <button type="submit" name="modify_product" class="btn btn-warning">Save Changes</button>
            <button type="button" class="btn btn-danger" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>

<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

/* ── Image Preview for ADD form ── */
document.getElementById('prod_image').addEventListener('change', function(e) {
    const preview = document.getElementById('prod_image_preview');
    preview.innerHTML = '';
    const files = e.target.files;

    Array.from(files).forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const wrap = document.createElement('div');
            wrap.className = 'img-thumb';
            wrap.innerHTML = `
                <img src="${ev.target.result}" alt="">
                ${idx === 0 ? '<span class="badge-main">MAIN</span>' : ''}
            `;
            preview.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
});

/* ── Image Preview for EDIT form (new uploads) ── */
document.getElementById('mod_image').addEventListener('change', function(e) {
    const preview = document.getElementById('mod_image_preview');
    preview.innerHTML = '';
    const files = e.target.files;

    Array.from(files).forEach((file) => {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const wrap = document.createElement('div');
            wrap.className = 'img-thumb';
            wrap.innerHTML = `<img src="${ev.target.result}" alt="">`;
            preview.appendChild(wrap);
        };
        reader.readAsDataURL(file);
    });
});

/* ── Open Edit Modal — now receives an object with images array ── */
function openEditModal(data) {
    document.getElementById('mod_id').value    = data.id;
    document.getElementById('mod_name').value  = data.name;
    document.getElementById('mod_desc').value  = data.description;
    document.getElementById('mod_price').value = data.price;
    document.getElementById('mod_qty').value   = data.stock;

    const sel = document.getElementById('mod_category');
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].value === data.category) { sel.selectedIndex = i; break; }
    }

    // Render existing images as removable thumbs
    const container = document.getElementById('mod_current_images');
    container.innerHTML = '';

    if (Array.isArray(data.images) && data.images.length > 0) {
        data.images.forEach((imgName, idx) => {
            const wrap = document.createElement('div');
            wrap.className = 'img-thumb';
            wrap.innerHTML = `
                <img src="images/${imgName}" alt="" onerror="this.src='https://via.placeholder.com/80x80/f5ebe0/b08d57?text=Img'">
                <input type="hidden" name="mod_existing_images[]" value="${imgName}">
                ${idx === 0 ? '<span class="badge-main">MAIN</span>' : ''}
                <button type="button" class="remove-btn" title="Remove">✕</button>
            `;
            wrap.querySelector('.remove-btn').addEventListener('click', () => {
                wrap.remove();
                refreshMainBadges();
            });
            container.appendChild(wrap);
        });
    } else {
        container.innerHTML = '<small style="color:#888;">No images yet — upload some below.</small>';
    }

    // Reset new uploads preview
    document.getElementById('mod_image_preview').innerHTML = '';
    document.getElementById('mod_image').value = '';

    document.getElementById('editModal').classList.add('open');
}

/* Refresh which thumb shows the MAIN badge after a removal */
function refreshMainBadges() {
    const thumbs = document.querySelectorAll('#mod_current_images .img-thumb');
    thumbs.forEach((t, i) => {
        const existing = t.querySelector('.badge-main');
        if (existing) existing.remove();
        if (i === 0) {
            const b = document.createElement('span');
            b.className = 'badge-main';
            b.textContent = 'MAIN';
            t.appendChild(b);
        }
    });
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

function validateAddProduct() {
    var valid = true;
    [['prod_name','prodNameError','Name is required!'],['prod_desc','prodDescError','Description is required!'],['prod_category','prodCategoryError','Select a category!']].forEach(function(f) {
        document.getElementById(f[1]).textContent = '';
        if (!document.getElementById(f[0]).value.trim()) { document.getElementById(f[1]).textContent = f[2]; valid = false; }
    });
    var price = document.getElementById('prod_price').value;
    var qty   = document.getElementById('prod_qty').value;
    document.getElementById('prodPriceError').textContent = '';
    document.getElementById('prodQtyError').textContent = '';
    if (!price || price <= 0) { document.getElementById('prodPriceError').textContent = 'Enter a valid price!'; valid = false; }
    if (qty === '' || qty < 0) { document.getElementById('prodQtyError').textContent = 'Enter a valid quantity!'; valid = false; }
    return valid;
}

function validateModifyProduct(form) {
    var name = form.querySelector('[name="mod_name"]').value.trim();
    var price = form.querySelector('[name="mod_price"]').value;
    if (!name) { alert('Product name is required!'); return false; }
    if (price <= 0) { alert('Enter a valid price!'); return false; }
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>

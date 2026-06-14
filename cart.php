<?php

include 'includes/header.php';
include_once 'currency_helper.php';
// db.php is included via header.php — provides $pdo (LISTING 14.7 try/catch pattern)

// LISTING 15.8 — check for existence of session value before accessing
// Block admins — they cannot purchase
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo '<div class="message message-error" style="text-align:center; max-width:600px; margin:80px auto; padding:30px; background:#fff3cd; color:#856404; border:1px solid #ffe69c; border-radius:10px;">
            <h2>👁️ View-Only Access</h2>
            <p style="margin:14px 0;">Admin accounts cannot make purchases. You are only allowed to view products.</p>
            <a href="index.php" class="btn btn-primary">Back to Products</a>
          </div>';
    include 'includes/footer.php';
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/**
 * Helper: get the first image from a comma-separated list.
 */
function getFirstImage($imageField) {
    $list = array_filter(array_map('trim', explode(',', $imageField ?? '')));
    return !empty($list) ? reset($list) : '';
}

$message = '';

// Show stock-check error from buy.php (if redirected back due to insufficient stock)
if (isset($_SESSION['cart_error'])) {
    $message = '<div class="message message-error">⚠️ ' . $_SESSION['cart_error'] . '</div>';
    unset($_SESSION['cart_error']);
}

// Handle Delete Single Item
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $deleteId) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
            $message = '<div class="message message-success">✅ Item removed from cart.</div>';
            break;
        }
    }
}

// Handle Empty Cart (Delete All)
if (isset($_GET['empty'])) {
    $_SESSION['cart'] = [];
    $message = '<div class="message message-success">✅ Cart emptied successfully.</div>';
}

// Handle Update Quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $productId => $newQty) {
        $newQty = intval($newQty);
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $productId) {
                // Check stock
                // LISTING 14.17 — Prepared statement for stock check
                try {
                    $sql = "SELECT stock FROM products WHERE product_id = :pid";
                    $stockStmt = $pdo->prepare($sql);
                    $stockStmt->bindValue(':pid', $productId);
                    $stockStmt->execute();
                    $stockRow = $stockStmt->fetch();
                }
                catch (PDOException $e) {
                    die($e->getMessage());
                }
                
                if ($newQty <= 0) {
                    $message = '<div class="message message-error">Quantity must be at least 1!</div>';
                } elseif ($newQty > $stockRow['stock']) {
                    $message = '<div class="message message-error">Only ' . $stockRow['stock'] . ' of ' . $item['name'] . ' available!</div>';
                } else {
                    $item['quantity'] = $newQty;
                    $item['total'] = $newQty * $item['price'];
                    $message = '<div class="message message-success">✅ Cart updated!</div>';
                }
                break;
            }
        }
        unset($item);
    }
}
?>

<h1 class="page-title">🛒 Shopping Cart</h1>

<?php echo $message; ?>

<?php if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])): ?>
    <div class="message" style="text-align:center; padding:50px;">
        <h2>Your cart is empty! 🛒</h2>
        <p>Browse our beautiful pearl collection.</p>
        <br>
        <a href="index.php" class="btn btn-primary">🦪 Shop Now</a>
    </div>
<?php else: ?>
    <form method="POST" id="cartForm" onsubmit="return validateCartUpdate()">
        <table class="cart-table" aria-label="Shopping Cart Items">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $grandTotal = 0;
                foreach ($_SESSION['cart'] as $item): 
                    $grandTotal += $item['total'];
                    $itemImage  = getFirstImage($item['image']); // ← Take first image only
                ?>
                <tr>
                    <td>
                        <img src="images/<?php echo htmlspecialchars($itemImage); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             style="width:60px; height:60px; object-fit:cover; border-radius:5px;"
                             onerror="this.src='https://via.placeholder.com/60x60?text=Pearl'">
                    </td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo formatPrice($item['price']); ?></td>
                    <td>
                        <input type="number" name="quantities[<?php echo $item['product_id']; ?>]" 
                               value="<?php echo $item['quantity']; ?>" 
                               min="1" class="quantity-input"
                               aria-label="Quantity for <?php echo htmlspecialchars($item['name']); ?>">
                    </td>
                    <td><?php echo formatPrice($item['total']); ?></td>
                    <td>
                        <a href="cart.php?delete=<?php echo $item['product_id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Remove this item?')"
                           aria-label="Delete <?php echo htmlspecialchars($item['name']); ?>">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-total">
            Grand Total: <?php echo formatPrice($grandTotal); ?>
        </div>

        <div style="margin-top:20px; text-align:right;">
            <button type="submit" name="update_cart" class="btn btn-warning">🔄 Update Cart</button>
            <a href="cart.php?empty=1" class="btn btn-danger" onclick="return confirm('Empty entire cart?')">🗑️ Empty Cart</a>
            <button type="button" class="btn btn-success" onclick="openCheckoutModal()">💰 Buy Now</button>
            <a href="index.php" class="btn btn-primary">🦪 Continue Shopping</a>
        </div>
    </form>
<?php endif; ?>

<!-- CHECKOUT CONFIRMATION MODAL -->
<div class="modal-overlay" id="checkoutModal">
    <div class="modal-box">
        <h2 style="margin: 0 0 24px 0; text-align: center; color: #2C1F0E;">🛍️ Confirm Order</h2>
        
        <div style="background: linear-gradient(135deg, #f5ebe0, #e8dfd2); padding: 20px; border-radius: 8px; margin-bottom: 24px;">
            <p style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #2C1F0E;">Are you sure you want to purchase these items?</p>
            
            <!-- Cart Items Summary in Modal -->
            <div style="max-height: 300px; overflow-y: auto; margin-bottom: 16px;">
                <?php 
                foreach ($_SESSION['cart'] as $item):
                ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(176,141,87,0.2);">
                    <div>
                        <p style="margin: 0; font-weight: 600; color: #2C1F0E;"><?php echo htmlspecialchars($item['name']); ?></p>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #7A6250;">Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></p>
                    <p style="margin: 0; font-weight: 600; color: #8A6B3A;"><?php echo formatPrice($item['total']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Total -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-top: 2px solid #B08D57; border-bottom: 2px solid #B08D57;">
                <p style="margin: 0; font-size: 16px; font-weight: 600; color: #2C1F0E;">Total Amount:</p>
                <p style="margin: 0; font-size: 20px; font-weight: 700; color: #8A6B3A;"><?php echo formatPrice($grandTotal); ?></p>
            </div>
        </div>

        <div style="display: flex; gap: 12px; margin-bottom: 0;">
            <form method="POST" action="buy.php" style="flex: 1;">
                <button type="submit" class="btn btn-success" style="width: 100%;">✅ Yes, Finalize the Order</button>
            </form>
            <button type="button" class="btn btn-warning" onclick="closeCheckoutModal()" style="flex: 1;">❌ No, Keep Shopping</button>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(30, 18, 9, 0.62);
    backdrop-filter: blur(5px);
    z-index: 2000;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease-out;
}

.modal-overlay.active {
    display: flex;
}

.modal-box {
    background: var(--color-ivory);
    padding: 32px;
    max-width: 500px;
    width: 90%;
    border-top: 3px solid #B08D57;
    box-shadow: 0 20px 60px rgba(30, 18, 9, 0.35);
    animation: slideUp 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    border-radius: 8px;
}

@keyframes slideUp {
    from { 
        opacity: 0; 
        transform: translateY(30px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<script>
function openCheckoutModal() {
    document.getElementById('checkoutModal').classList.add('active');
}

function closeCheckoutModal() {
    document.getElementById('checkoutModal').classList.remove('active');
}

// Close modal when clicking outside
document.getElementById('checkoutModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCheckoutModal();
    }
});

// Task 13: Validate cart update
function validateCartUpdate() {
    var inputs = document.querySelectorAll('.quantity-input');
    for (var i = 0; i < inputs.length; i++) {
        if (inputs[i].value <= 0 || inputs[i].value === '') {
            alert('Quantity must be at least 1!');
            inputs[i].focus();
            return false;
        }
    }
    return true;
}
</script>

<?php include 'includes/footer.php'; ?>

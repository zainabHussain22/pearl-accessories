<?php

session_start();
include 'db.php';
include_once 'currency_helper.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] == true) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}


$user_id  = intval($_SESSION['user_id']);
$order_id = intval($_GET['id']);

/**
 * Helper: get the first image from a comma-separated list.
 */
function getFirstImage($imageField) {
    $list = array_filter(array_map('trim', explode(',', $imageField ?? '')));
    return !empty($list) ? reset($list) : '';
}

// ---- Fetch order with ownership check (Prepared Statement - Slide 28) ----
$sql = "SELECT * FROM orders WHERE order_id = :oid AND user_id = :uid";
$statement = $pdo->prepare($sql);
$statement->bindValue(':oid', $order_id);
$statement->bindValue(':uid', $user_id);
$statement->execute();
$order = $statement->fetch();

if (!$order) {
    echo '<div style="max-width: 1100px; margin: 50px auto; padding: 40px; text-align: center; background: #fff3cd; border-left: 4px solid #ffc107;">
            <h2 style="color: #856404;">⚠️ Order Not Found!</h2>
            <p style="color: #856404; margin: 10px 0;">This order does not exist or you do not have permission to view it.</p>
            <a href="customer_panel.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #ffc107; color: #000; text-decoration: none; border-radius: 5px;">Back to Orders</a>
          </div>';
    exit;
}

// ---- Fetch order items (Prepared Statement - Slide 28) ----
$sql = "SELECT oi.*, p.name, p.image
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id = :oid";
$statement = $pdo->prepare($sql);
$statement->bindValue(':oid', $order_id);
$statement->execute();
$items = $statement->fetchAll();

// ---- Fetch user info ONCE (used both in HTML and JavaScript) ----
$sql = "SELECT country, city, address FROM users WHERE user_id = :uid";
$statement = $pdo->prepare($sql);
$statement->bindValue(':uid', $user_id);
$statement->execute();
$userInfo = $statement->fetch();

$country = (!empty($userInfo['country'])) ? $userInfo['country'] : 'Not provided';
$city    = (!empty($userInfo['city']))    ? $userInfo['city']    : 'Not provided';
$address = (!empty($userInfo['address'])) ? $userInfo['address'] : 'Not provided';

include 'includes/header.php';
?>

<div class="order-details-wrapper">
    <div class="order-details-container">

        <!-- HEADER -->
        <div class="order-header">
            <div>
                <h1>Order #<?php echo $order['order_id']; ?></h1>
                <p class="order-date">📅 Placed on <?php echo date('M d, Y \a\t H:i', strtotime($order['order_date'])); ?></p>
            </div>
            <div class="order-status-badge">
                <?php
                $status = $order['status'] ?? 'Pending';
                $badgeClass = 'badge-pending';
                $statusEmoji = '⏳';

                if (strtolower($status) === 'shipped') {
                    $badgeClass = 'badge-shipped';
                    $statusEmoji = '🚚';
                } elseif (strtolower($status) === 'delivered') {
                    $badgeClass = 'badge-delivered';
                    $statusEmoji = '✅';
                } elseif (strtolower($status) === 'cancelled') {
                    $badgeClass = 'badge-cancelled';
                    $statusEmoji = '❌';
                }
                ?>
                <span class="badge <?php echo $badgeClass; ?>"><?php echo $statusEmoji; ?> <?php echo htmlspecialchars($status); ?></span>
            </div>
        </div>

        <!-- ORDER INFO GRID -->
        <div class="order-info-grid">
            <div class="info-card">
                <h3>📦 Order Information</h3>
                <div class="info-row">
                    <span class="info-label">Order ID:</span>
                    <span class="info-value">#<?php echo $order['order_id']; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Total Amount:</span>
                    <span class="info-value" style="font-size: 18px; font-weight: bold; color: var(--color-gold);"><?php echo formatPrice($order['total_amount']); ?></span>
                </div>
            </div>

            <div class="info-card">
                <h3>🏠 Customer Information</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Address:</span>
                    <span class="info-value">
                        <?php echo htmlspecialchars($country . ', ' . $city . ', ' . $address); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- ORDER ITEMS -->
        <div class="order-items">
            <h2>📋 Items Ordered</h2>

            <?php if (count($items) > 0): ?>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product Image</th>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $subtotal = 0;
                        foreach ($items as $item):
                            $itemSubtotal = $item['quantity'] * $item['price'];
                            $subtotal += $itemSubtotal;
                            $itemImage  = getFirstImage($item['image']); // ← Take first image only
                        ?>
                        <tr>
                            <td>
                                <img src="images/<?php echo htmlspecialchars($itemImage); ?>"
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/80x80/f5ebe0/b08d57?text=Product'">
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td style="text-align: center;"><?php echo $item['quantity']; ?></td>
                            <td style="text-align: right;"><?php echo formatPrice($item['price']); ?></td> 
                            <td style="text-align: right; font-weight: bold;"><?php echo formatPrice($itemSubtotal); ?></td>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- ORDER SUMMARY -->
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotalValue"><?php echo formatPrice($subtotal); ?></span>
                    <div class="summary-row">
                        <span>Tax (5%):</span>
                        <span id="taxValue"><?php echo formatPrice($subtotal * 0.05); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span id="totalValue"><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                </div>

            <?php else: ?>
                <div style="text-align: center; padding: 30px; background: var(--color-ivory-warm); border-radius: 8px;">
                    <p style="color: #888;">No items found in this order.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ORDER ACTIONS -->
        <div class="order-actions">
            <button onclick="printReceiptOD()" class="btn btn-gold" style="flex: 1;">🖨️ Print Receipt</button>
            <a href="review.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-success" style="flex: 1;">⭐ Rate Your Experience</a>
            <a href="index.php" class="btn btn-primary" style="flex: 1;">🛍️ Continue Shopping</a>
        </div>

    </div>
</div>

<script>
function printReceiptOD() {
    // Get values
    var orderDate = '<?php echo date('F d, Y', strtotime($order['order_date'])); ?>';
    var orderId = '<?php echo $order_id; ?>';
    var customerName = '<?php echo addslashes(htmlspecialchars($order['customer_name'])); ?>';
    var customerEmail = '<?php echo addslashes(htmlspecialchars($order['customer_email'])); ?>';
    var customerAddress = '<?php echo addslashes($country . ', ' . $city . ', ' . $address); ?>';

    // Extract items from table
    var itemsHTML = '';
    var itemsTable = document.querySelector('.items-table tbody');

    if (itemsTable) {
        var rows = itemsTable.querySelectorAll('tr');
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            if (cells.length >= 5) {
                var imageURL = cells[0].querySelector('img') ? cells[0].querySelector('img').src : '';
                var name = cells[1] ? cells[1].textContent.trim() : '';
                var qty = cells[2] ? cells[2].textContent.trim() : '';
                var price = cells[3] ? cells[3].textContent.trim() : '';
                var subtotal = cells[4] ? cells[4].textContent.trim() : '';

                itemsHTML += '<div class="bill-item">' +
                    '<div class="bill-item-image">' +
                        '<img src="' + imageURL + '" alt="' + name + '" onerror="this.src=\'https://via.placeholder.com/60x60?text=Product\'">' +
                    '</div>' +
                    '<div class="bill-item-details">' +
                        '<div class="bill-item-name">' + name + '</div>' +
                        '<div class="bill-item-qty">Qty: ' + qty + ' x ' + price + '</div>' +
                    '</div>' +
                    '<div class="bill-item-price">' +
                        '<div class="bill-item-amount">' + subtotal + '</div>' +
                    '</div>' +
                '</div>';
            }
        });
    }

    // Get totals
    var subtotalValue = document.getElementById('subtotalValue').textContent;
    var totalValue = document.getElementById('totalValue').textContent;

    // Build CSS
    var styles = `
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Jost", sans-serif; background: #fff; padding: 40px; color: #2C1F0E; }

        .currency-icon {
            height: 1.1em !important;      /* Forces it to perfectly scale with the text height */
            width: auto !important;        /* Keeps the icon from getting squished */
            vertical-align: middle;
            margin-right: 3px;
            display: inline-block !important;
        }

        .bill-popup { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .bill-content { width: 100%; max-width: 500px; padding: 40px; border: 1px solid #E6DEC9; background: #FFFDF9; position: relative; }
        .bill-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 30px; border-bottom: 2px solid #EDD89A; }
        .bill-logo { font-size: 32px; font-weight: 700; letter-spacing: 2px; color: #8A6B3A; }
        .bill-header-info { text-align: right; }
        .bill-header-date { font-size: 12px; color: #7A6250; margin-bottom: 5px; }
        .bill-header-order { font-size: 13px; color: #2C1F0E; font-weight: 500; }
        .bill-header-order span { font-weight: 700; color: #8A6B3A; }
        .bill-greeting { text-align: right; margin-bottom: 30px; }
        .bill-greeting h3 { font-size: 14px; font-weight: 600; color: #2C1F0E; margin-bottom: 5px; }
        .bill-greeting p { font-size: 12px; color: #7A6250; margin: 0; }
        .bill-items { margin-bottom: 40px; }
        .bill-item { display: flex; gap: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #f0e8df; }
        .bill-item:last-child { border-bottom: none; }
        .bill-item-image { width: 80px; height: 80px; background: #FAF7F2; border-radius: 4px; border: 1px solid #EDD89A; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; }
        .bill-item-image img { width: 100%; height: 100%; object-fit: cover; }
        .bill-item-details { flex: 1; }
        .bill-item-name { font-size: 15px; font-weight: 600; color: #2C1F0E; margin-bottom: 5px; }
        .bill-item-qty { font-size: 12px; color: #7A6250; }
        .bill-item-price { text-align: right; }
        .bill-item-amount { font-size: 15px; font-weight: 600; color: #8A6B3A; }
        .bill-summary { display: flex; justify-content: space-between; margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px solid #EDD89A; }
        .bill-summary-label { font-size: 13px; color: #7A6250; }
        .bill-summary-value { font-size: 13px; color: #2C1F0E; font-weight: 500; }
        .bill-total { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .bill-total-label { font-size: 14px; font-weight: 600; color: #2C1F0E; }
        .bill-total-amount { font-size: 28px; font-weight: 700; color: #8A6B3A; }
        .bill-customer-info { margin-top: 40px; padding-top: 25px; border-top: 2px solid #EDD89A; }
        .bill-customer-title { font-size: 13px; font-weight: 600; color: #2C1F0E; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px; }
        .bill-customer-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0e8df; font-size: 12px; }
        .bill-customer-row:last-child { border-bottom: none; }
        .bill-customer-label { color: #7A6250; font-weight: 500; }
        .bill-customer-value { color: #2C1F0E; font-weight: 500; text-align: right; }
        @media print { body { padding: 0; } .bill-content { box-shadow: none; } }
    `;

    // Build HTML
   var receiptHTML = `
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Order Receipt #${orderId}</title>
            <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
            <style>${styles}</style>
        </head>
        <body>
            <div class="bill-popup">
                <div class="bill-content">
                    <div class="bill-header">
                        <div class="bill-logo">Pearl</div>
                        <div class="bill-header-info">
                            <div class="bill-header-date">${orderDate}</div>
                            <div class="bill-header-order">Order <span>#${orderId}</span></div>
                        </div>
                    </div>

                    <div class="bill-greeting">
                        <h3>Hi, ${customerName}!</h3>
                        <p>Thanks for your order!</p>
                    </div>

                    <div class="bill-items">
                        ${itemsHTML}
                    </div>

                    <div class="bill-summary">
                        <span class="bill-summary-label">Subtotal</span>
                        <span class="bill-summary-value"><img src="images/SAR.webp" class="currency-icon" alt="SAR"> ${parseFloat(subtotalValue.replace(/[^0-9.]/g, '')).toFixed(2)}</span>
                    </div>

                    <div class="bill-total">
                        <span class="bill-total-label">Total</span>
                        <span class="bill-total-amount"><img src="images/SAR.webp" class="currency-icon" alt="SAR"> ${parseFloat(totalValue.replace(/[^0-9.]/g, '')).toFixed(2)}</span>
                    </div>

                    <div class="bill-customer-info">
                        <div class="bill-customer-title">Customer Information</div>
                        <div class="bill-customer-row">
                            <span class="bill-customer-label">Name:</span>
                            <span class="bill-customer-value">${customerName}</span>
                        </div>
                        <div class="bill-customer-row">
                            <span class="bill-customer-label">Email:</span>
                            <span class="bill-customer-value">${customerEmail}</span>
                        </div>
                        <div class="bill-customer-row">
                            <span class="bill-customer-label">Address:</span>
                            <span class="bill-customer-value">${customerAddress}</span>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
    `;

    // Create a hidden iframe
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    document.body.appendChild(iframe);

    // Write content to iframe
    iframe.contentDocument.open();
    iframe.contentDocument.write(receiptHTML);
    iframe.contentDocument.close();

    // Print the iframe
    iframe.contentWindow.print();

    // Remove iframe after printing
    setTimeout(function() {
        document.body.removeChild(iframe);
    }, 1000);
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
/**
 * ============================================================================
 * FILE:  customer_panel.php
 * ROLE:  Authenticated user dashboard. Three tabs:
 *          1. Profile  — read-only view of account info.
 *          2. Orders   — history of all the user's orders with status badges.
 *          3. Settings — edit profile + change password.
 *
 * ACCESS CONTROL:
 *   Top-of-file check `user_logged_in` — non-users redirected to login.
 *
 * CHANGE PASSWORD FEATURE (LISTING 12.11 + LISTING 14.17):
 *   • Server verifies the CURRENT password with password_verify() (falls
 *     back to plain text for legacy accounts).
 *   • New password is checked against the same rules as register.php:
 *     ≥ 8 chars, ≥ 1 uppercase, ≥ 1 digit, doesn't start with a digit.
 *   • New password is hashed with password_hash() before UPDATE.
 *   • Confirmation field must match.
 *
 * UX TOUCHES:
 *   • Live "requirement chips" turn green as the user types.
 *   • Eye toggle to show/hide each password field.
 *   • Inline error messages instead of alert popups.
 * ============================================================================
 */
// Student Name: Lujain

session_start();
include 'db.php';
include_once 'currency_helper.php';

if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ──────── PASSWORD CHANGE HANDLER (mirrors admin_panel.php pattern) ────────
$pwd_message      = '';
$pwd_message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $perrors = [];

    // Verify current password matches DB
    try {
        // LISTING 14.17 — Prepared statement
        $sql = "SELECT password FROM users WHERE user_id = :uid";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':uid', $user_id);
        $statement->execute();
        $row = $statement->fetch();

        // password_verify handles both hashed and (legacy) plain-text passwords
        if (!$row || !(password_verify($current_password, $row['password']) || $current_password === $row['password'])) {
            $perrors[] = "Current password is incorrect.";
        }
    } catch (PDOException $e) {
        $perrors[] = "Database error.";
    }

    // Validate new password (Ch 12 slide concepts — for loop, character comparison)
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

            $sql = "UPDATE users SET password = :pwd WHERE user_id = :uid";
            $statement = $pdo->prepare($sql);
            $statement->bindValue(':pwd', $hashedNewPassword);
            $statement->bindValue(':uid', $user_id);
            $statement->execute();
            $pwd_message      = "✅ Password updated successfully!";
            $pwd_message_type = 'success';
        } catch (PDOException $e) {
            $pwd_message      = "Failed to update password.";
            $pwd_message_type = 'error';
        }
    } else {
        $pwd_message      = implode("<br>", $perrors);
        $pwd_message_type = 'error';
    }
}

try {
    // LISTING 14.17 — Prepared statement for user
    $sql = "SELECT * FROM users WHERE user_id = :uid";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':uid', $user_id);
    $statement->execute();
    $user = $statement->fetch();

    if (!$user) {
        die("User not found");
    }

    // LISTING 14.17 — Prepared statement for orders
    $sql = "SELECT * FROM orders WHERE user_id = :uid ORDER BY order_date DESC";
    $ordersStmt = $pdo->prepare($sql);
    $ordersStmt->bindValue(':uid', $user_id);
    $ordersStmt->execute();
}
catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="customer-panel-wrapper">
    <div class="customer-panel-container">
        
        <div class="customer-tabs">
            <button class="tab-btn active" onclick="switchCustomerTab('profile', this)">👤 My Profile</button>
            <button class="tab-btn" onclick="switchCustomerTab('orders', this)">📦 My Orders</button>
            <button class="tab-btn" onclick="switchCustomerTab('settings', this)">⚙️ Settings</button>
        </div>

        <!-- ===== TAB 1: PROFILE ===== -->
        <div id="tab-profile" class="customer-tab-content active">
            <div class="profile-card">
                <h2>Account Information</h2>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="message message-success">✅ Profile updated successfully!</div>
                <?php endif; ?>
                
                <?php if (isset($_GET['error'])): ?>
                    <div class="message message-error">❌ Error updating profile. Please try again.</div>
                <?php endif; ?>
                
                <div class="profile-grid">
                    <div class="profile-field">
                        <label>Username:</label>
                        <p><?php echo htmlspecialchars($user['username'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Email:</label>
                        <p><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>First Name:</label>
                        <p><?php echo htmlspecialchars($user['first_name'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Last Name:</label>
                        <p><?php echo htmlspecialchars($user['last_name'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Phone:</label>
                        <p><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>City:</label>
                        <p><?php echo htmlspecialchars($user['city'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Country:</label>
                        <p><?php echo htmlspecialchars($user['country'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="profile-field">
                        <label>Member Since:</label>
                        <p><?php echo !empty($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                    </div>
                </div>
                
                <div class="profile-field full-width">
                    <label>Address:</label>
                    <p><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></p>
                </div>
                
                <button class="btn btn-primary" style="margin-top: 20px;" onclick="switchCustomerTab('settings', document.querySelectorAll('.tab-btn')[2])">✏️ Edit Profile</button>
            </div>
        </div>

        <!-- ===== TAB 2: ORDERS ===== -->
        <div id="tab-orders" class="customer-tab-content">
            <div class="orders-card">
                <h2>Your Order History</h2>
                
                <?php
                // LISTING 14.11 — Loop through results using fetch()
                $orders = $ordersStmt->fetchAll();
                if (count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td><strong><?php echo formatPrice($order['total_amount']); ?></strong></td>
                                <td>
                                    <?php
                                    $status = $order['status'] ?? 'Pending';
                                    $badgeClass = 'badge-pending';
                                    
                                    if (strtolower($status) === 'shipped') {
                                        $badgeClass = 'badge-shipped';
                                    } elseif (strtolower($status) === 'delivered') {
                                        $badgeClass = 'badge-delivered';
                                    } elseif (strtolower($status) === 'cancelled') {
                                        $badgeClass = 'badge-cancelled';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span>
                                </td>
                            <!-- In the orders table action buttons -->
                            <td style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                                <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-gold" style="flex: 1; min-width: 120px; text-align: center;">📋 View Details</a>
    
                                <?php if (strtolower($order['status'] ?? 'pending') !== 'cancelled'): ?>
                                <a href="review.php?order_id=<?php echo intval($order['order_id']); ?>" class="btn btn-success" style="flex: 1; min-width: 120px; text-align: center;">⭐ Rate Order</a>
                                <?php endif; ?>
                            </td> 
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: var(--color-ivory-warm); border-radius: 8px;">
                        <p style="color: #888; font-size: 16px; margin-bottom: 16px;">📦 No orders yet</p>
                        <a href="index.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== TAB 3: SETTINGS ===== -->
        <div id="tab-settings" class="customer-tab-content">
            <div class="settings-card">
                <h2>Edit Profile</h2>
                <form method="POST" action="update_profile.php" class="profile-form">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" placeholder="Enter your first name">
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" placeholder="Enter your last name">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="Your phone number">
                        </div>
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="Your city">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                        <div class="form-group">
                            <label>Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" placeholder="Your country">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" rows="3" placeholder="Your full address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 24px;">
                        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                        <button type="button" class="btn btn-warning" onclick="switchCustomerTab('profile', document.querySelectorAll('.tab-btn')[0])">Cancel</button>
                    </div>
                </form>

                <!-- ===== CHANGE PASSWORD SECTION ===== -->
                <div style="margin-top: 48px;">
                    <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 8px;">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--color-gold), var(--color-gold-dark)); display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(176, 141, 87, 0.25);">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </div>
                        <h2 style="margin: 0; padding: 0; border: none;">Change Password</h2>
                    </div>
                    <p style="color: var(--color-brown-soft); font-size: 14px; margin: 0 0 20px 58px; font-style: italic;">
                        Keep your account secure with a strong password.
                    </p>

                    <?php if ($pwd_message != ''): ?>
                        <div style="padding:14px 18px; border-radius:8px; margin-bottom:18px; font-size:14px; <?php echo $pwd_message_type=='success' ? 'background:linear-gradient(135deg, #e8f5e8, #d4edda); color:#155724; border-left:4px solid #4E7040;' : 'background:linear-gradient(135deg, #fdeaea, #f8d7da); color:#721c24; border-left:4px solid #7A2E2E;'; ?>">
                            <?php echo $pwd_message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="customer_panel.php" class="profile-form" id="changePasswordForm" novalidate>
                        <input type="hidden" name="change_password" value="1">

                        <div class="form-group">
                            <label>Current Password</label>
                            <div style="position:relative;">
                                <input type="password" id="upw_current" name="current_password" placeholder="Enter your current password" required style="padding-right:48px;">
                                <button type="button" onclick="toggleUserPw('upw_current', this)" aria-label="Show password"
                                        style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; padding:8px; color:var(--color-brown-soft); display:flex; align-items:center;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label>New Password</label>
                                <div style="position:relative;">
                                    <input type="password" id="upw_new" name="new_password" placeholder="Create a strong password" required style="padding-right:48px;">
                                    <button type="button" onclick="toggleUserPw('upw_new', this)" aria-label="Show password"
                                            style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; padding:8px; color:var(--color-brown-soft); display:flex; align-items:center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                                <span id="newPwError" class="error-text" style="color:#7A2E2E; font-size:12px; display:block; margin-top:6px; min-height:14px;"></span>
                            </div>

                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div style="position:relative;">
                                    <input type="password" id="upw_confirm" name="confirm_password" placeholder="Re-enter new password" required style="padding-right:48px;">
                                    <button type="button" onclick="toggleUserPw('upw_confirm', this)" aria-label="Show password"
                                            style="position:absolute; right:8px; top:50%; transform:translateY(-50%); background:transparent; border:none; cursor:pointer; padding:8px; color:var(--color-brown-soft); display:flex; align-items:center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>
                                <span id="confirmPwError" class="error-text" style="color:#7A2E2E; font-size:12px; display:block; margin-top:6px; min-height:14px;"></span>
                            </div>
                        </div>

                        <!-- Password requirements as visual chips -->
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin: 4px 0 24px 0;">
                            <span class="pw-req" id="req-length" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:var(--color-brown-mid); letter-spacing:0.3px;">
                                <span class="dot" style="width:6px; height:6px; border-radius:50%; background:var(--color-brown-soft);"></span>
                                8+ characters
                            </span>
                            <span class="pw-req" id="req-upper" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:var(--color-brown-mid); letter-spacing:0.3px;">
                                <span class="dot" style="width:6px; height:6px; border-radius:50%; background:var(--color-brown-soft);"></span>
                                1 uppercase letter
                            </span>
                            <span class="pw-req" id="req-digit" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:var(--color-brown-mid); letter-spacing:0.3px;">
                                <span class="dot" style="width:6px; height:6px; border-radius:50%; background:var(--color-brown-soft);"></span>
                                1 number
                            </span>
                            <span class="pw-req" id="req-start" style="display:inline-flex; align-items:center; gap:6px; padding:6px 12px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:12px; color:var(--color-brown-mid); letter-spacing:0.3px;">
                                <span class="dot" style="width:6px; height:6px; border-radius:50%; background:var(--color-brown-soft);"></span>
                                Doesn't start with number
                            </span>
                        </div>

                        <button type="submit" class="btn btn-primary">🔒 Update Password</button>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function switchCustomerTab(tabName, buttonElement) {
    const contents = document.querySelectorAll('.customer-tab-content');
    contents.forEach(content => content.classList.remove('active'));

    const tabElement = document.getElementById('tab-' + tabName);
    if (tabElement) {
        tabElement.classList.add('active');
    }

    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    if (buttonElement) {
        buttonElement.classList.add('active');
    }
}

// Toggle password visibility — swaps eye SVG to eye-off SVG
function toggleUserPw(inputId, btn) {
    var input = document.getElementById(inputId);
    var isPwd = input.type === 'password';
    input.type = isPwd ? 'text' : 'password';
    btn.setAttribute('aria-label', isPwd ? 'Hide password' : 'Show password');
    btn.innerHTML = isPwd
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
}

// Live password requirements feedback + form validation
document.addEventListener('DOMContentLoaded', function() {
    var form     = document.getElementById('changePasswordForm');
    if (!form) return;

    var newPw    = document.getElementById('upw_new');
    var confPw   = document.getElementById('upw_confirm');

    var reqLen   = document.getElementById('req-length');
    var reqUpper = document.getElementById('req-upper');
    var reqDigit = document.getElementById('req-digit');
    var reqStart = document.getElementById('req-start');

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
            chip.style.color = '';
            dot.style.background = 'var(--color-brown-soft)';
        }
    }

    // Live update chips as user types
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
        var newErr   = document.getElementById('newPwError');
        var confErr  = document.getElementById('confirmPwError');
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
});
</script>

<?php include 'includes/footer.php'; ?>
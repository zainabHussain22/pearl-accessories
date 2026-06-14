<?php
/**
 * ============================================================================
 * FILE:  login.php
 * ROLE:  Handles authentication for BOTH admins and regular users using a
 *        single form. The page first looks for the submitted username in
 *        the `admins` table; if found and password matches → admin session.
 *        Otherwise it tries the `users` table → user session.
 *
 * AUTHENTICATION FLOW:
 *   1. Form submits username + password (POST).
 *   2. Fetch row by username only (LISTING 14.17 prepared statement).
 *   3. Verify the submitted password against the stored hash using
 *      password_verify() — falls back to direct comparison if the stored
 *      value is legacy plain text (so old records keep working).
 *   4. On success: clear any opposite-role session keys, set the new
 *      role's keys, and redirect.
 *
 * SECURITY:
 *   • Prepared statements throughout (LISTING 14.17).
 *   • Passwords compared via password_verify(), never via SQL.
 *   • Old "admin" and "user" session keys are explicitly cleared on
 *     login so a user can't inherit admin privileges and vice versa.
 * ============================================================================
 */
// Student Name: Zainab

session_start();
include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // LISTING 14.17 — Fetch admin by username, then verify hashed password
        $sql = "SELECT admin_id, username, password FROM admins WHERE username = :username";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':username', $username);
        $statement->execute();
        $admin = $statement->fetch();

        // password_verify handles both hashed and (legacy) plain-text passwords gracefully
        $adminOk = $admin && (password_verify($password, $admin['password']) || $password === $admin['password']);

        if ($adminOk) {
            // Clear any old user session first
            unset($_SESSION['user_logged_in']);
            unset($_SESSION['user_id']);
            unset($_SESSION['username']);
            unset($_SESSION['email']);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $admin['admin_id'];
            $pdo = null; // LISTING 14.15
            header("Location: admin_panel.php");
            exit;
        }

        // LISTING 14.17 — Fetch user by username, then verify hashed password
        $sql = "SELECT user_id, username, email, password FROM users WHERE username = :username";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':username', $username);
        $statement->execute();
        $user = $statement->fetch();

        $userOk = $user && (password_verify($password, $user['password']) || $password === $user['password']);

        if ($userOk) {
            // Clear any old admin session first
            unset($_SESSION['admin_logged_in']);
            unset($_SESSION['admin_id']);

            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = $user['user_id'];
            $_SESSION['username']       = $user['username'];
            $_SESSION['email']          = $user['email'];
            
            // ── Load this user's past purchases from DB into SESSION ──
            // Only shows in ticker if user has purchased before
            // Gets last 5 items from this user's orders
            $sql = "SELECT p.name, oi.quantity, (oi.quantity * oi.price) AS total
                    FROM order_items oi
                    JOIN orders o ON oi.order_id = o.order_id
                    JOIN products p ON oi.product_id = p.product_id
                    WHERE o.user_id = :uid
                    ORDER BY o.order_date DESC, oi.item_id DESC
                    LIMIT 5";
            $statement = $pdo->prepare($sql);
            $statement->bindValue(':uid', $user['user_id']);
            $statement->execute();
            $_SESSION['past_purchases'] = $statement->fetchAll(PDO::FETCH_ASSOC);
            
            $pdo = null;
            header("Location: index.php");
            exit;
        }

        $message = "Invalid username or password.";
    }
    catch (PDOException $e) {
        die($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — MAISON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ── Auth page layout (standalone, no header/footer) ── */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background:
                radial-gradient(ellipse at 15% 50%, rgba(176,141,87,0.06) 0%, transparent 55%),
                var(--color-parchment);
        }

        /* ── Card shell ── */
        .auth-card {
            display: flex;
            width: 100%;
            max-width: 860px;
            min-height: 520px;
            box-shadow:
                0 2px 4px rgba(30,18,9,0.04),
                0 20px 60px rgba(30,18,9,0.16),
                0 0 0 1px rgba(176,141,87,0.12);
            overflow: hidden;
            animation: card-rise 0.55s cubic-bezier(0.4,0,0.2,1) both;
        }

        @keyframes card-rise {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ══════════════════════════════════════
           LEFT PANEL — dark espresso
        ══════════════════════════════════════ */
        .auth-left {
            flex: 0 0 42%;
            background: linear-gradient(160deg, #2C1F0E 0%, #1a0e06 55%, #0e0804 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 52px 44px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        /* Ambient gold glows */
        .auth-left::before {
            content: '';
            position: absolute;
            top: -80px; left: -80px;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(176,141,87,0.18) 0%, transparent 65%);
            animation: glow-float 7s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -60px; right: -60px;
            width: 240px; height: 240px;
            background: radial-gradient(circle, rgba(212,175,114,0.12) 0%, transparent 65%);
            animation: glow-float 9s ease-in-out infinite alternate-reverse;
            pointer-events: none;
        }
        @keyframes glow-float {
            from { transform: translate(0, 0); }
            to   { transform: translate(25px, 18px); }
        }

        /* Corner bracket decorations */
        .auth-corner {
            position: absolute;
            width: 38px; height: 38px;
            opacity: 0.35;
        }
        .auth-corner--tl { top: 20px; left: 20px; border-top: 1px solid #B08D57; border-left: 1px solid #B08D57; }
        .auth-corner--tr { top: 20px; right: 20px; border-top: 1px solid #B08D57; border-right: 1px solid #B08D57; }
        .auth-corner--bl { bottom: 20px; left: 20px; border-bottom: 1px solid #B08D57; border-left: 1px solid #B08D57; }
        .auth-corner--br { bottom: 20px; right: 20px; border-bottom: 1px solid #B08D57; border-right: 1px solid #B08D57; }

        /* Eyebrow line */
        .auth-eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        .auth-eyebrow::before,
        .auth-eyebrow::after {
            content: '';
            flex: 1;
            height: 1px;
        }
        .auth-eyebrow::before { background: linear-gradient(90deg, transparent, rgba(176,141,87,0.5)); }
        .auth-eyebrow::after  { background: linear-gradient(90deg, rgba(176,141,87,0.5), transparent); }
        .auth-eyebrow span {
            font-size: 8px;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            color: #B08D57;
            white-space: nowrap;
            font-family: var(--font-sans);
            font-weight: 500;
        }

        .auth-left-wordmark {
            font-family: var(--font-serif);
            font-size: 11px;
            letter-spacing: 7px;
            text-transform: uppercase;
            color: rgba(212,175,114,0.55);
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }

        .auth-left h2 {
            font-family: var(--font-serif);
            font-size: 48px;
            font-weight: 300;
            font-style: italic;
            line-height: 1.06;
            color: #E8D5B7;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
            letter-spacing: -0.5px;
        }

        .auth-left p {
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 300;
            color: rgba(232,213,183,0.45);
            letter-spacing: 0.5px;
            line-height: 1.85;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
            max-width: 26ch;
        }

        /* Register CTA button */
        .auth-left-btn {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            padding: 13px 32px;
            border: 1px solid rgba(176,141,87,0.4);
            background: transparent;
            color: #D4AF72;
            font-family: var(--font-sans);
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            text-decoration: none;
            position: relative;
            z-index: 1;
            overflow: hidden;
            transition: border-color 0.3s, color 0.3s, transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.3s;
        }
        .auth-left-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(176,141,87,0.15), rgba(176,141,87,0.05));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .auth-left-btn:hover {
            border-color: #D4AF72;
            color: #FFFDF9;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(176,141,87,0.2);
        }
        .auth-left-btn:hover::before { opacity: 1; }
        .auth-left-btn svg {
            width: 13px; height: 13px;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .auth-left-btn:hover svg { transform: translateX(4px); }

        /* ══════════════════════════════════════
           RIGHT PANEL — ivory form area
        ══════════════════════════════════════ */
        .auth-right {
            flex: 1;
            background: var(--color-ivory);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 52px 48px;
            position: relative;
        }

        /* Gold top accent bar */
        .auth-right::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #8A6B3A, #D4AF72, #8A6B3A);
        }

        .auth-right-heading {
            font-family: var(--font-serif);
            font-size: 32px;
            font-weight: 300;
            font-style: italic;
            color: var(--color-brown-deeper);
            margin-bottom: 5px;
            letter-spacing: 0.2px;
        }

        .auth-right-sub {
            font-family: var(--font-sans);
            font-size: 11px;
            font-weight: 300;
            color: var(--color-brown-soft);
            letter-spacing: 0.5px;
            margin-bottom: 36px;
        }

        /* Error banner */
        .auth-error {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(122,46,46,0.07);
            border-left: 2px solid #8C3535;
            padding: 12px 16px;
            margin-bottom: 22px;
            font-family: var(--font-sans);
            font-size: 12.5px;
            color: #7D2E2E;
            letter-spacing: 0.3px;
            animation: err-shake 0.35s ease;
        }
        @keyframes err-shake {
            0%,100% { transform: translateX(0); }
            25%     { transform: translateX(-5px); }
            75%     { transform: translateX(5px); }
        }
        .auth-error svg { flex-shrink: 0; opacity: 0.7; }

        /* Input fields */
        .auth-field { margin-bottom: 20px; }

        .auth-field label {
            display: block;
            font-family: var(--font-sans);
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            color: var(--color-brown-mid);
            margin-bottom: 7px;
        }

        .auth-field-wrap {
            position: relative;
        }

        .auth-field input {
            width: 100%;
            padding: 13px 44px 13px 16px;
            border: 1px solid rgba(176,141,87,0.24);
            background: var(--color-ivory-warm);
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 300;
            color: var(--color-brown-deeper);
            outline: none;
            transition: border-color 0.22s, box-shadow 0.22s, background 0.22s;
            -webkit-appearance: none;
            border-radius: 0;
        }
        .auth-field input:focus {
            border-color: var(--color-gold);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(176,141,87,0.09);
        }
        .auth-field input::placeholder {
            color: rgba(122,98,80,0.35);
            font-weight: 300;
        }

        .auth-field-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(176,141,87,0.38);
            pointer-events: none;
            transition: color 0.22s;
        }
        .auth-field:focus-within .auth-field-icon {
            color: var(--color-gold);
        }

        /* Password eye toggle button */
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            padding: 6px;
            cursor: pointer;
            color: rgba(176,141,87,0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: color 0.22s, background 0.22s;
        }
        .pw-toggle:hover { color: var(--color-gold); background: rgba(176,141,87,0.08); }
        #password { padding-right: 44px; }

        /* Login submit */
        .auth-submit {
            width: 100%;
            padding: 15px 24px;
            margin-top: 6px;
            background: linear-gradient(135deg, var(--color-brown-deeper) 0%, #3a2812 100%);
            color: var(--color-ivory-dark);
            border: none;
            font-family: var(--font-sans);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 3px;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s;
        }
        /* Shimmer sweep */
        .auth-submit::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 55%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: skewX(-18deg);
            transition: left 0.5s ease;
        }
        .auth-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(30,18,9,0.22); }
        .auth-submit:hover::after { left: 160%; }
        .auth-submit:active { transform: translateY(0); }

        /* Divider */
        .auth-divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0 16px;
        }
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(176,141,87,0.18);
        }
        .auth-divider span {
            font-family: var(--font-sans);
            font-size: 9.5px;
            letter-spacing: 1.5px;
            color: var(--color-brown-soft);
            opacity: 0.6;
            white-space: nowrap;
        }

        /* Bottom link */
        .auth-bottom {
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 300;
            color: var(--color-brown-soft);
            text-align: center;
            letter-spacing: 0.3px;
        }
        .auth-bottom a {
            color: var(--color-gold-dark);
            font-weight: 500;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s, color 0.2s;
        }
        .auth-bottom a:hover {
            color: var(--color-gold);
            border-color: var(--color-gold);
        }

        /* ── Responsive ── */
        @media (max-width: 680px) {
            .auth-card     { flex-direction: column; max-width: 420px; min-height: auto; }
            .auth-left     { flex: none; padding: 44px 36px; }
            .auth-left h2  { font-size: 38px; }
            .auth-right    { padding: 44px 36px 40px; }
            .auth-corner   { display: none; }
        }
        @media (max-width: 440px) {
            .auth-right    { padding: 36px 28px 32px; }
            .auth-left     { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <!-- ═══ LEFT PANEL ═══ -->
        <div class="auth-left">
            <div class="auth-corner auth-corner--tl"></div>
            <div class="auth-corner auth-corner--tr"></div>
            <div class="auth-corner auth-corner--bl"></div>
            <div class="auth-corner auth-corner--br"></div>

            <div class="auth-eyebrow"><span>Luxury Jewellery</span></div>
            <div class="auth-left-wordmark">Maison</div>

            <h2>Hello,<br>Welcome</h2>
            <p>Don't have an account?<br>Join us and discover curated pieces crafted for the discerning.</p>

            <a href="register.php" class="auth-left-btn">
                Register
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 8h10M9 4l4 4-4 4"/>
                </svg>
            </a>
        </div>

        <!-- ═══ RIGHT PANEL ═══ -->
        <div class="auth-right">
            <div class="auth-right-heading">Account Login</div>
            <div class="auth-right-sub">Sign in to continue to your account</div>

            <?php if ($message): ?>
                <div class="auth-error">
                    <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="8" cy="8" r="7"/><path d="M8 5v3.5M8 11h.01"/>
                    </svg>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="auth-field">
                    <label for="username">Username</label>
                    <div class="auth-field-wrap">
                        <input type="text" id="username" name="username"
                               placeholder="Enter your username"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                               required autofocus>
                        <span class="auth-field-icon">
                            <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
                                <circle cx="8" cy="5.5" r="2.5"/><path d="M2.5 13.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-field-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="pw-toggle" id="togglePassword" aria-label="Show password">
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <svg id="eyeOffIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit">Sign In</button>
            </form>

            <script>
            // Password show/hide toggle
            (function() {
                var toggle = document.getElementById('togglePassword');
                var input  = document.getElementById('password');
                var eye    = document.getElementById('eyeIcon');
                var eyeOff = document.getElementById('eyeOffIcon');
                if (!toggle || !input) return;

                toggle.addEventListener('click', function() {
                    if (input.type === 'password') {
                        input.type = 'text';
                        eye.style.display = 'none';
                        eyeOff.style.display = 'block';
                        toggle.setAttribute('aria-label', 'Hide password');
                    } else {
                        input.type = 'password';
                        eye.style.display = 'block';
                        eyeOff.style.display = 'none';
                        toggle.setAttribute('aria-label', 'Show password');
                    }
                });
            })();
            </script>

            <div class="auth-divider"><span>or</span></div>

            <div class="auth-bottom">
                Don't have an account? <a href="register.php">Create one</a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
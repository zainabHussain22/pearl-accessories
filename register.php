<?php

session_start();
include 'db.php';

$message      = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // ── Validation: username and password rules ──
    // Uses ONLY: empty(), if/else if, for loop, character comparison (Ch 12 slides)
    $errors = [];

    // Username: must not be empty, must not start with a digit, must not be all digits
    if (empty($username)) {
        $errors[] = "Username is required.";
    } else {
        $usernameAllDigits = true;
        for ($i = 0; $i < strlen($username); $i++) {
            if ($username[$i] < '0' || $username[$i] > '9') {
                $usernameAllDigits = false;
            }
        }
        if ($usernameAllDigits == true) {
            $errors[] = "Username cannot be all numbers.";
        } else if ($username[0] >= '0' && $username[0] <= '9') {
            $errors[] = "Username cannot start with a number.";
        }
    }

    // Email: must not be empty and must be a valid format (server-side check)
    if (empty($email)) {
        $errors[] = "Email is required.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Password: min 8 chars, ≥1 uppercase, ≥1 number, not all digits, not starting with digit
    if (empty($password)) {
        $errors[] = "Password is required.";
    } else if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    } else if ($password[0] >= '0' && $password[0] <= '9') {
        $errors[] = "Password cannot start with a number.";
    } else {
        // Loop through password to check rules — LISTING 12.11 (for loop)
        $hasUpper       = false;
        $hasDigit       = false;
        $passAllDigits  = true;

        for ($i = 0; $i < strlen($password); $i++) {
            if ($password[$i] >= 'A' && $password[$i] <= 'Z') {
                $hasUpper = true;
            }
            if ($password[$i] >= '0' && $password[$i] <= '9') {
                $hasDigit = true;
            }
            if ($password[$i] < '0' || $password[$i] > '9') {
                $passAllDigits = false;
            }
        }

        if ($passAllDigits == true) {
            $errors[] = "Password cannot be all numbers.";
        } else if ($hasUpper == false) {
            $errors[] = "Password must contain at least one uppercase letter.";
        } else if ($hasDigit == false) {
            $errors[] = "Password must contain at least one number.";
        }
    }

    if (!empty($errors)) {
        $message      = implode("<br>", $errors);
        $message_type = 'error';
    } else {
    try {
        // LISTING 14.17 — Prepared statement to check if username/email exists
        $sql = "SELECT user_id, username, email FROM users WHERE username = :username OR email = :email";
        $statement = $pdo->prepare($sql);
        $statement->bindValue(':username', $username);
        $statement->bindValue(':email', $email);
        $statement->execute();
        $existing = $statement->fetch();

        if ($existing) {
            if ($existing['username'] === $username) {
                $message = "Username already taken. Please choose another.";
            } else {
                $message = "This email is already registered. Please sign in instead.";
            }
            $message_type = 'error';
        } else {
            // Hash the password before storing (security best practice)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // LISTING 14.17 — Prepared statement for INSERT
            $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
            $statement = $pdo->prepare($sql);
            $statement->bindValue(':username', $username);
            $statement->bindValue(':email', $email);
            $statement->bindValue(':password', $hashedPassword);

            if ($statement->execute()) {
                $message      = "Account created successfully! You can now sign in.";
                $message_type = 'success';
            } else {
                $message      = "Something went wrong. Please try again.";
                $message_type = 'error';
            }
        }

        $pdo = null; // LISTING 14.15 — close connection
    }
    catch (PDOException $e) {
        die($e->getMessage());
    }
    } // end of validation else
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — MAISON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Jost:wght@200;300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        html, body { height: 100%; margin: 0; padding: 0; }

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background:
                radial-gradient(ellipse at 85% 50%, rgba(176,141,87,0.06) 0%, transparent 55%),
                var(--color-parchment);
        }

        .auth-card {
            display: flex;
            width: 100%;
            max-width: 900px;
            min-height: 560px;
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

        .auth-left {
            flex: 1;
            background: var(--color-ivory);
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 52px 48px;
            position: relative;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, #8A6B3A, #D4AF72, #8A6B3A);
        }

        .auth-left-heading {
            font-family: var(--font-serif);
            font-size: 32px;
            font-weight: 300;
            font-style: italic;
            color: var(--color-brown-deeper);
            margin-bottom: 5px;
        }

        .auth-left-sub {
            font-family: var(--font-sans);
            font-size: 11px;
            font-weight: 300;
            color: var(--color-brown-soft);
            letter-spacing: 0.5px;
            margin-bottom: 32px;
        }

        .auth-message {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-family: var(--font-sans);
            font-size: 12.5px;
            letter-spacing: 0.3px;
        }
        .auth-message svg { flex-shrink: 0; opacity: 0.75; }
        .auth-message--error {
            background: rgba(122,46,46,0.07);
            border-left: 2px solid #8C3535;
            color: #7D2E2E;
            animation: err-shake 0.35s ease;
        }
        .auth-message--success {
            background: rgba(78,112,64,0.08);
            border-left: 2px solid #5A8048;
            color: #3A5A28;
        }
        @keyframes err-shake {
            0%,100% { transform: translateX(0); }
            25%     { transform: translateX(-5px); }
            75%     { transform: translateX(5px); }
        }

        .auth-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .auth-field { margin-bottom: 18px; }

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

        .auth-field-wrap { position: relative; }

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
            border-radius: 0;
            transition: border-color 0.22s, box-shadow 0.22s, background 0.22s;
            -webkit-appearance: none;
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
        .auth-field:focus-within .auth-field-icon { color: var(--color-gold); }

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

        .auth-submit {
            width: 100%;
            padding: 15px 24px;
            margin-top: 4px;
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

        .auth-bottom {
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 300;
            color: var(--color-brown-soft);
            text-align: center;
            margin-top: 20px;
            letter-spacing: 0.3px;
        }
        .auth-bottom a {
            color: var(--color-gold-dark);
            font-weight: 500;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s, color 0.2s;
        }
        .auth-bottom a:hover { color: var(--color-gold); border-color: var(--color-gold); }

        .auth-right {
            flex: 0 0 38%;
            background: linear-gradient(160deg, #2C1F0E 0%, #1a0e06 55%, #0e0804 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 52px 40px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .auth-right::before {
            content: '';
            position: absolute;
            top: -80px; right: -80px;
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(176,141,87,0.18) 0%, transparent 65%);
            animation: glow-float 7s ease-in-out infinite alternate;
            pointer-events: none;
        }
        .auth-right::after {
            content: '';
            position: absolute;
            bottom: -60px; left: -60px;
            width: 220px; height: 220px;
            background: radial-gradient(circle, rgba(212,175,114,0.1) 0%, transparent 65%);
            animation: glow-float 9s ease-in-out infinite alternate-reverse;
            pointer-events: none;
        }
        @keyframes glow-float {
            from { transform: translate(0, 0); }
            to   { transform: translate(22px, 16px); }
        }

        .auth-corner {
            position: absolute;
            width: 36px; height: 36px;
            opacity: 0.32;
        }
        .auth-corner--tl { top: 20px; left: 20px; border-top: 1px solid #B08D57; border-left: 1px solid #B08D57; }
        .auth-corner--tr { top: 20px; right: 20px; border-top: 1px solid #B08D57; border-right: 1px solid #B08D57; }
        .auth-corner--bl { bottom: 20px; left: 20px; border-bottom: 1px solid #B08D57; border-left: 1px solid #B08D57; }
        .auth-corner--br { bottom: 20px; right: 20px; border-bottom: 1px solid #B08D57; border-right: 1px solid #B08D57; }

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
        .auth-eyebrow::after { content: ''; flex: 1; height: 1px; }
        .auth-eyebrow::before { background: linear-gradient(90deg, transparent, rgba(176,141,87,0.5)); }
        .auth-eyebrow::after  { background: linear-gradient(90deg, rgba(176,141,87,0.5), transparent); }
        .auth-eyebrow span {
            font-family: var(--font-sans);
            font-size: 8px;
            font-weight: 500;
            letter-spacing: 3.5px;
            text-transform: uppercase;
            color: #B08D57;
            white-space: nowrap;
        }

        .auth-right-wordmark {
            font-family: var(--font-serif);
            font-size: 11px;
            letter-spacing: 7px;
            text-transform: uppercase;
            color: rgba(212,175,114,0.5);
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .auth-right h2 {
            font-family: var(--font-serif);
            font-size: 44px;
            font-weight: 300;
            font-style: italic;
            line-height: 1.06;
            color: #E8D5B7;
            margin-bottom: 14px;
            position: relative;
            z-index: 1;
        }

        .auth-right p {
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 300;
            color: rgba(232,213,183,0.42);
            letter-spacing: 0.5px;
            line-height: 1.85;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
            max-width: 24ch;
        }

        .auth-right-btn {
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
        .auth-right-btn::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(176,141,87,0.15), rgba(176,141,87,0.05));
            opacity: 0;
            transition: opacity 0.3s;
        }
        .auth-right-btn:hover {
            border-color: #D4AF72;
            color: #FFFDF9;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(176,141,87,0.2);
        }
        .auth-right-btn:hover::before { opacity: 1; }
        .auth-right-btn svg {
            width: 13px; height: 13px;
            transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1);
        }
        .auth-right-btn:hover svg { transform: translateX(4px); }

        @media (max-width: 720px) {
            .auth-card    { flex-direction: column; max-width: 460px; min-height: auto; }
            .auth-right   { flex: none; padding: 44px 36px; order: -1; }
            .auth-right h2 { font-size: 36px; }
            .auth-left    { padding: 44px 36px 40px; }
            .auth-row     { grid-template-columns: 1fr; gap: 0; }
            .auth-corner  { display: none; }
        }
        @media (max-width: 480px) {
            .auth-left  { padding: 36px 28px 32px; }
            .auth-right { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-card">

        <div class="auth-left">
            <div class="auth-left-heading">Create Account</div>
            <div class="auth-left-sub">Join MAISON and explore our curated collections</div>

            <?php if ($message): ?>
                <div class="auth-message auth-message--<?= $message_type ?>">
                    <?php if ($message_type === 'error'): ?>
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="8" cy="8" r="7"/><path d="M8 5v3.5M8 11h.01"/>
                        </svg>
                    <?php else: ?>
                        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="8" cy="8" r="7"/><path d="M5 8l2 2 4-4"/>
                        </svg>
                    <?php endif; ?>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm" novalidate>

                <div class="auth-row">
                    <div class="auth-field">
                        <label for="username">Username</label>
                        <div class="auth-field-wrap">
                            <input type="text" id="username" name="username"
                                   placeholder="Choose a username"
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   required autofocus>
                            <span class="auth-field-icon">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
                                    <circle cx="8" cy="5.5" r="2.5"/><path d="M2.5 13.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/>
                                </svg>
                            </span>
                        </div>
                        <span id="usernameError" class="error-text" style="color:#C0392B; font-size:12px; display:block; margin-top:4px;"></span>
                    </div>

                    <div class="auth-field">
                        <label for="email">Email Address</label>
                        <div class="auth-field-wrap">
                            <input type="email" id="email" name="email"
                                   placeholder="your@email.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required>
                            <span class="auth-field-icon">
                                <svg width="15" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4">
                                    <rect x="1.5" y="3.5" width="13" height="9" rx="1.5"/>
                                    <path d="M1.5 5.5l6.5 4 6.5-4"/>
                                </svg>
                            </span>
                        </div>
                        <span id="emailError" class="error-text" style="color:#C0392B; font-size:12px; display:block; margin-top:4px;"></span>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-field-wrap">
                        <input type="password" id="password" name="password"
                               placeholder="Create a strong password"
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
                    <span id="passwordError" class="error-text" style="color:#C0392B; font-size:12px; display:block; margin-top:4px;"></span>

                    <!-- Password requirements chips -->
                    <div style="display: flex; flex-wrap: wrap; gap: 6px; margin: 10px 0 4px 0;">
                        <span class="pw-req" id="req-length" style="display:inline-flex; align-items:center; gap:5px; padding:5px 10px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:11px; color:var(--color-brown-mid, #50412A); letter-spacing:0.3px; transition: all 0.2s;">
                            <span class="dot" style="width:5px; height:5px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                            8+ characters
                        </span>
                        <span class="pw-req" id="req-upper" style="display:inline-flex; align-items:center; gap:5px; padding:5px 10px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:11px; color:var(--color-brown-mid, #50412A); letter-spacing:0.3px; transition: all 0.2s;">
                            <span class="dot" style="width:5px; height:5px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                            1 uppercase letter
                        </span>
                        <span class="pw-req" id="req-digit" style="display:inline-flex; align-items:center; gap:5px; padding:5px 10px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:11px; color:var(--color-brown-mid, #50412A); letter-spacing:0.3px; transition: all 0.2s;">
                            <span class="dot" style="width:5px; height:5px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                            1 number
                        </span>
                        <span class="pw-req" id="req-start" style="display:inline-flex; align-items:center; gap:5px; padding:5px 10px; background:rgba(176, 141, 87, 0.08); border:1px solid rgba(176, 141, 87, 0.22); border-radius:20px; font-size:11px; color:var(--color-brown-mid, #50412A); letter-spacing:0.3px; transition: all 0.2s;">
                            <span class="dot" style="width:5px; height:5px; border-radius:50%; background:#7A6250; transition: background 0.2s;"></span>
                            Doesn't start with number
                        </span>
                    </div>
                </div>

                <button type="submit" class="auth-submit">Create Account</button>
            </form>

            <script src="js/validation.js"></script>
            <script>
            document.getElementById('registerForm').addEventListener('submit', function(e) {
                var usernameInput = document.getElementById('username');
                var emailInput    = document.getElementById('email');
                var passwordInput = document.getElementById('password');
                var userErr       = document.getElementById('usernameError');
                var emailErr      = document.getElementById('emailError');
                var passErr       = document.getElementById('passwordError');
                var valid = true;

                userErr.textContent  = '';
                emailErr.textContent = '';
                passErr.textContent  = '';

                // Username: not empty, not all digits, not starting with digit
                var u = usernameInput.value.trim();
                if (u === '') {
                    userErr.textContent = 'Username is required.';
                    valid = false;
                } else if (/^[0-9]+$/.test(u)) {
                    userErr.textContent = 'Username cannot be all numbers.';
                    valid = false;
                } else if (/^[0-9]/.test(u)) {
                    userErr.textContent = 'Username cannot start with a number.';
                    valid = false;
                }

                // Email
                if (!isValidEmail(emailInput.value.trim())) {
                    emailErr.textContent = 'Please enter a valid email address.';
                    valid = false;
                }

                // Password rules: min 8 chars, ≥1 uppercase, ≥1 number, no leading/all digits
                var p = passwordInput.value;
                if (p.length < 8) {
                    passErr.textContent = 'Password must be at least 8 characters.';
                    valid = false;
                } else if (/^[0-9]+$/.test(p)) {
                    passErr.textContent = 'Password cannot be all numbers.';
                    valid = false;
                } else if (/^[0-9]/.test(p)) {
                    passErr.textContent = 'Password cannot start with a number.';
                    valid = false;
                } else if (!/[A-Z]/.test(p)) {
                    passErr.textContent = 'Password must contain at least one uppercase letter.';
                    valid = false;
                } else if (!/[0-9]/.test(p)) {
                    passErr.textContent = 'Password must contain at least one number.';
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                }
            });

            // Password show/hide toggle
            (function() {
                var toggle    = document.getElementById('togglePassword');
                var input     = document.getElementById('password');
                var eye       = document.getElementById('eyeIcon');
                var eyeOff    = document.getElementById('eyeOffIcon');
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

            // Live password requirement chips
            (function() {
                var pw       = document.getElementById('password');
                var reqLen   = document.getElementById('req-length');
                var reqUpper = document.getElementById('req-upper');
                var reqDigit = document.getElementById('req-digit');
                var reqStart = document.getElementById('req-start');
                if (!pw || !reqLen) return;

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
                        dot.style.background = '#7A6250';
                    }
                }

                pw.addEventListener('input', function() {
                    var v = pw.value;
                    setReq(reqLen,   v.length >= 8);
                    setReq(reqUpper, /[A-Z]/.test(v));
                    setReq(reqDigit, /[0-9]/.test(v));
                    setReq(reqStart, v.length > 0 && !/^[0-9]/.test(v));
                });
            })();
            </script>

            <div class="auth-bottom">
                Already have an account? <a href="login.php">Sign in</a>
            </div>
        </div>

        <div class="auth-right">
            <div class="auth-corner auth-corner--tl"></div>
            <div class="auth-corner auth-corner--tr"></div>
            <div class="auth-corner auth-corner--bl"></div>
            <div class="auth-corner auth-corner--br"></div>

            <div class="auth-eyebrow"><span>Luxury Jewellery</span></div>
            <div class="auth-right-wordmark">Maison</div>

            <h2>Already<br>a Member?</h2>
            <p>Sign in to your account and continue your journey with us.</p>

            <a href="login.php" class="auth-right-btn">
                Sign In
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 8h10M9 4l4 4-4 4"/>
                </svg>
            </a>
        </div>

    </div>
</div>

</body>
</html>

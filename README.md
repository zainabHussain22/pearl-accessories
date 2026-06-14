# Pearl Accessories — CIS 423 Final Project

A full-stack pearl jewelry e-commerce site built with PHP + MySQL, following the patterns from Chapters 12 (Server-Side PHP), 14 (Databases), and 15 (Sessions & Cookies) of *Fundamentals of Web Development* (Connolly & Hoar, 3rd ed.).

---

## 📁 Project Structure

```
pearl-accessories/
├── config.inc.php          ← DB credentials (LISTING 14.2)
├── db.php                  ← PDO connection helper (LISTING 14.7)
├── pearl_accessories.sql   ← Schema + sample data (import this to phpMyAdmin)
│
├── index.php               ← Homepage with product grid + past purchases cookie
├── product.php             ← Single product page with image gallery + recently viewed
├── cart.php                ← Shopping cart (session-based)
├── buy.php                 ← Checkout: creates order, decrements stock, writes cookie
├── order_details.php       ← Receipt page (owner-only)
│
├── register.php            ← New user sign-up with live password chips
├── login.php               ← Single login for admins + users
├── logout.php              ← Destroys the session
│
├── customer_panel.php      ← User dashboard (Profile / Orders / Settings)
├── update_profile.php      ← Handles Edit Profile form
│
├── admin_panel.php         ← Admin dashboard (Products / Orders / Ratings / Profile)
│
├── review.php              ← Rating form (sliders + comments)
├── save_review.php         ← Processes rating submission (with ownership check)
│
├── contact.php             ← Contact info + Google Map
│
├── includes/
│   ├── header.php          ← Site-wide nav (role-aware)
│   └── footer.php          ← Site-wide footer + newsletter
│
├── css/
│   └── style.css           ← All styling
│
├── js/
│   ├── validation.js       ← Generic helpers (email, etc.)
│   ├── auth.js             ← Legacy login/register card switch
│   └── review.js           ← Slider sentiment + emoji animation
│
└── images/                 ← Product photos
```

---

## 🚀 Setup (XAMPP)

1. Copy the `pearl-accessories` folder into `htdocs/`.
2. Start Apache + MySQL from the XAMPP control panel.
3. Open `localhost/phpmyadmin` → import the `pearl_accessories.sql` file → done.
4. Open `localhost/pearl-accessories/` in your browser.

### Default Accounts

| Role  | Username           | Password       |
|-------|--------------------|----------------|
| Admin | `admin`            | `admin123`     |
| User  | `ayah`             | `1234`         |
| User  | `mahdikhudear-max` | `password123`  |

> Stored passwords are hashed with `password_hash()` — `password_verify()` is used at login.
> If you set a different MySQL password locally, change `DBPASS` inside `config.inc.php`.

---

## ✅ Rubric Coverage

### Functionality 1: Cart (3 pts)
| Item                    | File(s)                          | Status |
|-------------------------|----------------------------------|--------|
| Display products        | `index.php`                      | ✅     |
| Product details         | `product.php`                    | ✅     |
| Add to cart + stock check | `product.php`, `cart.php`      | ✅     |
| Checkout + totals       | `cart.php`                       | ✅     |
| Modify/Delete/Empty     | `cart.php`                       | ✅     |
| Buy + DB update         | `buy.php` (with transaction)     | ✅     |

### Functionality 2: Admin (2 pts)
| Item                    | File(s)                          | Status |
|-------------------------|----------------------------------|--------|
| Authenticate + Security | `login.php`, `admin_panel.php`   | ✅     |
| Add product + upload    | `admin_panel.php`                | ✅     |
| Search / Modify / Delete | `admin_panel.php`               | ✅     |

### Cookie (1 pt)
"Past Purchases" cookie written by `buy.php` (LISTING 15.2), read on `index.php` (LISTING 15.3).

### JavaScript (2 pts)
Form validation in `register.php`, `cart.php`, `customer_panel.php`, plus the Help modal in `product.php` and sentiment sliders in `review.js`.

---

## 🧠 Patterns Used (mapped to slides)

| Pattern | Slide / Listing | Where it appears |
|--------|-------|----------|
| `define()` for DB constants | LISTING 14.2 | `config.inc.php` |
| `try/catch` for PDO errors | LISTING 14.7 | `db.php` |
| `$pdo->query()` for params-less SELECT | LISTING 14.9 | `index.php`, `admin_panel.php` |
| `while ($row = $stmt->fetch())` | LISTING 14.11 | All listing pages |
| `$pdo = null` to close | LISTING 14.15 | After every DB block |
| Prepared statements with `:placeholder` | LISTING 14.17 | Every parameterized query |
| `for` loop for char-by-char validation | LISTING 12.11 | `register.php`, `admin_panel.php`, `customer_panel.php` |
| `setcookie()` with expiry | LISTING 15.2 | `buy.php`, `product.php` |
| `isset()` before reading session/cookie | LISTING 15.8 | Everywhere |

---

## 🔒 Security Features

- ✅ All SQL parameterized (no SQL injection)
- ✅ All output escaped with `htmlspecialchars()` (no XSS)
- ✅ Passwords hashed via `password_hash()` / `password_verify()`
- ✅ Server-side validation (PHP) on top of client-side (JS)
- ✅ Ownership checks on `order_details.php` and `save_review.php`
- ✅ Role separation: admins can't purchase; users can't access admin panel
- ✅ Stock checked **three times** before order completion (product page, cart page, buy.php) + DB transaction
- ✅ Atomic order creation: `beginTransaction` → `commit` or `rollBack` on any failure

---

## 🎨 UX Highlights

- Live "requirement chips" on every password field — turn green as rules are met
- Eye toggle to show/hide each password input
- Recently viewed products: per-user via session, per-browser via cookie for guests
- Welcome ticker on homepage showing the 5 most recent purchases (from cookie)
- Role-aware navigation: admins see Admin link, users see My Account, guests see Login/Register

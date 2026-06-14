<?php


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';
require_once 'currency_helper.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$isLoggedInUser = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;

$oldRecentIds = [];

if ($isLoggedInUser) {
    if (isset($_SESSION['recently_viewed']) && is_array($_SESSION['recently_viewed'])) {
        $oldRecentIds = $_SESSION['recently_viewed'];
    }
} else {
    if (isset($_COOKIE['recently_viewed']) && $_COOKIE['recently_viewed'] != "") {
        $oldRecentIds = explode(',', $_COOKIE['recently_viewed']);
    }
}

if ($product_id > 0) {
    $new_list = [];
    $new_list[] = $product_id;
    $count = 1;

    foreach ($oldRecentIds as $old_id) {
        if ($count >= 6) {
            break;
        }
        if (intval($old_id) != $product_id && $old_id != "") {
            $new_list[] = intval($old_id);
            $count = $count + 1;
        }
    }

    if ($isLoggedInUser) {
        $_SESSION['recently_viewed'] = $new_list;
    } else {
        $cookie_string = implode(',', $new_list);
        setcookie('recently_viewed', $cookie_string, time() + 2592000, '/');
    }
}

include 'includes/header.php';

try {
    $sql = "SELECT * FROM products WHERE product_id = :pid";
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':pid', $product_id);
    $statement->execute();
    $product = $statement->fetch();
}
catch (PDOException $e) {
    die($e->getMessage());
}

if (!$product) {
    echo '<div class="message message-error">Product not found!</div>';
    include 'includes/footer.php';
    exit;
}

$images = array_filter(array_map('trim', explode(',', $product['image'])));
$images = array_values($images);
if (empty($images)) { $images = ['placeholder.jpg']; }

$message = "";

$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    if ($isAdmin) {
        $message = '<div class="pd-msg error">Admins are view-only and cannot purchase products.</div>';
    } else {
    $qty = intval($_POST['quantity']);
    if ($qty <= 0) {
        $message = '<div class="pd-msg error">Please enter valid quantity</div>';
    } elseif ($qty > $product['stock']) {
        $message = '<div class="pd-msg error">Only '.$product['stock'].' items available</div>';
    } else {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $qty;
                $item['total'] = $item['quantity'] * $product['price'];
                $message = '<div class="pd-msg success">Cart Updated</div>';
                $found = true;
                break;
            }
        }
        unset($item);
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id'=>$product['product_id'],
                'name'=>$product['name'],
                'price'=>$product['price'],
                'quantity'=>$qty,
                'total'=>$qty * $product['price'],
                'image'=>$product['image']
            ];
            $message = '<div class="pd-msg success">Product Added To Cart</div>';
        }
    }
    }
}
?>

<link rel="stylesheet" href="css/style.css">

<div class="pd-wrap">
<div class="pd-layout">

  <div class="pd-image-wrap" style="position: relative;">
    <div class="pd-gallery" id="pdGallery">
      <?php foreach ($images as $i => $img): ?>
        <img class="pd-image-main pd-gallery-img <?php echo $i === 0 ? 'active' : ''; ?>"
             src="images/<?php echo htmlspecialchars($img); ?>"
             data-index="<?php echo $i; ?>"
             onerror="this.src='https://via.placeholder.com/600x600/f5ebe0/b08d57?text=Pearl'">
      <?php endforeach; ?>

      <?php if (count($images) > 1): ?>
        <button type="button" class="pd-arrow pd-arrow-prev" aria-label="Previous image">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <button type="button" class="pd-arrow pd-arrow-next" aria-label="Next image">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
            <path d="M9 6L15 12L9 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>

        <div class="pd-dots">
          <?php foreach ($images as $i => $img): ?>
            <span class="pd-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-index="<?php echo $i; ?>"></span>
          <?php endforeach; ?>
        </div>

        <div class="pd-counter">
          <span id="pdCounterCurrent">1</span> / <?php echo count($images); ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($product['stock'] <= 0): ?>
      <span class="out-of-stock">OUT OF STOCK</span>
    <?php endif; ?>
  </div>

  <div class="pd-info">
    <?php echo $message; ?>
    <div class="pd-category"><?php echo htmlspecialchars($product['category']); ?></div>
    <h1 class="pd-name"><?php echo htmlspecialchars($product['name']); ?></h1>
    <div class="pd-price"><?php echo formatPrice($product['price']); ?></div>
    <p class="pd-description"><?php echo htmlspecialchars($product['description']); ?></p>
    <span class="pd-stock in-stock">
      <span class="pd-stock-dot"></span>
      In Stock — <?php echo intval($product['stock']); ?> available
    </span>

    <form method="POST" id="addToCartForm">
      <?php if (!$isAdmin): ?>
      <div class="pd-qty-row">
        <span class="pd-qty-label">Quantity</span>
        <div class="pd-qty-control">
          <input type="number" name="quantity" id="quantity" class="pd-qty-input"
                 min="1" max="<?php echo $product['stock']; ?>" value="1">
        </div>
      </div>
      <?php endif; ?>

      <div class="pd-actions">
        <?php if ($isAdmin): ?>

          <div class="pd-msg" style="background:#fff3cd; color:#856404; padding:14px 18px; border-radius:8px; border:1px solid #ffe69c; font-size:14px;">
            👁️ <strong>Admin Mode</strong> • Read-only access to product catalog
          </div>

        <?php elseif ($product['stock'] > 0): ?>

          <button type="submit" name="add_to_cart" id="cartBtn" class="pd-btn pd-btn-cart cart-anim-btn">
            <div class="cart-icon-wrap">
              <svg width="34" height="28" viewBox="0 0 34 28" fill="none">
                <circle cx="11" cy="26" r="2" fill="#B08D57"/>
                <circle cx="25" cy="26" r="2" fill="#B08D57"/>
                <path d="M1 1h4l3.5 16h14l3-10H8" stroke="#FAF7F2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <div class="box-icon">
                <svg width="14" height="12" viewBox="0 0 14 12" fill="none">
                  <rect x="1" y="4" width="12" height="8" rx="1.5" fill="#B08D57"/>
                  <path d="M4 4V3a3 3 0 0 1 6 0v1" stroke="#B08D57" stroke-width="1.5" stroke-linecap="round"/>
                  <rect x="5" y="4" width="4" height="2" rx="0.5" fill="#FAF7F2"/>
                </svg>
              </div>
            </div>
            <span class="cart-label label-default">Add to Cart</span>
            <span class="cart-label label-adding">Adding...</span>
            <span class="cart-label label-done">Added!</span>
            <div class="check-circle">
              <svg width="22" height="22" viewBox="0 0 22 22" fill="none">
                <circle cx="11" cy="11" r="10" fill="#FAF7F2" fill-opacity="0.2"/>
                <polyline points="6,11 10,15 16,8" stroke="#FAF7F2" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
          </button>

        <?php else: ?>
          <button class="pd-btn pd-btn-cart" disabled style="opacity:0.5; cursor:not-allowed;">
            Out of Stock
          </button>
        <?php endif; ?>

        <button type="button" class="pd-btn pd-btn-help" onclick="openHelpModal()">
          <img src="images/help.png" alt="Help" class="help-btn-img">
        </button>

        <div id="helpModal" class="help-modal" style="display:none;">
          <div class="help-content">
            <span class="help-close" onclick="closeHelpModal()">&times;</span>
            <h2>Product Details</h2>
            <div class="help-faq">
              <div class="faq-item">
                <h3>💍 Product Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($product['name']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                <p><strong>SKU:</strong> #<?php echo str_pad($product['product_id'], 5, '0', STR_PAD_LEFT); ?></p>
              </div>
              <div class="faq-item">
                <h3>🦪 Material & Authenticity</h3>
                <p><strong>Material:</strong> <?php echo htmlspecialchars($product['material'] ?? 'Genuine Freshwater Pearl'); ?></p>
                <p><strong>Metal:</strong> <?php echo htmlspecialchars($product['metal'] ?? 'Sterling Silver 925'); ?></p>
                <p><strong>Authenticity:</strong> <?php echo htmlspecialchars($product['authenticity'] ?? 'Certified Genuine • 100% Authentic'); ?></p>
              </div>
              <div class="faq-item">
                <h3>📏 Specifications</h3>
                <p><strong>Dimensions:</strong> <?php echo htmlspecialchars($product['dimensions'] ?? 'Available upon request'); ?></p>
                <p><strong>Weight:</strong> <?php echo htmlspecialchars($product['weight'] ?? 'Lightweight'); ?></p>
                <p><strong>Pearl Size:</strong> <?php echo htmlspecialchars($product['pearl_size'] ?? '8-10mm'); ?></p>
              </div>
              <div class="faq-item">
                <h3>🧼 Care & Maintenance</h3>
                <p><?php echo htmlspecialchars($product['care_instructions'] ?? 'Avoid water and chemicals. Store in a soft pouch. Clean gently with a dry cloth.'); ?></p>
              </div>
              <div class="faq-item">
                <h3>✅ Quality Guarantee</h3>
                <p>✓ 30-day money-back guarantee<br>
                   ✓ Certified authentic pearls<br>
                   ✓ Premium craftsmanship<br>
                   ✓ Lifetime support</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>

</div>
</div>

<style>
.pd-qty-input {
  width: 90px !important;
  height: 42px !important;
  font-size: 18px !important;
  padding: 8px 12px !important;
  border-radius: 6px !important;
}

.pd-gallery {
  position: relative;
  width: 100%;
  overflow: hidden;
  border-radius: 12px;
}

.pd-gallery-img {
  width: 100%;
  height: auto;
  display: none;
  animation: pdFadeIn 0.4s ease;
}

.pd-gallery-img.active {
  display: block;
}

@keyframes pdFadeIn {
  from { opacity: 0; transform: scale(1.02); }
  to   { opacity: 1; transform: scale(1); }
}

.pd-arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.92);
  border: 1px solid rgba(176, 141, 87, 0.25);
  color: #2C1F0E;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  z-index: 5;
  box-shadow: 0 2px 14px rgba(0, 0, 0, 0.08);
  padding: 0;
}

.pd-arrow:hover {
  background: #B08D57;
  color: #FAF7F2;
  border-color: #B08D57;
  box-shadow: 0 4px 18px rgba(176, 141, 87, 0.45);
  transform: translateY(-50%) scale(1.06);
}

.pd-arrow-prev { left: 14px; }
.pd-arrow-next { right: 14px; }

.pd-dots {
  position: absolute;
  bottom: 18px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 8px;
  z-index: 5;
  background: rgba(255, 255, 255, 0.7);
  padding: 8px 14px;
  border-radius: 50px;
  backdrop-filter: blur(6px);
}

.pd-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: rgba(44, 31, 14, 0.25);
  cursor: pointer;
  transition: all 0.3s ease;
}

.pd-dot.active {
  background: #B08D57;
  width: 26px;
  border-radius: 4px;
}

.pd-dot:hover {
  background: rgba(176, 141, 87, 0.6);
}

.pd-counter {
  position: absolute;
  top: 14px;
  right: 14px;
  background: rgba(44, 31, 14, 0.7);
  color: #FAF7F2;
  padding: 6px 12px;
  border-radius: 50px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 1px;
  z-index: 5;
  backdrop-filter: blur(4px);
}

.cart-anim-btn {
  position: relative;
  width: 220px;
  height: 58px;
  padding: 0;
  background: #2C1F0E;
  border: 2px solid #B08D57;
  border-radius: 50px;
  cursor: pointer;
  overflow: hidden;
  transition: border-color 0.4s, background 0.4s, box-shadow 0.3s;
}

.cart-anim-btn:hover {
  background: #1E1209;
  box-shadow: 0 6px 24px rgba(176,141,87,0.35);
}

.cart-anim-btn.success {
  border-color: #4E7040;
  background: #4E7040;
  box-shadow: 0 6px 24px rgba(78,112,64,0.35);
}

.cart-anim-btn .cart-icon-wrap {
  position: absolute;
  left: 24px;
  top: 50%;
  transform: translateY(-50%);
}

.cart-anim-btn .box-icon {
  position: absolute;
  top: 4px;
  left: 50%;
  transform: translateX(-50%) translateY(-50%);
  transform: translateX(-50%) translateY(-36px);
  opacity: 0;
  transition: transform 0.4s ease 0.45s, opacity 0.3s ease 0.45s;
}

.cart-anim-btn.is-adding .box-icon,
.cart-anim-btn.success .box-icon {
  transform: translateX(-50%) translateY(0);
  opacity: 1;
}

.cart-anim-btn .cart-label {
  position: absolute;
  left: 70px;
  top: 50%;
  transform: translateY(-50%);
  font-family: var(--font-sans, 'Jost', sans-serif);
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 1.5px;
  color: #FAF7F2;
  white-space: nowrap;
  pointer-events: none;
  transition: opacity 0.25s ease;
}

.cart-anim-btn .label-default { opacity: 1; }
.cart-anim-btn .label-adding  { opacity: 0; }
.cart-anim-btn .label-done    { opacity: 0; }

.cart-anim-btn.is-adding .label-default { opacity: 0; }
.cart-anim-btn.is-adding .label-adding  { opacity: 1; }
.cart-anim-btn.is-adding .label-done    { opacity: 0; }

.cart-anim-btn.success .label-default { opacity: 0; }
.cart-anim-btn.success .label-adding  { opacity: 0; }
.cart-anim-btn.success .label-done    { opacity: 1; }

.cart-anim-btn .check-circle {
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%) scale(0);
  transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1) 0.1s;
  pointer-events: none;
}

.cart-anim-btn.success .check-circle {
  transform: translateY(-50%) scale(1);
}
</style>

<script>
(function () {
  const gallery = document.getElementById('pdGallery');
  if (!gallery) return;

  const imgs    = gallery.querySelectorAll('.pd-gallery-img');
  const dots    = gallery.querySelectorAll('.pd-dot');
  const prevBtn = gallery.querySelector('.pd-arrow-prev');
  const nextBtn = gallery.querySelector('.pd-arrow-next');
  const counter = document.getElementById('pdCounterCurrent');

  if (imgs.length <= 1) return;

  let current = 0;

  function show(index) {
    if (index < 0) index = imgs.length - 1;
    if (index >= imgs.length) index = 0;

    imgs.forEach((img, i) => img.classList.toggle('active', i === index));
    dots.forEach((dot, i) => dot.classList.toggle('active', i === index));
    if (counter) counter.textContent = index + 1;
    current = index;
  }

  prevBtn && prevBtn.addEventListener('click', () => show(current - 1));
  nextBtn && nextBtn.addEventListener('click', () => show(current + 1));

  dots.forEach(dot => {
    dot.addEventListener('click', () => show(parseInt(dot.dataset.index, 10)));
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'ArrowLeft')  show(current - 1);
    if (e.key === 'ArrowRight') show(current + 1);
  });

  let touchStartX = 0;
  gallery.addEventListener('touchstart', e => { touchStartX = e.changedTouches[0].screenX; });
  gallery.addEventListener('touchend',   e => {
    const diff = e.changedTouches[0].screenX - touchStartX;
    if (Math.abs(diff) > 40) show(current + (diff < 0 ? 1 : -1));
  });
})();

(function () {
  const btn  = document.getElementById('cartBtn');
  const form = document.getElementById('addToCartForm');
  if (!btn || !form) return;

  btn.addEventListener('click', function (e) {
    if (btn.classList.contains('is-adding') || btn.classList.contains('success')) {
      e.preventDefault();
      return;
    }

    e.preventDefault();
    btn.classList.add('is-adding');
    btn.style.pointerEvents = 'none';

    setTimeout(() => {
      btn.classList.remove('is-adding');
      btn.classList.add('success');

      setTimeout(() => {
        form.submit();
      }, 900);
    }, 1600);
  });
})();

function openHelpModal()  { document.getElementById('helpModal').style.display = 'block'; }
function closeHelpModal() { document.getElementById('helpModal').style.display = 'none'; }
window.onclick = function (e) {
  const m = document.getElementById('helpModal');
  if (e.target === m) m.style.display = 'none';
};
</script>

<?php
$recentProducts = [];
foreach ($oldRecentIds as $rid) {
    $rid = intval($rid);
    if ($rid > 0 && $rid != $product_id) {
        try {
            $sql = "SELECT product_id, name, price, image FROM products WHERE product_id = :pid";
            $rstatement = $pdo->prepare($sql);
            $rstatement->bindValue(':pid', $rid);
            $rstatement->execute();
            $rrow = $rstatement->fetch();
            if ($rrow) {
                $recentProducts[] = $rrow;
            }
        } catch (PDOException $e) {
        }
    }
}
?>

<?php if (count($recentProducts) > 0): ?>
<section class="recent-section">
    <div class="wrap">
        <h2 class="sec-title">Recently Viewed</h2>
        <div class="recent-grid">
            <?php foreach ($recentProducts as $rp): ?>
                <?php
                $rImgList = array_filter(array_map('trim', explode(',', $rp['image'] ?? '')));
                $rMain    = !empty($rImgList) ? reset($rImgList) : '';
                ?>
                <a href="product.php?id=<?php echo $rp['product_id']; ?>" class="recent-card">
                    <img src="images/<?php echo htmlspecialchars($rMain); ?>"
                         alt="<?php echo htmlspecialchars($rp['name']); ?>"
                         onerror="this.src='https://via.placeholder.com/180x180/f5ebe0/b08d57?text=Pearl'">
                    <h4><?php echo htmlspecialchars($rp['name']); ?></h4>
                    <p><?php echo formatPrice($rp['price']); ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>

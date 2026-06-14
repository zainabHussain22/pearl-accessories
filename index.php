<?php

include 'includes/header.php';
include 'currency_helper.php';
?>

<?php if (isset($_GET['rating_success'])): ?>
<style>
    .alert-top {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 16px 32px;
        border-radius: 8px;
        z-index: 9999;
        animation: slideDown 0.4s ease-out;
        font-weight: 600;
        letter-spacing: 1px;
        font-family: 'Jost', sans-serif;
        font-size: 14px;
        background: linear-gradient(135deg, #4e7040 0%, #3a5330 100%);
        color: #fff;
        box-shadow: 0 4px 16px rgba(78, 112, 64, 0.4);
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }

    @keyframes slideUp {
        from { opacity: 1; transform: translateX(-50%) translateY(0); }
        to { opacity: 0; transform: translateX(-50%) translateY(-20px); }
    }
</style>

<div id="rating-alert" class="alert-top">
    ✨ Thank you! Your review has been submitted successfully.
</div>

<script>
    setTimeout(function() {
        const alert = document.getElementById('rating-alert');
        if(alert) {
            alert.style.animation = 'slideUp 0.5s ease-in forwards';
            setTimeout(() => alert.remove(), 500);
        }
    }, 4000);
</script>
<?php endif; ?>

<section class="hero">
    <div class="hero-left">
        <h1>ELEGANT<br>JEWELRY</h1>
        <a href="index.php#products" class="hero-btn">SHOP NOW</a>
    </div>
    <div class="hero-right">
        <img src="images/header.jpg" alt="Elegant Jewelry Model">
    </div>
</section>

<?php
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true &&
    isset($_SESSION['past_purchases']) && !empty($_SESSION['past_purchases'])) {
    $past = $_SESSION['past_purchases'];
?>
        <style>
            .past-bar {
                overflow: hidden;
                padding: 14px 0;
            }
            .past-bar .wrap {
                display: flex;
                align-items: center;
                gap: 0;
            }
            .past-bar h3 {
                flex-shrink: 0;
                margin: 0;
                padding-right: 18px;
                white-space: nowrap;
                border-right: 1px solid rgba(176, 141, 87, 0.35);
            }
            .past-items-viewport {
                flex: 1;
                overflow: hidden;
                position: relative;
                margin-left: 18px;
            }
            .past-items {
                display: inline-flex;
                flex-wrap: nowrap;
                white-space: nowrap;
                animation: pastTickerScroll 40s linear infinite;
            }
            .past-items:hover {
                animation-play-state: paused;
            }
            .past-tag {
                display: inline-block;
                padding: 0 28px;
                white-space: nowrap;
                flex-shrink: 0;
            }
            @keyframes pastTickerScroll {
                0%   { transform: translateX(0); }
                100% { transform: translateX(-50%); }
            }
        </style>

        <div class="past-bar">
            <div class="wrap">
                <h3>🕐 Welcome Back! Your Recent Purchases:</h3>
                <div class="past-items-viewport">
                    <div class="past-items">
                        <?php
                        for ($i = 0; $i < 2; $i++) {
                            foreach ($past as $p) {
                                echo '<span class="past-tag">'
                                    . htmlspecialchars($p['name'])
                                    . ' (x' . $p['quantity'] . ') — '
                                    . formatPrice($p['total'])
                                    . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
<?php
}
?>

<section class="cat-section">
    <div class="wrap">
        <div class="cat-row">
            <div class="cat-item active-cat" data-cat="All">
                <div class="cat-img">
                    <img src="images/all.png" alt="All Jewelry">
                </div>
                <h3>All</h3>
            </div>
            <div class="cat-item" data-cat="Bracelets">
                <div class="cat-img">
                    <img src="images/ornament.png" alt="Bracelets" onerror="this.src='https://via.placeholder.com/180x180/f5ebe0/b08d57?text=💍'">
                </div>
                <h3>Bracelets</h3>
            </div>
            <div class="cat-item" data-cat="Rings">
                <div class="cat-img">
                    <img src="images/ring.png" alt="Rings" onerror="this.src='https://via.placeholder.com/180x180/f5ebe0/b08d57?text=💎'">
                </div>
                <h3>Rings</h3>
            </div>
            <div class="cat-item" data-cat="Necklaces">
                <div class="cat-img">
                    <img src="images/pendant.png" alt="Necklaces" onerror="this.src='https://via.placeholder.com/180x180/f5ebe0/b08d57?text=📿'">
                </div>
                <h3>Necklaces</h3>
            </div>
            <div class="cat-item" data-cat="Earrings">
                <div class="cat-img">
                    <img src="images/earrings.png" alt="Earrings" onerror="this.src='https://via.placeholder.com/180x180/f5ebe0/b08d57?text=✨'">
                </div>
                <h3>Earrings</h3>
            </div>
            <div class="cat-item" data-cat="Pendants">
                <div class="cat-img">
                    <img src="images/diamond-pendant.png" alt="Pendants" onerror="this.src='https://via.placeholder.com/180x180/f5ebe0/b08d57?text=👑'">
                </div>
                <h3>Pendants</h3>
            </div>
        </div>
    </div>
</section>

<section class="prod-section" id="products">
    <div class="wrap">
        <h2 class="sec-title">Our Collection</h2>
        <?php
        try {
            $result = $pdo->query("SELECT * FROM products");
        }
        catch (PDOException $e) {
            die($e->getMessage());
        }
        ?>
        <div class="prod-grid">
            <?php while ($p = $result->fetch()): ?>
                <?php
                $imgList   = array_filter(array_map('trim', explode(',', $p['image'] ?? '')));
                $mainImage = !empty($imgList) ? reset($imgList) : '';
                ?>
            <a href="product.php?id=<?php echo $p['product_id']; ?>"
               class="prod-card"
               data-category="<?php echo htmlspecialchars($p['category'] ?? ''); ?>">
                <div class="prod-thumb" style="position: relative;">
                    <img src="images/<?php echo htmlspecialchars($mainImage); ?>"
                         alt="<?php echo htmlspecialchars($p['name']); ?>"
                         onerror="this.src='https://via.placeholder.com/280x280/f5ebe0/b08d57?text=Pearl'">
                    <?php if ($p['stock'] <= 0): ?>
                        <span class="out-of-stock">OUT OF STOCK</span>
                    <?php endif; ?>
                </div>
                <div class="prod-detail">
                    <span class="prod-tag"><?php echo htmlspecialchars($p['category']); ?></span>
                    <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                    <p class="prod-price"><?php echo formatPrice($p['price']); ?></p>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<script>
const catItems = document.querySelectorAll('.cat-item');
const prodCards = document.querySelectorAll('.prod-card');
const productsSection = document.getElementById('products');

catItems.forEach(cat => {
    cat.addEventListener('click', () => {
        const category = cat.dataset.cat;
        prodCards.forEach(card => {
            const cardCategories = card.dataset.category.split(',').map(c => c.trim());
            if (category === 'All' || cardCategories.includes(category)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        catItems.forEach(c => c.classList.remove('active-cat'));
        cat.classList.add('active-cat');

        if (productsSection) {
            productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>

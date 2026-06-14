<?php


function formatPrice($amount, $decimals = 2) {
    $formatted = number_format($amount, $decimals);
    return '<span class="price-with-sar"><img src="images/SAR.webp" alt="SAR" class="currency-icon" title="Saudi Riyal">' . $formatted . '</span>';
}

function getSARIcon() {
    return '<img src="images/SAR.webp" alt="SAR" class="currency-icon" title="Saudi Riyal">';
}

function formatPricePlainText($amount, $decimals = 2) {
    return 'SR ' . number_format($amount, $decimals);
}
?>

<?php
/**
 * ============================================================================
 * FILE:  currency_helper.php
 * PURPOSE: Displays currency amounts in SAR with the SAR icon image.
 *          Replaces all "$" symbols with the SAR image icon from images/SAR.webp
 *
 * USAGE: Include at top of page, then use formatPrice($amount) instead of '$...'
 *
 * IMAGE REQUIREMENT: Must have images/SAR.webp in the project folder.
 * ============================================================================
 */
// Student Name: Ayah

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

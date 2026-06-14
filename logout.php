<?php

session_start();

// Delete the cookie for past_purchases
setcookie('past_purchases', '', time() - 3600, '/');

session_destroy();
header("Location: login.php");
exit;
?>

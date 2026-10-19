<?php
// Get Cart Count API
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/cart.php';

header('Content-Type: application/json');

echo json_encode(['count' => getCartCount()]);


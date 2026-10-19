<?php
// Add to Cart API
// Start session first (before any output)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/lang.php';
require_once 'includes/cart.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$productId = (int)($_POST['product_id'] ?? 0);
$size = trim($_POST['size'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 1);

if ($productId <= 0 || empty($size)) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or size']);
    exit;
}

// Verify product exists and is available
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT p.*, ps.quantity as stock_quantity
        FROM products p
        LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.size = ?
        WHERE p.id = ? AND p.is_active = 1 AND p.is_ecommerce = 1
    ");
    $stmt->execute([$size, $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Check stock availability
    // For e-commerce products, if stock is 0 or NULL, treat as unlimited stock
    $availableStock = $product['stock_quantity'] !== null ? (int)$product['stock_quantity'] : null;
    $currentCartQuantity = 0;
    
    // Check if item already in cart
    $cartKey = $productId . '_' . $size;
    if (isset($_SESSION['cart'][$cartKey])) {
        $currentCartQuantity = $_SESSION['cart'][$cartKey]['quantity'];
    }
    
    // Only check stock if stock is tracked (not null and > 0)
    // E-commerce products with 0 stock are treated as unlimited
    if ($availableStock !== null && $availableStock > 0) {
        if ($availableStock < ($currentCartQuantity + $quantity)) {
            echo json_encode([
                'success' => false, 
                'message' => getLang() === 'ar' 
                    ? 'الكمية المتاحة غير كافية' 
                    : 'Insufficient stock available'
            ]);
            exit;
        }
    }
    // If stock is 0 or NULL, allow unlimited purchases (common for e-commerce products)
    
    // Add to cart
    addToCart($productId, $size, $quantity);
    
    echo json_encode([
        'success' => true, 
        'message' => getLang() === 'ar' ? 'تمت الإضافة بنجاح' : 'Added successfully',
        'cart_count' => getCartCount()
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log('Add to cart error: ' . $e->getMessage());
    
    // Return detailed error in development, generic in production
    $errorMessage = getLang() === 'ar' ? 'حدث خطأ' : 'An error occurred';
    
    // For debugging, you can uncomment the next line to see the actual error
    // $errorMessage = $e->getMessage();
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage,
        'error' => $e->getMessage() // Remove this in production
    ]);
}


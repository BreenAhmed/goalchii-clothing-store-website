<?php
/**
 * Shopping Cart Helper Functions
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (!function_exists('cartLocalizedProductName')) {
    function cartLocalizedProductName(array $product): string {
        $lang = function_exists('getLang') ? getLang() : 'en';
        $priorityMap = [
            'ar' => ['name_ar', 'name_ku', 'name'],
            'ku' => ['name_ku', 'name_ar', 'name'],
            'en' => ['name', 'name_ar', 'name_ku'],
        ];

        $order = $priorityMap[$lang] ?? ['name', 'name_ar', 'name_ku'];

        foreach ($order as $field) {
            if (!empty($product[$field])) {
                return $product[$field];
            }
        }

        return $product['product_code'] ?? '';
    }
}

/**
 * Add product to cart
 */
function addToCart($productId, $size, $quantity = 1) {
    $key = $productId . '_' . $size;
    
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $productId,
            'size' => $size,
            'quantity' => $quantity
        ];
    }
    
    return true;
}

/**
 * Update cart item quantity
 */
function updateCartItem($productId, $size, $quantity) {
    $key = $productId . '_' . $size;
    
    if ($quantity <= 0) {
        removeFromCart($productId, $size);
        return false;
    }
    
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] = $quantity;
        return true;
    }
    
    return false;
}

/**
 * Remove item from cart
 */
function removeFromCart($productId, $size) {
    $key = $productId . '_' . $size;
    
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        return true;
    }
    
    return false;
}

/**
 * Get cart items with product details
 */
function getCartItems() {
    if (empty($_SESSION['cart'])) {
        return [
            'items' => [],
            'subtotal' => 0,
            'count' => 0
        ];
    }
    
    // Use __DIR__ to get absolute path from this file's location
    require_once __DIR__ . '/../../config/database.php';
    $pdo = getDBConnection();
    
    $cartItems = [];
    $total = 0;
    
    foreach ($_SESSION['cart'] as $key => $item) {
        try {
            // Get product details
            $stmt = $pdo->prepare("
                SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.id = ? AND p.is_active = 1 AND p.is_ecommerce = 1
            ");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Get product image
                $imageSrc = $product['image_url'];
                try {
                    $tableCheck = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetch();
                    if ($tableCheck) {
                        $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY display_order ASC LIMIT 1");
                        $imgStmt->execute([$product['id']]);
                        $primaryImg = $imgStmt->fetch();
                        if ($primaryImg) {
                            $imageSrc = $primaryImg['image_url'];
                        }
                    }
                } catch (Exception $e) {
                    // Use default image_url
                }
                
                $itemTotal = $product['price'] * $item['quantity'];
                $total += $itemTotal;
                
                $cartItems[] = [
                    'key' => $key,
                    'product_id' => $product['id'],
                    'product_name' => cartLocalizedProductName($product),
                    'product_code' => $product['product_code'],
                    'size' => $item['size'],
                    'quantity' => $item['quantity'],
                    'price' => $product['price'],
                    'total' => $itemTotal,
                    'image' => $imageSrc
                ];
            }
        } catch (Exception $e) {
            // Skip invalid products
            continue;
        }
    }
    
    return [
        'items' => $cartItems,
        'subtotal' => $total,
        'count' => count($cartItems)
    ];
}

/**
 * Get cart count
 */
function getCartCount() {
    if (empty($_SESSION['cart'])) {
        return 0;
    }
    
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

/**
 * Clear cart
 */
function clearCart() {
    $_SESSION['cart'] = [];
    return true;
}

/**
 * Calculate shipping cost
 */
function calculateShipping($subtotal) {
    // Free shipping for orders over 200 IQD
    if ($subtotal >= 200) {
        return 0;
    }
    // Standard shipping cost
    return 10;
}


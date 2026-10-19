<?php
// Order Success Page
require_once '../includes/lang.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$orderData = $_SESSION['order_confirmation'] ?? null;

if (empty($orderData)) {
    header('Location: index.php');
    exit;
}

$orderItems = $orderData['items'] ?? [];
$orderNumber = $orderData['order_number'];
$createdAt = $orderData['created_at'];
$paymentMethod = $orderData['payment_method'] ?? 'cash_on_delivery';

// Clear confirmation data after retrieval
unset($_SESSION['order_confirmation']);
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _t('order_success_title'); ?> - <?php _t('brand_name'); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .success-container {
            max-width: 800px;
            margin: 4rem auto;
            padding: 2rem;
            text-align: center;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        
        .success-icon i {
            font-size: 3rem;
            color: var(--black);
        }
        
        .order-details {
            background: var(--white);
            padding: 2rem;
            margin: 2rem 0;
            border-radius: 0;
            text-align: right;
        }
        
        .order-details h3 {
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .order-items-list {
            margin-top: 2rem;
            text-align: right;
        }
        
        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            align-items: center;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
        }
        
        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 0;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: var(--black);
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--gray-200);
            color: var(--black);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="nav-container">
            <nav class="navbar">
                <a href="index.php" class="logo">
                    <img src="../assets/images/logo/logo.png?v=1" alt="GOALCHII" style="height: 60px; width: 60px; border-radius: 50%; object-fit: cover; display: block;">
                </a>
                
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="nav-link"><?php _t('home'); ?></a></li>
                    <li><a href="products.php" class="nav-link"><?php _t('products'); ?></a></li>
                    <li class="nav-cart-item">
                        <a href="cart.php" class="nav-link" style="position: relative;">
                            <i class="bi bi-cart3" style="font-size: 1.25rem;"></i>
                            <?php 
                            require_once 'includes/cart.php';
                            $cartCount = getCartCount();
                            if ($cartCount > 0): 
                            ?>
                                <span id="cart-count" style="position: absolute; top: -8px; right: -8px; background: var(--primary-color); color: var(--black); border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                                    <?php echo $cartCount; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Success Section -->
    <section class="section" style="padding: 4rem 0;">
        <div class="container">
            <div class="success-container">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?php _t('order_success_title'); ?></h1>
                
                <p style="font-size: 1.125rem; color: var(--gray-600); margin-bottom: 2rem;"><?php _t('order_success_message'); ?></p>
                
                <div class="order-details">
                    <h3><?php _t('order_details_title'); ?></h3>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_number_label'); ?>:</span>
                        <span><?php echo htmlspecialchars($orderNumber); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_date_label'); ?>:</span>
                        <span><?php echo date('Y-m-d H:i', strtotime($createdAt)); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_name_label'); ?>:</span>
                        <span><?php echo htmlspecialchars($orderData['customer_name']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_email_label'); ?>:</span>
                        <span><?php echo htmlspecialchars($orderData['customer_email']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_phone_label'); ?>:</span>
                        <span><?php echo htmlspecialchars($orderData['customer_phone']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_city_label'); ?>:</span>
                        <span><?php echo htmlspecialchars($orderData['city']); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_shipping_address_label'); ?>:</span>
                        <span><?php echo nl2br(htmlspecialchars($orderData['shipping_address'])); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_payment_method_label'); ?>:</span>
                        <span><?php _t('cash_on_delivery'); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span style="font-weight: 600;"><?php _t('order_payment_status_label'); ?>:</span>
                        <span style="color: green;"><?php _t('order_payment_status_pending'); ?></span>
                    </div>
                    
                    <?php if (!empty($orderData['customer_notes'])): ?>
                        <div class="detail-row" style="align-items: flex-start; flex-direction: column; gap: 0.5rem; border-bottom: none;">
                            <span style="font-weight: 600;"><?php _t('order_customer_notes_label'); ?>:</span>
                            <span><?php echo nl2br(htmlspecialchars($orderData['customer_notes'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($orderItems)): ?>
                        <div class="order-items-list" style="margin-top: 2.5rem;">
                            <h4 style="margin-bottom: 1rem; text-align: right;"><?php _t('order_summary_title'); ?></h4>
                            <?php foreach ($orderItems as $item): ?>
                                <div class="order-item">
                                    <?php if (!empty($item['image'])): ?>
                                        <?php 
                                            $itemImage = $item['image'];
                                            if ($itemImage && !preg_match('/^https?:\/\//', $itemImage)) {
                                                $itemImage = '../' . ltrim($itemImage, '/');
                                            }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($itemImage); ?>"
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             class="order-item-image"
                                             onerror="this.style.display='none'">
                                    <?php endif; ?>
                                    <div style="flex: 1; text-align: right;">
                                        <p style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                        <p style="margin: 0.25rem 0 0 0; color: var(--gray-600); font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($item['size']); ?> × <?php echo $item['quantity']; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p style="margin: 0; font-weight: 600;"><?php echo number_format($item['total']); ?> د.ع</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="detail-row" style="border-bottom: none; margin-top: 1rem;">
                                <span style="font-weight: 600;"><?php _t('order_subtotal_label'); ?>:</span>
                                <span><?php echo number_format($orderData['subtotal']); ?> د.ع</span>
                            </div>
                            <div class="detail-row" style="border-bottom: none;">
                                <span style="font-weight: 600;"><?php _t('order_shipping_label'); ?>:</span>
                                <span><?php echo number_format($orderData['shipping_cost']); ?> د.ع</span>
                            </div>
                            <div class="detail-row" style="border-bottom: none;">
                                <span style="font-weight: 600;"><?php _t('order_total_due_label'); ?>:</span>
                                <span style="font-size: 1.2rem; font-weight: 700;"><?php echo number_format($orderData['total_amount']); ?> د.ع</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group">
                    <a href="products.php" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> 
                        <?php _t('continue_shopping'); ?>
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <?php _t('back_home'); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 <a href="https://www.brezzo.co/" target="_blank" style="color: var(--primary-color); text-decoration: none;">Brezzo</a>. <?php _t('copyright'); ?></p>
            </div>
        </div>
    </footer>
</body>
</html>


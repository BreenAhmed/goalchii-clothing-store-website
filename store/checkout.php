<?php
// Checkout Page - Multi-language E-commerce
require_once '../config/database.php';
require_once 'includes/cart.php';
require_once '../includes/lang.php';
require_once '../includes/whatsapp-helper.php';

// Redirect if cart is empty
$cart = getCartItems();
if (empty($cart['items'])) {
    header('Location: products.php');
    exit;
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerEmail = trim($_POST['customer_email'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? 'Iraq');
    $customerNotes = trim($_POST['customer_notes'] ?? '');
    $paymentMethod = 'cash_on_delivery'; // Only cash on delivery available
    
    // Validation
    if (empty($customerName)) {
        $errors[] = t('customer_name_required');
    }
    if (empty($customerEmail) || !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('valid_email_required');
    }
    if (empty($customerPhone)) {
        $errors[] = t('phone_required');
    }
    if (empty($shippingAddress)) {
        $errors[] = t('shipping_address_required');
    }
    if (empty($city)) {
        $errors[] = t('city_required');
    }
    
    // Process order if no errors
    if (empty($errors)) {
        try {
            $pdo = getDBConnection();
            
            // Ensure order_requests table exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS order_requests (
                    id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
                    order_number VARCHAR(50) NOT NULL,
                    customer_name VARCHAR(150) NOT NULL,
                    customer_email VARCHAR(150) NOT NULL,
                    customer_phone VARCHAR(30),
                    city VARCHAR(100),
                    postal_code VARCHAR(30),
                    country VARCHAR(100) DEFAULT 'Iraq',
                    shipping_address TEXT,
                    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
                    shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
                    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
                    payment_method VARCHAR(50) DEFAULT 'cash_on_delivery',
                    customer_notes TEXT,
                    items_json LONGTEXT,
                    status ENUM('new','processing','completed','cancelled') DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_order_number (order_number),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
            
            // Calculate totals
            $subtotal = $cart['subtotal'];
            $shippingCost = calculateShipping($subtotal);
            $totalAmount = $subtotal + $shippingCost;
            
            // Generate order reference
            $orderNumber = 'REQ-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Prepare order items payload
            $itemsPayload = [];
            foreach ($cart['items'] as $item) {
                $itemsPayload[] = [
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_code' => $item['product_code'],
                    'size'         => $item['size'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'total'        => $item['total'],
                    'image'        => $item['image'] ?? null
                ];
            }
            $itemsJson = json_encode($itemsPayload, JSON_UNESCAPED_UNICODE);
            
            // Insert order request
            $stmt = $pdo->prepare("
                INSERT INTO order_requests (
                    order_number, customer_name, customer_email, customer_phone,
                    city, postal_code, country, shipping_address,
                    subtotal, shipping_cost, total_amount,
                    payment_method, customer_notes, items_json
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderNumber,
                $customerName,
                $customerEmail,
                $customerPhone,
                $city,
                $postalCode,
                $country,
                $shippingAddress,
                $subtotal,
                $shippingCost,
                $totalAmount,
                $paymentMethod,
                $customerNotes,
                $itemsJson
            ]);
            
            // Preserve summary for confirmation page
            $_SESSION['order_confirmation'] = [
                'order_number' => $orderNumber,
                'created_at' => date('Y-m-d H:i:s'),
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'shipping_address' => $shippingAddress,
                'city' => $city,
                'postal_code' => $postalCode,
                'country' => $country,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'customer_notes' => $customerNotes,
                'items' => $cart['items']
            ];
            
            // Send WhatsApp message to customer
            try {
                $whatsappResult = sendOrderWhatsAppMessage($customerPhone, $_SESSION['order_confirmation']);
                
                // Store WhatsApp result for admin reference
                if ($whatsappResult['success']) {
                    $_SESSION['whatsapp_sent'] = true;
                    $_SESSION['whatsapp_link'] = $whatsappResult['link'] ?? null;
                    error_log('[WhatsApp] Message sent successfully to ' . $customerPhone);
                } else {
                    error_log('[WhatsApp] Failed to send message: ' . ($whatsappResult['error'] ?? 'Unknown error'));
                }
            } catch (Exception $e) {
                error_log('[WhatsApp] Exception: ' . $e->getMessage());
            }
            
            // Clear cart
            clearCart();
            
            // Redirect to success page
            header('Location: order-success.php');
            exit;
            
        } catch (Exception $e) {
            $logEntry = '[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL . str_repeat('-', 80) . PHP_EOL;
            @file_put_contents(__DIR__ . '/checkout-error.log', $logEntry, FILE_APPEND);
            error_log('[Checkout] Order processing error: ' . $e->getMessage());
            
            $errors[] = t('order_processing_error');
        }
    }
}

// Calculate shipping
$shippingCost = calculateShipping($cart['subtotal']);
$totalAmount = $cart['subtotal'] + $shippingCost;
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _t('checkout'); ?> - جول تشي سبورتس</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }
        
        .checkout-form {
            background: var(--white);
            padding: 2rem;
            border-radius: 0;
        }
        
        .order-summary {
            background: var(--gray-100);
            padding: 2rem;
            border-radius: 0;
            position: sticky;
            top: 2rem;
            height: fit-content;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--black);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 0;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .payment-method {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .payment-option {
            flex: 1;
            padding: 1rem;
            border: 2px solid var(--gray-300);
            border-radius: 0;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        
        .payment-option input[type="radio"] {
            display: none;
        }
        
        .payment-option.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: var(--black);
        }
        
        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-totals {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--gray-300);
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .total-row.final {
            font-size: 1.25rem;
            font-weight: 700;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid var(--gray-300);
        }
        
        .btn-checkout {
            width: 100%;
            padding: 1rem;
            background: var(--primary-color);
            color: var(--black);
            border: none;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            border-radius: 0;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="nav-container">
            <nav class="navbar">
                <?php $currentLang = getLang(); ?>
                <div class="navbar-brand">
                    <a href="index.php" class="logo">
                        <img src="../assets/images/logo/logo.png?v=1" alt="GOALCHII" style="height: 60px; width: 60px; border-radius: 50%; object-fit: cover; display: block;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display: none; font-size: 2rem; font-weight: 900; color: var(--primary-color);">GOALCHII</span>
                    </a>
                    <div class="mobile-language-switcher">
                        <a href="#" class="nav-link lang-toggle-mobile" onclick="event.preventDefault(); var dropdown = document.getElementById('langDropdownMobile'); if (dropdown) { dropdown.classList.toggle('show'); } var desktopDropdown = document.getElementById('langDropdownDesktop'); if (desktopDropdown) { desktopDropdown.classList.remove('show'); }">
                            <?php 
                            echo $currentLang === 'ar' ? 'العربية' : ($currentLang === 'ku' ? 'کوردی' : 'English'); 
                            ?> ▼
                        </a>
                        <div id="langDropdownMobile" class="lang-dropdown">
                            <a href="?lang=ar" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
                        </div>
                    </div>
                    <a href="cart.php" class="mobile-cart-icon" style="position: relative;">
                        <i class="bi bi-cart3" style="font-size: 1.25rem;"></i>
                        <?php 
                        require_once 'includes/cart.php';
                        $cartCount = getCartCount();
                        if ($cartCount > 0): 
                        ?>
                            <span class="cart-badge" style="position: absolute; top: -8px; right: -8px; background: var(--primary-color); color: var(--black); border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.php" class="nav-link"><?php _t('home'); ?></a></li>
                    <li><a href="products.php" class="nav-link"><?php _t('products'); ?></a></li>
                    <li><a href="about.php" class="nav-link"><?php _t('about'); ?></a></li>
                    <li><a href="blog.php" class="nav-link"><?php _t('blog'); ?></a></li>
                    <li><a href="contact.php" class="nav-link"><?php _t('contact'); ?></a></li>
                    <li class="language-switcher">
                        <a href="#" class="nav-link lang-toggle" style="font-weight: 600;" onclick="event.preventDefault(); var dropdown = document.getElementById('langDropdownDesktop'); if (dropdown) { dropdown.classList.toggle('show'); } var mobileDropdown = document.getElementById('langDropdownMobile'); if (mobileDropdown) { mobileDropdown.classList.remove('show'); }">
                            <?php
                            echo $currentLang === 'ar' ? 'العربية' : ($currentLang === 'ku' ? 'کوردی' : 'English'); 
                            ?> ▼
                        </a>
                        <div id="langDropdownDesktop" class="lang-dropdown">
                            <a href="?lang=ar" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
                        </div>
                    </li>
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
                
                <button class="menu-toggle" id="menuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Checkout Section -->
    <section class="section" style="padding: 4rem 0;">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem; font-size: 2.5rem;">
                <?php _t('checkout'); ?>
            </h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-right: 1.5rem;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="checkout-container">
                <!-- Checkout Form -->
                <div class="checkout-form">
                    <form method="POST" id="checkoutForm">
                        <!-- Customer Information -->
                        <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">
                            <?php _t('customer_information'); ?>
                        </h2>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _t('full_name'); ?> *</label>
                            <input type="text" name="customer_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($_POST['customer_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php _t('email'); ?> *</label>
                                <input type="email" name="customer_email" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['customer_email'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php _t('phone'); ?> *</label>
                                <input type="tel" name="customer_phone" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['customer_phone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <!-- Shipping Address -->
                        <h2 style="margin-top: 2rem; margin-bottom: 1.5rem; font-size: 1.5rem;">
                            <?php _t('shipping_address'); ?>
                        </h2>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _t('full_address'); ?> *</label>
                            <textarea name="shipping_address" class="form-control" rows="3" required><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label"><?php _t('city'); ?> *</label>
                                <input type="text" name="city" class="form-control" required
                                       value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label"><?php _t('postal_code'); ?></label>
                                <input type="text" name="postal_code" class="form-control"
                                       value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label"><?php _t('country'); ?></label>
                            <input type="text" name="country" class="form-control" value="Iraq"
                                   value="<?php echo htmlspecialchars($_POST['country'] ?? 'Iraq'); ?>">
                        </div>
                        
                        <!-- Payment Method -->
                        <h2 style="margin-top: 2rem; margin-bottom: 1.5rem; font-size: 1.5rem;">
                            <?php _t('payment_method'); ?>
                        </h2>
                        
                        <div class="payment-method" style="display: flex; justify-content: center;">
                            <div style="padding: 2rem; background: var(--gray-100); border-radius: 0; text-align: center; max-width: 400px;">
                                <i class="bi bi-cash" style="font-size: 3rem; color: var(--primary-color); display: block; margin-bottom: 1rem;"></i>
                                <h3 style="margin-bottom: 0.5rem;">
                                    <?php _t('cash_on_delivery'); ?>
                                </h3>
                                <p style="color: var(--gray-600); margin: 0;">
                                    <?php _t('cash_on_delivery_desc'); ?>
                                </p>
                                <input type="hidden" name="payment_method" value="cash_on_delivery">
                            </div>
                        </div>
                        
                        <!-- Customer Notes -->
                        <div class="form-group" style="margin-top: 2rem;">
                            <label class="form-label"><?php _t('notes_optional'); ?></label>
                            <textarea name="customer_notes" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['customer_notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-checkout">
                            <i class="bi bi-lock-fill"></i> 
                            <?php _t('complete_order'); ?>
                        </button>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h2 style="margin-bottom: 1.5rem; font-size: 1.5rem;">
                        <?php _t('order_summary'); ?>
                    </h2>
                    
                    <div class="order-items">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="order-item">
                                <?php if ($item['image']): ?>
                                    <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="order-item-image"
                                         onerror="this.src='https://via.placeholder.com/80x80'">
                                <?php else: ?>
                                    <div class="order-item-image" style="background: var(--gray-200); display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-image" style="font-size: 2rem; color: var(--gray-400);"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="order-item-details">
                                    <h4 style="margin: 0 0 0.25rem 0; font-size: 1rem;">
                                        <?php echo htmlspecialchars($item['product_name']); ?>
                                    </h4>
                                    <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                                        <?php echo htmlspecialchars($item['size']); ?> × <?php echo $item['quantity']; ?>
                                    </p>
                                    <p style="margin: 0.5rem 0 0 0; font-weight: 600;">
                                        <?php echo number_format($item['total']); ?> د.ع
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-totals">
                        <div class="total-row">
                            <span><?php _t('subtotal'); ?></span>
                            <span><?php echo number_format($cart['subtotal']); ?> د.ع</span>
                        </div>
                        <div class="total-row">
                            <span><?php _t('shipping'); ?></span>
                            <span>
                                <?php if ($shippingCost == 0): ?>
                                    <?php _t('free'); ?>
                                <?php else: ?>
                                    <?php echo number_format($shippingCost); ?> د.ع
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="total-row final">
                            <span><?php _t('grand_total'); ?></span>
                            <span><?php echo number_format($totalAmount); ?> د.ع</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?php _t('brand_name'); ?></h3>
                    <p><?php _t('brand_description'); ?></p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 <a href="https://www.brezzo.co/" target="_blank" style="color: var(--primary-color); text-decoration: none;">Brezzo</a>. <?php _t('copyright'); ?></p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                menuToggle.classList.toggle('active');
            });
        }
        
        // Language Dropdown - Close when clicking outside
        document.addEventListener('click', (e) => {
            const desktopDropdown = document.getElementById('langDropdownDesktop');
            const desktopToggle = document.querySelector('.language-switcher .lang-toggle');
            if (desktopDropdown && desktopToggle && !desktopToggle.contains(e.target) && !desktopDropdown.contains(e.target)) {
                desktopDropdown.classList.remove('show');
            }

            const mobileDropdown = document.getElementById('langDropdownMobile');
            const mobileToggle = document.querySelector('.mobile-language-switcher .lang-toggle-mobile');
            if (mobileDropdown && mobileToggle && !mobileToggle.contains(e.target) && !mobileDropdown.contains(e.target)) {
                mobileDropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>


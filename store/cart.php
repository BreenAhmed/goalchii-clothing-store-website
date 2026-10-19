<?php
// Shopping Cart Page
require_once '../config/database.php';
require_once 'includes/cart.php';
require_once '../includes/lang.php';

$cart = getCartItems();
$shippingCost = calculateShipping($cart['subtotal']);
$totalAmount = $cart['subtotal'] + $shippingCost;

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update') {
            $key = $_POST['key'] ?? '';
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if (isset($_SESSION['cart'][$key])) {
                $item = $_SESSION['cart'][$key];
                updateCartItem($item['product_id'], $item['size'], $quantity);
            }
        } elseif ($_POST['action'] === 'remove') {
            $key = $_POST['key'] ?? '';
            
            if (isset($_SESSION['cart'][$key])) {
                $item = $_SESSION['cart'][$key];
                removeFromCart($item['product_id'], $item['size']);
            }
        } elseif ($_POST['action'] === 'clear') {
            clearCart();
        }
        
        header('Location: cart.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _t('shopping_cart'); ?> - جول تشي سبورتس</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .cart-header {
            margin-bottom: 2rem;
        }
        
        .cart-table {
            width: 100%;
            background: var(--white);
            border-collapse: collapse;
            margin-bottom: 2rem;
            display: table;
        }
        
        .cart-table th,
        .cart-table td {
            padding: 1rem;
            text-align: right;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .cart-table th {
            background: var(--gray-100);
            font-weight: 600;
        }
        
        .cart-table-mobile {
            display: none;
        }
        
        .cart-item-mobile {
            background: var(--white);
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid var(--gray-200);
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quantity-btn {
            width: 30px;
            height: 30px;
            border: 1px solid var(--gray-300);
            background: var(--white);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            text-align: center;
            border: 1px solid var(--gray-300);
        }
        
        .remove-btn {
            background: transparent;
            border: none;
            color: #c33;
            cursor: pointer;
            font-size: 1.25rem;
        }
        
        .cart-summary {
            background: var(--gray-100);
            padding: 2rem;
            border-radius: 0;
            max-width: 400px;
            margin-right: auto;
            width: 100%;
        }
        
        .cart-layout {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
            flex-wrap: wrap;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .summary-row.total {
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
            text-decoration: none;
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }
        
        .btn-checkout:hover {
            background: var(--primary-dark);
        }
        
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .empty-cart-icon {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .cart-table {
                display: none;
            }
            
            .cart-table-mobile {
                display: block;
            }
            
            .cart-layout {
                flex-direction: column;
            }
            
            .cart-summary {
                max-width: 100%;
                margin-right: 0;
            }
            
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
            
            .quantity-control {
                width: 100%;
                justify-content: center;
            }
            
            .cart-container {
                padding: 0 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .cart-summary {
                padding: 1.5rem;
            }
            
            .cart-item-mobile {
                padding: 0.75rem;
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
                        <img src="../assets/images/logo/logo.png?v=1" alt="GOALCHII" style="height: 60px; width: 60px; border-radius: 50%; object-fit: cover; display: block;">
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
                        <a href="cart.php" class="nav-link active" style="position: relative;">
                            <i class="bi bi-cart3" style="font-size: 1.25rem;"></i>
                            <?php 
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

    <!-- Cart Section -->
    <section class="section" style="padding: 4rem 0;">
        <div class="container">
            <div class="cart-container">
                <div class="cart-header">
                    <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">
                        <?php _t('shopping_cart'); ?>
                    </h1>
                    <p style="color: var(--gray-600);">
                        <?php echo t_replace('cart_items_count', ['count' => $cart['count']]); ?>
                    </p>
                </div>
                
                <?php if (empty($cart['items'])): ?>
                    <div class="empty-cart">
                        <div class="empty-cart-icon">
                            <i class="bi bi-cart-x"></i>
                        </div>
                        <h2 style="margin-bottom: 1rem;">
                            <?php _t('cart_empty'); ?>
                        </h2>
                        <p style="color: var(--gray-600); margin-bottom: 2rem;">
                            <?php _t('cart_empty_message'); ?>
                        </p>
                        <a href="products.php" class="btn-checkout" style="max-width: 300px; margin: 0 auto;">
                            <?php _t('browse_products'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Desktop Table View -->
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th><?php _t('product'); ?></th>
                                <th><?php _t('size'); ?></th>
                                <th><?php _t('quantity'); ?></th>
                                <th><?php _t('price'); ?></th>
                                <th><?php _t('total'); ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="cart-item">
                                            <?php if ($item['image']): ?>
                                                <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                     class="cart-item-image"
                                                     onerror="this.src='https://via.placeholder.com/80x80'">
                                            <?php else: ?>
                                                <div class="cart-item-image" style="background: var(--gray-200); display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-image" style="font-size: 1.5rem; color: var(--gray-400);"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="cart-item-details">
                                                <h4 style="margin: 0 0 0.25rem 0;">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </h4>
                                                <p style="margin: 0; color: var(--gray-600); font-size: 0.875rem;">
                                                    <?php echo htmlspecialchars($item['product_code']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['size']); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="key" value="<?php echo htmlspecialchars($item['key']); ?>">
                                            <div class="quantity-control">
                                                <button type="button" class="quantity-btn" onclick="updateQuantity('<?php echo htmlspecialchars($item['key']); ?>', -1)">-</button>
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                       min="1" class="quantity-input" 
                                                       onchange="this.form.submit()">
                                                <button type="button" class="quantity-btn" onclick="updateQuantity('<?php echo htmlspecialchars($item['key']); ?>', 1)">+</button>
                                            </div>
                                        </form>
                                    </td>
                                    <td><?php echo number_format($item['price']); ?> د.ع</td>
                                    <td style="font-weight: 600;"><?php echo number_format($item['total']); ?> د.ع</td>
                                    <td>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('<?php _t('are_you_sure'); ?>');">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="key" value="<?php echo htmlspecialchars($item['key']); ?>">
                                            <button type="submit" class="remove-btn">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Mobile Card View -->
                    <div class="cart-table-mobile">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="cart-item-mobile">
                                <div class="cart-item">
                                    <?php if ($item['image']): ?>
                                        <img src="../<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             class="cart-item-image"
                                             onerror="this.src='https://via.placeholder.com/80x80'">
                                    <?php else: ?>
                                        <div class="cart-item-image" style="background: var(--gray-200); display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-image" style="font-size: 1.5rem; color: var(--gray-400);"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="cart-item-details" style="width: 100%; margin-top: 1rem;">
                                        <h4 style="margin: 0 0 0.5rem 0;">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </h4>
                                        <p style="margin: 0 0 0.5rem 0; color: var(--gray-600); font-size: 0.875rem;">
                                            <?php echo htmlspecialchars($item['product_code']); ?>
                                        </p>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                            <span><strong><?php _t('size'); ?>:</strong> <?php echo htmlspecialchars($item['size']); ?></span>
                                            <span><strong><?php _t('price'); ?>:</strong> <?php echo number_format($item['price']); ?> د.ع</span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                            <span><strong><?php _t('quantity'); ?>:</strong></span>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="key" value="<?php echo htmlspecialchars($item['key']); ?>">
                                                <div class="quantity-control">
                                                    <button type="button" class="quantity-btn" onclick="updateQuantity('<?php echo htmlspecialchars($item['key']); ?>', -1)">-</button>
                                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                           min="1" class="quantity-input" 
                                                           onchange="this.form.submit()">
                                                    <button type="button" class="quantity-btn" onclick="updateQuantity('<?php echo htmlspecialchars($item['key']); ?>', 1)">+</button>
                                                </div>
                                            </form>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span style="font-weight: 600; font-size: 1.125rem;">
                                                <?php _t('total'); ?>: <?php echo number_format($item['total']); ?> د.ع
                                            </span>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('<?php _t('are_you_sure'); ?>');">
                                                <input type="hidden" name="action" value="remove">
                                                <input type="hidden" name="key" value="<?php echo htmlspecialchars($item['key']); ?>">
                                                <button type="submit" class="remove-btn" style="font-size: 1.5rem;">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-layout">
                        <a href="products.php" style="color: var(--primary-color); text-decoration: none; margin-bottom: 1rem;">
                            <i class="bi bi-arrow-right"></i> 
                            <?php _t('continue_shopping'); ?>
                        </a>
                        
                        <div class="cart-summary">
                            <h3 style="margin-bottom: 1.5rem;">
                                <?php _t('order_summary'); ?>
                            </h3>
                            
                            <div class="summary-row">
                                <span><?php _t('subtotal'); ?></span>
                                <span><?php echo number_format($cart['subtotal']); ?> د.ع</span>
                            </div>
                            
                            <div class="summary-row">
                                <span><?php _t('shipping'); ?></span>
                                <span>
                                    <?php if ($shippingCost == 0): ?>
                                        <?php _t('free'); ?>
                                    <?php else: ?>
                                        <?php echo number_format($shippingCost); ?> د.ع
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="summary-row total">
                                <span><?php _t('grand_total'); ?></span>
                                <span><?php echo number_format($totalAmount); ?> د.ع</span>
                            </div>
                            
                            <a href="checkout.php" class="btn-checkout">
                                <i class="bi bi-lock-fill"></i> 
                                <?php _t('proceed_to_checkout'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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

    <script>
        function updateQuantity(key, change) {
            const form = event.target.closest('form');
            const input = form.querySelector('input[name="quantity"]');
            const currentValue = parseInt(input.value) || 1;
            const newValue = Math.max(1, currentValue + change);
            input.value = newValue;
            form.submit();
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


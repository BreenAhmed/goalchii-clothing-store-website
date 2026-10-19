<?php
// Homepage - Multi-language E-commerce
require_once '../config/database.php';
require_once '../includes/lang.php';

$currentLang = getLang();

if (!function_exists('productLocalizedField')) {
    function productLocalizedField(array $product, string $field, string $lang): string {
        $priorityMap = [
            'ar' => [$field . '_ar', $field . '_ku', $field],
            'ku' => [$field . '_ku', $field . '_ar', $field],
            'en' => [$field, $field . '_ar', $field . '_ku'],
        ];

        $order = $priorityMap[$lang] ?? [$field, $field . '_ar', $field . '_ku'];

        foreach ($order as $key) {
            if (isset($product[$key]) && $product[$key] !== '' && $product[$key] !== null) {
                return $product[$key];
            }
        }

        return '';
    }
}

// Get featured products from database
try {
    $pdo = getDBConnection();
    
    // Get featured e-commerce products (featured = 1 or latest 4 products)
    $sql = "SELECT p.*, c.name as category_name, c.name_ar as category_name_ar
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1 AND p.is_ecommerce = 1
            ORDER BY p.featured DESC, p.created_at DESC 
            LIMIT 4";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $featured_products = $stmt->fetchAll();
    
    // Get active clubs from database with their categories
    $clubs = [];
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'clubs'")->fetch();
        if ($tableCheck) {
            $stmt = $pdo->query("
                SELECT c.* 
                FROM clubs c 
                WHERE c.is_active = 1 
                ORDER BY c.display_order ASC, c.created_at ASC
            ");
            $clubs = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        // Table doesn't exist yet
        $clubs = [];
    }
    
    // Get images for each product
    foreach ($featured_products as &$product) {
        $imageSrc = null;
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
            // Table doesn't exist or error
        }
        
        // Fallback to main image_url
        if (!$imageSrc && $product['image_url']) {
            $imageSrc = $product['image_url'];
        }
        
        // Handle path for display (homepage is in store/ directory)
        if ($imageSrc && !preg_match('/^https?:\/\//', $imageSrc)) {
            // It's a local file path - store pages are in subdirectory, so use relative path
            $imageSrc = '../' . ltrim($imageSrc, '/');
        }
        
        $product['display_image'] = $imageSrc;
    }
    unset($product);
    
} catch (Exception $e) {
    $featured_products = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جول تشي سبورتس - ملابس رياضية عالية الجودة</title>
    <meta name="description" content="اكتشف مجموعتنا من الملابس الرياضية عالية الجودة. تصاميم عصرية وأداء متفوق لجميع الرياضيين.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="nav-container">
            <nav class="navbar">
                <div class="navbar-brand">
                    <a href="index.php" class="logo">
                        <img src="../assets/images/logo/logo.png?v=1" alt="GOALCHII" style="height: 60px; width: 60px; border-radius: 50%; object-fit: cover; display: block;" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                        <span style="display: none; font-size: 2rem; font-weight: 900; color: var(--primary-color);">GOALCHII</span>
                    </a>
                    <div class="mobile-language-switcher">
                        <a href="#" class="nav-link lang-toggle-mobile" onclick="event.preventDefault(); var dropdown = document.getElementById('langDropdownMobile'); if (dropdown) { dropdown.classList.toggle('show'); } var desktopDropdown = document.getElementById('langDropdownDesktop'); if (desktopDropdown) { desktopDropdown.classList.remove('show'); }">
                            <?php 
                            $currentLang = getLang();
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
                    <li><a href="index.php" class="nav-link active"><?php _t('home'); ?></a></li>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg">
            <video id="heroVideo" autoplay muted loop playsinline preload="auto">
                <source src="1113.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        <div class="hero-content">
            <h1 class="hero-title">
                <?php _t('hero_title'); ?>
                <span><?php _t('hero_title_span'); ?></span>
            </h1>
            <p class="hero-subtitle"><?php _t('hero_subtitle'); ?></p>
            <div class="hero-buttons">
                <a href="products.php" class="btn btn-primary"><?php _t('shop_now'); ?></a>
                <a href="#featured" class="btn btn-outline"><?php _t('discover_collection'); ?></a>
            </div>
        </div>
    </section>

    <!-- Partners Bar -->
    <section class="partners-bar" style="padding: 3rem 0; background: var(--white); border-top: 1px solid var(--gray-200); border-bottom: 1px solid var(--gray-200); overflow: hidden;">
        <div class="container">
            <div style="text-align: center; margin-bottom: 2rem; animation: fadeInDown 0.8s ease-out;">
                <h3 style="font-size: 2rem; color: var(--black); font-weight: 800; margin-bottom: 0.75rem; font-family: 'Arial Black', 'Helvetica Neue', Arial, sans-serif; letter-spacing: 1px;">
                    <?php _t('our_clubs'); ?>
                </h3>
            </div>
            <div class="partners-scroll-container" style="overflow: hidden; position: relative;">
                <?php if (!empty($clubs)): ?>
                    <div class="partners-scroll" style="display: flex; align-items: center; gap: 3rem; animation: scrollPartners 20s linear infinite;">
                        <!-- First set of clubs -->
                        <?php foreach ($clubs as $index => $club): ?>
                            <?php 
                            $clubLink = $club['category_id'] ? 'products.php?category=' . $club['category_id'] : '#';
                            $hasLink = !empty($club['category_id']);
                            ?>
                            <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; animation: fadeInUp 0.8s ease-out <?php echo ($index * 0.1) + 0.1; ?>s both; <?php echo $hasLink ? 'cursor: pointer;' : ''; ?>" 
                                 <?php if ($hasLink): ?>onclick="window.location.href='<?php echo $clubLink; ?>'"<?php endif; ?>>
                                <?php if ($club['logo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($club['logo_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($club['name']); ?>"
                                         style="max-width: 100%; max-height: 100%; object-fit: contain; pointer-events: none;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="display: none; font-size: 1.25rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; text-align: center; pointer-events: none;">
                                        <?php echo htmlspecialchars(getLang() === 'ar' ? ($club['name_ar'] ?? $club['name']) : $club['name']); ?>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; text-align: center; pointer-events: none;">
                                        <?php echo htmlspecialchars(getLang() === 'ar' ? ($club['name_ar'] ?? $club['name']) : $club['name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <!-- Duplicate set for seamless loop -->
                        <?php foreach ($clubs as $club): ?>
                            <?php 
                            $clubLink = $club['category_id'] ? 'products.php?category=' . $club['category_id'] : '#';
                            $hasLink = !empty($club['category_id']);
                            ?>
                            <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; <?php echo $hasLink ? 'cursor: pointer;' : ''; ?>" 
                                 <?php if ($hasLink): ?>onclick="window.location.href='<?php echo $clubLink; ?>'"<?php endif; ?>>
                                <?php if ($club['logo_url']): ?>
                                    <img src="<?php echo htmlspecialchars($club['logo_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($club['name']); ?>"
                                         style="max-width: 100%; max-height: 100%; object-fit: contain; pointer-events: none;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div style="display: none; font-size: 1.25rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; text-align: center; pointer-events: none;">
                                        <?php echo htmlspecialchars(getLang() === 'ar' ? ($club['name_ar'] ?? $club['name']) : $club['name']); ?>
                                    </div>
                                <?php else: ?>
                                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; text-align: center; pointer-events: none;">
                                        <?php echo htmlspecialchars(getLang() === 'ar' ? ($club['name_ar'] ?? $club['name']) : $club['name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <!-- Fallback: Show example clubs if database table doesn't exist or no clubs -->
                    <div class="partners-scroll" style="display: flex; align-items: center; gap: 3rem; animation: scrollPartners 20s linear infinite;">
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; animation: fadeInUp 0.8s ease-out 0.1s both;">
                            <div style="font-size: 2rem; font-weight: 900; color: var(--gray-400); font-family: Arial, sans-serif;">NIKE</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; animation: fadeInUp 0.8s ease-out 0.2s both;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; letter-spacing: 2px;">ADIDAS</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; animation: fadeInUp 0.8s ease-out 0.3s both;">
                            <div style="font-size: 1.75rem; font-weight: 800; color: var(--gray-400); font-family: Arial, sans-serif;">PUMA</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; animation: fadeInUp 0.8s ease-out 0.4s both;">
                            <div style="font-size: 0.875rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; text-align: center; line-height: 1.2;">UNDER<br>ARMOUR</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s; animation: fadeInUp 0.8s ease-out 0.5s both;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif;">REEBOK</div>
                        </div>
                        <!-- Duplicate set for seamless loop -->
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s;">
                            <div style="font-size: 2rem; font-weight: 900; color: var(--gray-400); font-family: Arial, sans-serif;">NIKE</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; letter-spacing: 2px;">ADIDAS</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s;">
                            <div style="font-size: 1.75rem; font-weight: 800; color: var(--gray-400); font-family: Arial, sans-serif;">PUMA</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s;">
                            <div style="font-size: 0.875rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif; text-align: center; line-height: 1.2;">UNDER<br>ARMOUR</div>
                        </div>
                        <div class="partner-logo" style="flex: 0 0 auto; width: 120px; height: 60px; display: flex; align-items: center; justify-content: center; filter: grayscale(100%); transition: all 0.3s;">
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--gray-400); font-family: Arial, sans-serif;">REEBOK</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <style>
            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 0.6;
                    transform: translateY(0);
                }
            }
            
            @keyframes scrollPartners {
                0% {
                    transform: translateX(0);
                }
                100% {
                    transform: translateX(-50%);
                }
            }
            
            .partners-bar .partner-logo {
                cursor: pointer;
            }
            
            .partners-bar .partner-logo:hover {
                opacity: 1 !important;
                filter: grayscale(0%) !important;
                transform: scale(1.15) !important;
                animation-play-state: paused;
            }
            
            .partners-bar .partner-logo:hover div {
                color: var(--primary-color) !important;
            }
            
            .partners-scroll-container::before,
            .partners-scroll-container::after {
                content: '';
                position: absolute;
                top: 0;
                width: 100px;
                height: 100%;
                z-index: 2;
                pointer-events: none;
            }
            
            .partners-scroll-container::before {
                left: 0;
                background: linear-gradient(to right, var(--white), transparent);
            }
            
            .partners-scroll-container::after {
                right: 0;
                background: linear-gradient(to left, var(--white), transparent);
            }
            
            @media (max-width: 768px) {
                .partners-scroll {
                    gap: 2rem !important;
                }
                .partner-logo {
                    width: 100px !important;
                    height: 50px !important;
                }
                .partners-scroll-container::before,
                .partners-scroll-container::after {
                    width: 50px;
                }
            }
        </style>
    </section>

    <!-- Featured Products -->
    <section id="featured" class="section">
        <div class="container">
            <div class="section-title">
                <h2><?php _t('featured_products'); ?></h2>
                <p><?php _t('discover_latest'); ?></p>
            </div>
            
            <div class="products-grid">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $product): ?>
                    <div class="product-card" onclick="window.location.href='product-detail.php?id=<?php echo $product['id']; ?>';" style="cursor: pointer;">
                        <div class="product-image">
                            <?php if ($product['display_image']): ?>
                            <img src="<?php echo htmlspecialchars($product['display_image']); ?>" 
                                 alt="<?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?>"
                                 onerror="this.src='https://via.placeholder.com/400x400?text=No+Image'">
                            <?php else: ?>
                            <img src="https://via.placeholder.com/400x400?text=No+Image" 
                                 alt="<?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?>">
                            <?php endif; ?>
                            
                            <?php if ($product['featured']): ?>
                            <span class="product-badge">مميز</span>
                            <?php endif; ?>
                            
                            <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                            <span class="product-badge" style="background: var(--primary-color); color: var(--black);">خصم</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?></h3>
                            <p class="product-price">
                                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                <span><?php echo number_format($product['old_price']); ?> د.ع</span>
                                <?php endif; ?>
                                <?php echo number_format($product['price']); ?> د.ع
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                        <p style="color: var(--gray-500); font-size: 1.125rem;"><?php _t('no_products'); ?></p>
                        <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;"><?php _t('all_products'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="container">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-truck"></i>
                    </div>
                    <h3 class="feature-title"><?php _t('free_delivery'); ?></h3>
                    <p class="feature-text"><?php _t('free_delivery_desc'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h3 class="feature-title"><?php _t('quality_guarantee'); ?></h3>
                    <p class="feature-text"><?php _t('quality_guarantee_desc'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>
                    <h3 class="feature-title"><?php _t('easy_return'); ?></h3>
                    <p class="feature-text"><?php _t('easy_return_desc'); ?></p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-headset"></i>
                    </div>
                    <h3 class="feature-title"><?php _t('support'); ?></h3>
                    <p class="feature-text"><?php _t('support_desc'); ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section" id="faq">
        <div class="container">
            <div class="section-title">
                <h2><?php _t('faq_title'); ?></h2>
                <p><?php _t('faq_subtitle'); ?></p>
            </div>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-icon"><i class="bi bi-truck"></i></div>
                    <div class="faq-content">
                        <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-delivery">
                            <span class="faq-question-text"><?php _t('faq_delivery_q'); ?></span>
                            <span class="faq-toggle-icon"><i class="bi bi-plus-lg"></i></span>
                        </button>
                        <div class="faq-answer" id="faq-delivery">
                            <p><?php _t('faq_delivery_a'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-icon"><i class="bi bi-bag-check"></i></div>
                    <div class="faq-content">
                        <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-ordering">
                            <span class="faq-question-text"><?php _t('faq_ordering_q'); ?></span>
                            <span class="faq-toggle-icon"><i class="bi bi-plus-lg"></i></span>
                        </button>
                        <div class="faq-answer" id="faq-ordering">
                            <p><?php _t('faq_ordering_a'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-icon"><i class="bi bi-rulers"></i></div>
                    <div class="faq-content">
                        <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-sizes">
                            <span class="faq-question-text"><?php _t('faq_sizes_q'); ?></span>
                            <span class="faq-toggle-icon"><i class="bi bi-plus-lg"></i></span>
                        </button>
                        <div class="faq-answer" id="faq-sizes">
                            <p><?php _t('faq_sizes_a'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-icon"><i class="bi bi-credit-card"></i></div>
                    <div class="faq-content">
                        <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-payment">
                            <span class="faq-question-text"><?php _t('faq_payment_q'); ?></span>
                            <span class="faq-toggle-icon"><i class="bi bi-plus-lg"></i></span>
                        </button>
                        <div class="faq-answer" id="faq-payment">
                            <p><?php _t('faq_payment_a'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-icon"><i class="bi bi-arrow-repeat"></i></div>
                    <div class="faq-content">
                        <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-returns">
                            <span class="faq-question-text"><?php _t('faq_returns_q'); ?></span>
                            <span class="faq-toggle-icon"><i class="bi bi-plus-lg"></i></span>
                        </button>
                        <div class="faq-answer" id="faq-returns">
                            <p><?php _t('faq_returns_a'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="faq-item">
                    <div class="faq-icon"><i class="bi bi-brush"></i></div>
                    <div class="faq-content">
                        <button class="faq-question" type="button" aria-expanded="false" aria-controls="faq-custom">
                            <span class="faq-question-text"><?php _t('faq_custom_q'); ?></span>
                            <span class="faq-toggle-icon"><i class="bi bi-plus-lg"></i></span>
                        </button>
                        <div class="faq-answer" id="faq-custom">
                            <p><?php _t('faq_custom_a'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section bg-gray">
        <div class="container">
            <div class="section-title">
                <h2><?php _t('join_family'); ?> <span class="text-primary"><?php _t('join_family_span'); ?></span></h2>
                <p><?php _t('get_discount'); ?></p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 2rem;"><?php _t('start_shopping'); ?></a>
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
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/17hDXhUg72/?mibextid=wwXIfr" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/goalchii" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.tiktok.com/@goalchii?_r=1&_t=ZS-91K2YnqmcYK" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3><?php _t('quick_links'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="products.php"><?php _t('all_products'); ?></a></li>
                        <li><a href="about.php"><?php _t('about'); ?></a></li>
                        <li><a href="contact.php"><?php _t('contact'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php _t('customer_service'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="#faq"><?php _t('shipping_policy'); ?></a></li>
                        <li><a href="#faq"><?php _t('return_policy'); ?></a></li>
                        <li><a href="terms.php"><?php _t('terms'); ?></a></li>
                        <li><a href="privacy.php"><?php _t('privacy'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php _t('contact_us'); ?></h3>
                    <ul class="footer-links">
                        <li><i class="bi bi-geo-alt"></i> <?php _t('location'); ?></li>
                        <li><a href="tel:+9647515215455" style="color: inherit; text-decoration: none;"><i class="bi bi-telephone"></i> +964 751 521 5455</a></li>
                        <li><a href="mailto:info@goalchii.store" style="color: inherit; text-decoration: none;"><i class="bi bi-envelope"></i> info@goalchii.store</a></li>
                        <li><a href="https://wa.me/9647515215455" style="color: inherit; text-decoration: none;"><i class="bi bi-whatsapp"></i> <?php _t('whatsapp'); ?></a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 <a href="https://www.brezzo.co/" target="_blank" style="color: var(--primary-color); text-decoration: none;">Brezzo</a>. <?php _t('copyright'); ?></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Hero Video Autoplay Fix
        document.addEventListener('DOMContentLoaded', function() {
            const heroVideo = document.getElementById('heroVideo');
            if (heroVideo) {
                heroVideo.play().catch(function(error) {
                    console.log('Video autoplay failed:', error);
                    // Try to play on user interaction
                    document.addEventListener('click', function() {
                        heroVideo.play();
                    }, { once: true });
                });
            }
        });

        // Mobile Menu Toggle
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
        
        // Header Scroll Effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('header');
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
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

        // FAQ Accordion
        const faqItems = document.querySelectorAll('.faq-item');
        faqItems.forEach((item) => {
            const button = item.querySelector('.faq-question');
            const answer = item.querySelector('.faq-answer');
            const icon = item.querySelector('.faq-toggle-icon i');

            if (!button || !answer) return;

            answer.style.maxHeight = '0px';

            button.addEventListener('click', () => {
                const isOpen = item.classList.contains('open');

                if (isOpen) {
                    item.classList.remove('open');
                    button.setAttribute('aria-expanded', 'false');
                    answer.style.maxHeight = '0px';
                    if (icon) {
                        icon.classList.remove('bi-dash-lg');
                        icon.classList.add('bi-plus-lg');
                    }
                } else {
                    item.classList.add('open');
                    button.setAttribute('aria-expanded', 'true');
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                    if (icon) {
                        icon.classList.remove('bi-plus-lg');
                        icon.classList.add('bi-dash-lg');
                    }
                }
            });
        });

        window.addEventListener('resize', () => {
            faqItems.forEach((item) => {
                if (!item.classList.contains('open')) return;
                const answer = item.querySelector('.faq-answer');
                if (answer) {
                    answer.style.maxHeight = answer.scrollHeight + 'px';
                }
            });
        });
    </script>
</body>
</html>

<?php
// Product Detail Page - Multi-language E-commerce
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

// Get product ID
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$product_id) {
    header('Location: products.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get product details - Only e-commerce products
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, c.name_ar as category_name_ar
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.is_active = 1 AND p.is_ecommerce = 1
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }

    // Get product sizes
    $stmt = $pdo->prepare("SELECT size FROM product_sizes WHERE product_id = ? ORDER BY 
        CASE size
            WHEN 'XS' THEN 1
            WHEN 'S' THEN 2
            WHEN 'M' THEN 3
            WHEN 'L' THEN 4
            WHEN 'XL' THEN 5
            WHEN 'XXL' THEN 6
            WHEN 'XXXL' THEN 7
            ELSE 8
        END");
    $stmt->execute([$product_id]);
    $productSizes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get product images (check if table exists)
    $productImages = [];
    try {
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetch();
        if ($tableCheck) {
            $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC, id ASC");
            $stmt->execute([$product_id]);
            $productImages = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        // Table doesn't exist yet
    }
    
    // If no images in gallery, use main image_url as fallback
    if (empty($productImages) && $product['image_url']) {
        $productImages = [['image_url' => $product['image_url'], 'is_primary' => 1]];
    }
    
    if (!$product) {
        header('Location: products.php');
        exit;
    }
    
    // Get related products (same category, excluding current product)
    $related_products = [];
    if ($product['category_id']) {
        $stmt = $pdo->prepare("
            SELECT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.category_id = ? 
            AND p.id != ? 
            AND p.is_active = 1 
            AND p.is_ecommerce = 1
            ORDER BY p.created_at DESC
            LIMIT 4
        ");
        $stmt->execute([$product['category_id'], $product_id]);
        $related_products = $stmt->fetchAll();
        
        // Get images for related products
        foreach ($related_products as &$related_product) {
            $relatedImageSrc = null;
            // Try to get primary image from product_images table
            try {
                $tableCheck = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetch();
                if ($tableCheck) {
                    $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY display_order ASC LIMIT 1");
                    $imgStmt->execute([$related_product['id']]);
                    $primaryImg = $imgStmt->fetch();
                    if ($primaryImg) {
                        $relatedImageSrc = $primaryImg['image_url'];
                    }
                }
            } catch (Exception $e) {
                // Table doesn't exist or error
            }
            
            // Fallback to main image_url
            if (!$relatedImageSrc && $related_product['image_url']) {
                $relatedImageSrc = $related_product['image_url'];
            }
            
            // Handle path for display (store pages are in subdirectory)
            if ($relatedImageSrc && !preg_match('/^https?:\/\//', $relatedImageSrc)) {
                $relatedImageSrc = '../' . ltrim($relatedImageSrc, '/');
            }
            $related_product['display_image'] = $relatedImageSrc;
        }
        unset($related_product); // Break reference
    }
    
    // Update view count
try {
    $stmt = $pdo->prepare("UPDATE products SET views = views + 1 WHERE id = ?");
    $stmt->execute([$product_id]);
} catch (PDOException $e) {
    // Ignore if views column doesn't exist yet
}
    
} catch (Exception $e) {
    header('Location: products.php');
    exit;
}

// Determine image source
$imageSrc = $product['image_url'];
if ($imageSrc && !preg_match('/^https?:\/\//', $imageSrc)) {
    $imageSrc = '../assets/images/products/' . $imageSrc;
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang) ?: ($product['name'] ?? '')); ?> - جول تشي سبورتس</title>
    <meta name="description" content="<?php echo htmlspecialchars($product['meta_description'] ?? productLocalizedField($product, 'description', $currentLang)); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .product-detail {
            padding: 6rem 0 4rem;
        }
        
        .product-detail-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-bottom: 4rem;
        }
        
        .product-image-section {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }
        
        .product-main-image {
            width: 100%;
            height: 600px;
            object-fit: cover;
            border-radius: 0;
            background: var(--gray-100);
            margin-bottom: 1rem;
        }
        
        .product-info-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--black);
        }
        
        .product-category {
            font-size: 0.875rem;
            color: var(--gray-500);
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        
        .product-price-section {
            margin: 2rem 0;
            padding: 2rem;
            background: var(--gray-100);
            border-radius: 0;
        }
        
        .product-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .product-old-price {
            font-size: 1.5rem;
            color: var(--gray-500);
            text-decoration: line-through;
            margin-right: 1rem;
        }
        
        .product-description {
            font-size: 1.125rem;
            line-height: 1.8;
            color: var(--gray-700);
            margin: 2rem 0;
        }
        
        .product-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-add-cart {
            flex: 1;
            padding: 1rem 2rem;
            background: var(--primary-color);
            color: var(--black);
            border: none;
            font-size: 1.125rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-add-cart:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-wishlist {
            padding: 1rem;
            background: var(--white);
            border: 2px solid var(--gray-300);
            color: var(--gray-700);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-wishlist:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .related-products {
            margin-top: 4rem;
        }
        
        .related-products h2 {
            font-size: 2rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .product-detail-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .product-main-image {
                height: 400px;
            }
            
            .product-info-section h1 {
                font-size: 1.75rem;
            }
            
            .product-price {
                font-size: 2rem;
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
                            <a href="?lang=ar&id=<?php echo $product_id; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en&id=<?php echo $product_id; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku&id=<?php echo $product_id; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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
                    <li><a href="products.php" class="nav-link active"><?php _t('products'); ?></a></li>
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
                            <a href="?lang=ar&id=<?php echo $product_id; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en&id=<?php echo $product_id; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku&id=<?php echo $product_id; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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

    <!-- Product Detail -->
    <section class="product-detail section">
        <div class="container">
            <!-- Breadcrumb -->
            <nav style="margin-bottom: 2rem;">
                <a href="index.php" style="color: var(--gray-500); text-decoration: none;"><?php _t('home'); ?></a>
                <span style="color: var(--gray-500); margin: 0 0.5rem;">/</span>
                <a href="products.php" style="color: var(--gray-500); text-decoration: none;"><?php _t('products'); ?></a>
                <span style="color: var(--gray-500); margin: 0 0.5rem;">/</span>
                <span style="color: var(--black);"><?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?></span>
            </nav>
            
            <div class="product-detail-container">
                <!-- Product Images Gallery -->
                <div class="product-image-section">
                    <?php if (!empty($productImages)): ?>
                        <!-- Main Image Display -->
                        <div class="main-image-container mb-3">
                            <img id="main-product-image" 
                                 src="<?php 
                                    $mainImg = $productImages[0]['image_url'];
                                    if (!preg_match('/^https?:\/\//', $mainImg)) {
                                        // It's a local file path - store pages are in subdirectory, so use relative path
                                        $mainImg = '../' . ltrim($mainImg, '/');
                                    }
                                    echo htmlspecialchars($mainImg); 
                                 ?>" 
                                 alt="<?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?>"
                                 class="product-main-image"
                                 onerror="this.src='https://via.placeholder.com/600x800/f0f0f0/666?text=Image+Not+Found'">
                        </div>
                        
                        <!-- Thumbnail Gallery -->
                        <?php if (count($productImages) > 1): ?>
                        <div class="image-thumbnails" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            <?php foreach ($productImages as $index => $img): 
                                $thumbSrc = $img['image_url'];
                                if (!preg_match('/^https?:\/\//', $thumbSrc)) {
                                    // It's a local file path - store pages are in subdirectory, so use relative path
                                    $thumbSrc = '../' . ltrim($thumbSrc, '/');
                                }
                            ?>
                                <img src="<?php echo htmlspecialchars($thumbSrc); ?>" 
                                     alt="Product image <?php echo $index + 1; ?>"
                                     class="thumbnail-image" 
                                     style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border: 2px solid var(--gray-300); border-radius: 4px;"
                                     onclick="changeMainImage('<?php echo htmlspecialchars($thumbSrc); ?>')"
                                     onerror="this.src='https://via.placeholder.com/80x80/f0f0f0/666?text=Image'">
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php elseif ($imageSrc): ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                             alt="<?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?>"
                             class="product-main-image"
                             onerror="this.src='https://via.placeholder.com/600x800/f0f0f0/666?text=Image+Not+Found'">
                    <?php else: ?>
                        <div class="product-main-image" style="display: flex; align-items: center; justify-content: center; background: var(--gray-200);">
                            <i class="bi bi-image" style="font-size: 4rem; color: var(--gray-400);"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="product-info-section">
                    <p class="product-category"><?php echo htmlspecialchars(getLang() === 'ar' ? ($product['category_name_ar'] ?? $product['category_name'] ?? 'غير مصنف') : ($product['category_name'] ?? $product['category_name_ar'] ?? 'Uncategorized')); ?></p>
                    <h1><?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?></h1>
                    
                    <?php if ($product['product_code']): ?>
                        <p style="color: var(--gray-500); margin-bottom: 1rem;">
                            <small><?php _t('product_code'); ?>: <?php echo htmlspecialchars($product['product_code']); ?></small>
                        </p>
                    <?php endif; ?>
                    
                    <div class="product-price-section">
                        <div>
                            <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                <span class="product-old-price"><?php echo number_format($product['old_price']); ?> د.ع</span>
                                <span class="product-price"><?php echo number_format($product['price']); ?> د.ع</span>
                                <span style="background: var(--primary-color); color: var(--black); padding: 0.25rem 0.75rem; font-weight: 700; font-size: 0.875rem; margin-right: 1rem;">
                                    خصم <?php echo round((1 - $product['price']/$product['old_price']) * 100); ?>%
                                </span>
                            <?php else: ?>
                                <span class="product-price"><?php echo number_format($product['price']); ?> د.ع</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($productSizes)): ?>
                        <div style="margin: 2rem 0;">
                            <h3 style="font-size: 1.5rem; margin-bottom: 1rem;"><?php _t('select_size'); ?></h3>
                            <div class="size-selection" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                                <?php foreach ($productSizes as $size): ?>
                                    <label style="
                                        display: inline-block;
                                        padding: 0.75rem 1.5rem;
                                        border: 2px solid var(--gray-300);
                                        cursor: pointer;
                                        transition: var(--transition);
                                        font-weight: 600;
                                        min-width: 60px;
                                        text-align: center;
                                    " class="size-option">
                                        <input type="radio" name="selected_size" value="<?php echo htmlspecialchars($size); ?>" 
                                               style="display: none;" required>
                                        <span><?php echo htmlspecialchars($size); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($product['description']) || !empty($product['description_ar']) || !empty($product['description_ku'])): ?>
                        <div class="product-description">
                            <h3 style="font-size: 1.5rem; margin-bottom: 1rem;"><?php _t('description'); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars(productLocalizedField($product, 'description', $currentLang))); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-actions">
                        <button class="btn-add-cart" onclick="addToCart(<?php echo $product['id']; ?>, event)">
                            <i class="bi bi-cart-plus me-2"></i><?php _t('add_to_cart'); ?>
                        </button>
                        <button class="btn-wishlist" onclick="addToWishlist(<?php echo $product['id']; ?>)">
                            <i class="bi bi-heart"></i>
                        </button>
                    </div>
                    
                    <?php if ($product['views']): ?>
                        <p style="color: var(--gray-500); margin-top: 2rem; font-size: 0.875rem;">
                            <i class="bi bi-eye me-1"></i><?php _t('views_count'); ?> <?php echo number_format($product['views']); ?> <?php _t('times'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
                <div class="related-products">
                    <h2><?php _t('related_products'); ?></h2>
                    <div class="products-grid">
                        <?php foreach ($related_products as $related): 
                            $relatedImageSrc = $related['display_image'] ?? $related['image_url'];
                            if (!$relatedImageSrc) {
                                $relatedImageSrc = null;
                            }
                        ?>
                            <div class="product-card" onclick="window.location.href='product-detail.php?id=<?php echo $related['id']; ?>';" style="cursor: pointer;">
                                <div class="product-image">
                                    <?php if ($relatedImageSrc): ?>
                                        <img src="<?php echo htmlspecialchars($relatedImageSrc); ?>" 
                                             alt="<?php echo htmlspecialchars(productLocalizedField($related, 'name', $currentLang)); ?>"
                                             onerror="this.src='https://via.placeholder.com/600x800/f0f0f0/666?text=Image+Not+Found'">
                                    <?php else: ?>
                                        <img src="https://via.placeholder.com/600x800/f0f0f0/666?text=No+Image" alt="No Image">
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <p class="product-category"><?php echo htmlspecialchars($related['category_name'] ?? 'غير مصنف'); ?></p>
                                    <h3 class="product-title"><?php echo htmlspecialchars(productLocalizedField($related, 'name', $currentLang)); ?></h3>
                                    <p class="product-price">
                                        <?php if ($related['old_price'] && $related['old_price'] > $related['price']): ?>
                                            <span style="text-decoration: line-through; color: var(--gray-500); margin-left: 0.5rem;">
                                                <?php echo number_format($related['old_price']); ?> د.ع
                                            </span>
                                        <?php endif; ?>
                                        <?php echo number_format($related['price']); ?> د.ع
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
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
                
                <div class="footer-section">
                    <h3><?php _t('quick_links'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="index.php"><?php _t('home'); ?></a></li>
                        <li><a href="products.php"><?php _t('products'); ?></a></li>
                        <li><a href="about.php"><?php _t('about'); ?></a></li>
                        <li><a href="blog.php"><?php _t('blog'); ?></a></li>
                        <li><a href="contact.php"><?php _t('contact'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3><?php _t('customer_service'); ?></h3>
                    <ul class="footer-links">
                        <li><a href="index.php?lang=<?php echo htmlspecialchars(getLang()); ?>#faq-delivery"><?php _t('shipping_policy'); ?></a></li>
                        <li><a href="index.php?lang=<?php echo htmlspecialchars(getLang()); ?>#faq-returns"><?php _t('return_policy'); ?></a></li>
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
        
        // Size selection styling
        document.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all options
                document.querySelectorAll('.size-option').forEach(opt => {
                    opt.style.borderColor = 'var(--gray-300)';
                    opt.style.background = 'transparent';
                    opt.style.color = 'var(--black)';
                });
                
                // Add active class to selected option
                this.style.borderColor = 'var(--primary-color)';
                this.style.background = 'var(--primary-color)';
                this.style.color = 'var(--black)';
                
                // Check the radio button
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Add to Cart function
        function addToCart(productId, event) {
            // Get the button that was clicked
            const btn = event ? event.target.closest('.btn-add-cart') : document.querySelector('.btn-add-cart');
            
            const selectedSize = document.querySelector('input[name="selected_size"]:checked');
            if (!selectedSize) {
                alert('<?php echo addslashes(t('please_select_size')); ?>');
                return;
            }
            
            // Disable button to prevent double-clicking
            if (btn) {
                const originalHTML = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-hourglass-split"></i> <?php echo getLang() === 'ar' ? 'جاري الإضافة...' : 'Adding...'; ?>';
            }
            
            // Add to cart via AJAX
            fetch('add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId + '&size=' + encodeURIComponent(selectedSize.value) + '&quantity=1'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Get text first to check if it's valid JSON
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        // Redirect to cart page
                        window.location.href = 'cart.php';
                    } else {
                        alert(data.message || '<?php echo addslashes(getLang() === 'ar' ? 'حدث خطأ' : 'An error occurred'); ?>');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', text);
                    alert('<?php echo addslashes(getLang() === 'ar' ? 'حدث خطأ في الاستجابة' : 'Error parsing response'); ?>');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('<?php echo addslashes(getLang() === 'ar' ? 'حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.' : 'Connection error. Please try again.'); ?>');
            })
            .finally(() => {
                // Re-enable button
                if (btn) {
                    btn.disabled = false;
                    const originalHTML = '<i class="bi bi-cart-plus me-2"></i><?php echo addslashes(t('add_to_cart')); ?>';
                    btn.innerHTML = originalHTML;
                }
            });
        }
        
        // Update cart count in header
        function updateCartCount() {
            fetch('get-cart-count.php')
                .then(response => response.json())
                .then(data => {
                    const cartBadge = document.getElementById('cart-count');
                    if (cartBadge) {
                        cartBadge.textContent = data.count || 0;
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Change main image when thumbnail is clicked
        function changeMainImage(imageSrc) {
            const mainImage = document.getElementById('main-product-image');
            if (mainImage) {
                mainImage.src = imageSrc;
                
                // Update active thumbnail
                document.querySelectorAll('.thumbnail-image').forEach(thumb => {
                    thumb.style.borderColor = 'var(--gray-300)';
                });
                event.target.style.borderColor = 'var(--primary-color)';
            }
        }
        
        // Add to Wishlist (placeholder function)
        function addToWishlist(productId) {
            alert('تم إضافة المنتج إلى قائمة المفضلة! (هذه وظيفة تجريبية)');
            // TODO: Implement actual wishlist functionality
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


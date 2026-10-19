<?php
// Products Page - Multi-language E-commerce
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

// Get filters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDBConnection();
    
    // Get categories for filter
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
    
    // Build query - Only show e-commerce products (not inventory products)
    $where = ["p.is_active = 1", "p.is_ecommerce = 1"];
    $params = [];
    
    if ($category_id) {
        $where[] = "p.category_id = :category";
        $params[':category'] = $category_id;
    }
    
    if ($search) {
        $where[] = "(p.name LIKE :search OR p.name_ar LIKE :search OR p.name_ku LIKE :search OR p.description LIKE :search OR p.description_ar LIKE :search OR p.description_ku LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $where_clause = implode(" AND ", $where);
    
    // Sort order
    switch($sort) {
        case 'price_low':
            $order = "p.price ASC";
            break;
        case 'price_high':
            $order = "p.price DESC";
            break;
        case 'name':
            if ($currentLang === 'ar') {
                $order = "COALESCE(NULLIF(p.name_ar, ''), NULLIF(p.name_ku, ''), p.name) ASC";
            } elseif ($currentLang === 'ku') {
                $order = "COALESCE(NULLIF(p.name_ku, ''), NULLIF(p.name_ar, ''), p.name) ASC";
            } else {
                $order = "COALESCE(NULLIF(p.name, ''), NULLIF(p.name_ar, ''), p.name_ku) ASC";
            }
            break;
        default:
            $order = "p.created_at DESC";
    }
    
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM products p WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $total_products = $stmt->fetchColumn();
    $total_pages = ceil($total_products / $per_page);
    
    // Get products
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE $where_clause 
            ORDER BY $order 
            LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
    
} catch (Exception $e) {
    $products = [];
    $categories = [];
    error_log('Products page error: ' . $e->getMessage());
    // Uncomment below for debugging:
    // echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المنتجات - جول تشي سبورتس</title>
    <meta name="description" content="تسوق أحدث تشكيلات الملابس الرياضية من جول تشي. جودة عالية وتصاميم عصرية.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .products-hero {
            height: 50vh;
            min-height: 400px;
            padding-top: 100px;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--black) 0%, var(--gray-900) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .products-hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 35px,
                rgba(192, 255, 114, 0.1) 35px,
                rgba(192, 255, 114, 0.1) 70px
            );
        }
        
        .filters-section {
            background: var(--gray-100);
            padding: 2rem 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .filters-container {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        
        .filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--gray-300);
            background: var(--white);
            font-size: 1rem;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .filter-select:hover,
        .filter-select:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .search-box {
            display: flex;
            gap: 0;
            max-width: 400px;
            flex: 1;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: 2px solid var(--gray-300);
            border-left: none;
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .search-btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: var(--black);
            border: 2px solid var(--primary-color);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .search-btn:hover {
            background: var(--primary-dark);
        }
        
        .results-info {
            text-align: center;
            padding: 2rem 0 1rem;
            color: var(--gray-600);
        }
        
        .product-card .product-image {
            height: 400px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 4rem;
        }
        
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            background: var(--white);
            border: 2px solid var(--gray-300);
            color: var(--black);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }
        
        .pagination a:hover {
            border-color: var(--primary-color);
            background: var(--primary-color);
        }
        
        .pagination .active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--black);
        }
        
        .no-products {
            text-align: center;
            padding: 4rem 2rem;
        }
        
        .no-products i {
            font-size: 5rem;
            color: var(--gray-400);
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .filters-container {
                justify-content: center;
            }
            
            .search-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="nav-container">
            <nav class="navbar">
                <?php
                $currentLang = getLang();
                $queryParams = '';
                if (isset($_GET['category'])) $queryParams .= '&category=' . $_GET['category'];
                if (isset($_GET['search'])) $queryParams .= '&search=' . urlencode($_GET['search']);
                if (isset($_GET['sort'])) $queryParams .= '&sort=' . $_GET['sort'];
                if (isset($_GET['page'])) $queryParams .= '&page=' . $_GET['page'];
                ?>
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
                            <a href="?lang=ar<?php echo $queryParams; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en<?php echo $queryParams; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku<?php echo $queryParams; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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
                            <a href="?lang=ar<?php echo $queryParams; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en<?php echo $queryParams; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku<?php echo $queryParams; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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

    <!-- Products Hero -->
    <section class="products-hero">
        <div class="container">
            <h1 class="hero-title" style="color: var(--white); font-size: clamp(2.5rem, 5vw, 4rem);">
                <?php _t('shop_products'); ?>
            </h1>
            <p class="hero-subtitle" style="color: var(--gray-300); font-size: 1.25rem;"><?php _t('discover_latest'); ?></p>
        </div>
    </section>

    <!-- Filters -->
    <section class="filters-section">
        <div class="container">
            <form method="GET" action="products.php">
                <div class="filters-container">
                    <div class="filter-group">
                        <select name="category" class="filter-select" onchange="this.form.submit()">
                            <option value=""><?php _t('all_categories'); ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="sort" class="filter-select" onchange="this.form.submit()">
                            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>><?php _t('newest'); ?></option>
                            <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>><?php _t('name_asc'); ?></option>
                            <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>><?php _t('price_low_to_high'); ?></option>
                            <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>><?php _t('price_high_to_low'); ?></option>
                        </select>
                    </div>
                    
                    <div class="search-box">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php _t('search_placeholder'); ?>" class="search-input">
                        <button type="submit" class="search-btn"><?php _t('search'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Products Grid -->
    <section class="section">
        <div class="container">
            <?php if ($total_products > 0): ?>
                <div class="results-info">
                    <p><?php _t('showing'); ?> <?php echo $offset + 1; ?> - <?php echo min($offset + $per_page, $total_products); ?> <?php _t('of'); ?> <?php echo $total_products; ?> <?php echo $total_products == 1 ? _t('product') : _t('products'); ?></p>
                </div>
                
                <div class="products-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-product-id="<?php echo $product['id']; ?>" onclick="window.location.href='product-detail.php?id=<?php echo $product['id']; ?>';" style="cursor: pointer;">
                            <div class="product-image">
                                <?php 
                                // Get primary image from product_images table if available
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
                                
                                if ($imageSrc): 
                                    // Check if it's a full URL or local path
                                    if (!preg_match('/^https?:\/\//', $imageSrc)) {
                                        // It's a local file path - store pages are in subdirectory, so use relative path
                                        $imageSrc = '../' . ltrim($imageSrc, '/');
                                    }
                                ?>
                                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                                         alt="<?php echo htmlspecialchars(productLocalizedField($product, 'name', $currentLang)); ?>"
                                         onerror="this.src='https://via.placeholder.com/600x800/f0f0f0/666?text=Image+Not+Found'">
                                <?php else: ?>
                                    <img src="https://via.placeholder.com/600x800/f0f0f0/666?text=No+Image" alt="No Image">
                                <?php endif; ?>
                                
                                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                                    <span class="product-badge"><?php _t('discount'); ?> <?php echo round((1 - $product['price']/$product['old_price']) * 100); ?>%</span>
                                <?php elseif (strtotime($product['created_at']) > strtotime('-7 days')): ?>
                                    <span class="product-badge"><?php _t('new'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-info">
                                <p class="product-category"><?php echo htmlspecialchars(getLang() === 'ar' ? ($product['category_name_ar'] ?? $product['category_name'] ?? 'عام') : ($product['category_name'] ?? $product['category_name_ar'] ?? 'General')); ?></p>
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
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-products">
                    <i class="bi bi-bag-x"></i>
                    <h3><?php _t('no_products'); ?></h3>
                    <p><?php _t('no_products_found'); ?></p>
                    <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;"><?php _t('view_all_products'); ?></a>
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
        
        // Debug product card clicks
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.product-card');
            console.log('Found ' + productCards.length + ' product cards');
            
            productCards.forEach((card, index) => {
                card.addEventListener('click', function(e) {
                    console.log('Product card ' + index + ' clicked!');
                    console.log('Link href:', this.href);
                    console.log('Event target:', e.target);
                });
            });
        });
    </script>
</body>
</html>

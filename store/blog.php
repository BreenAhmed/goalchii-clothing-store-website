<?php
// Blog Page - Multi-language E-commerce
require_once '../config/database.php';
require_once '../includes/lang.php';

// Get filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

try {
    $pdo = getDBConnection();
    
    // Get total count
    $total_posts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
    $total_pages = ceil($total_posts / $per_page);
    
    // Get blog posts
    $sql = "SELECT bp.*, u.full_name as author_name 
            FROM blog_posts bp 
            LEFT JOIN users u ON bp.author_id = u.id 
            WHERE bp.status = 'published' 
            ORDER BY bp.published_at DESC 
            LIMIT :offset, :limit";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();
    
} catch (Exception $e) {
    $posts = [];
}

$currentLang = getLang();

function blogLocalizedField(array $post, string $field, string $lang): string {
    $priorityMap = [
        'ar' => [$field . '_ar', $field . '_ku', $field . '_en'],
        'en' => [$field . '_en', $field . '_ar', $field . '_ku'],
        'ku' => [$field . '_ku', $field . '_ar', $field . '_en'],
    ];
    $fallbackOrder = [$field . '_ar', $field . '_ku', $field . '_en'];
    $order = $priorityMap[$lang] ?? $fallbackOrder;
    
    foreach ($order as $key) {
        if (!empty($post[$key])) {
            return $post[$key];
        }
    }
    
    foreach ($fallbackOrder as $key) {
        if (!empty($post[$key])) {
            return $post[$key];
        }
    }
    
    return '';
}

// Sample blog posts if no data in database
if (empty($posts)) {
    $posts = [
        [
            'id' => 1,
            'title_ar' => 'أهمية اختيار الحذاء الرياضي المناسب',
            'title_ku' => 'گرنگی هەلبژاردنی پێڵاوی وەرزشی گونجاو',
            'excerpt_ar' => 'اختيار الحذاء الرياضي المناسب يمكن أن يحدث فرقاً كبيراً في أدائك الرياضي. تعرف على النصائح الأساسية لاختيار الحذاء المثالي.',
            'excerpt_ku' => 'هەلبژاردنی پێڵاوی وەرزشی دروست دەتوانێت گۆڕانکاری زۆر بخەیتە کارەتدا. ئەم ڕێنماییانە بۆ هەلبژاردنی باشترین پێڵاو بپشکنە.',
            'featured_image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800',
            'author_name' => 'أحمد محمد',
            'published_at' => '2024-01-15',
            'views' => 1250
        ],
        [
            'id' => 2,
            'title_ar' => '5 تمارين أساسية لتحسين لياقتك البدنية',
            'title_ku' => 'پێنج ڕاژه‌ی سەرەکی بۆ باشترکردنی لەبەردەستی',
            'excerpt_ar' => 'تعرف على أهم التمارين التي يمكنك ممارستها في المنزل أو الصالة الرياضية لتحسين لياقتك البدنية بشكل ملحوظ.',
            'excerpt_ku' => 'بەرزترین ڕاژه‌کانی وەرزشی فێربە کە دەتوانیت لە ماڵ یان جیم دا بنێیت بۆ باشترکردنی لەبەردەستیت.',
            'featured_image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800',
            'author_name' => 'فاطمة أحمد',
            'published_at' => '2024-01-10',
            'views' => 890
        ],
        [
            'id' => 3,
            'title_ar' => 'دليلك الشامل للعناية بالملابس الرياضية',
            'title_ku' => 'رێبازی تەواو بۆ چاودێری كردنی جلە وەرزشییەکان',
            'excerpt_ar' => 'الملابس الرياضية تحتاج عناية خاصة للحفاظ على جودتها. إليك أفضل النصائح للعناية بملابسك الرياضية.',
            'excerpt_ku' => 'جلە وەرزشییەکان پێویستی بە چاودێری تایبەت هەیە بۆ ئەوەی کوالیتییەکانیان بپارێزرێت. ئەم ڕوانامەیە باشترین ئامۆژگاریان پێشکەش دەکات.',
            'featured_image' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=800',
            'author_name' => 'خالد عبدالله',
            'published_at' => '2024-01-05',
            'views' => 650
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المدونة - جول تشي سبورتس</title>
    <meta name="description" content="اقرأ أحدث المقالات والنصائح حول اللياقة البدنية والرياضة من خبراء جول تشي.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .blog-hero {
            height: 50vh;
            min-height: 400px;
            padding-top: 100px;
            display: flex;
            align-items: center;
            background: var(--black);
            position: relative;
            overflow: hidden;
        }
        
        .blog-hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 20% 50%, var(--primary-color) 0%, transparent 50%);
            opacity: 0.1;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(-10%, -10%) rotate(0deg); }
            33% { transform: translate(-5%, -15%) rotate(120deg); }
            66% { transform: translate(-15%, -5%) rotate(240deg); }
        }
        
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }
        
        .blog-card {
            background: var(--white);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            border: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }
        
        .blog-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .blog-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .blog-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .blog-card:hover .blog-image img {
            transform: scale(1.1);
        }
        
        .blog-category {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary-color);
            color: var(--black);
            padding: 0.5rem 1rem;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        
        .blog-content {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .blog-date {
            color: var(--gray-500);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .blog-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--black);
            line-height: 1.4;
        }
        
        .blog-excerpt {
            color: var(--gray-600);
            line-height: 1.8;
            margin-bottom: 1.5rem;
            flex: 1;
        }
        
        .blog-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .blog-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.875rem;
        }
        
        .blog-stats {
            display: flex;
            gap: 1rem;
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        
        .read-more {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }
        
        .read-more:hover {
            gap: 1rem;
        }
        
        /* Featured Post */
        .featured-post {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 3rem;
            background: var(--gray-100);
            border: 2px solid var(--primary-color);
            margin-bottom: 3rem;
        }
        
        .featured-post .blog-image {
            height: 400px;
        }
        
        .featured-badge {
            background: var(--black);
            color: var(--primary-color);
            padding: 0.5rem 1.5rem;
            display: inline-block;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 991px) {
            .featured-post {
                grid-template-columns: 1fr;
                padding: 2rem;
            }
            
            .featured-post .blog-image {
                height: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .blog-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
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
                $pageParam = isset($_GET['page']) ? '&page=' . $_GET['page'] : '';
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
                            <a href="?lang=ar<?php echo $pageParam; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en<?php echo $pageParam; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku<?php echo $pageParam; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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
                    <li><a href="blog.php" class="nav-link active"><?php _t('blog'); ?></a></li>
                    <li><a href="contact.php" class="nav-link"><?php _t('contact'); ?></a></li>
                    <li class="language-switcher">
                        <a href="#" class="nav-link lang-toggle" style="font-weight: 600;" onclick="event.preventDefault(); var dropdown = document.getElementById('langDropdownDesktop'); if (dropdown) { dropdown.classList.toggle('show'); } var mobileDropdown = document.getElementById('langDropdownMobile'); if (mobileDropdown) { mobileDropdown.classList.remove('show'); }">
                            <?php 
                            echo $currentLang === 'ar' ? 'العربية' : ($currentLang === 'ku' ? 'کوردی' : 'English'); 
                            ?> ▼
                        </a>
                        <div id="langDropdownDesktop" class="lang-dropdown">
                            <a href="?lang=ar<?php echo $pageParam; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en<?php echo $pageParam; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku<?php echo $pageParam; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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

    <!-- Blog Hero -->
    <section class="blog-hero">
        <div class="container">
            <h1 class="hero-title" style="color: var(--white); font-size: clamp(2.5rem, 5vw, 4rem);">
                <?php _t('blog_title'); ?>
            </h1>
            <p class="hero-subtitle" style="color: var(--gray-300); font-size: 1.25rem;">
                <?php _t('blog_subtitle'); ?>
            </p>
        </div>
    </section>

    <!-- Blog Posts -->
    <section class="section">
        <div class="container">
            <?php if (!empty($posts)): ?>
                <!-- Featured Post (First Post) -->
                <?php if ($page == 1 && !empty($posts[0])): 
                    $featured = $posts[0];
                    array_shift($posts); // Remove featured from regular posts
                ?>
                    <div class="featured-post">
                        <div class="blog-image">
                            <?php 
                            $featuredImgSrc = $featured['featured_image'];
                            if (!preg_match('/^https?:\/\//', $featuredImgSrc)) {
                                $featuredImgSrc = '../' . ltrim($featuredImgSrc, '/');
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($featuredImgSrc); ?>" 
                                 alt="<?php echo htmlspecialchars(blogLocalizedField($featured, 'title', $currentLang)); ?>">
                        </div>
                        <div>
                            <span class="featured-badge"><?php _t('featured_post'); ?></span>
                            <p class="blog-date">
                                <i class="bi bi-calendar3"></i> 
                                <?php echo date('d F Y', strtotime($featured['published_at'])); ?>
                            </p>
                            <h2 class="blog-title" style="font-size: 2rem;">
                                <?php echo htmlspecialchars(blogLocalizedField($featured, 'title', $currentLang) ?: ''); ?>
                            </h2>
                            <p class="blog-excerpt" style="font-size: 1.125rem;">
                                <?php echo htmlspecialchars(blogLocalizedField($featured, 'excerpt', $currentLang) ?: ''); ?>
                            </p>
                            <div class="blog-meta">
                                <div class="blog-author">
                                    <i class="bi bi-person-circle"></i>
                                    <?php echo htmlspecialchars($featured['author_name']); ?>
                                </div>
                                <div class="blog-stats">
                                    <span><i class="bi bi-eye"></i> <?php echo number_format($featured['views']); ?></span>
                                </div>
                            </div>
                            <a href="blog-detail.php?id=<?php echo $featured['id']; ?>" class="read-more" style="margin-top: 1.5rem;">
                                <?php _t('read_more'); ?> <i class="bi bi-arrow-left"></i>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Regular Posts Grid -->
                <div class="blog-grid">
                    <?php foreach ($posts as $post): ?>
                        <article class="blog-card" onclick="window.location.href='blog-detail.php?id=<?php echo $post['id']; ?>'">
                            <div class="blog-image">
                                <?php 
                                $postImgSrc = $post['featured_image'];
                                if (!preg_match('/^https?:\/\//', $postImgSrc)) {
                                    $postImgSrc = '../' . ltrim($postImgSrc, '/');
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($postImgSrc); ?>" 
                                     alt="<?php echo htmlspecialchars(blogLocalizedField($post, 'title', $currentLang)); ?>">
                                <span class="blog-category"><?php _t('sports_tips'); ?></span>
                            </div>
                            <div class="blog-content">
                                <p class="blog-date">
                                    <i class="bi bi-calendar3"></i> 
                                    <?php echo date('d F Y', strtotime($post['published_at'])); ?>
                                </p>
                                <h3 class="blog-title">
                                <?php echo htmlspecialchars(blogLocalizedField($post, 'title', $currentLang) ?: ''); ?>
                                </h3>
                                <p class="blog-excerpt">
                                    <?php echo htmlspecialchars(blogLocalizedField($post, 'excerpt', $currentLang) ?: ''); ?>
                                </p>
                                <div class="blog-meta">
                                    <div class="blog-author">
                                        <i class="bi bi-person-circle"></i>
                                        <?php echo htmlspecialchars($post['author_name']); ?>
                                    </div>
                                    <div class="blog-stats">
                                        <span><i class="bi bi-eye"></i> <?php echo number_format($post['views']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-products">
                    <i class="bi bi-newspaper"></i>
                    <h3><?php _t('no_posts_message'); ?></h3>
                    <p><?php _t('new_posts_coming'); ?></p>
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
                        <li><a href="index.php?lang=<?php echo htmlspecialchars(getLang()); ?>#faq"><?php _t('shipping_policy'); ?></a></li>
                        <li><a href="index.php?lang=<?php echo htmlspecialchars(getLang()); ?>#faq"><?php _t('return_policy'); ?></a></li>
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
    </script>
</body>
</html>

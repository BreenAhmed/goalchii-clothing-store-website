<?php
// Blog Detail Page - Multi-language E-commerce
require_once '../config/database.php';
require_once '../includes/lang.php';

// Get post ID
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$post_id) {
    header('Location: blog.php');
    exit;
}

try {
    $pdo = getDBConnection();
    
    // Get blog post details
    $stmt = $pdo->prepare("
        SELECT bp.*, u.full_name as author_name 
        FROM blog_posts bp 
        LEFT JOIN users u ON bp.author_id = u.id 
        WHERE bp.id = ? AND bp.status = 'published'
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header('Location: blog.php');
        exit;
    }
    
    // Increment view count
    $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = ?")->execute([$post_id]);
    $post['views'] = ($post['views'] ?? 0) + 1;
    
    // Get related posts (excluding current post)
    $stmt = $pdo->prepare("
        SELECT bp.*, u.full_name as author_name 
        FROM blog_posts bp 
        LEFT JOIN users u ON bp.author_id = u.id 
        WHERE bp.id != ? 
        AND bp.status = 'published'
        ORDER BY bp.published_at DESC
        LIMIT 4
    ");
    $stmt->execute([$post_id]);
    $related_posts = $stmt->fetchAll();
    
} catch (Exception $e) {
    header('Location: blog.php');
    exit;
}

// Handle image path
$featuredImgSrc = $post['featured_image'] ?? '';
if ($featuredImgSrc && !preg_match('/^https?:\/\//', $featuredImgSrc)) {
    $featuredImgSrc = '../' . ltrim($featuredImgSrc, '/');
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
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(blogLocalizedField($post, 'title', $currentLang) ?: 'Blog Post'); ?> - GoalChii Sports</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    <style>
        .blog-detail-hero {
            height: 50vh;
            min-height: 400px;
            padding-top: 100px;
            display: flex;
            align-items: center;
            background: var(--black);
            position: relative;
            overflow: hidden;
            margin-bottom: 3rem;
        }
        
        .blog-detail-hero::before {
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
        
        
        .blog-detail-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
        }
        
        .blog-detail-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }
        
        .blog-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .blog-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            font-size: 0.95rem;
        }
        
        .blog-meta-item i {
            color: var(--primary-color);
        }
        
        .blog-detail-content {
            font-size: 1.125rem;
            line-height: 2;
            color: var(--black);
            margin-bottom: 3rem;
            white-space: pre-wrap;
        }
        
        .blog-detail-content p {
            margin-bottom: 1.5rem;
        }
        
        .blog-content-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .related-posts {
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 1px solid var(--gray-200);
        }
        
        .related-posts-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            color: var(--black);
        }
        
        .related-posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 3rem;
        }
        
        .related-post-card {
            background: var(--white);
            overflow: hidden;
            transition: var(--transition);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
            border: 1px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }
        
        .related-post-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .related-post-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .related-post-card:hover .related-post-image {
            transform: scale(1.1);
        }
        
        .related-post-content {
            padding: 2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .related-post-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--black);
            line-height: 1.4;
        }
        
        .related-post-excerpt {
            color: var(--gray-600);
            line-height: 1.8;
            margin-bottom: 1.5rem;
            flex: 1;
        }
        
        .related-post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-200);
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        .back-to-blog {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            margin-bottom: 2rem;
            transition: var(--transition);
        }
        
        .back-to-blog:hover {
            gap: 1rem;
            color: var(--white);
        }
        
        @media (max-width: 991px) {
            .blog-content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .related-posts-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .blog-detail-title {
                font-size: 1.75rem;
            }
            
            .blog-detail-content {
                font-size: 1rem;
            }
            
            .blog-detail-meta {
                flex-direction: column;
                gap: 1rem;
            }
            
            .related-posts-grid {
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
                            <a href="?lang=ar&id=<?php echo $post_id; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en&id=<?php echo $post_id; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku&id=<?php echo $post_id; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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
                            <a href="?lang=ar&id=<?php echo $post_id; ?>" class="lang-option <?php echo getLang() === 'ar' ? 'active' : ''; ?>">العربية</a>
                            <a href="?lang=en&id=<?php echo $post_id; ?>" class="lang-option <?php echo getLang() === 'en' ? 'active' : ''; ?>">English</a>
                            <a href="?lang=ku&id=<?php echo $post_id; ?>" class="lang-option <?php echo getLang() === 'ku' ? 'active' : ''; ?>">کوردی</a>
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

    <!-- Blog Detail Hero -->
    <section class="blog-detail-hero">
        <div class="container">
            <div>
                <a href="blog.php" class="back-to-blog">
                    <i class="bi bi-arrow-right"></i> <?php _t('back_to_blog'); ?>
                </a>
                <h1 class="hero-title" style="color: var(--white); font-size: clamp(2rem, 5vw, 3rem); margin-bottom: 1rem;">
                    <?php echo htmlspecialchars(blogLocalizedField($post, 'title', $currentLang) ?: 'Untitled'); ?>
                </h1>
                <div style="display: flex; flex-wrap: wrap; gap: 2rem; color: var(--gray-300);">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-person-circle"></i>
                        <span><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown Author'); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-calendar3"></i>
                        <span><?php echo date('d F Y', strtotime($post['published_at'] ?? $post['created_at'])); ?></span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <i class="bi bi-eye"></i>
                        <span><?php echo number_format($post['views']); ?> <?php _t('views'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Content -->
    <section class="section">
        <div class="container">
            <?php if ($featuredImgSrc): ?>
            <img src="<?php echo htmlspecialchars($featuredImgSrc); ?>" 
                 alt="<?php echo htmlspecialchars(blogLocalizedField($post, 'title', $currentLang) ?: ''); ?>" 
                 class="blog-detail-image"
                 onerror="this.style.display='none'">
            <?php endif; ?>
            
            <div class="blog-content-wrapper">
                <div>
                    <div class="blog-detail-content">
                        <?php 
                        $content = blogLocalizedField($post, 'content', $currentLang);
                        echo nl2br(htmlspecialchars($content));
                        ?>
                    </div>
                </div>
                <div>
                    <!-- Sidebar can be added here for categories, tags, etc. -->
                </div>
            </div>
        </div>
    </section>

    <!-- Related Posts -->
    <?php if (!empty($related_posts)): ?>
    <section class="section related-posts">
        <div class="container">
            <h2 class="related-posts-title"><?php _t('related_posts'); ?></h2>
            <div class="related-posts-grid">
                <?php foreach ($related_posts as $related_post): 
                    $relatedImgSrc = $related_post['featured_image'] ?? '';
                    if ($relatedImgSrc && !preg_match('/^https?:\/\//', $relatedImgSrc)) {
                        $relatedImgSrc = '../' . ltrim($relatedImgSrc, '/');
                    }
                ?>
                <a href="blog-detail.php?id=<?php echo $related_post['id']; ?>" class="related-post-card">
                    <div style="position: relative; height: 250px; overflow: hidden;">
                        <?php if ($relatedImgSrc): ?>
                        <img src="<?php echo htmlspecialchars($relatedImgSrc); ?>" 
                             alt="<?php echo htmlspecialchars(blogLocalizedField($related_post, 'title', $currentLang) ?: ''); ?>"
                             class="related-post-image"
                             onerror="this.style.display='none'">
                        <?php endif; ?>
                        <span class="blog-category" style="position: absolute; top: 1rem; right: 1rem; background: var(--primary-color); color: var(--black); padding: 0.5rem 1rem; font-weight: 700; font-size: 0.875rem; text-transform: uppercase;"><?php _t('sports_tips'); ?></span>
                    </div>
                    <div class="related-post-content">
                        <p class="blog-date" style="color: var(--gray-500); font-size: 0.875rem; margin-bottom: 0.5rem;">
                            <i class="bi bi-calendar3"></i> 
                            <?php echo date('d F Y', strtotime($related_post['published_at'] ?? $related_post['created_at'])); ?>
                        </p>
                        <h3 class="related-post-title">
                            <?php echo htmlspecialchars(blogLocalizedField($related_post, 'title', $currentLang) ?: 'Untitled'); ?>
                        </h3>
                        <?php $relatedExcerpt = blogLocalizedField($related_post, 'excerpt', $currentLang); ?>
                        <?php if ($relatedExcerpt): ?>
                        <p class="related-post-excerpt">
                            <?php echo htmlspecialchars($relatedExcerpt); ?>
                        </p>
                        <?php endif; ?>
                        <div class="related-post-meta">
                            <div class="blog-author" style="display: flex; align-items: center; gap: 0.5rem; color: var(--gray-600); font-size: 0.875rem;">
                                <i class="bi bi-person-circle"></i>
                                <?php echo htmlspecialchars($related_post['author_name'] ?? 'Unknown'); ?>
                            </div>
                            <div class="blog-stats" style="display: flex; gap: 1rem; color: var(--gray-500); font-size: 0.875rem;">
                                <span><i class="bi bi-eye"></i> <?php echo number_format($related_post['views'] ?? 0); ?></span>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Newsletter CTA -->
    <section class="section" style="background: linear-gradient(135deg, var(--black) 0%, var(--gray-900) 100%); position: relative; overflow: hidden;">
        <div style="position: absolute; width: 100%; height: 100%; background: radial-gradient(circle at 30% 50%, var(--primary-color) 0%, transparent 60%); opacity: 0.15; animation: pulse 4s ease-in-out infinite;"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 4rem 2rem;">
                <div style="display: inline-block; background: var(--primary-color); color: var(--black); padding: 0.5rem 1.5rem; font-weight: 700; font-size: 0.875rem; text-transform: uppercase; margin-bottom: 1.5rem; letter-spacing: 1px;">
                    ✉️ كن أول من يعرف
                </div>
                <h2 style="color: var(--white); font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: 1rem; line-height: 1.2;">
                    انضم إلى <span style="color: var(--primary-color);">قائمة المشتركين</span>
                </h2>
                <p style="color: var(--gray-300); font-size: 1.125rem; margin-bottom: 2.5rem; line-height: 1.6;">
                    احصل على عروض خاصة حصرية، نصائح رياضية، وأخبار المنتجات الجديدة قبل الجميع
                </p>
                <form style="max-width: 600px; margin: 0 auto;" onsubmit="alert('شكراً لاشتراكك!'); return false;">
                    <div style="display: flex; gap: 1rem; background: var(--gray-800); padding: 0.5rem; border-radius: 50px; border: 2px solid var(--gray-700); transition: all 0.3s ease;" onfocusin="this.style.borderColor='var(--primary-color)'" onfocusout="this.style.borderColor='var(--gray-700)'">
                        <input type="email" placeholder="أدخل بريدك الإلكتروني هنا..." required 
                               style="flex: 1; background: transparent; border: none; color: var(--white); padding: 1rem 1.5rem; font-size: 1rem; outline: none;"
                               onfocus="this.parentElement.style.borderColor='var(--primary-color)'"
                               onblur="this.parentElement.style.borderColor='var(--gray-700)'">
                        <button type="submit" 
                                style="background: var(--primary-color); color: var(--black); border: none; padding: 1rem 2.5rem; font-weight: 700; font-size: 1rem; border-radius: 50px; cursor: pointer; transition: all 0.3s ease; white-space: nowrap;"
                                onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 20px rgba(192, 255, 114, 0.4)'"
                                onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none'">
                            اشترك
                        </button>
                    </div>
                    <p style="color: var(--gray-400); font-size: 0.875rem; margin-top: 1rem;">
                        🎁 اشترك الآن واحصل على خصم 10% على أول طلبية لك
                    </p>
                </form>
            </div>
        </div>
        <style>
            @keyframes pulse {
                0%, 100% { opacity: 0.15; transform: scale(1); }
                50% { opacity: 0.25; transform: scale(1.1); }
            }
        </style>
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
    </script>
</body>
</html>


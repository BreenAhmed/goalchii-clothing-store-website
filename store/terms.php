<?php
require_once '../config/database.php';
require_once '../includes/lang.php';

$currentLang = getLang();
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $currentLang === 'ar' || $currentLang === 'ku' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _t('terms_title'); ?> - <?php _t('brand_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
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

    <!-- Terms & Conditions Content -->
    <main class="terms-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="terms-content">
                        <header class="terms-header">
                            <h1 class="terms-main-title"><?php _t('terms_title'); ?></h1>
                            <p class="terms-intro"><?php _t('terms_intro'); ?></p>
                        </header>

                        <div class="terms-sections">
                            <!-- Section 1: Acceptance -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_acceptance_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_acceptance_text'); ?></p>
                            </section>

                            <!-- Section 2: Products -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_products_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_products_text'); ?></p>
                            </section>

                            <!-- Section 3: Orders -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_orders_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_orders_text'); ?></p>
                            </section>

                            <!-- Section 4: Shipping -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_shipping_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_shipping_text'); ?></p>
                            </section>

                            <!-- Section 5: Returns -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_returns_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_returns_text'); ?></p>
                            </section>

                            <!-- Section 6: Liability -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_liability_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_liability_text'); ?></p>
                            </section>

                            <!-- Section 7: Changes -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_changes_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_changes_text'); ?></p>
                            </section>

                            <!-- Section 8: Contact -->
                            <section class="terms-section">
                                <h2 class="section-title"><?php _t('terms_contact_title'); ?></h2>
                                <p class="section-text"><?php _t('terms_contact_text'); ?></p>
                            </section>
                        </div>

                        <div class="terms-footer">
                            <p class="last-updated">
                                <i class="bi bi-calendar-check"></i>
                                <?php echo $currentLang === 'ar' ? 'آخر تحديث: نوفمبر 2024' : ($currentLang === 'ku' ? 'دوایین نوێکردنەوە: تشرینی دووەمی ٢٠٢٤' : 'Last updated: November 2024'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
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

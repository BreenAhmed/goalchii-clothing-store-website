<?php
// Contact Page - Multi-language E-commerce
require_once '../config/database.php';
require_once '../includes/lang.php';

$success = false;
$error = false;
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = t('fill_required_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('valid_email');
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $subject, $message]);
            $success = true;
            $_POST = [];
            $name = $email = $phone = $subject = $message = '';
        } catch (Exception $e) {
            $error = t('error_sending');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _t('contact'); ?> - GoalChii Sports</title>
    <meta name="description" content="<?php _t('contact_subtitle'); ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .contact-hero {
            height: 50vh;
            min-height: 400px;
            padding-top: 100px;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--black) 0%, var(--gray-900) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .contact-hero::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border: 3px solid var(--primary-color);
            border-radius: 50%;
            top: -250px;
            left: -250px;
            opacity: 0.1;
            animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-top: 3rem;
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .contact-card {
            background: var(--white);
            padding: 2rem;
            border: 1px solid var(--gray-200);
            display: flex;
            gap: 1.5rem;
            align-items: flex-start;
            transition: var(--transition);
        }
        
        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .contact-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            color: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .contact-details h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .contact-details p,
        .contact-details a {
            color: var(--gray-600);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .contact-details a:hover {
            color: var(--primary-color);
        }
        
        /* Contact Form */
        .contact-form {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 28px;
            padding: 3.5rem;
            backdrop-filter: blur(18px);
            box-shadow: 0 30px 60px rgba(9, 12, 20, 0.45);
            position: relative;
            overflow: hidden;
        }

        .contact-form::before,
        .contact-form::after {
            content: '';
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(192, 255, 114, 0.35), rgba(192, 255, 114, 0));
            filter: blur(12px);
            z-index: -1;
        }

        .contact-form::before {
            top: -60px;
            right: -60px;
        }

        .contact-form::after {
            bottom: -80px;
            left: -60px;
        }

        .form-title {
            font-size: 2.5rem;
            margin-bottom: 2.5rem;
            color: var(--black);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .form-title i {
            font-size: 2rem;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.75rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-weight: 700;
            color: var(--black);
            margin-bottom: 0.75rem;
            letter-spacing: 0.05rem;
        }

        .form-group label i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 1px solid rgba(0, 0, 0, 0.12);
            background: #ffffff;
            color: var(--black);
            border-radius: 14px;
            font-size: 1rem;
            transition: border-color 0.3s ease, background 0.3s ease, box-shadow 0.3s ease;
        }

        .form-control::placeholder {
            color: rgba(0, 0, 0, 0.4);
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(192, 255, 114, 0.75);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(192, 255, 114, 0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.75rem;
            margin-bottom: 1rem;
        }

        .required {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        html[dir="rtl"] .form-group label {
            flex-direction: row-reverse;
        }

        html[dir="rtl"] .form-group label i {
            margin-left: 0.35rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 0;
            font-weight: 500;
            animation: slideInDown 0.3s ease-out;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Social Links */
        .contact-social {
            margin-top: 3rem;
            text-align: center;
        }
        
        .contact-social h3 {
            margin-bottom: 1.5rem;
            color: var(--gray-700);
        }
        
        .social-links-large {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
        }
        
        .social-links-large a {
            width: 60px;
            height: 60px;
            background: var(--black);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            transition: var(--transition);
            border: 2px solid var(--black);
        }
        
        .social-links-large a:hover {
            background: var(--primary-color);
            color: var(--black);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(192, 255, 114, 0.4);
        }
        
        @media (max-width: 991px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
            
            .contact-form {
                padding: 2.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-title {
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
                    <li><a href="contact.php" class="nav-link active"><?php _t('contact'); ?></a></li>
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

    <!-- Contact Hero -->
    <section class="contact-hero">
        <div class="container">
            <h1 class="hero-title" style="color: var(--white); font-size: clamp(2.5rem, 5vw, 4rem);">
                <?php _t('contact_title'); ?>
            </h1>
            <p class="hero-subtitle" style="color: var(--gray-300); font-size: 1.25rem;">
                <?php _t('contact_subtitle'); ?>
            </p>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="section">
        <div class="container">
            <div class="contact-content">
                <!-- Contact Info -->
                <div class="contact-info">
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div class="contact-details">
                            <h3><?php _t('address'); ?></h3>
                            <p><?php _t('location'); ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <a href="tel:+9647515215455" style="color: inherit;">
                                <i class="bi bi-telephone-fill"></i>
                            </a>
                        </div>
                        <div class="contact-details">
                            <h3><?php _t('phone'); ?></h3>
                            <p><a href="tel:+9647515215455">+964 751 521 5455</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <a href="mailto:info@goalchii.store" style="color: inherit;">
                                <i class="bi bi-envelope-fill"></i>
                            </a>
                        </div>
                        <div class="contact-details">
                            <h3><?php _t('email'); ?></h3>
                            <p><a href="mailto:info@goalchii.store">info@goalchii.store</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <div class="contact-details">
                            <h3><?php _t('working_hours'); ?></h3>
                            <p>Everyday 24/7 Hours</p>
                        </div>
                    </div>

                    <div class="contact-card">
                        <div class="contact-icon">
                            <i class="bi bi-chat-left-dots-fill"></i>
                        </div>
                        <div class="contact-details">
                            <h3><?php _t('message'); ?></h3>
                            <p><?php _t('contact_subtitle'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form">
                    <h2 class="form-title"><i class="bi bi-envelope-paper"></i><?php _t('send_us_message'); ?></h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="bi bi-person"></i><?php _t('full_name'); ?> <span class="required">*</span></label>
                                <input type="text" name="name" class="form-control" required placeholder="<?php _t('full_name'); ?>" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-at"></i><?php _t('email'); ?> <span class="required">*</span></label>
                                <input type="email" name="email" class="form-control" required placeholder="example@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label><i class="bi bi-telephone"></i><?php _t('phone_number'); ?></label>
                                <input type="tel" name="phone" class="form-control" placeholder="+964 7xx xxx xxxx" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label><i class="bi bi-chat-dots"></i><?php _t('subject'); ?></label>
                                <input type="text" name="subject" class="form-control" placeholder="<?php _t('subject'); ?>" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label><i class="bi bi-chat-left-text"></i><?php _t('message'); ?> <span class="required">*</span></label>
                            <textarea name="message" class="form-control" rows="5" required placeholder="<?php _t('message_placeholder'); ?>"><?php echo htmlspecialchars($message); ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem; padding: 1rem 1.5rem;">
                            <i class="bi bi-send-fill"></i> <?php _t('send_message'); ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Social Links -->
            <div class="contact-social">
                <h3><?php _t('follow_us_social'); ?></h3>
                <div class="social-links-large">
                    <a href="https://www.facebook.com/share/17hDXhUg72/?mibextid=wwXIfr" title="Facebook" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/goalchii" target="_blank" rel="noopener" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.tiktok.com/@goalchii?_r=1&_t=ZS-91K2YnqmcYK" title="TikTok" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>
                    <a href="https://wa.me/9647515215455" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
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
                    <p><?php _t('follow_us_social'); ?></p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/share/17hDXhUg72/?mibextid=wwXIfr" target="_blank" rel="noopener"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/goalchii" target="_blank" rel="noopener"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.tiktok.com/@goalchii?_r=1&_t=ZS-91K2YnqmcYK" target="_blank" rel="noopener"><i class="bi bi-tiktok"></i></a>
                        <a href="https://wa.me/9647515215455"><i class="bi bi-whatsapp"></i></a>
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
        
        // Form Validation
        const form = document.querySelector('form');
        if (form && !<?php echo $success ? 'true' : 'false'; ?>) {
            form.addEventListener('submit', (e) => {
                const email = form.email.value;
                if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    e.preventDefault();
                    alert('<?php echo addslashes(t('enter_valid_email')); ?>');
                    return false;
                }
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


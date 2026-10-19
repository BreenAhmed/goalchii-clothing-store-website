<?php
// About Us Page - Multi-language E-commerce
require_once '../config/database.php';
require_once '../includes/lang.php';

// Get about content from database
$about_sections = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM about_content WHERE is_active = 1 ORDER BY display_order ASC");
    $about_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create array keyed by section_key
    $sections = [];
    foreach ($about_sections as $section) {
        $sections[$section['section_key']] = $section;
    }
} catch (Exception $e) {
    $sections = [];
}
?>
<!DOCTYPE html>
<html lang="<?php echo getLangAttr(); ?>" dir="<?php echo getDir(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>من نحن - جول تشي سبورتس</title>
    <meta name="description" content="تعرف على قصة جول تشي سبورتس ورحلتنا في تقديم أفضل الملابس الرياضية.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/images/logo/logo.png">
    
    <style>
        .about-hero {
            height: 70vh;
            min-height: 500px;
            padding-top: 100px;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, var(--black) 0%, var(--gray-900) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .about-hero::before {
            content: '';
            position: absolute;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, var(--primary-color) 0%, transparent 70%);
            opacity: 0.1;
            top: -400px;
            left: -400px;
            animation: pulse 4s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .timeline::after {
            content: '';
            position: absolute;
            width: 4px;
            background: var(--primary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -2px;
        }
        
        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            opacity: 0;
            animation: fadeInUp 0.8s forwards;
        }
        
        .timeline-item:nth-child(odd) {
            left: 0;
            padding-right: 60px;
            text-align: right;
        }
        
        .timeline-item:nth-child(even) {
            left: 50%;
            padding-left: 60px;
            text-align: left;
        }
        
        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            background: var(--primary-color);
            border: 4px solid var(--white);
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }
        
        .timeline-item:nth-child(odd)::after {
            right: -12.5px;
        }
        
        .timeline-item:nth-child(even)::after {
            left: -12.5px;
        }
        
        .timeline-content {
            padding: 30px;
            background: var(--white);
            border-radius: 0;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }
        
        .timeline-content:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary-color);
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }
        
        .value-card {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--white);
            border: 2px solid var(--gray-200);
            transition: var(--transition);
        }
        
        .value-card:hover {
            transform: translateY(-10px);
            border-color: var(--primary-color);
            box-shadow: var(--shadow-xl);
        }
        
        .value-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        
        .mission-vision {
            background: linear-gradient(135deg, #090d14 0%, #101823 50%, #0a0f17 100%);
            color: var(--white);
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }

        .mission-vision::before,
        .mission-vision::after {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            filter: blur(70px);
            opacity: 0.45;
            z-index: -1;
        }

        .mission-vision::before {
            background: rgba(192, 255, 114, 0.35);
            top: -140px;
            left: -120px;
        }

        .mission-vision::after {
            background: rgba(192, 255, 114, 0.2);
            bottom: -160px;
            right: -120px;
        }

        .mission-vision .section-title h2 {
            color: var(--white);
            text-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .mission-vision-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            margin-top: 3.5rem;
        }

        .mv-card {
            background: rgba(13, 18, 26, 0.82);
            border: 1px solid rgba(192, 255, 114, 0.18);
            border-radius: 28px;
            padding: 2.75rem;
            box-shadow: 0 30px 60px rgba(4, 6, 10, 0.45);
            position: relative;
            overflow: hidden;
            transition: transform 0.4s ease, box-shadow 0.4s ease, border-color 0.4s ease;
            text-align: left;
        }

        .mv-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(192, 255, 114, 0.18), transparent 55%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .mv-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 40px 80px rgba(4, 6, 10, 0.55);
            border-color: rgba(192, 255, 114, 0.45);
        }

        .mv-card:hover::before {
            opacity: 1;
        }

        .mv-card-header {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-bottom: 1.75rem;
        }

        .mv-card-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: grid;
            place-items: center;
            font-size: 1.9rem;
            color: var(--black);
            background: linear-gradient(135deg, rgba(192, 255, 114, 0.85), rgba(192, 255, 114, 0.55));
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35), 0 15px 30px rgba(192, 255, 114, 0.28);
        }

        .mv-card.mission .mv-card-icon {
            background: linear-gradient(135deg, rgba(192, 255, 114, 0.95), rgba(192, 255, 114, 0.65));
        }

        .mv-card.vision .mv-card-icon {
            background: linear-gradient(135deg, rgba(192, 255, 114, 0.7), rgba(192, 255, 114, 0.4));
        }

        .mv-card-kicker {
            display: inline-block;
            font-size: 0.75rem;
            letter-spacing: 0.3rem;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.35rem;
        }

        .mv-card-title {
            font-size: 2.1rem;
            font-weight: 800;
            color: var(--white);
            margin: 0;
        }

        .mv-card-body p {
            color: rgba(255, 255, 255, 0.78);
            font-size: 1.1rem;
            line-height: 1.9;
            margin: 0;
        }

        .mv-card-footer {
            margin-top: 1.75rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .mv-card-stat {
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .mv-card-stat span:first-child {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary-color);
        }

        .mv-card-stat span:last-child {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.55);
            letter-spacing: 0.08rem;
        }

        html[dir="rtl"] .mission-vision-grid {
            direction: rtl;
        }

        html[dir="rtl"] .mv-card {
            text-align: right;
        }

        html[dir="rtl"] .mv-card-header {
            flex-direction: row-reverse;
        }

        html[dir="rtl"] .mv-card-kicker {
            letter-spacing: 0.15rem;
        }

        @media (max-width: 991px) {
            .mission-vision {
                padding: 5rem 0;
            }

            .mission-vision-grid {
                gap: 2rem;
            }
        }

        @media (max-width: 600px) {
            .mv-card {
                padding: 2.25rem;
            }

            .mv-card-title {
                font-size: 1.8rem;
            }

            .mv-card-body p {
                font-size: 1rem;
            }
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-top: 3rem;
        }
        
        .team-member {
            text-align: center;
        }
        
        .team-member img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 5px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .team-member:hover img {
            transform: scale(1.1);
            box-shadow: 0 10px 30px rgba(192, 255, 114, 0.4);
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 991px) {
            .timeline::after {
                left: 28px;
                right: auto;
            }
            
            .timeline-item {
                width: 100%;
                left: auto;
                right: auto;
                margin-left: 0;
                margin-bottom: 2rem;
                padding-left: 70px;
                padding-right: 25px;
                text-align: left;
            }
            
            .timeline-item::after {
                left: 18px;
                right: auto;
            }
        }

        html[dir="rtl"] .timeline::after {
            left: auto;
            right: 50%;
            margin-right: -2px;
        }

        html[dir="rtl"] .timeline-item:nth-child(odd) {
            left: auto;
            right: 0;
            padding-right: 60px;
            padding-left: 40px;
            text-align: left;
        }

        html[dir="rtl"] .timeline-item:nth-child(even) {
            left: auto;
            right: 50%;
            padding-left: 40px;
            padding-right: 60px;
            text-align: right;
        }

        html[dir="rtl"] .timeline-item:nth-child(odd)::after {
            right: auto;
            left: -12.5px;
        }

        html[dir="rtl"] .timeline-item:nth-child(even)::after {
            left: auto;
            right: -12.5px;
        }

        @media (max-width: 991px) {
            html[dir="rtl"] .timeline::after {
                left: auto;
                right: 28px;
            }

            html[dir="rtl"] .timeline-item {
                left: auto;
                right: 0;
                padding-right: 70px;
                padding-left: 25px;
                text-align: right;
                margin-right: 0;
            }

            html[dir="rtl"] .timeline-item::after {
                left: auto;
                right: 18px;
            }

            html[dir="rtl"] .timeline-item:nth-child(odd),
            html[dir="rtl"] .timeline-item:nth-child(even) {
                left: auto;
                right: 0;
                padding-right: 70px;
                padding-left: 25px;
                text-align: right;
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
                    <li><a href="about.php" class="nav-link active"><?php _t('about'); ?></a></li>
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

    <!-- About Hero -->
    <section class="about-hero">
        <div class="container">
            <h1 class="hero-title" style="color: var(--white);">
                <?php _t('story_title'); ?>
            </h1>
            <p class="hero-subtitle"><?php _t('story_subtitle'); ?></p>
        </div>
    </section>

    <!-- Our Values -->
    <section class="section bg-gray">
        <div class="container">
            <div class="section-title">
                <h2><?php _t('values_title'); ?></h2>
                <p><?php _t('values_subtitle'); ?></p>
            </div>
            
            <div class="values-grid">
                <?php if (isset($sections['values']) && !empty($sections['values']['content_ar'])): ?>
                    <?php 
                    // Split values content by common separators - use correct language
                    $values_text = getLang() === 'ar' ? ($sections['values']['content_ar'] ?? '') : ($sections['values']['content_en'] ?? $sections['values']['content_ar'] ?? '');
                    $values_list = preg_split('/[•\-\n\r]+/', $values_text);
                    $values_list = array_filter(array_map('trim', $values_list));

                    if (getLang() === 'ku') {
                        $value_translations = [
                            'quality' => t('quality'),
                            'innovation' => t('innovation'),
                            'sustainability' => t('sustainability'),
                            'passion' => t('passion'),
                            'excellence' => t('excellence'),
                            'excellenc' => t('excellence'),
                            'community' => t('community'),
                            'teamwork' => t('teamwork'),
                        ];

                        foreach ($values_list as &$value_item) {
                            $normalized_value = strtolower($value_item);
                            if (isset($value_translations[$normalized_value])) {
                                $value_item = $value_translations[$normalized_value];
                            }
                        }
                        unset($value_item);
                    }
                    $values_list = array_values(array_unique($values_list));

                    if (!in_array(t('teamwork'), $values_list, true)) {
                        $values_list[] = t('teamwork');
                    }

                    $default_values = [
                        t('quality'),
                        t('innovation'),
                        t('community'),
                        t('sustainability'),
                        t('passion'),
                        t('excellence')
                    ];

                    foreach ($default_values as $default_value) {
                        if (count($values_list) >= 6) {
                            break;
                        }
                        if (!in_array($default_value, $values_list, true)) {
                            $values_list[] = $default_value;
                        }
                    }

                    $icons = ['bi-gem', 'bi-lightbulb', 'bi-people', 'bi-heart', 'bi-star', 'bi-award', 'bi-people-fill'];
                    $icon_index = 0;
                    ?>
                    <?php foreach (array_slice($values_list, 0, 7) as $value): ?>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="bi <?php echo $icons[$icon_index % count($icons)]; ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($value); ?></h3>
                        </div>
                        <?php $icon_index++; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php 
                        $default_values = [
                            ['key' => 'teamwork', 'icon' => 'bi-people-fill'],
                            ['key' => 'quality', 'icon' => 'bi-gem'],
                            ['key' => 'innovation', 'icon' => 'bi-lightbulb'],
                            ['key' => 'community', 'icon' => 'bi-people'],
                            ['key' => 'sustainability', 'icon' => 'bi-heart'],
                            ['key' => 'passion', 'icon' => 'bi-star'],
                            ['key' => 'excellence', 'icon' => 'bi-award'],
                        ];
                    ?>
                    <?php foreach ($default_values as $value): ?>
                        <div class="value-card">
                            <div class="value-icon">
                                <i class="bi <?php echo $value['icon']; ?>"></i>
                            </div>
                            <h3><?php _t($value['key']); ?></h3>
                            <p><?php _t($value['key'] . '_desc'); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="section mission-vision">
        <div class="container">
            <div class="section-title">
                <h2><?php _t('mission_vision_title'); ?></h2>
            </div>

            <?php
                $currentLang = getLang();
                $missionVisionLabel = t('mission_vision_title');

                $missionTitle = t('our_mission');
                $missionContent = t('mission_text');
                if (isset($sections['mission']) && !empty($sections['mission']['content_ar'])) {
                    if ($currentLang === 'ar') {
                        $missionTitle = $sections['mission']['title_ar'] ?? $missionTitle;
                        $missionContent = $sections['mission']['content_ar'] ?? $missionContent;
                    } elseif ($currentLang === 'ku') {
                        $missionTitle = $sections['mission']['title_ku'] ?? ($sections['mission']['title_ar'] ?? $missionTitle);
                        $missionContent = $sections['mission']['content_ku'] ?? ($sections['mission']['content_ar'] ?? $missionContent);
                    } else {
                        $missionTitle = $sections['mission']['title_en'] ?? ($sections['mission']['title_ar'] ?? $missionTitle);
                        $missionContent = $sections['mission']['content_en'] ?? ($sections['mission']['content_ar'] ?? $missionContent);
                    }
                }
                $missionTitle = trim((string)$missionTitle) !== '' ? $missionTitle : t('our_mission');
                $missionContent = trim((string)$missionContent) !== '' ? $missionContent : t('mission_text');

                $visionTitle = t('our_vision');
                $visionContent = t('vision_text');
                if (isset($sections['vision']) && !empty($sections['vision']['content_ar'])) {
                    if ($currentLang === 'ar') {
                        $visionTitle = $sections['vision']['title_ar'] ?? $visionTitle;
                        $visionContent = $sections['vision']['content_ar'] ?? $visionContent;
                    } elseif ($currentLang === 'ku') {
                        $visionTitle = $sections['vision']['title_ku'] ?? ($sections['vision']['title_ar'] ?? $visionTitle);
                        $visionContent = $sections['vision']['content_ku'] ?? ($sections['vision']['content_ar'] ?? $visionContent);
                    } else {
                        $visionTitle = $sections['vision']['title_en'] ?? ($sections['vision']['title_ar'] ?? $visionTitle);
                        $visionContent = $sections['vision']['content_en'] ?? ($sections['vision']['content_ar'] ?? $visionContent);
                    }
                }
                $visionTitle = trim((string)$visionTitle) !== '' ? $visionTitle : t('our_vision');
                $visionContent = trim((string)$visionContent) !== '' ? $visionContent : t('vision_text');
            ?>

            <div class="mission-vision-grid">
                <article class="mv-card mission">
                    <div class="mv-card-header">
                        <span class="mv-card-icon">
                            <i class="bi bi-bullseye"></i>
                        </span>
                        <div>
                            <span class="mv-card-kicker"><?php echo htmlspecialchars($missionVisionLabel); ?></span>
                            <h3 class="mv-card-title"><?php echo htmlspecialchars($missionTitle); ?></h3>
                        </div>
                    </div>
                    <div class="mv-card-body">
                        <p><?php echo nl2br(htmlspecialchars($missionContent)); ?></p>
                    </div>
                </article>

                <article class="mv-card vision">
                    <div class="mv-card-header">
                        <span class="mv-card-icon">
                            <i class="bi bi-stars"></i>
                        </span>
                        <div>
                            <span class="mv-card-kicker"><?php echo htmlspecialchars($missionVisionLabel); ?></span>
                            <h3 class="mv-card-title"><?php echo htmlspecialchars($visionTitle); ?></h3>
                        </div>
                    </div>
                    <div class="mv-card-body">
                        <p><?php echo nl2br(htmlspecialchars($visionContent)); ?></p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="section">
        <div class="container">
            <div class="section-title">
                <h2><?php _t('cta_title'); ?></h2>
                <p><?php _t('cta_subtitle'); ?></p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 2rem;"><?php _t('shop_now'); ?></a>
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
        
        // Timeline Animation on Scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                }
            });
        }, observerOptions);
        
        document.querySelectorAll('.timeline-item').forEach(item => {
            observer.observe(item);
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

-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Oct 11, 2026 at 12:19 AM
-- Server version: 11.8.9-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u100517965_goalchii0751`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`u100517965_goalchii0751`@`127.0.0.1` PROCEDURE `add_product_with_sizes` (IN `p_code` VARCHAR(50), IN `p_name` VARCHAR(200), IN `p_name_ar` VARCHAR(200), IN `p_description` TEXT, IN `p_description_ar` TEXT, IN `p_category_id` INT, IN `p_price` DECIMAL(10,2), IN `p_cost` DECIMAL(10,2), IN `p_image_url` VARCHAR(500), IN `p_sizes` JSON)   BEGIN
    DECLARE new_product_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE size_count INT;
    
    START TRANSACTION;
    
    -- Insert product
    INSERT INTO products (product_code, name, name_ar, description, description_ar, category_id, price, cost, image_url)
    VALUES (p_code, p_name, p_name_ar, p_description, p_description_ar, p_category_id, p_price, p_cost, p_image_url);
    
    SET new_product_id = LAST_INSERT_ID();
    
    -- Insert sizes
    SET size_count = JSON_LENGTH(p_sizes);
    WHILE i < size_count DO
        INSERT INTO product_sizes (product_id, size, quantity)
        VALUES (
            new_product_id,
            JSON_UNQUOTE(JSON_EXTRACT(p_sizes, CONCAT('$[', i, '].size'))),
            JSON_EXTRACT(p_sizes, CONCAT('$[', i, '].quantity'))
        );
        SET i = i + 1;
    END WHILE;
    
    COMMIT;
    SELECT new_product_id as product_id;
END$$

CREATE DEFINER=`u100517965_goalchii0751`@`127.0.0.1` PROCEDURE `update_inventory` (IN `p_product_id` INT, IN `p_size` VARCHAR(10), IN `p_quantity_change` INT, IN `p_reason` VARCHAR(200), IN `p_user_id` INT)   BEGIN
    DECLARE old_qty INT;
    DECLARE new_qty INT;
    
    START TRANSACTION;
    
    -- Get current quantity
    SELECT quantity INTO old_qty 
    FROM product_sizes 
    WHERE product_id = p_product_id AND size = p_size
    FOR UPDATE;
    
    SET new_qty = old_qty + p_quantity_change;
    
    -- Update quantity
    UPDATE product_sizes 
    SET quantity = new_qty 
    WHERE product_id = p_product_id AND size = p_size;
    
    -- Log the change
    INSERT INTO inventory_logs (product_id, size, quantity_change, old_quantity, new_quantity, reason, changed_by)
    VALUES (p_product_id, p_size, p_quantity_change, old_qty, new_qty, p_reason, p_user_id);
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `about_content`
--

CREATE TABLE `about_content` (
  `id` int(11) NOT NULL,
  `section_key` varchar(50) NOT NULL,
  `title_ar` varchar(200) DEFAULT NULL,
  `title_en` varchar(200) DEFAULT NULL,
  `title_ku` varchar(200) DEFAULT NULL,
  `content_ar` text DEFAULT NULL,
  `content_en` text DEFAULT NULL,
  `content_ku` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `about_content`
--

INSERT INTO `about_content` (`id`, `section_key`, `title_ar`, `title_en`, `title_ku`, `content_ar`, `content_en`, `content_ku`, `image_url`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'mission', 'مهمتنا', 'Our Mission', 'ئەرکەکەمان', 'نسعى لتوفير أفضل الملابس الرياضية عالية الجودة التي تلهم الرياضيين لتحقيق أهدافهم وتجاوز حدودهم، مع الحفاظ على الأناقة والراحة في كل قطعة نصممها.', 'We strive to provide the best high-quality sportswear that inspires athletes to achieve their goals and push their limits, while maintaining elegance and comfort in every piece we design.', 'هەوڵ دەدەین باشترین جلی وەرزشی کوالیتی بەرز دابین بکەین کە وەرزشوانان هاندەدات بۆ گەیشتن بە ئامانجەکانیان و تێپەڕاندنی سنوورەکانیان، لەگەڵ پاراستنی جوانی و ئاسوودەیی لە هەموو پارچەیەکدا کە دیزاینی دەکەین.', NULL, 1, 1, '2025-11-05 11:41:34', '2025-11-07 20:55:41'),
(2, 'vision', 'رؤيتنا', 'Our Vision', 'بینینەکەمان', 'أن نصبح العلامة التجارية الرائدة في الملابس الرياضية في المنطقة العربية، معروفين بالابتكار والجودة والأناقة، ونكون الخيار الأول لكل رياضي يسعى للتميز.', 'To become the leading sports apparel brand in the Arab region, known for innovation, quality, and elegance, and to be the first choice for every athlete seeking excellence.', 'ببینە براندی پێشەنگ لە جلی وەرزشی لە هەرێمی عەرەبی، ناسراو بە داهێنان، کوالیتی، و جوانی، و ببینە یەکەم هەڵبژاردن بۆ هەموو وەرزشوانێک کە بەدوای تایبەتمەندیدا دەگەڕێت.', NULL, 2, 1, '2025-11-05 11:41:34', '2025-11-07 20:55:41'),
(3, 'values', 'قيمنا', 'Our Values', 'بەهاکانمان', 'الجودة • الابتكار • الاستدامة • الشغف • التميز', 'Quality • Innovation • Sustainability • Passion • Excellence', 'کوالیتی • داهێنان • بەردەوامی • خولیا • تایبەتمەندی', NULL, 3, 1, '2025-11-05 11:41:34', '2025-11-07 20:55:41');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_categories`
--

CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL,
  `name_ar` varchar(100) NOT NULL,
  `name_en` varchar(100) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `title_ar` varchar(255) NOT NULL,
  `title_en` varchar(255) DEFAULT NULL,
  `title_ku` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `content_ar` text NOT NULL,
  `content_en` text DEFAULT NULL,
  `content_ku` text DEFAULT NULL,
  `excerpt_ar` text DEFAULT NULL,
  `excerpt_en` text DEFAULT NULL,
  `excerpt_ku` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `published_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_post_categories`
--

CREATE TABLE `blog_post_categories` (
  `post_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_ar` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `name_ar`, `description`, `parent_id`, `image_url`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 'real madrid', 'قمصان', '', NULL, NULL, 2, 1, '2025-11-05 11:41:33', '2025-11-06 21:37:41'),
(4, 'تراكسود', 'جاكيتات', '', NULL, NULL, 4, 1, '2025-11-05 11:41:33', '2025-11-05 11:58:39'),
(5, 'barcelona', 'إكسسوارات', '', NULL, NULL, 5, 1, '2025-11-05 11:41:33', '2025-11-06 21:37:32');

-- --------------------------------------------------------

--
-- Table structure for table `clubs`
--

CREATE TABLE `clubs` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `name_ar` varchar(200) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `clubs`
--

INSERT INTO `clubs` (`id`, `name`, `name_ar`, `category_id`, `logo_url`, `website_url`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 'barcelona', 'برشلونة', 5, 'https://upload.wikimedia.org/wikipedia/en/thumb/4/47/FC_Barcelona_%28crest%29.svg/345px-FC_Barcelona_%28crest%29.svg.png', NULL, 0, 1, '2025-11-30 23:02:33', '2025-11-30 23:02:33'),
(4, 'real madrid', 'برشلونة', 2, 'https://upload.wikimedia.org/wikipedia/en/thumb/5/56/Real_Madrid_CF.svg/255px-Real_Madrid_CF.svg.png', NULL, 0, 1, '2025-11-30 23:03:33', '2025-11-30 23:03:33');

-- --------------------------------------------------------

--
-- Table structure for table `club_categories`
--

CREATE TABLE `club_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `name_ar` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `club_categories`
--

INSERT INTO `club_categories` (`id`, `name`, `name_ar`, `description`, `display_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Football', 'كرة القدم', NULL, 1, 1, '2025-11-06 21:40:40', '2025-11-06 21:40:40'),
(2, 'Basketball', 'كرة السلة', NULL, 2, 1, '2025-11-06 21:40:40', '2025-11-06 21:40:40'),
(3, 'Tennis', 'تنس', NULL, 3, 1, '2025-11-06 21:40:40', '2025-11-06 21:40:40'),
(4, 'Volleyball', 'كرة الطائرة', NULL, 4, 1, '2025-11-06 21:40:40', '2025-11-06 21:40:40'),
(5, 'Other', 'أخرى', NULL, 5, 1, '2025-11-06 21:40:40', '2025-11-06 21:40:40');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `replied` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`, `ip_address`, `is_read`, `replied`, `created_at`) VALUES
(7, 'Deepa Gupta', 'deepa.dgtlsolution@gmail.com', '7042514198', 'Get Your Website to Google 1st Page.', 'Hi http://goalchii.shop\r\n\r\nJust a quick reminder — our 20% OFF Black Friday SEO Discount is closing shortly.\r\n\r\nIf you want better keyword rankings, more website visits, and quality leads, I can help.\r\n\r\nSend me your target keywords + locations, and I’ll create a personalized SEO roadmap for you.\r\n\r\nThank You,\r\nDeepa', NULL, 0, 0, '2025-11-27 06:57:09'),
(8, 'Mitchel Pyke', 'contact@domainregistry.pro', '9621315728', 'goalchii.shop', 'List goalchii.shop website in Google Search Index and have it displayed in Web Search Results.\r\n\r\nAdd goalchii.shop at https://searchregister.org', NULL, 0, 0, '2025-12-06 17:28:27'),
(9, 'Shad Boucher', 'info@domain-submit.info', '149306483', 'goalchii.shop', 'Add goalchii.shop website to Google Search Index and it will be displayed in Web Search Results.\r\n\r\nRegister goalchii.shop now at https://searchregister.net', NULL, 0, 0, '2025-12-16 18:44:54'),
(10, 'Andrewtooft', 'no.reply.CharlieLeroy@gmail.com', '85569839463', 'Do you seek to bring in more customers for your business?', 'Howdy-ho! goalchii.shop \r\n \r\nDid you know that it is possible to send a letter legally? \r\nWhen such requests are sent, no personal data is used and messages are securely routed to forms designed to receive them and any subsequent appeals. Messages sent via Communication Forms are least likely to be flagged as spam, since they are viewed as highly significant. \r\nTry out our service – no cost involved! \r\nOur service offers up to 50,000 messages for you. \r\n \r\nThe cost of sending one million messages is $59. \r\n \r\nThis letter is automatically generated. \r\n \r\nContact us. \r\nTelegram - https://t.me/FeedbackFormEU \r\nWhatsApp - +375259112693 \r\nWhatsApp  https://wa.me/+375259112693 \r\nWe only use chat for communication.', NULL, 0, 0, '2025-12-22 11:09:19'),
(11, 'Mike Donald Maes', 'info@strictlydigital.net', '88633896566', 'Semrush links for goalchii.shop', 'Greetings, \r\n \r\nGetting some bunch of links linking to goalchii.shop could have zero worth or worse for your website. \r\n \r\nIt really doesn’t matter how many inbound links you have, what matters is the number of search terms those websites rank for. \r\n \r\nThat is the most important element. \r\nNot the fake Moz DA or ahrefs DR score. \r\nAnyone can manipulate those. \r\nBUT the number of ranking keywords the domains that point to your site rank for. \r\nThat’s what really matters. \r\n \r\nGet these quality links link to your domain and your site will see real growth! \r\n \r\nWe are introducing this exclusive service here: \r\nhttps://www.strictlydigital.net/product/semrush-backlinks/ \r\n \r\nNeed more details, or want clarification, message us here: \r\nhttps://www.strictlydigital.net/whatsapp-us/ \r\n \r\nSincerely, \r\nMike Donald Maes\r\n \r\nstrictlydigital.net \r\nPhone/WhatsApp: +1 (877) 566-3738', NULL, 0, 0, '2026-01-03 09:51:48'),
(12, 'Mike Olof Eriksson', 'info@speed-seo.net', '85197882257', 'Find goalchii.shop SEO Issues totally free', 'Hi, \r\nWorried about hidden SEO issues on your website? Let us help — completely free. \r\nRun a 100% free SEO check and discover the exact problems holding your site back from ranking higher on Google. \r\n \r\nRun Your Free SEO Check Now \r\nhttps://www.speed-seo.net/check-site-seo-score/ \r\n \r\nOr chat with us and our agent will run the report for you: https://www.speed-seo.net/whatsapp-with-us/ \r\n \r\nBest regards, \r\n \r\n \r\nMike Olof Eriksson\r\n \r\nSpeed SEO Digital \r\nEmail: info@speed-seo.net \r\nPhone/WhatsApp: +1 (833) 454-8622', NULL, 0, 0, '2026-01-10 00:25:16'),
(13, 'Olivier Gabriel Balzac', 'duckmenoffice11@gmail.com', '86716668173', 'Please response to this email', 'Good day, \r\n \r\nMy name is Olivier Gabriel Balzac, a practicing lawyer from France. I previously contacted you regarding a transaction involving 13.5 million Euros, which was left by my late client before his unexpected demise. \r\n \r\nI am reaching out to you once more because, after examining your profile, I am thoroughly convinced that you are capable of managing this transaction effectively alongside me. \r\nIf you are interested, I would like to emphasize that after the transaction, 5% of the funds will be allocated to charitable organizations, while the remaining 95% will be divided equally between us, resulting in 47.5% for each party. \r\nThis transaction is entirely risk-free. Please respond to me at your earliest convenience to receive further details regarding the transaction. \r\nMy email: info@balzacavocate.com Sincerely, I look forward to your prompt response. \r\nBest regards. \r\nOlivier Gabriel Balzac, \r\nAttorney. \r\nPhone: +33 756 850 084 \r\nEmail: info@balzacavocate.com', NULL, 0, 0, '2026-01-23 04:37:54'),
(14, 'Mike Robert Taylor', 'info@digital-x-press.com', '89936848734', 'Add AEO to your SEO strategies today !', 'Hi, \r\nI realize that many businesses struggle understanding that Answer Engine Optimization (AEO) is a gradual process and a carefully organized regular commitment. \r\n \r\nUnfortunately, very few marketers have the willingness to observe the gradual yet significant improvements that can completely transform their online presence. \r\n \r\nWith constant algorithm changes, a stable, continuous SEO strategy including Answer Engine Optimization (AEO) is vital for achieving a profitable outcome. \r\n \r\nIf you see this as the best method, partner with us! \r\n \r\nExplore Our Monthly SEO Services https://www.digital-x-press.com/unbeatable-seo/ \r\n \r\nReach Out on Instant Messaging https://www.digital-x-press.com/whatsapp-us/ \r\n \r\nWe offer exceptional results for your budget, and you will value choosing us as your growth partner. \r\n \r\nWarm regards, \r\nDigital X SEO Experts \r\nPhone/WhatsApp: +1 (844) 754-1148', NULL, 0, 0, '2026-01-26 03:25:15'),
(15, 'Michalak Aleksandra', 'aleksandramichalakalek51@gmail.com', '83316492234', 'Loan proposal. Do you need a business loan please contact me', 'Good day. \r\nMy name is Michalak Aleksandra, a Poland based business consultant. \r\nRunning a business means juggling a million things, and getting the funding you need shouldn\'t be another hurdle. We\'ve helped businesses to secure debt financing for growth, inventory, or operations, without the typical bank delays. \r\nTogether with our partners (investors), we offer a straightforward, transparent process with clear terms, designed to get you funded quickly so you can focus on your business. \r\nReady to explore our services? Please feel free to contact me directly by michalak.aleksandra@mail.com Let\'s make your business goals a reality, together. \r\nRegards, \r\nMichalak Aleksandra. \r\nEmail: michalak.aleksandra@mail.com', NULL, 0, 0, '2026-02-04 21:10:36'),
(16, 'Mike Helmuth Bonnet', 'info@speed-seo.net', '87782627799', 'Find goalchii.shop SEO Issues totally free', 'Hi, \r\nWorried about hidden SEO issues on your website? Let us help — completely free. \r\nRun a 100% free SEO check and discover the exact problems holding your site back from ranking higher on Google. \r\n \r\nRun Your Free SEO Check Now \r\nhttps://www.speed-seo.net/check-site-seo-score/ \r\n \r\nOr chat with us and our agent will run the report for you: https://www.speed-seo.net/whatsapp-with-us/ \r\n \r\nBest regards, \r\n \r\n \r\nMike Helmuth Bonnet\r\n \r\nSpeed SEO Digital \r\nEmail: info@speed-seo.net \r\nPhone/WhatsApp: +1 (833) 454-8622', NULL, 0, 0, '2026-02-07 02:55:49'),
(17, 'Mike Jayden Taylor', 'info@professionalseocleanup.com', '85433134715', 'Fix August Google Spam update ranking problems for free', 'Hi, \r\nWhile reviewing goalchii.shop, we spotted toxic backlinks that could put your site at risk of a Google penalty. Especially that this Google SPAM update had a high impact in ranks. This is an easy and quick fix for you. Totally free of charge. No obligations. \r\n \r\nFix it now: \r\nhttps://www.professionalseocleanup.com/ \r\n \r\nNeed help or questions? Chat here: \r\nhttps://www.professionalseocleanup.com/whatsapp/ \r\n \r\nBest, \r\nMike Jayden Taylor\r\n \r\n+1 (855) 221-7591 \r\ninfo@professionalseocleanup.com', NULL, 0, 0, '2026-02-13 09:15:53'),
(18, 'Marcela Deuchar', 'sales@goalchii.shop', '4314798', 'Marcela Deuchar', 'Hello there \r\n\r\nLooking to improve your posture and live a healthier life? Our Medico Postura™ Body Posture Corrector is here to help!\r\n\r\nExperience instant posture improvement with Medico Postura™. This easy-to-use device can be worn anywhere, anytime – at home, work, or even while you sleep.\r\n\r\nMade from lightweight, breathable fabric, it ensures comfort all day long.\r\n\r\nGrab it today at a fantastic 60% OFF: https://medicopostura.com\r\n\r\nPlus, enjoy FREE shipping for today only!\r\n\r\nDon\'t miss out on this amazing deal. Get yours now and start transforming your posture!\r\n\r\nSincerely, \r\n\r\nMarcela', NULL, 0, 0, '2026-03-17 05:34:11'),
(19, 'Callie Hooker', 'sales@goalchii.shop', '556680746', 'Just reaching out', 'Hi,\r\n\r\nThought you might like this.\r\n\r\nWe’re running a short promo on our FitRx™ Wireless Muscle Massager.\r\n\r\nhttps://easerelief.net\r\n\r\nIt’s designed for easy, portable use and works great for muscle relief, recovery, and relaxation.\r\n\r\nThe discount won’t last long, so it’s worth a quick look.\r\n\r\nBest regards,\r\nTeam FitRx™', NULL, 0, 0, '2026-03-19 04:34:33'),
(20, 'Charlene Caird', 'sales@caird.tidbuy.com', '3501924418', 'Charlene Caird', 'Good day \r\n \r\nIs your dog\'s nails getting too long? If you\'re tired of going to the vet or groomer to get them trimmed, why not try PawSafer™? \r\nWith PawSafer™, you can trim your dog\'s nails from the comfort of your own home, and it only takes a few minutes!\r\n\r\nPawSafer™ is the safest and most convenient way to trim your dog\'s nails, and it\'s very affordable. \r\n\r\nGet it while it\'s still 50% OFF + FREE Shipping\r\n\r\nBuy here: https://tidbuy.com\r\n \r\nKind Regards, \r\n \r\nCharlene', NULL, 0, 0, '2026-03-22 14:17:14'),
(21, 'Ervin Lawes', 'sales@lawes.bangeshop.com', '7726561990', 'Ervin Lawes', 'Good day, \r\n\r\nI hope you\'re doing well. I wanted to let you know about our new BANGE backpacks and sling bags that just released.\r\n\r\nBange is perfect for students, professionals and travelers. The backpacks and sling bags feature a built-in USB charging port, making it easy to charge your devices on the go.  Also they are waterproof and anti-theft design, making it ideal for carrying your valuables.\r\n\r\nBoth bags are made of durable and high-quality materials, and are perfect for everyday use or travel.\r\n\r\nOrder yours now at 50% OFF with FREE Shipping: http://bangeshop.com\r\n\r\nBest,\r\n\r\nErvin', NULL, 0, 0, '2026-04-02 03:19:36'),
(22, 'Shane Prisco', 'sales@prisco.tidbuy.com', '362706811', 'Shane Prisco', 'Hey \r\n \r\nIs your dog\'s nails getting too long? If you\'re tired of going to the vet or groomer to get them trimmed, why not try PawSafer™? \r\nWith PawSafer™, you can trim your dog\'s nails from the comfort of your own home, and it only takes a few minutes!\r\n\r\nPawSafer™ is the safest and most convenient way to trim your dog\'s nails, and it\'s very affordable. \r\n\r\nGet it while it\'s still 50% OFF + FREE Shipping\r\n\r\nBuy here: https://tidbuy.com\r\n \r\nRegards, \r\n \r\nShane', NULL, 0, 0, '2026-05-09 12:14:50'),
(23, 'Wilford Garran', 'sales@goalchii.shop', '6999487114', 'Wilford Garran', 'Hello \r\n\r\nI wanted to reach out and let you know about our new dog harness. It\'s really easy to put on and take off - in just 2 seconds - and it\'s personalized for each dog. \r\nPlus, we offer a lifetime warranty so you can be sure your pet is always safe and stylish.\r\n\r\nWe\'ve had a lot of success with it so far and I think your dog would love it. \r\n\r\nGet yours today with 50% OFF: https://caredogbest.com\r\n\r\nFREE Shipping - TODAY ONLY! \r\n\r\nEnjoy, \r\n\r\nWilford', NULL, 0, 0, '2026-05-19 12:36:48'),
(24, 'Michaelviove', 'jacksrenome@gmx.com', '84793737132', 'Aaaqfjfwkdjifiefowkd foekdwdeoghiejijgero jojfw', 'YyErjcwdkdjwjjwjjdwjddjwsjf ndsaKAqwdweihduncbbwebidaa iudwnishqwuvdwqihbfvweuiojsqjqioqdefiw dwqsqwijbfiewdncbhvdifqhioqsjnqw goalchii.shop', NULL, 0, 0, '2026-05-31 06:44:20'),
(25, 'Ingeborg Keaton', 'sales@goalchii.shop', '7002303734', 'Ingeborg Keaton', 'Hey there \r\n\r\nLooking to improve your posture and live a healthier life? Our Medico Postura™ Body Posture Corrector is here to help!\r\n\r\nExperience instant posture improvement with Medico Postura™. This easy-to-use device can be worn anywhere, anytime – at home, work, or even while you sleep.\r\n\r\nMade from lightweight, breathable fabric, it ensures comfort all day long.\r\n\r\nGrab it today at a fantastic 60% OFF: https://medicopostura.com\r\n\r\nPlus, enjoy FREE shipping for today only!\r\n\r\nDon\'t miss out on this amazing deal. Get yours now and start transforming your posture!\r\n\r\nKind Regards, \r\n\r\nIngeborg', NULL, 0, 0, '2026-06-04 18:33:45'),
(26, 'DanielCaw', 'bottke31@gmail.com', '82399442354', 'IMPORTANT MESSAGE! 1.3426 BTC is pending withdraw to confirm', 'IMPORTANT MESSAGE! TAKE THE RETURN YOUR 1.3426 BTC PROJECT https://s.kode4u.tech/STsPH', NULL, 0, 0, '2026-06-11 03:53:27'),
(27, 'DanielCaw', 'snunez123@gmail.com', '87533961195', 'THE $27,000,000 JACKPOT IS CLOSER THAN YOU THINK—TAKE THE LEAP', 'A Simple Step Could Lead to the $27,000,000 Jackpot http://dbolc.com/uj7GskpM', NULL, 0, 0, '2026-06-11 13:41:25'),
(28, 'Davidduepe', 'no.reply.CharlieJohnson@gmail.com', '85573521153', 'Giving feedback through the feedback form.', 'Hi! goalchii.shop, \r\nI found goalchii.shop while checking different websites. \r\nWe offer a solution that helps businesses connect with websites online. \r\nOur platform allows businesses to manage outreach . \r\n  \r\nYou can quickly test the platform with a free trial. \r\nWe offer a free test so you can see how it works. \r\n \r\nThanks for your attention. \r\nContact us. \r\nTelegram - https://t.me/FeedbackFormEU \r\nWhatsApp - +375259112693 \r\nWhatsApp  https://wa.me/+375259112693', NULL, 0, 0, '2026-06-14 04:34:38'),
(29, 'DanielCaw', 'cyates5266@gmail.com', '86354834156', 'THE $27,000,000 JACKPOT IS A TOKEN OF GOOD THINGS AHEAD', 'THE $27,000,000 JACKPOT IS A CIRCLE WITH CASHFLOW https://x11.pw/YNEjM', NULL, 0, 0, '2026-06-14 15:06:38'),
(30, 'DanielCaw', 'meaganlynn2019@gmail.com', '83167323932', 'YOUR GUT SAYS GO FOR THE $27,000,000 JACKPOT', 'TAKE THE LEAP AND CAPTURE THE $27,000,000 JACKPOT https://come.ac/f7PPN', NULL, 0, 0, '2026-06-17 05:54:49'),
(31, 'DanielCaw', 'jarhead1978@gmail.com', '89824517437', 'The $27,000,000 Jackpot Is the Ultimate Confidence Boost', 'The $27,000,000 Jackpot Is Hot—Strike Before the Momentum Fades http://dbolc.com/IknLR5ap', NULL, 0, 0, '2026-06-19 09:34:07'),
(32, 'DanielCaw', 'andcris79@hotmail.com', '89294318426', 'MILLIONS HAVE TRIED—WILL YOU WIN THE $27,000,000 JACKPOT', 'The $27,000,000 Jackpot Is a Dream with Deposits https://jbiv.com/fMhMB', NULL, 0, 0, '2026-06-21 18:14:53'),
(33, 'DanielCaw', 'mahimahi6969@hotmail.com', '87254351269', 'THE $27,000,000 JACKPOT IS WITHIN REACH—STRETCH FOR IT', 'The $27,000,000 Jackpot Is Ready to Elevate Your Day https://s.ubyt.es/q3xv9n', NULL, 0, 0, '2026-06-24 15:59:20'),
(34, 'DanielCaw', 'imme6916@mail.com', '84779845234', 'URGENT! Claim Your 1.3426 BTC Without Delay', 'IMPORTANT! YOU\'VE QUALIFIED FOR 1.3426 BTC WITHDRAW https://x11.pw/kpknt', NULL, 0, 0, '2026-06-28 14:05:45'),
(35, 'DanielCaw', 'mojama344@gmail.com', '88989958955', 'URGENT! DON\'T BE PASSIVE YOUR 1.3426 BTC OUTCOME', 'IMPORTANT! DON\'T SHILLY-SHALLY YOUR 1.3426 BTC YIELD IS SET https://short.vird.co/DreNE', NULL, 0, 0, '2026-06-29 05:13:04'),
(36, 'DanielCaw', 'joannelouisecross@outlook.com', '84662718965', 'THE $27,000,000 JACKPOT IS A HIDDEN GEM ONLINE', 'THE $27,000,000 JACKPOT IS A PAT ON THE BACK FROM LUCK https://ufasofilmebi.one/MHTBU', NULL, 0, 0, '2026-07-02 03:29:07'),
(37, 'DanielCaw', 'enquiries@trinityplains.com.au', '81255346689', 'A Rare Opportunity to Secure the $27,000,000 Jackpot', 'The $27,000,000 Jackpot Is a Dash to Delight https://lmy.de/rfyKj', NULL, 0, 0, '2026-07-02 07:48:37'),
(38, 'DanielCaw', 'kirsten_2012@yahoo.com', '83892151364', 'YOU’VE GOT NOTHING TO LOSE AND $27,000,000 JACKPOT TO GAIN', 'DON’T SECOND-GUESS YOURSELF—GO FOR THE $27,000,000 JACKPOT https://gnosis.link/FTtjo', NULL, 0, 0, '2026-07-07 03:22:49'),
(39, 'Henry Baker', 'henry.baker19889@gmail.com', '1565866810', 'Question about your Google visibility', 'Hi,\r\n\r\nI was looking over your website and noticed a few specific areas where optimization could boost your Google rankings and attract higher-quality leads.\r\n\r\nI have put together a brief SEO strategy and pricing proposal designed to help scale your organic growth.\r\n\r\nWould you be open to reviewing it?\r\n\r\nBest regards,\r\n\r\nHenry', NULL, 0, 0, '2026-07-10 09:30:26'),
(40, 'DanielCaw', 'shebop75@outlook.com', '86789611443', 'THE $27,000,000 JACKPOT IS A PLOT FOR PROSPERITY', 'THE $27,000,000 JACKPOT IS YOUR INVITATION TO THE BIG LEAGUES https://telegra.ph/CLAIM-YOUR-25000-BONUS-AND-CHASE-THE-27000000-JACKPOT--Message-ID-285957-06-29', NULL, 0, 0, '2026-07-11 05:44:10'),
(41, 'DanielCaw', 'fontanillajunior@yahoo.com', '88695798152', 'THE $27,000,000 JACKPOT IS A SCHEME FOR SMILES', 'The $27,000,000 Jackpot Is a Crate of Confidence https://qrlinkgenerator.com/xkGuM', NULL, 0, 0, '2026-07-11 10:49:04'),
(42, 'DanielCaw', 'arras1@onet.eu', '89387912966', 'TURN A SMALL BET INTO THE $27,000,000 JACKPOT', 'THE $27,000,000 JACKPOT IS A MAP TO MONEY https://plu.sh/ykaqb', NULL, 0, 0, '2026-07-13 13:00:55'),
(43, 'DanielCaw', 'benny.pecoraio@gmx.de', '89511222364', 'THE $27,000,000 JACKPOT IS A STAGE FOR SMILES', 'The $27,000,000 Jackpot Is a Trophy for Tenacity https://qrlinkgenerator.com/dwfje', NULL, 0, 0, '2026-07-14 15:35:27'),
(44, 'HarryMut', 'sharbankumar67@gmail.com', '88489227198', 'THE $27,000,000 JACKPOT IS A CONTRACT WITH CASHFLOW', 'The $27,000,000 Jackpot Is Your Invitation to a Big Payout https://url.in.th/hqMdW', NULL, 0, 0, '2026-07-16 05:00:51'),
(45, 'HarryMut', 'swipe22316@gmail.com', '84217463591', 'THE $27,000,000 JACKPOT IS A BADGE FOR BOLD BETS', 'TURN A ROUTINE DAY INTO A VICTORY WITH THE $27,000,000 JACKPOT https://makeachang-e.com/WbJPTfU', NULL, 0, 0, '2026-07-20 05:12:45'),
(46, 'HarryMut', 'joshfeery2011@icloud.com', '88385819513', 'Why Ordinary Wins Don’t Compare to This $27,000,000 Jackpot', 'Why the $27,000,000 Jackpot Is the Highlight of Your Day https://myip.kr/dxEeL', NULL, 0, 0, '2026-07-22 03:56:19'),
(47, 'HarryMut', 'killove1971@gmail.com', '87736727495', 'ACCOUNTING SOFTWARE TRACKING $1,500 PER DAY OR MORE HISTORY', 'GET $1,500 PER DAY OR MORE BY PARTICIPATING IN TESTNETS https://telegra.ph/Collect-cryptocurrency-automatically-every-day-over-1500-Message-ID-62273-07-23', NULL, 0, 0, '2026-07-23 11:53:24'),
(48, 'Ewan Hallock', 'sales@hallock.bangeshop.com', '622926655', 'Ewan Hallock', 'Hello there, \r\n\r\nI hope you\'re doing well. I wanted to let you know about our new BANGE backpacks and sling bags that just released.\r\n\r\nThe bags are waterproof and anti-theft, and have a built-in USB cable that can recharge your phone while you\'re on the go.\r\n\r\nBoth bags are made of durable and high-quality materials, and are perfect for everyday use or travel.\r\n\r\nOrder yours now at 50% OFF with FREE Shipping: http://bangeshop.com\r\n\r\nBest,\r\n\r\nEwan', NULL, 0, 0, '2026-07-27 23:02:13'),
(49, 'Tammi Summerville', 'tammi.summerville@outlook.com', '467704480', 'Top Quality Proxies Servers - by Dreamproxies', 'Welcome from DreamProxies\r\n\r\nHot proxies offer: top quality, ultra speed and unlimited private proxies now even better price!\r\n50% Deal - for all private proxies:\r\n\r\nhttps://tiny.cc/dreamproxies-deal\r\n\r\nFully Private, Top Quality, Blazing Speed, Unlimited Bandwidth, Strong Servers, Best Prices, Bonus Discounts and many more\r\n\r\n50% Sales for all proxies packages - at Dreamproxies.com', NULL, 0, 0, '2026-07-28 02:02:47'),
(50, 'HarryMut', 'miss_marli@hotmail.com', '82844783494', 'STACK OVERFLOW CRYPTO ANSWERS EARNING $1,500 PER DAY OR MORE', 'Start earning $1,500 per day or more from crypto marathons https://telegra.ph/Collect-cryptocurrency-automatically-every-day-over-1500-Message-ID-192745-07-23', NULL, 0, 0, '2026-07-28 05:58:07'),
(51, 'HarryMut', 'cerbajbr@johnson.biz', '87823858449', 'THE ULTIMATE DEAL A $25,000 PROMO CODE', 'A $25,000 promo code to take the lead https://myip.kr/jjKqk', NULL, 0, 0, '2026-08-04 04:32:16'),
(52, 'Marcela Ruyle', 'sales@goalchii.shop', '93135213', 'Marcela Ruyle', 'Good day \r\n\r\nLooking to improve your posture and live a healthier life? Our Medico Postura™ Body Posture Corrector is here to help!\r\n\r\nExperience instant posture improvement with Medico Postura™. This easy-to-use device can be worn anywhere, anytime – at home, work, or even while you sleep.\r\n\r\nMade from lightweight, breathable fabric, it ensures comfort all day long.\r\n\r\nGrab it today at a fantastic 60% OFF: https://medicopostura.com\r\n\r\nPlus, enjoy FREE shipping for today only!\r\n\r\nDon\'t miss out on this amazing deal. Get yours now and start transforming your posture!\r\n\r\nCheers, \r\n\r\nMarcela', NULL, 0, 0, '2026-08-11 01:59:07'),
(53, 'Margaret Wiedermann', 'sales@goalchii.shop', '25415026', 'Margaret Wiedermann', 'Hey there \r\n\r\nI wanted to reach out and let you know about our new dog harness. It\'s really easy to put on and take off - in just 2 seconds - and it\'s personalized for each dog. \r\nPlus, we offer a lifetime warranty so you can be sure your pet is always safe and stylish.\r\n\r\nWe\'ve had a lot of success with it so far and I think your dog would love it. \r\n\r\nGet yours today with 50% OFF: https://caredogbest.com\r\n\r\nFREE Shipping - TODAY ONLY! \r\n\r\nRegards, \r\n\r\nMargaret', NULL, 0, 0, '2026-09-07 19:40:19'),
(54, 'Mel Capuano', 'sales@capuano.bangeshop.com', '4373914', 'Mel Capuano', 'Hi, \r\n\r\nI hope you\'re doing well. I wanted to let you know about our new BANGE backpacks and sling bags that just released.\r\n\r\nThe bags are waterproof and anti-theft, and have a built-in USB cable that can recharge your phone while you\'re on the go.\r\n\r\nBoth bags are made of durable and high-quality materials, and are perfect for everyday use or travel.\r\n\r\nOrder yours now at 50% OFF with FREE Shipping: http://bangeshop.com\r\n\r\nThank You,\r\n\r\nMel', NULL, 0, 0, '2026-09-11 01:00:46'),
(55, 'Jefferey Angulo', 'sales@goalchii.shop', '634632115', 'Jefferey Angulo', 'Hi \r\n\r\nLooking to improve your posture and live a healthier life? Our Medico Postura™ Body Posture Corrector is here to help!\r\n\r\nExperience instant posture improvement with Medico Postura™. This easy-to-use device can be worn anywhere, anytime – at home, work, or even while you sleep.\r\n\r\nMade from lightweight, breathable fabric, it ensures comfort all day long.\r\n\r\nGrab it today at a fantastic 60% OFF: https://medicopostura.com\r\n\r\nPlus, enjoy FREE shipping for today only!\r\n\r\nDon\'t miss out on this amazing deal. Get yours now and start transforming your posture!\r\n\r\nMany Thanks, \r\n\r\nJefferey', NULL, 0, 0, '2026-09-16 20:26:51'),
(56, 'Gabrielle Marie', 'gabrielle.m@noreply.com', '479580166', 'Ready to grow your brand in the USA?', 'USBrandLaunch helps international brands enter the US market faster and smarter with market intelligence, buyer opportunities and contacts, competitive research, and a step-by-step launch strategy.\r\n\r\nStop guessing where to sell, who to target, and how to enter the market.\r\n\r\nFind your US opportunity → https://USBrandLaunch.com', NULL, 0, 0, '2026-09-17 20:08:23'),
(57, 'Stephaine Halse', 'sales@halse.caredogbest.com', '4434306801', 'Stephaine Halse', 'Hey there \r\n\r\nI wanted to reach out and let you know about our new dog harness. It\'s really easy to put on and take off - in just 2 seconds - and it\'s personalized for each dog. \r\nPlus, we offer a lifetime warranty so you can be sure your pet is always safe and stylish.\r\n\r\nWe\'ve had a lot of success with it so far and I think your dog would love it. \r\n\r\nGet yours today with 50% OFF: https://caredogbest.com\r\n\r\nFREE Shipping - TODAY ONLY! \r\n\r\nRegards, \r\n\r\nStephaine', NULL, 0, 0, '2026-09-26 09:41:15'),
(58, 'Will Gabb', 'sales@goalchii.shop', '3631525264', 'Will Gabb', 'Good day, \r\n\r\nI hope you\'re doing well. I wanted to let you know about our new BANGE backpacks and sling bags that just released.\r\n\r\nBange is perfect for students, professionals and travelers. The backpacks and sling bags feature a built-in USB charging port, making it easy to charge your devices on the go.  Also they are waterproof and anti-theft design, making it ideal for carrying your valuables.\r\n\r\nBoth bags are made of durable and high-quality materials, and are perfect for everyday use or travel.\r\n\r\nOrder yours now at 50% OFF with FREE Shipping: http://bangeshop.com\r\n\r\nCheers,\r\n\r\nWill', NULL, 0, 0, '2026-09-28 13:55:48'),
(59, 'Davidduepe', 'no.reply.SimonSchulz@gmail.com', '84655627991', 'جرّب خدمتنا مجانًا', 'مرحباً! \r\nوجدت موقعك الإلكتروني وأردت مشاركة شيء معك. \r\nمنصتنا تدعم التواصل عبر نماذج الاتصال في المواقع الإلكترونية. \r\nاختبر ما يصل إلى 50,000 عملية إرسال للرسائل خلال الفترة التجريبية المجانية. \r\nسعر مليون رسالة هو 59 دولارًا. \r\nإذا كان هذا يبدو ذا صلة، فاتصل بنا عبر الدردشة. \r\n \r\nنقدر وقتك. \r\nTelegram - https://t.me/FeedbackFormEU \r\nWhatsApp - https://wa.me/375259112693 \r\nنستخدم الدردشة كقناة التواصل الوحيدة لدينا.', NULL, 0, 0, '2026-10-01 09:02:04'),
(60, 'Cindy Quesinberry', 'sales@goalchii.shop', '2161878611', 'Cindy Quesinberry', 'Hi \r\n\r\nLooking to improve your posture and live a healthier life? Our Medico Postura™ Body Posture Corrector is here to help!\r\n\r\nExperience instant posture improvement with Medico Postura™. This easy-to-use device can be worn anywhere, anytime – at home, work, or even while you sleep.\r\n\r\nMade from lightweight, breathable fabric, it ensures comfort all day long.\r\n\r\nGrab it today at a fantastic 60% OFF: https://medicopostura.com\r\n\r\nPlus, enjoy FREE shipping for today only!\r\n\r\nDon\'t miss out on this amazing deal. Get yours now and start transforming your posture!\r\n\r\nCheers, \r\n\r\nCindy', NULL, 0, 0, '2026-10-03 02:53:43');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `old_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reason` varchar(200) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`id`, `product_id`, `size`, `quantity_change`, `old_quantity`, `new_quantity`, `reason`, `changed_by`, `created_at`) VALUES
(1, 2, 'XS', 4, 0, 4, 'Initial stock', 1, '2025-11-05 11:53:12'),
(2, 2, 'S', 3, 0, 3, 'Initial stock', 1, '2025-11-05 11:53:12'),
(3, 2, 'M', 3, 0, 3, 'Initial stock', 1, '2025-11-05 11:53:12'),
(4, 2, 'L', 3, 0, 3, 'Initial stock', 1, '2025-11-05 11:53:12'),
(5, 2, 'XL', 3, 0, 3, 'Initial stock', 1, '2025-11-05 11:53:12'),
(6, 2, 'XXL', 4, 0, 4, 'Initial stock', 1, '2025-11-05 11:53:12'),
(7, 2, 'XXXL', 3, 0, 3, 'Initial stock', 1, '2025-11-05 11:53:12'),
(8, 2, 'S', -3, 3, 0, 'Stock adjustment', 1, '2025-11-05 11:55:26'),
(9, 2, 'M', -3, 3, 0, 'Stock adjustment', 1, '2025-11-05 11:55:26'),
(10, 2, 'L', -3, 3, 0, 'Stock adjustment', 1, '2025-11-05 11:55:35'),
(11, 2, 'XL', -3, 3, 0, 'Stock adjustment', 1, '2025-11-05 11:55:35'),
(12, 3, 'S', 1, 0, 1, 'Initial stock', 1, '2025-11-05 11:57:47'),
(13, 3, 'L', 1, 0, 1, 'Initial stock', 1, '2025-11-05 11:57:47'),
(14, 3, 'XS', 1, 0, 1, 'Stock adjustment', 1, '2025-11-05 12:03:21'),
(15, 3, 'S', 3, 1, 4, 'Stock adjustment', 1, '2025-11-05 12:03:21'),
(16, 3, 'M', 4, 0, 4, 'Stock adjustment', 1, '2025-11-05 12:03:21'),
(17, 3, 'XL', 7, 0, 7, 'Stock adjustment', 1, '2025-11-05 12:03:21'),
(18, 3, 'XXL', 4, 0, 4, 'Stock adjustment', 1, '2025-11-05 12:03:21');

-- --------------------------------------------------------

--
-- Stand-in structure for view `low_stock_alert`
-- (See below for the actual view)
--
CREATE TABLE `low_stock_alert` (
`id` int(11)
,`product_code` varchar(50)
,`name` varchar(200)
,`name_ar` varchar(200)
,`size` varchar(10)
,`quantity` int(11)
,`category_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_financial_summary`
-- (See below for the actual view)
--
CREATE TABLE `monthly_financial_summary` (
`month` varchar(7)
,`year` int(5)
,`month_number` int(3)
,`total_income` decimal(32,2)
,`total_expense` decimal(32,2)
,`net_profit` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `unsubscribed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_requests`
--

CREATE TABLE `order_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_phone` varchar(30) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Iraq',
  `shipping_address` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'cash_on_delivery',
  `customer_notes` text DEFAULT NULL,
  `items_json` longtext DEFAULT NULL,
  `status` enum('new','processing','completed','cancelled') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_requests`
--

INSERT INTO `order_requests` (`id`, `order_number`, `customer_name`, `customer_email`, `customer_phone`, `city`, `postal_code`, `country`, `shipping_address`, `subtotal`, `shipping_cost`, `total_amount`, `payment_method`, `customer_notes`, `items_json`, `status`, `created_at`, `updated_at`) VALUES
(9, 'REQ-20251130-F8A6AA', 'Zuhir', 'zozobabaloos@gmail.com', '07511014226', 'Hdhd', 'Gxhd', 'Iraq', 'Gdhdh', 33000.00, 0.00, 33000.00, 'cash_on_delivery', 'U', '[{\"product_id\":9,\"product_name\":\"تراكسود ريال مدريد\",\"product_code\":\"545546565587444\",\"size\":\"XXL\",\"quantity\":1,\"price\":\"33000.00\",\"total\":33000,\"image\":\"assets\\/images\\/products\\/product_9_1764543674_692cccba32e95.png\"}]', 'new', '2025-11-30 23:13:51', '2025-11-30 23:13:51'),
(10, 'REQ-20260619-BCA6B8', 'زيد', 'gfdgs@gamil.com', '07511452687', 'اربيل', 'زانياري', 'Iraq', 'كوارتز', 30000.00, 0.00, 30000.00, 'cash_on_delivery', 'لا يوجد', '[{\"product_id\":8,\"product_name\":\"تراكسود برشلونة\",\"product_code\":\"545546565587\",\"size\":\"S\",\"quantity\":1,\"price\":\"30000.00\",\"total\":30000,\"image\":\"assets\\/images\\/products\\/product_8_1764543598_692ccc6ee1452.png\"}]', 'new', '2026-06-19 16:40:27', '2026-06-19 16:40:27');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `name_ar` varchar(200) DEFAULT NULL,
  `name_ku` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `description_ar` text DEFAULT NULL,
  `description_ku` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_ecommerce` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_code`, `name`, `name_ar`, `name_ku`, `description`, `description_ar`, `description_ku`, `category_id`, `price`, `old_price`, `cost`, `image_url`, `featured`, `views`, `meta_title`, `meta_description`, `meta_keywords`, `is_active`, `is_ecommerce`, `created_at`, `updated_at`) VALUES
(2, '54554656', 'jn gjknrejkgnnjgn', NULL, NULL, 'rjkgnjkrngj4ngjo4ng', NULL, NULL, 5, 124.00, NULL, 19.00, 'https://tse2.mm.bing.net/th/id/OIP.DxjCtrMWuF1OYOpW9W9QcwHaHa?rs=1&amp;amp;amp;pid=ImgDetMain&amp;amp;amp;o=7&amp;amp;amp;rm=3', 0, 0, NULL, NULL, NULL, 0, 0, '2025-11-05 11:53:12', '2025-11-05 11:55:39'),
(3, '423234234', 'لقثتل', NULL, NULL, 'fytrh', NULL, NULL, 4, 34.00, NULL, 19.00, 'https://tse2.mm.bing.net/th/id/OIP.DxjCtrMWuF1OYOpW9W9QcwHaHa?rs=1&amp;amp;amp;pid=ImgDetMain&amp;amp;amp;o=7&amp;amp;amp;rm=3', 0, 0, NULL, NULL, NULL, 0, 0, '2025-11-05 11:57:47', '2025-11-10 23:45:03'),
(4, '545546563', 'barca', 'nara', 'بريزو', 'ew', 'ewd', 't', 2, 15000.00, NULL, 0.00, '', 1, 46, 'e', 'e', NULL, 0, 1, '2025-11-06 13:03:09', '2025-11-10 23:45:41'),
(5, '545546564', 'barca', 'سس', NULL, 'س', 'يس', NULL, 5, 15000.00, NULL, 0.00, '', 0, 46, 'e', 'س', 'س', 0, 1, '2025-11-06 13:30:21', '2025-11-10 23:45:45'),
(6, '5455465655', 'Barcelona Kit (HOME) + Badge (Sticker)', 'طقم برشلونة (الاساسي) + باج (لاصق)', 'درێسی بەرشەلۆنە (ماڵەوە) +باجی (ستیکەر)', '', NULL, NULL, 5, 18000.00, 25000.00, 0.00, '', 0, 6, NULL, NULL, NULL, 1, 1, '2025-11-30 22:46:52', '2026-09-29 15:07:11'),
(7, '54554656558', 'Real Madrid Kit (HOME) + Badge (Sticker)', 'طقم ريال مدريد (الاساسي) + باج (لاصق)', 'درێسی ريال مدريد(ماڵەوە) +باجی (ستیکەر)', '', NULL, NULL, 2, 18000.00, 25000.00, 0.00, '', 0, 5, NULL, NULL, NULL, 1, 1, '2025-11-30 22:58:17', '2026-10-08 12:28:07'),
(8, '545546565587', 'Barcelona Training Tracksuit', 'تراكسود برشلونة', 'جلوبەرگی مەشقی برشلونة', '', NULL, NULL, 5, 30000.00, 40000.00, 0.00, '', 0, 4, NULL, NULL, NULL, 1, 1, '2025-11-30 22:59:58', '2026-06-19 16:39:50'),
(9, '545546565587444', 'Real Madrid Training Tracksuit', 'تراكسود ريال مدريد', 'جلوبەرگی مەشقی ڕیال مەدرید', '', NULL, NULL, 2, 30000.00, 40000.00, 0.00, '', 0, 6, NULL, NULL, NULL, 1, 1, '2025-11-30 23:01:14', '2026-10-04 04:56:52');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_url`, `display_order`, `is_primary`, `created_at`) VALUES
(15, 5, 'assets/images/products/product_5_1762463743_690d0fffd62eb.jpg', 0, 1, '2025-11-06 21:38:23'),
(17, 4, 'assets/images/products/product_4_1762463716_690d0fe408fe9.jpg', 0, 1, '2025-11-07 21:44:36'),
(18, 6, 'assets/images/products/product_6_1764542812_692cc95cdeb1d.png', 0, 1, '2025-11-30 22:46:52'),
(19, 6, 'assets/images/products/product_6_1764542812_692cc95cdf40f.png', 1, 0, '2025-11-30 22:46:52'),
(20, 7, 'assets/images/products/product_7_1764543497_692ccc09e40e1.png', 0, 1, '2025-11-30 22:58:17'),
(21, 7, 'assets/images/products/product_7_1764543497_692ccc09e477c.png', 1, 0, '2025-11-30 22:58:17'),
(22, 8, 'assets/images/products/product_8_1764543598_692ccc6ee1452.png', 0, 1, '2025-11-30 22:59:58'),
(23, 9, 'assets/images/products/product_9_1764543674_692cccba32e95.png', 0, 1, '2025-11-30 23:01:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `product_inventory_status`
-- (See below for the actual view)
--
CREATE TABLE `product_inventory_status` (
`id` int(11)
,`product_code` varchar(50)
,`name` varchar(200)
,`name_ar` varchar(200)
,`category_name` varchar(100)
,`total_quantity` decimal(32,0)
,`size_count` bigint(21)
,`price` decimal(10,2)
,`cost` decimal(10,2)
,`is_active` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `product_reviews`
--

CREATE TABLE `product_reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_sizes`
--

CREATE TABLE `product_sizes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_sizes`
--

INSERT INTO `product_sizes` (`id`, `product_id`, `size`, `quantity`, `created_at`, `updated_at`) VALUES
(8, 2, 'XS', 4, '2025-11-05 11:53:12', '2025-11-05 11:53:12'),
(9, 2, 'S', 0, '2025-11-05 11:53:12', '2025-11-05 11:55:26'),
(10, 2, 'M', 0, '2025-11-05 11:53:12', '2025-11-05 11:55:26'),
(11, 2, 'L', 0, '2025-11-05 11:53:12', '2025-11-05 11:55:35'),
(12, 2, 'XL', 0, '2025-11-05 11:53:12', '2025-11-05 11:55:35'),
(13, 2, 'XXL', 4, '2025-11-05 11:53:12', '2025-11-05 11:53:12'),
(14, 2, 'XXXL', 3, '2025-11-05 11:53:12', '2025-11-05 11:53:12'),
(15, 3, 'XS', 1, '2025-11-05 11:57:47', '2025-11-05 12:03:21'),
(16, 3, 'S', 4, '2025-11-05 11:57:47', '2025-11-05 12:03:21'),
(17, 3, 'M', 4, '2025-11-05 11:57:47', '2025-11-05 12:03:21'),
(18, 3, 'L', 1, '2025-11-05 11:57:47', '2025-11-05 11:57:47'),
(19, 3, 'XL', 7, '2025-11-05 11:57:47', '2025-11-05 12:03:21'),
(20, 3, 'XXL', 4, '2025-11-05 11:57:47', '2025-11-05 12:03:21'),
(21, 3, 'XXXL', 0, '2025-11-05 11:57:47', '2025-11-05 11:57:47'),
(67, 5, 'S', 0, '2025-11-06 21:38:23', '2025-11-06 21:38:23'),
(68, 5, 'M', 0, '2025-11-06 21:38:23', '2025-11-06 21:38:23'),
(69, 5, 'L', 0, '2025-11-06 21:38:23', '2025-11-06 21:38:23'),
(70, 5, 'XL', 0, '2025-11-06 21:38:23', '2025-11-06 21:38:23'),
(71, 5, 'XXL', 0, '2025-11-06 21:38:23', '2025-11-06 21:38:23'),
(77, 4, 'S', 0, '2025-11-07 21:44:36', '2025-11-07 21:44:36'),
(78, 4, 'M', 0, '2025-11-07 21:44:36', '2025-11-07 21:44:36'),
(79, 4, 'L', 0, '2025-11-07 21:44:36', '2025-11-07 21:44:36'),
(80, 4, 'XL', 0, '2025-11-07 21:44:36', '2025-11-07 21:44:36'),
(81, 4, 'XXL', 0, '2025-11-07 21:44:36', '2025-11-07 21:44:36'),
(100, 9, 'S', 0, '2025-12-30 13:38:11', '2025-12-30 13:38:11'),
(101, 9, 'M', 0, '2025-12-30 13:38:11', '2025-12-30 13:38:11'),
(102, 9, 'L', 0, '2025-12-30 13:38:11', '2025-12-30 13:38:11'),
(103, 9, 'XL', 0, '2025-12-30 13:38:11', '2025-12-30 13:38:11'),
(104, 9, 'XXL', 0, '2025-12-30 13:38:11', '2025-12-30 13:38:11'),
(105, 8, 'S', 0, '2025-12-30 13:38:27', '2025-12-30 13:38:27'),
(106, 8, 'M', 0, '2025-12-30 13:38:27', '2025-12-30 13:38:27'),
(107, 8, 'L', 0, '2025-12-30 13:38:27', '2025-12-30 13:38:27'),
(108, 8, 'XL', 0, '2025-12-30 13:38:27', '2025-12-30 13:38:27'),
(109, 8, 'XXL', 0, '2025-12-30 13:38:27', '2025-12-30 13:38:27'),
(122, 7, 'M', 0, '2026-01-10 15:44:18', '2026-01-10 15:44:18'),
(123, 7, 'L', 0, '2026-01-10 15:44:18', '2026-01-10 15:44:18'),
(124, 7, 'XL', 0, '2026-01-10 15:44:18', '2026-01-10 15:44:18'),
(125, 7, 'XXL', 0, '2026-01-10 15:44:18', '2026-01-10 15:44:18'),
(126, 6, 'M', 0, '2026-01-10 15:44:26', '2026-01-10 15:44:26'),
(127, 6, 'L', 0, '2026-01-10 15:44:26', '2026-01-10 15:44:26'),
(128, 6, 'XL', 0, '2026-01-10 15:44:26', '2026-01-10 15:44:26'),
(129, 6, 'XXL', 0, '2026-01-10 15:44:26', '2026-01-10 15:44:26');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `sale_date` date NOT NULL,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sliders`
--

CREATE TABLE `sliders` (
  `id` int(11) NOT NULL,
  `title_ar` varchar(200) DEFAULT NULL,
  `title_en` varchar(200) DEFAULT NULL,
  `subtitle_ar` varchar(300) DEFAULT NULL,
  `subtitle_en` varchar(300) DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `button_text_ar` varchar(50) DEFAULT NULL,
  `button_text_en` varchar(50) DEFAULT NULL,
  `button_link` varchar(200) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sliders`
--

INSERT INTO `sliders` (`id`, `title_ar`, `title_en`, `subtitle_ar`, `subtitle_en`, `image_url`, `button_text_ar`, `button_text_en`, `button_link`, `display_order`, `is_active`, `created_at`) VALUES
(2, 'مجموعة بريزو تبدأ في إدارة مشروع جديد', 'brezzo Group Starts Managing New Project', 'شش', 'شس', 'https://tse2.mm.bing.net/th/id/OIP.DxjCtrMWuF1OYOpW9W9QcwHaHa?rs=1&amp;pid=ImgDetMain&amp;o=7&amp;rm=3', 'fd', 'sf', '', 0, 1, '2025-11-06 13:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `payment_method` enum('cash','card','bank_transfer','other') DEFAULT 'cash',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `transaction_date`, `type`, `category_id`, `amount`, `description`, `reference_number`, `payment_method`, `created_by`, `created_at`, `updated_at`) VALUES
(3, '2025-12-03', 'expense', 8, 35000.00, '', '', '', 1, '2025-12-03 22:07:37', '2025-12-03 22:08:00'),
(4, '2025-12-03', 'expense', 9, 15000.00, '', '', '', 1, '2025-12-03 22:09:02', '2025-12-03 22:09:02'),
(5, '2025-12-03', 'expense', 9, 6000.00, 'توصيل طلبات', '', 'cash', 1, '2025-12-03 22:09:43', '2025-12-03 22:09:43'),
(6, '2025-12-03', 'income', 1, 10000.00, 'تراكسودين برشلونة\r\nقياس ميديم و سمول \r\nاصدقاء برين', '', 'cash', 1, '2025-12-03 22:10:54', '2025-12-03 22:10:54'),
(7, '2025-12-03', 'income', 1, 10000.00, 'Online \r\nXxl traksoud madrid', '', '', 1, '2025-12-03 22:11:56', '2025-12-03 22:11:56'),
(8, '2025-12-03', 'income', 1, 2500.00, 'تيشرت مدريد قياس لارج \r\nسيزار', '', '', 1, '2025-12-03 22:12:53', '2025-12-03 22:12:53');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_categories`
--

CREATE TABLE `transaction_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `color` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaction_categories`
--

INSERT INTO `transaction_categories` (`id`, `name`, `type`, `description`, `icon`, `color`, `is_active`, `created_at`) VALUES
(1, 'Product Sales', 'income', NULL, 'bi-cart-check', '#10b981', 1, '2025-11-05 11:41:33'),
(2, 'Service Revenue', 'income', NULL, 'bi-tools', '#10b981', 1, '2025-11-05 11:41:33'),
(3, 'Other Income', 'income', NULL, 'bi-cash-coin', '#10b981', 1, '2025-11-05 11:41:33'),
(4, 'Inventory Purchase', 'expense', NULL, 'bi-box-seam', '#ef4444', 1, '2025-11-05 11:41:33'),
(5, 'Rent', 'expense', NULL, 'bi-building', '#ef4444', 1, '2025-11-05 11:41:33'),
(6, 'Salaries', 'expense', NULL, 'bi-people', '#ef4444', 1, '2025-11-05 11:41:33'),
(7, 'Utilities', 'expense', NULL, 'bi-lightning', '#ef4444', 1, '2025-11-05 11:41:33'),
(8, 'Marketing', 'expense', NULL, 'bi-megaphone', '#ef4444', 1, '2025-11-05 11:41:33'),
(9, 'Other Expenses', 'expense', NULL, 'bi-dash-circle', '#ef4444', 1, '2025-11-05 11:41:33');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','accountant','manager') DEFAULT 'admin',
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `phone`, `avatar`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$XtuFHaGSh80sUzYKqyXvwOH524h2m18.74zt3L4U1QSquCDa0vXUO', 'admin@goalchii.com', 'Administrator', 'admin', NULL, NULL, 1, NULL, '2025-11-05 11:41:33', '2025-11-05 11:46:53'),
(2, 'accountant', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFe7g3z3VrCNKLXdqN7qLeHqGKzLKKhi', 'accountant@goalchii.com', 'Accountant User', 'accountant', NULL, NULL, 1, NULL, '2025-11-05 11:41:33', '2025-11-05 11:41:33'),
(3, 'manager', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFe7g3z3VrCNKLXdqN7qLeHqGKzLKKhi', 'manager@goalchii.com', 'Manager User', 'manager', NULL, NULL, 1, NULL, '2025-11-05 11:41:33', '2025-11-05 11:41:33');

-- --------------------------------------------------------

--
-- Table structure for table `website_settings`
--

CREATE TABLE `website_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `website_settings`
--

INSERT INTO `website_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name_ar', 'جول تشي سبورتس', 'text', 'Site name in Arabic', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(2, 'site_name_en', 'GoalChii Sports', 'text', 'Site name in English', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(3, 'site_email', 'info@goalchii.com', 'email', 'Contact email', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(4, 'site_phone', '+966 12 345 6789', 'text', 'Contact phone', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(5, 'site_address_ar', 'الرياض، المملكة العربية السعودية', 'text', 'Address in Arabic', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(6, 'site_address_en', 'Riyadh, Saudi Arabia', 'text', 'Address in English', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(7, 'facebook_url', 'https://facebook.com/goalchii', 'url', 'Facebook page URL', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(8, 'twitter_url', 'https://twitter.com/goalchii', 'url', 'Twitter profile URL', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(9, 'instagram_url', 'https://instagram.com/goalchii', 'url', 'Instagram profile URL', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(10, 'youtube_url', 'https://youtube.com/@goalchii', 'url', 'YouTube channel URL', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(11, 'whatsapp_number', '+966123456789', 'text', 'WhatsApp contact number', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(12, 'free_shipping_threshold', '200', 'number', 'Minimum order for free shipping', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(13, 'currency', 'SAR', 'text', 'Currency code', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(14, 'currency_symbol', 'ريال', 'text', 'Currency symbol', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(15, 'db_version', '2.0', 'text', 'Database schema version', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(16, 'db_created_at', '2025-11-05 14:41:34', 'text', 'Database creation timestamp', '2025-11-05 11:41:34', '2025-11-05 11:41:34'),
(17, 'db_description', 'Complete GoalChii Sports Management System', 'text', 'Database description', '2025-11-05 11:41:34', '2025-11-05 11:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `whatsapp_logs`
--

CREATE TABLE `whatsapp_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `phone_number` varchar(30) NOT NULL,
  `message` text NOT NULL,
  `method` varchar(20) DEFAULT NULL,
  `success` tinyint(1) DEFAULT 0,
  `message_id` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `whatsapp_logs`
--

INSERT INTO `whatsapp_logs` (`id`, `order_number`, `phone_number`, `message`, `method`, `success`, `message_id`, `error_message`, `created_at`) VALUES
(1, 'REQ-20251130-F8A6AA', '07511014226', 'مرحباً Zuhir! 👋\n\nشكراً لطلبك من *GOALCHII Sports* ⚽\n\n📦 *تفاصيل الطلب:*\nرقم الطلب: *REQ-20251130-F8A6AA*\nالتاريخ: 2025-11-30 23:13\n\n🛍️ *المنتجات:*\n1. تراكسود ريال مدريد\n   المقاس: XXL | الكمية: 1\n   السعر: 33,000 د.ع\n\n💰 *الإجمالي:*\nالمجموع الفرعي: 33,000 د.ع\nتكلفة الشحن: 0 د.ع\nالمجموع الكلي: *33,000 د.ع*\n\n📍 *عنوان الشحن:*\nGdhdh\nHdhd, Iraq\n\n📝 *ملاحظاتك:*\nU\n\n✅ *طريقة الدفع:* الدفع عند الاستلام\n\nسنتواصل معك قريباً لتأكيد الطلب والتسليم.\n\nللاستفسارات، يمكنك الرد على هذه الرسالة.\n\nشكراً لثقتك بنا! 🙏', 'link', 1, NULL, NULL, '2025-11-30 23:13:51'),
(2, 'REQ-20260619-BCA6B8', '07511452687', 'مرحباً زيد! 👋\n\nشكراً لطلبك من *GOALCHII Sports* ⚽\n\n📦 *تفاصيل الطلب:*\nرقم الطلب: *REQ-20260619-BCA6B8*\nالتاريخ: 2026-06-19 16:40\n\n🛍️ *المنتجات:*\n1. تراكسود برشلونة\n   المقاس: S | الكمية: 1\n   السعر: 30,000 د.ع\n\n💰 *الإجمالي:*\nالمجموع الفرعي: 30,000 د.ع\nتكلفة الشحن: 0 د.ع\nالمجموع الكلي: *30,000 د.ع*\n\n📍 *عنوان الشحن:*\nكوارتز\nاربيل, Iraq\n\n📝 *ملاحظاتك:*\nلا يوجد\n\n✅ *طريقة الدفع:* الدفع عند الاستلام\n\nسنتواصل معك قريباً لتأكيد الطلب والتسليم.\n\nللاستفسارات، يمكنك الرد على هذه الرسالة.\n\nشكراً لثقتك بنا! 🙏', 'link', 1, NULL, NULL, '2026-06-19 16:40:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about_content`
--
ALTER TABLE `about_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `section_key` (`section_key`),
  ADD KEY `idx_key` (`section_key`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `blog_categories`
--
ALTER TABLE `blog_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_published` (`status`,`published_at`),
  ADD KEY `idx_blog_status_date` (`status`,`published_at`);
ALTER TABLE `blog_posts` ADD FULLTEXT KEY `idx_search` (`title_ar`,`title_en`,`title_ku`,`content_ar`,`content_en`,`content_ku`);

--
-- Indexes for table `blog_post_categories`
--
ALTER TABLE `blog_post_categories`
  ADD PRIMARY KEY (`post_id`,`category_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_parent` (`parent_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`);

--
-- Indexes for table `clubs`
--
ALTER TABLE `clubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`),
  ADD KEY `idx_category` (`category_id`);

--
-- Indexes for table `club_categories`
--
ALTER TABLE `club_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `order_requests`
--
ALTER TABLE `order_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_code` (`product_code`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_featured` (`featured`,`is_active`),
  ADD KEY `idx_code` (`product_code`),
  ADD KEY `idx_product_category_active` (`category_id`,`is_active`),
  ADD KEY `idx_ecommerce` (`is_ecommerce`,`is_active`);
ALTER TABLE `products` ADD FULLTEXT KEY `idx_search` (`name`,`name_ar`,`name_ku`,`description`,`description_ar`,`description_ku`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_order` (`product_id`,`display_order`),
  ADD KEY `idx_primary` (`product_id`,`is_primary`);

--
-- Indexes for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_rating` (`product_id`,`rating`),
  ADD KEY `idx_approved` (`is_approved`);

--
-- Indexes for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_size` (`product_id`,`size`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_quantity` (`quantity`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`sale_date`),
  ADD KEY `idx_product` (`product_id`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `sliders`
--
ALTER TABLE `sliders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_order` (`display_order`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_date` (`transaction_date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_transaction_date_type` (`transaction_date`,`type`);

--
-- Indexes for table `transaction_categories`
--
ALTER TABLE `transaction_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `website_settings`
--
ALTER TABLE `website_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_key` (`setting_key`);

--
-- Indexes for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`order_number`),
  ADD KEY `idx_phone` (`phone_number`),
  ADD KEY `idx_created` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about_content`
--
ALTER TABLE `about_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_categories`
--
ALTER TABLE `blog_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `clubs`
--
ALTER TABLE `clubs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `club_categories`
--
ALTER TABLE `club_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_requests`
--
ALTER TABLE `order_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `product_reviews`
--
ALTER TABLE `product_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_sizes`
--
ALTER TABLE `product_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=130;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sliders`
--
ALTER TABLE `sliders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transaction_categories`
--
ALTER TABLE `transaction_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `website_settings`
--
ALTER TABLE `website_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `whatsapp_logs`
--
ALTER TABLE `whatsapp_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- --------------------------------------------------------

--
-- Structure for view `low_stock_alert`
--
DROP TABLE IF EXISTS `low_stock_alert`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u100517965_goalchii0751`@`127.0.0.1` SQL SECURITY DEFINER VIEW `low_stock_alert`  AS SELECT `p`.`id` AS `id`, `p`.`product_code` AS `product_code`, `p`.`name` AS `name`, `p`.`name_ar` AS `name_ar`, `ps`.`size` AS `size`, `ps`.`quantity` AS `quantity`, `c`.`name` AS `category_name` FROM ((`products` `p` left join `product_sizes` `ps` on(`p`.`id` = `ps`.`product_id`)) left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) WHERE `p`.`is_active` = 1 AND `ps`.`quantity` < 10 ORDER BY `ps`.`quantity` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `monthly_financial_summary`
--
DROP TABLE IF EXISTS `monthly_financial_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u100517965_goalchii0751`@`127.0.0.1` SQL SECURITY DEFINER VIEW `monthly_financial_summary`  AS SELECT date_format(`transactions`.`transaction_date`,'%Y-%m') AS `month`, year(`transactions`.`transaction_date`) AS `year`, month(`transactions`.`transaction_date`) AS `month_number`, sum(case when `transactions`.`type` = 'income' then `transactions`.`amount` else 0 end) AS `total_income`, sum(case when `transactions`.`type` = 'expense' then `transactions`.`amount` else 0 end) AS `total_expense`, sum(case when `transactions`.`type` = 'income' then `transactions`.`amount` else -`transactions`.`amount` end) AS `net_profit` FROM `transactions` GROUP BY date_format(`transactions`.`transaction_date`,'%Y-%m'), year(`transactions`.`transaction_date`), month(`transactions`.`transaction_date`) ORDER BY year(`transactions`.`transaction_date`) DESC, month(`transactions`.`transaction_date`) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `product_inventory_status`
--
DROP TABLE IF EXISTS `product_inventory_status`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u100517965_goalchii0751`@`127.0.0.1` SQL SECURITY DEFINER VIEW `product_inventory_status`  AS SELECT `p`.`id` AS `id`, `p`.`product_code` AS `product_code`, `p`.`name` AS `name`, `p`.`name_ar` AS `name_ar`, `c`.`name` AS `category_name`, coalesce(sum(`ps`.`quantity`),0) AS `total_quantity`, count(distinct `ps`.`size`) AS `size_count`, `p`.`price` AS `price`, `p`.`cost` AS `cost`, `p`.`is_active` AS `is_active` FROM ((`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`id`)) left join `product_sizes` `ps` on(`p`.`id` = `ps`.`product_id`)) GROUP BY `p`.`id`, `p`.`product_code`, `p`.`name`, `p`.`name_ar`, `c`.`name`, `p`.`price`, `p`.`cost`, `p`.`is_active` ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `blog_post_categories`
--
ALTER TABLE `blog_post_categories`
  ADD CONSTRAINT `blog_post_categories_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_post_categories_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `clubs`
--
ALTER TABLE `clubs`
  ADD CONSTRAINT `clubs_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_logs_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_reviews`
--
ALTER TABLE `product_reviews`
  ADD CONSTRAINT `product_reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_sizes`
--
ALTER TABLE `product_sizes`
  ADD CONSTRAINT `product_sizes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `transaction_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

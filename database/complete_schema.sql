-- ============================================================================
-- GoalChii Sports - Complete Database Schema
-- Version: 2.0
-- Description: Comprehensive database for sports clothing business management
-- Includes: Inventory, Accounting, E-commerce, Blog, CRM
-- ============================================================================

-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS goalchii_sports;
CREATE DATABASE goalchii_sports CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE goalchii_sports;

-- ============================================================================
-- USER MANAGEMENT
-- ============================================================================

-- Users table - Admin, accountant, manager roles
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'accountant', 'manager') DEFAULT 'admin',
    phone VARCHAR(20),
    avatar VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123)
-- Password hash generated for 'admin123' using bcrypt
INSERT INTO users (username, password, email, full_name, role) VALUES
('admin', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFe7g3z3VrCNKLXdqN7qLeHqGKzLKKhi', 'admin@goalchii.com', 'Administrator', 'admin'),
('accountant', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFe7g3z3VrCNKLXdqN7qLeHqGKzLKKhi', 'accountant@goalchii.com', 'Accountant User', 'accountant'),
('manager', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHeFe7g3z3VrCNKLXdqN7qLeHqGKzLKKhi', 'manager@goalchii.com', 'Manager User', 'manager');

-- ============================================================================
-- PRODUCT MANAGEMENT & INVENTORY
-- ============================================================================

-- Product categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    description TEXT,
    parent_id INT NULL,
    image_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent (parent_id),
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO categories (name, name_ar, display_order) VALUES
('Shoes', 'أحذية', 1),
('T-Shirts', 'قمصان', 2),
('Pants', 'بناطيل', 3),
('Jackets', 'جاكيتات', 4),
('Accessories', 'إكسسوارات', 5);

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    name_ar VARCHAR(200),
    name_ku VARCHAR(200),
    description TEXT,
    description_ar TEXT,
    description_ku TEXT,
    category_id INT,
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2),
    cost DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(500),
    featured BOOLEAN DEFAULT FALSE,
    views INT DEFAULT 0,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_category (category_id),
    INDEX idx_active (is_active),
    INDEX idx_featured (featured, is_active),
    INDEX idx_code (product_code),
    FULLTEXT idx_search (name, name_ar, name_ku, description, description_ar, description_ku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product sizes and quantities
CREATE TABLE product_sizes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    size VARCHAR(10) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size (product_id, size),
    INDEX idx_product (product_id),
    INDEX idx_quantity (quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory change logs
CREATE TABLE inventory_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    size VARCHAR(10) NOT NULL,
    quantity_change INT NOT NULL,
    old_quantity INT NOT NULL,
    new_quantity INT NOT NULL,
    reason VARCHAR(200),
    changed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- ACCOUNTING & TRANSACTIONS
-- ============================================================================

-- Transaction categories
CREATE TABLE transaction_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default transaction categories
INSERT INTO transaction_categories (name, type, icon, color) VALUES
('Product Sales', 'income', 'bi-cart-check', '#10b981'),
('Service Revenue', 'income', 'bi-tools', '#10b981'),
('Other Income', 'income', 'bi-cash-coin', '#10b981'),
('Inventory Purchase', 'expense', 'bi-box-seam', '#ef4444'),
('Rent', 'expense', 'bi-building', '#ef4444'),
('Salaries', 'expense', 'bi-people', '#ef4444'),
('Utilities', 'expense', 'bi-lightning', '#ef4444'),
('Marketing', 'expense', 'bi-megaphone', '#ef4444'),
('Other Expenses', 'expense', 'bi-dash-circle', '#ef4444');

-- Financial transactions
CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    transaction_date DATE NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category_id INT,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    reference_number VARCHAR(50),
    payment_method ENUM('cash', 'card', 'bank_transfer', 'other') DEFAULT 'cash',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES transaction_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (transaction_date),
    INDEX idx_type (type),
    INDEX idx_category (category_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sales records
CREATE TABLE sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_date DATE NOT NULL,
    product_id INT NOT NULL,
    size VARCHAR(10),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    notes TEXT,
    transaction_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_date (sale_date),
    INDEX idx_product (product_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- E-COMMERCE WEBSITE CONTENT
-- ============================================================================

-- Website general settings
CREATE TABLE website_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default website settings
INSERT INTO website_settings (setting_key, setting_value, setting_type, description) VALUES 
('site_name_ar', 'جول تشي سبورتس', 'text', 'Site name in Arabic'),
('site_name_en', 'GoalChii Sports', 'text', 'Site name in English'),
('site_email', 'info@goalchii.store', 'email', 'Contact email'),
('site_phone', '+964 751 521 5455', 'text', 'Contact phone'),
('site_address_ar', 'الرياض، المملكة العربية السعودية', 'text', 'Address in Arabic'),
('site_address_en', 'Riyadh, Saudi Arabia', 'text', 'Address in English'),
('facebook_url', 'https://facebook.com/goalchii', 'url', 'Facebook page URL'),
('twitter_url', 'https://twitter.com/goalchii', 'url', 'Twitter profile URL'),
('instagram_url', 'https://instagram.com/goalchii', 'url', 'Instagram profile URL'),
('youtube_url', 'https://youtube.com/@goalchii', 'url', 'YouTube channel URL'),
('whatsapp_number', '+966123456789', 'text', 'WhatsApp contact number'),
('free_shipping_threshold', '200', 'number', 'Minimum order for free shipping'),
('currency', 'SAR', 'text', 'Currency code'),
('currency_symbol', 'ريال', 'text', 'Currency symbol');

-- Homepage sliders/hero banners
CREATE TABLE sliders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title_ar VARCHAR(200),
    title_en VARCHAR(200),
    subtitle_ar VARCHAR(300),
    subtitle_en VARCHAR(300),
    image_url VARCHAR(500) NOT NULL,
    button_text_ar VARCHAR(50),
    button_text_en VARCHAR(50),
    button_link VARCHAR(200),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- About us page content
CREATE TABLE about_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    section_key VARCHAR(50) UNIQUE NOT NULL,
    title_ar VARCHAR(200),
    title_en VARCHAR(200),
    content_ar TEXT,
    content_en TEXT,
    image_url VARCHAR(500),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (section_key),
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default about content
INSERT INTO about_content (section_key, title_ar, title_en, content_ar, content_en, display_order) VALUES 
('mission', 'مهمتنا', 'Our Mission', 'نسعى لتوفير أفضل الملابس الرياضية عالية الجودة التي تلهم الرياضيين لتحقيق أهدافهم وتجاوز حدودهم، مع الحفاظ على الأناقة والراحة في كل قطعة نصممها.', 'We strive to provide the best high-quality sportswear that inspires athletes to achieve their goals and push their limits, while maintaining elegance and comfort in every piece we design.', 1),
('vision', 'رؤيتنا', 'Our Vision', 'أن نصبح العلامة التجارية الرائدة في الملابس الرياضية في المنطقة العربية، معروفين بالابتكار والجودة والأناقة، ونكون الخيار الأول لكل رياضي يسعى للتميز.', 'To become the leading sports apparel brand in the Arab region, known for innovation, quality, and elegance, and to be the first choice for every athlete seeking excellence.', 2),
('values', 'قيمنا', 'Our Values', 'الجودة • الابتكار • الاستدامة • الشغف • التميز', 'Quality • Innovation • Sustainability • Passion • Excellence', 3);

-- ============================================================================
-- BLOG SYSTEM
-- ============================================================================

-- Blog posts
CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title_ar VARCHAR(255) NOT NULL,
    title_en VARCHAR(255),
    title_ku VARCHAR(255),
    slug VARCHAR(255) UNIQUE NOT NULL,
    content_ar TEXT NOT NULL,
    content_en TEXT,
    content_ku TEXT,
    excerpt_ar TEXT,
    excerpt_en TEXT,
    excerpt_ku TEXT,
    featured_image VARCHAR(500),
    author_id INT,
    status ENUM('draft', 'published') DEFAULT 'draft',
    views INT DEFAULT 0,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_author (author_id),
    INDEX idx_published (status, published_at),
    FULLTEXT idx_search (title_ar, title_en, title_ku, content_ar, content_en, content_ku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog categories (optional)
CREATE TABLE blog_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name_ar VARCHAR(100) NOT NULL,
    name_en VARCHAR(100),
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog post - category relationship
CREATE TABLE blog_post_categories (
    post_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (post_id, category_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- CUSTOMER RELATIONSHIP MANAGEMENT (CRM)
-- ============================================================================

-- Contact form messages
CREATE TABLE contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    ip_address VARCHAR(45),
    is_read BOOLEAN DEFAULT FALSE,
    replied BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product reviews
CREATE TABLE product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_rating (product_id, rating),
    INDEX idx_approved (is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter subscribers
CREATE TABLE newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SYSTEM LOGS & ANALYTICS
-- ============================================================================

-- Activity logs for audit trail
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- VIEWS FOR REPORTING
-- ============================================================================

-- View: Product inventory status
CREATE OR REPLACE VIEW product_inventory_status AS
SELECT 
    p.id,
    p.product_code,
    p.name,
    p.name_ar,
    c.name as category_name,
    COALESCE(SUM(ps.quantity), 0) as total_quantity,
    COUNT(DISTINCT ps.size) as size_count,
    p.price,
    p.cost,
    p.is_active
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN product_sizes ps ON p.id = ps.product_id
GROUP BY p.id, p.product_code, p.name, p.name_ar, c.name, p.price, p.cost, p.is_active;

-- View: Monthly financial summary
CREATE OR REPLACE VIEW monthly_financial_summary AS
SELECT 
    DATE_FORMAT(transaction_date, '%Y-%m') as month,
    YEAR(transaction_date) as year,
    MONTH(transaction_date) as month_number,
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
    SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net_profit
FROM transactions
GROUP BY DATE_FORMAT(transaction_date, '%Y-%m'), YEAR(transaction_date), MONTH(transaction_date)
ORDER BY year DESC, month_number DESC;

-- View: Low stock alert
CREATE OR REPLACE VIEW low_stock_alert AS
SELECT 
    p.id,
    p.product_code,
    p.name,
    p.name_ar,
    ps.size,
    ps.quantity,
    c.name as category_name
FROM products p
LEFT JOIN product_sizes ps ON p.id = ps.product_id
LEFT JOIN categories c ON p.category_id = c.id
WHERE p.is_active = 1 AND ps.quantity < 10
ORDER BY ps.quantity ASC;

-- ============================================================================
-- STORED PROCEDURES
-- ============================================================================

DELIMITER //

-- Procedure: Add product with sizes
CREATE PROCEDURE add_product_with_sizes(
    IN p_code VARCHAR(50),
    IN p_name VARCHAR(200),
    IN p_name_ar VARCHAR(200),
    IN p_name_ku VARCHAR(200),
    IN p_description TEXT,
    IN p_description_ar TEXT,
    IN p_description_ku TEXT,
    IN p_category_id INT,
    IN p_price DECIMAL(10,2),
    IN p_cost DECIMAL(10,2),
    IN p_image_url VARCHAR(500),
    IN p_sizes JSON
)
BEGIN
    DECLARE new_product_id INT;
    DECLARE i INT DEFAULT 0;
    DECLARE size_count INT;
    
    START TRANSACTION;
    
    -- Insert product
    INSERT INTO products (product_code, name, name_ar, name_ku, description, description_ar, description_ku, category_id, price, cost, image_url)
    VALUES (p_code, p_name, p_name_ar, p_name_ku, p_description, p_description_ar, p_description_ku, p_category_id, p_price, p_cost, p_image_url);
    
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
END //

-- Procedure: Update inventory and log
CREATE PROCEDURE update_inventory(
    IN p_product_id INT,
    IN p_size VARCHAR(10),
    IN p_quantity_change INT,
    IN p_reason VARCHAR(200),
    IN p_user_id INT
)
BEGIN
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
END //

DELIMITER ;

-- ============================================================================
-- PERFORMANCE INDEXES
-- ============================================================================

-- Additional composite indexes for complex queries
CREATE INDEX idx_product_category_active ON products(category_id, is_active);
CREATE INDEX idx_transaction_date_type ON transactions(transaction_date, type);
CREATE INDEX idx_blog_status_date ON blog_posts(status, published_at DESC);

-- ============================================================================
-- DATABASE INFORMATION
-- ============================================================================

-- Insert database metadata
INSERT INTO website_settings (setting_key, setting_value, setting_type, description) VALUES 
('db_version', '2.0', 'text', 'Database schema version'),
('db_created_at', NOW(), 'text', 'Database creation timestamp'),
('db_description', 'Complete GoalChii Sports Management System', 'text', 'Database description');

-- ============================================================================
-- COMPLETION MESSAGE
-- ============================================================================

SELECT 
    'Database schema created successfully!' as status,
    DATABASE() as database_name,
    COUNT(*) as total_tables
FROM information_schema.tables 
WHERE table_schema = DATABASE();

SELECT 'Default users created - Username: admin, Password: admin123' as note
UNION ALL
SELECT 'Default users created - Username: accountant, Password: admin123'
UNION ALL
SELECT 'Default users created - Username: manager, Password: admin123';


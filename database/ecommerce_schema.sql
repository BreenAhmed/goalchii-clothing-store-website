-- E-commerce Extension for GoalChii Sports
-- Author: Admin
-- Date: 2025-11-05

USE goalchii_sports;

-- Website settings table
CREATE TABLE IF NOT EXISTS website_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blog posts table
CREATE TABLE IF NOT EXISTS blog_posts (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_status (status)
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_is_read (is_read)
);

-- Product reviews table
CREATE TABLE IF NOT EXISTS product_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_rating (product_id, rating)
);

-- Sliders/Hero banners table
CREATE TABLE IF NOT EXISTS sliders (
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- About us content
CREATE TABLE IF NOT EXISTS about_content (
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Update products table for e-commerce (skip if columns already exist)
-- ALTER TABLE products 
-- ADD COLUMN name_ar VARCHAR(200) AFTER name,
-- ADD COLUMN description_ar TEXT AFTER description,
-- ADD COLUMN featured BOOLEAN DEFAULT FALSE,
-- ADD COLUMN views INT DEFAULT 0,
-- ADD COLUMN meta_title VARCHAR(200),
-- ADD COLUMN meta_description TEXT,
-- ADD COLUMN meta_keywords VARCHAR(500);

-- Insert default website settings
INSERT INTO website_settings (setting_key, setting_value) VALUES 
('site_name_ar', 'جول تشي سبورتس'),
('site_name_en', 'GoalChii Sports'),
('site_email', 'info@goalchii.store'),
('site_phone', '+964 751 521 5455'),
('site_address_ar', 'الرياض، المملكة العربية السعودية'),
('site_address_en', 'Riyadh, Saudi Arabia'),
('facebook_url', '#'),
('twitter_url', '#'),
('instagram_url', '#'),
('whatsapp_number', '+966123456789')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert sample about content
INSERT INTO about_content (section_key, title_ar, content_ar, display_order) VALUES 
('mission', 'مهمتنا', 'نحن نسعى لتوفير أفضل الملابس الرياضية عالية الجودة التي تلهم الرياضيين لتحقيق أهدافهم وتجاوز حدودهم.', 1),
('vision', 'رؤيتنا', 'أن نصبح العلامة التجارية الرائدة في الملابس الرياضية في المنطقة، معروفين بالابتكار والجودة والأناقة.', 2),
('values', 'قيمنا', 'الجودة • الابتكار • الاستدامة • الشغف • التميز', 3)
ON DUPLICATE KEY UPDATE title_ar = VALUES(title_ar);

-- Create indexes for better performance
CREATE INDEX idx_product_featured ON products(featured, is_active);
CREATE INDEX idx_blog_published ON blog_posts(status, published_at);

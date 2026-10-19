-- Club Categories Table
-- Author: Admin
-- Date: 2025-01-XX

USE goalchii_sports;

-- Club categories table
CREATE TABLE IF NOT EXISTS club_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    name_ar VARCHAR(100),
    description TEXT,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default club categories
INSERT INTO club_categories (name, name_ar, display_order) VALUES
('Football', 'كرة القدم', 1),
('Basketball', 'كرة السلة', 2),
('Tennis', 'تنس', 3),
('Volleyball', 'كرة الطائرة', 4),
('Other', 'أخرى', 5);

-- Add category_id to clubs table
ALTER TABLE clubs 
ADD COLUMN category_id INT NULL AFTER name_ar,
ADD FOREIGN KEY (category_id) REFERENCES club_categories(id) ON DELETE SET NULL,
ADD INDEX idx_category (category_id);


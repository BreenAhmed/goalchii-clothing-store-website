-- Add is_ecommerce field to products table
-- This separates e-commerce products from inventory products

USE goalchii_sports;

-- Add is_ecommerce column to products table
ALTER TABLE products 
ADD COLUMN is_ecommerce BOOLEAN DEFAULT FALSE AFTER is_active;

-- Add index for better query performance
ALTER TABLE products 
ADD INDEX idx_ecommerce (is_ecommerce, is_active);

-- Update existing products: Set is_ecommerce = FALSE by default (inventory products)
-- E-commerce products will be set to TRUE when created through e-commerce section


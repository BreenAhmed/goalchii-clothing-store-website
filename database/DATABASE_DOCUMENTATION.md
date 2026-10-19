# GoalChii Sports - Complete Database Documentation

## Overview
Complete MySQL database schema for sports clothing business management system with integrated e-commerce, inventory, accounting, and CRM features.

**Database Name:** `goalchii_sports`  
**Version:** 2.0  
**Character Set:** UTF8MB4 (supports Arabic and emoji)  
**Total Tables:** 21

---

## Database Structure

### 1. USER MANAGEMENT

#### `users`
Manages system users with role-based access control.

**Columns:**
- `id` - Primary key
- `username` - Unique username
- `password` - Bcrypt hashed password
- `email` - Unique email address
- `full_name` - User's full name
- `role` - ENUM('admin', 'accountant', 'manager')
- `phone` - Contact number
- `avatar` - Profile picture URL
- `is_active` - Account status
- `last_login` - Last login timestamp
- `created_at`, `updated_at` - Timestamps

**Default Users:**
- Username: `admin` / Password: `admin123` (Role: admin)
- Username: `accountant` / Password: `admin123` (Role: accountant)
- Username: `manager` / Password: `admin123` (Role: manager)

---

### 2. PRODUCT MANAGEMENT & INVENTORY

#### `categories`
Product categories with hierarchical support.

**Features:**
- Multi-language support (Arabic/English)
- Parent-child relationships
- Display ordering
- Active/inactive status

#### `products`
Main products catalog.

**Features:**
- Arabic, English, and Kurdish product information
- Multiple pricing (price, old_price, cost)
- SEO fields (meta_title, meta_description, keywords)
- Featured products flag
- View tracking
- Full-text search on name and description

#### `product_sizes`
Size-specific inventory tracking.

**Features:**
- Unique constraint on product + size
- Real-time quantity tracking
- Supports standard sizes (XS, S, M, L, XL, XXL, XXXL)

#### `inventory_logs`
Complete audit trail for inventory changes.

**Tracks:**
- Who made the change
- What changed (old/new quantities)
- Why (reason field)
- When (timestamp)

---

### 3. ACCOUNTING & FINANCIAL MANAGEMENT

#### `transaction_categories`
Income and expense categories.

**Default Categories:**
- **Income:** Product Sales, Service Revenue, Other Income
- **Expenses:** Inventory Purchase, Rent, Salaries, Utilities, Marketing, Other

#### `transactions`
All financial transactions.

**Features:**
- Income/expense tracking
- Multiple payment methods
- Reference numbers for tracking
- Category association
- User audit trail

#### `sales`
Detailed sales records.

**Features:**
- Links to products and transactions
- Customer information
- Size and quantity tracking
- Unit and total pricing
- Sales notes

---

### 4. E-COMMERCE WEBSITE CONTENT

#### `website_settings`
Global website configuration.

**Default Settings:**
- Site name (Arabic/English)
- Contact information
- Social media links
- Currency settings
- Free shipping threshold

#### `sliders`
Homepage hero banners.

**Features:**
- Bilingual titles and subtitles
- Button text and links
- Display ordering
- Active/inactive status

#### `about_content`
Dynamic About Us page content.

**Sections:**
- Mission
- Vision
- Values
- Story (optional)

---

### 5. BLOG SYSTEM

#### `blog_posts`
Complete blog/news system.

**Features:**
- Arabic, English, and Kurdish content support
- Draft/Published status
- SEO optimization
- View tracking
- Featured images
- Author association

#### `blog_categories`
Optional blog post categorization.

#### `blog_post_categories`
Many-to-many relationship between posts and categories.

---

### 6. CUSTOMER RELATIONSHIP MANAGEMENT

#### `contact_messages`
Contact form submissions.

**Features:**
- Read/unread status
- Reply tracking
- IP address logging
- Timestamp tracking

#### `product_reviews`
Customer product reviews.

**Features:**
- 1-5 star ratings
- Approval system
- Email for follow-up

#### `newsletter_subscribers`
Email marketing subscribers list.

**Features:**
- Active/inactive status
- Subscribe/unsubscribe tracking
- Duplicate prevention (unique email)

---

### 7. SYSTEM LOGS & ANALYTICS

#### `activity_logs`
Complete audit trail for system activities.

**Tracks:**
- User actions
- Entity changes
- IP addresses
- User agents
- Timestamps

---

## Database Views (Reporting)

### `product_inventory_status`
Real-time inventory overview with:
- Total quantities per product
- Size count
- Category information
- Pricing data

### `monthly_financial_summary`
Monthly profit/loss statements:
- Total income
- Total expenses
- Net profit
- Year/month breakdown

### `low_stock_alert`
Products with quantity < 10:
- Product details
- Size and quantity
- Category information

---

## Stored Procedures

### `add_product_with_sizes()`
Add a product with multiple sizes in one transaction.

**Parameters:**
- Product details (code, name, description, etc.)
- JSON array of sizes and quantities

### `update_inventory()`
Update inventory with automatic logging.

**Parameters:**
- Product ID
- Size
- Quantity change (positive or negative)
- Reason
- User ID

**Features:**
- Atomic transaction
- Automatic log creation
- Prevents race conditions with row locking

---

## Indexes & Performance

### Full-Text Indexes
- `products`: name, name_ar, name_ku, description, description_ar, description_ku
- `blog_posts`: title_ar, title_en, content_ar, content_en

### Composite Indexes
- `products`: (category_id, is_active), (featured, is_active)
- `transactions`: (transaction_date, type)
- `blog_posts`: (status, published_at)

### Foreign Key Indexes
All foreign keys have appropriate indexes for join performance.

---

## Security Features

1. **Password Hashing**: Bcrypt with cost factor 10
2. **CSRF Protection**: Token-based validation
3. **SQL Injection Prevention**: Prepared statements throughout
4. **XSS Prevention**: Output sanitization
5. **Audit Trail**: Activity logs for all major actions
6. **Role-Based Access**: Three-tier user roles

---

## Backup & Maintenance

### Backup Command
```bash
mysqldump -h 127.0.0.1 -P 3306 -u root goalchii_sports > backup_$(date +%Y%m%d).sql
```

### Restore Command
```bash
mysql -h 127.0.0.1 -P 3306 -u root goalchii_sports < backup_file.sql
```

### Optimize Tables
```sql
OPTIMIZE TABLE products, transactions, blog_posts;
```

---

## Connection to phpMyAdmin

### Access phpMyAdmin
1. Open browser: `http://localhost/phpmyadmin`
2. Login with:
   - Username: `root`
   - Password: (blank or your MySQL password)
3. Select database: `goalchii_sports`

### Connection Details
- **Host:** 127.0.0.1
- **Port:** 3306
- **Database:** goalchii_sports
- **Username:** root
- **Password:** (blank)
- **Charset:** utf8mb4

### PHP Connection (PDO)
```php
$dsn = "mysql:host=127.0.0.1;port=3306;dbname=goalchii_sports;charset=utf8mb4";
$pdo = new PDO($dsn, 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

---

## Database Statistics

| Category | Count |
|----------|-------|
| Total Tables | 21 |
| Default Categories | 5 |
| Transaction Categories | 9 |
| Default Users | 3 |
| About Sections | 3 |
| Views | 3 |
| Stored Procedures | 2 |
| Website Settings | 14 |

---

## Feature Coverage

✅ **Inventory Management**
- Products with multiple sizes
- Real-time stock tracking
- Low stock alerts
- Inventory change logs

✅ **Accounting System**
- Income/expense tracking
- Transaction categories
- Monthly reports
- Sales records

✅ **E-Commerce Website**
- Homepage sliders
- Product catalog
- About Us page
- SEO optimization

✅ **Blog System**
- Bilingual posts
- Categories
- Draft/publish workflow
- View tracking

✅ **CRM Features**
- Contact form
- Product reviews
- Newsletter subscriptions

✅ **System Logs**
- User activity tracking
- Inventory logs
- Audit trail

---

## Maintenance Schedule

### Daily
- Monitor low stock alerts
- Check unread messages

### Weekly
- Review sales reports
- Backup database
- Optimize tables

### Monthly
- Generate financial reports
- Review user activity logs
- Clean old logs (optional)

---

## Support & Documentation

For additional help:
1. Check database views for pre-built reports
2. Use stored procedures for complex operations
3. Review activity logs for troubleshooting
4. All tables use InnoDB for transaction support

---

**Last Updated:** 2024
**Schema Version:** 2.0
**Status:** Production Ready ✅


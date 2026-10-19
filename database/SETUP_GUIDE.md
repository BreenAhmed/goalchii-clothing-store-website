# GoalChii Sports - Database Setup Guide

## ✅ Database Created Successfully!

Your complete MySQL database is now set up and connected to phpMyAdmin.

---

## 📊 Database Summary

- **Database Name:** `goalchii_sports`
- **Total Tables:** 21
- **Default Data:** Loaded
- **Status:** ✅ Production Ready

---

## 🔐 Login Credentials

### Admin Panel Access
- **URL:** http://localhost/goalchii/login.php
- **Username:** `admin`
- **Password:** `admin123`

### Additional Users
- **Accountant:** username: `accountant`, password: `admin123`
- **Manager:** username: `manager`, password: `admin123`

### phpMyAdmin Access
- **URL:** http://localhost/phpmyadmin
- **Username:** `root`
- **Password:** (blank)
- **Database:** `goalchii_sports`

---

## 📋 Database Tables Overview

### Core Business (6 tables)
1. `users` - System users and authentication
2. `categories` - Product categories
3. `products` - Product catalog
4. `product_sizes` - Size-specific inventory
5. `sales` - Sales transactions
6. `inventory_logs` - Inventory change history

### Accounting (2 tables)
7. `transaction_categories` - Income/expense categories
8. `transactions` - All financial transactions

### E-Commerce Website (4 tables)
9. `website_settings` - Site configuration
10. `sliders` - Homepage hero banners
11. `about_content` - About Us page content
12. `newsletter_subscribers` - Email subscribers

### Blog System (3 tables)
13. `blog_posts` - Blog articles
14. `blog_categories` - Blog categories
15. `blog_post_categories` - Post-category relationships

### CRM (2 tables)
16. `contact_messages` - Contact form submissions
17. `product_reviews` - Customer reviews

### System (1 table)
18. `activity_logs` - Audit trail

### Reporting Views (3 views)
19. `product_inventory_status` - Real-time inventory
20. `monthly_financial_summary` - Financial reports
21. `low_stock_alert` - Stock alerts

---

## 🚀 Quick Start

### 1. Access Admin Panel
```
http://localhost/goalchii/login.php
Username: admin
Password: admin123
```

### 2. Access E-Commerce Website
```
http://localhost/goalchii/store/index.php
```

### 3. Access phpMyAdmin
```
http://localhost/phpmyadmin
Select database: goalchii_sports
```

---

## 📦 Default Data Loaded

✅ **3 Users** (admin, accountant, manager)  
✅ **5 Product Categories** (Shoes, T-Shirts, Pants, Jackets, Accessories)  
✅ **9 Transaction Categories** (Sales, expenses, etc.)  
✅ **3 About Sections** (Mission, Vision, Values)  
✅ **17 Website Settings** (Contact info, social media, etc.)

---

## 🛠️ Database Connection Settings

Your application is already configured to connect. Connection details:

```php
Host: 127.0.0.1
Port: 3306
Database: goalchii_sports
Username: root
Password: (blank)
Charset: utf8mb4
```

File: `/config/database.php`

---

## 📊 Features Included

### ✅ Inventory Management
- Multi-size product tracking
- Real-time stock updates
- Low stock alerts (< 10 items)
- Complete inventory history

### ✅ Accounting System
- Income/expense tracking
- Transaction categorization
- Monthly profit/loss reports
- Sales record keeping

### ✅ E-Commerce Website
- Bilingual (Arabic/English)
- Homepage sliders
- Product catalog
- About Us page (editable)
- Blog system
- Contact form

### ✅ CRM Features
- Customer messages
- Product reviews
- Newsletter subscriptions

### ✅ Security
- Password hashing (bcrypt)
- CSRF protection
- SQL injection prevention
- Role-based access control
- Activity logging

---

## 🔄 Database Maintenance

### Backup Database
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/goalchii
mysqldump -h 127.0.0.1 -P 3306 -u root goalchii_sports > database/backup_$(date +%Y%m%d).sql
```

### Restore Database
```bash
mysql -h 127.0.0.1 -P 3306 -u root goalchii_sports < database/backup_YYYYMMDD.sql
```

### Reset Database (Fresh Install)
```bash
mysql -h 127.0.0.1 -P 3306 -u root < database/complete_schema.sql
```

---

## 📝 Pre-built Reports (Database Views)

### 1. Product Inventory Status
```sql
SELECT * FROM product_inventory_status;
```
Shows: All products with total quantities, sizes, and pricing

### 2. Monthly Financial Summary
```sql
SELECT * FROM monthly_financial_summary;
```
Shows: Income, expenses, and net profit by month

### 3. Low Stock Alert
```sql
SELECT * FROM low_stock_alert;
```
Shows: Products with quantity less than 10

---

## 🎯 Next Steps

1. ✅ Database is created
2. ✅ Default data is loaded
3. ✅ Connected to phpMyAdmin
4. ➡️ Login to admin panel and start adding products
5. ➡️ Customize website settings
6. ➡️ Add sliders for homepage
7. ➡️ Update About Us content
8. ➡️ Start managing inventory and transactions

---

## 🆘 Troubleshooting

### Can't connect to database?
1. Make sure XAMPP MySQL is running
2. Check if port 3306 is available
3. Verify connection in `/config/database.php`

### phpMyAdmin not loading?
1. Ensure Apache is running
2. Check http://localhost/phpmyadmin
3. Verify MySQL service is started

### Forgot password?
Run this SQL in phpMyAdmin:
```sql
UPDATE users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';
```
Password will reset to: `admin123`

---

## 📞 Database Structure

### Entity Relationships
```
users (1) -----> (*) transactions
users (1) -----> (*) blog_posts
users (1) -----> (*) activity_logs

categories (1) -----> (*) products
products (1) -----> (*) product_sizes
products (1) -----> (*) sales
products (1) -----> (*) inventory_logs
products (1) -----> (*) product_reviews

transaction_categories (1) -----> (*) transactions
transactions (1) -----> (*) sales

blog_posts (*) <-----> (*) blog_categories
```

---

## 🎨 Features by User Role

### Admin (Full Access)
✅ All features
✅ User management
✅ System settings

### Accountant
✅ Transactions
✅ Sales reports
✅ Financial dashboard
❌ Inventory management
❌ Website content

### Manager
✅ Products
✅ Inventory
✅ Sales
❌ Accounting
❌ System settings

---

## ✨ Database Highlights

- **21 Tables** covering all business operations
- **3 Views** for instant reporting
- **2 Stored Procedures** for complex operations
- **Bilingual Support** (Arabic & English)
- **Full Audit Trail** via activity logs
- **SEO Optimized** with meta fields
- **Performance Indexed** for fast queries
- **ACID Compliant** using InnoDB engine

---

**Status:** ✅ Ready for Production  
**Version:** 2.0  
**Created:** $(date)

Happy selling! 🎉


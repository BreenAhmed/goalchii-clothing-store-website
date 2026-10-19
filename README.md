# GoalChii Sports - Sports Clothing Business Management System

A comprehensive web-based management system for sports clothing businesses with inventory management and accounting features.

## Features

### 🔐 Admin Panel
- Secure login system with session management
- User profile management
- Password change functionality

### 📦 Inventory Management
- Add, edit, and delete products
- Manage multiple sizes per product with individual stock quantities
- Track product costs and selling prices
- Product categorization
- Low stock alerts
- Inventory movement history
- Product image support

### 💰 Accounting System
- Calendar-based transaction view
- Daily income and expense tracking
- Monthly profit/loss calculations
- Transaction categorization
- Detailed transaction history
- Export functionality
- Visual dashboard with financial summaries

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP (recommended for local development)

### Setup Instructions

1. **Clone or copy the project to your web server directory**
   ```
   /Applications/XAMPP/xamppfiles/htdocs/goalchii/
   ```

2. **Create the database**
   - Open phpMyAdmin or MySQL command line
   - Run the SQL script located at:
   ```
   database/schema.sql
   ```

3. **Configure database connection**
   - Open `config/database.php`
   - Update the database credentials if needed:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'goalchii_sports');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **Access the application**
   - Navigate to: `http://localhost/goalchii/`
   - Default login credentials:
     - Username: `admin`
     - Password: `admin123`

## Usage

### Products Management
1. Navigate to Inventory → Products
2. Click "Add New Product" to add products
3. Enter product details including sizes and quantities
4. Monitor stock levels from the dashboard

### Accounting
1. Navigate to Accounting → Dashboard
2. Click on any date to add transactions
3. View monthly summaries and profit calculations
4. Export transaction data as needed

### Categories
- Manage product categories from Inventory → Categories
- Manage transaction categories are pre-configured but can be modified in the database

## Security Features
- Password hashing using bcrypt
- CSRF token protection
- Session-based authentication
- Input sanitization
- Prepared statements for database queries

## File Structure
```
goalchii/
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   └── accounting.css
│   └── js/
│       └── main.js
├── config/
│   ├── database.php
│   └── session.php
├── database/
│   └── schema.sql
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── helpers.php
├── index.php
├── login.php
├── logout.php
├── dashboard.php
├── profile.php
├── products.php
├── add-product.php
├── edit-product.php
├── view-product.php
├── categories.php
├── accounting.php
├── transactions.php
├── add-transaction.php
├── edit-transaction.php
└── README.md
```

## Troubleshooting

### Common Issues

1. **Database connection error**
   - Verify MySQL is running
   - Check database credentials in `config/database.php`
   - Ensure database exists

2. **Login issues**
   - Verify the database was imported correctly
   - Check if the users table contains data
   - Try resetting the password in the database

3. **Missing styles or JavaScript**
   - Ensure all files are properly uploaded
   - Check file permissions
   - Clear browser cache

## Support
For issues or questions, please check the code documentation or database schema.

## License
This project is proprietary software for GoalChii Sports business use.

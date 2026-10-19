<?php
require_once 'config/session.php';
require_once 'includes/helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>GoalChii Sports</title>
    <link rel="icon" type="image/png" href="assets/images/logo/logo.png">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="main-wrapper">
    <?php if (isLoggedIn()): ?>
    <!-- Modern Creative Navigation -->
    <nav class="modern-navbar">
        <div class="container-fluid px-4">
            <div class="navbar-content">
                <!-- Brand -->
                <a class="brand-logo" href="dashboard.php">
                    <div class="brand-icon">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div class="brand-text">
                        <span class="brand-name">GoalChii</span>
                        <span class="brand-tagline">Sports Management</span>
                    </div>
                </a>
                
                <!-- Navigation Menu -->
                <div class="navbar-menu-wrapper">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a class="nav-link <?php echo isCurrentPage('dashboard') ? 'active' : ''; ?>" href="dashboard.php">
                                <span class="nav-icon">📊</span>
                                <span class="nav-text">Dashboard</span>
                            </a>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link <?php echo in_array(getCurrentPage(), ['products', 'add-product', 'edit-product', 'view-product', 'categories']) ? 'active' : ''; ?>" 
                               href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="nav-icon">👕</span>
                                <span class="nav-text">Inventory</span>
                                <i class="bi bi-chevron-down ms-1"></i>
                            </a>
                            <ul class="modern-dropdown dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="products.php">
                                        <div class="dropdown-icon">📦</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">All Products</div>
                                            <div class="dropdown-desc">View inventory</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="add-product.php">
                                        <div class="dropdown-icon">➕</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Add Product</div>
                                            <div class="dropdown-desc">New item</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="categories.php">
                                        <div class="dropdown-icon">🏷️</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Categories</div>
                                            <div class="dropdown-desc">Manage groups</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link <?php echo in_array(getCurrentPage(), ['accounting', 'transactions', 'add-transaction', 'edit-transaction']) ? 'active' : ''; ?>" 
                               href="#" id="accountingDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="nav-icon">💰</span>
                                <span class="nav-text">Accounting</span>
                                <i class="bi bi-chevron-down ms-1"></i>
                            </a>
                            <ul class="modern-dropdown dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="accounting.php">
                                        <div class="dropdown-icon">📈</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Dashboard</div>
                                            <div class="dropdown-desc">Overview</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="transactions.php">
                                        <div class="dropdown-icon">💳</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Transactions</div>
                                            <div class="dropdown-desc">View history</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="add-transaction.php">
                                        <div class="dropdown-icon">➕</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Add Transaction</div>
                                            <div class="dropdown-desc">New entry</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link <?php echo in_array(getCurrentPage(), ['ecommerce-dashboard', 'manage-ecommerce-products', 'add-ecommerce-product', 'edit-ecommerce-product', 'manage-sliders', 'manage-clubs', 'add-club', 'edit-club', 'manage-blog', 'manage-messages', 'manage-orders', 'whatsapp-logs', 'website-settings']) ? 'active' : ''; ?>"
                               href="#" id="ecommerceDropdown" role="button" data-bs-toggle="dropdown">
                                <span class="nav-icon">🌐</span>
                                <span class="nav-text">E-Commerce</span>
                                <i class="bi bi-chevron-down ms-1"></i>
                            </a>
                            <ul class="modern-dropdown dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="ecommerce-dashboard.php">
                                        <div class="dropdown-icon">📊</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Website Dashboard</div>
                                            <div class="dropdown-desc">Manage content</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage-ecommerce-products.php">
                                        <div class="dropdown-icon">👕</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Products</div>
                                            <div class="dropdown-desc">E-commerce products</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage-blog.php">
                                        <div class="dropdown-icon">📰</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Blog</div>
                                            <div class="dropdown-desc">Posts & articles</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage-orders.php">
                                        <div class="dropdown-icon">🛒</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Orders</div>
                                            <div class="dropdown-desc">Website requests</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="manage-clubs.php">
                                        <div class="dropdown-icon">🏆</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Clubs</div>
                                            <div class="dropdown-desc">Our Clubs section</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="manage-messages.php">
                                        <div class="dropdown-icon">💬</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Messages</div>
                                            <div class="dropdown-desc">Customer inquiries</div>
                                        </div>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="whatsapp-logs.php">
                                        <div class="dropdown-icon">📱</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">WhatsApp Logs</div>
                                            <div class="dropdown-desc">Message history</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="store/index.php" target="_blank">
                                        <div class="dropdown-icon">🚀</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">View Website</div>
                                            <div class="dropdown-desc">Preview store</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                    
                    <!-- User Menu -->
                    <div class="user-menu">
                        <div class="nav-item dropdown">
                            <a class="user-profile" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <i class="bi bi-person-circle"></i>
                                    <span class="status-dot"></span>
                                </div>
                                <div class="user-info d-none d-lg-block">
                                    <div class="user-name"><?php echo $_SESSION['user_name']; ?></div>
                                    <div class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></div>
                                </div>
                                <i class="bi bi-chevron-down ms-2 d-none d-lg-inline"></i>
                            </a>
                            <ul class="modern-dropdown dropdown-menu dropdown-menu-end">
                                <li class="dropdown-header">
                                    <div class="user-dropdown-header">
                                        <div class="user-dropdown-avatar">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?php echo $_SESSION['user_name']; ?></div>
                                            <small class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?></small>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <div class="dropdown-icon">👤</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">My Profile</div>
                                            <div class="dropdown-desc">Account settings</div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <div class="dropdown-icon">🚪</div>
                                        <div class="dropdown-content">
                                            <div class="dropdown-title">Logout</div>
                                            <div class="dropdown-desc">Sign out</div>
                                        </div>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Flash Messages -->
    <?php if ($flash = getFlashMessage()): ?>
    <div class="container-fluid px-4 mt-3">
        <div class="alert <?php echo getAlertClass($flash['type']); ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?php echo $flash['type'] == 'success' ? 'check-circle' : ($flash['type'] == 'error' ? 'x-circle' : 'info-circle'); ?> me-2"></i>
            <?php echo $flash['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

<style>
/* Modern Navbar Styles */
.modern-navbar {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(255, 255, 255, 0.95));
    backdrop-filter: blur(20px);
    box-shadow: 0 8px 32px rgba(245, 158, 11, 0.15);
    padding: 0.75rem 0;
    position: sticky;
    top: 0;
    z-index: 1000;
    border-bottom: 3px solid transparent;
    border-image: linear-gradient(90deg, #f59e0b, #f97316, #ec4899) 1;
}

.navbar-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.navbar-menu-wrapper {
    display: flex;
    align-items: center;
    gap: 2rem;
    flex: 1;
    justify-content: space-between;
}

/* Brand Logo */
.brand-logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.brand-logo:hover {
    transform: scale(1.05);
}

.brand-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.75rem;
    color: white;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
    animation: iconBounce 2s infinite;
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(5deg); }
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand-name {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 1.5rem;
    background: linear-gradient(135deg, #f59e0b, #f97316, #ec4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
}

.brand-tagline {
    font-size: 0.75rem;
    color: #78716c;
    font-weight: 500;
}

/* Navigation Menu */
.nav-menu {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 12px;
    text-decoration: none;
    color: #44403c;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    position: relative;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(245, 158, 11, 0.2), transparent);
    transition: left 0.5s;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(236, 72, 153, 0.1));
    color: #f59e0b;
    transform: translateY(-2px);
}

.nav-link.active {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
}

.nav-link.active .nav-icon {
    animation: iconPulse 1.5s infinite;
}

@keyframes iconPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

.nav-icon {
    font-size: 1.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.nav-text {
    font-size: 0.95rem;
}

/* Modern Dropdown */
.modern-dropdown {
    border: none;
    border-radius: 16px;
    box-shadow: 0 15px 40px rgba(245, 158, 11, 0.25);
    padding: 0.75rem;
    min-width: 280px;
    margin-top: 0.5rem;
    background: white;
    border: 2px solid rgba(245, 158, 11, 0.2);
}

.modern-dropdown .dropdown-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    border: none;
}

.modern-dropdown .dropdown-item:hover {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(236, 72, 153, 0.1));
    transform: translateX(5px);
}

.dropdown-icon {
    font-size: 1.75rem;
    flex-shrink: 0;
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(236, 72, 153, 0.1));
    border-radius: 10px;
}

.dropdown-content {
    flex: 1;
}

.dropdown-title {
    font-weight: 600;
    color: #292524;
    font-size: 0.95rem;
}

.dropdown-desc {
    font-size: 0.75rem;
    color: #78716c;
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-color: rgba(245, 158, 11, 0.2);
}

/* User Menu */
.user-menu {
    margin-left: auto;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(236, 72, 153, 0.1));
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.user-profile:hover {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(236, 72, 153, 0.2));
    border-color: rgba(245, 158, 11, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(245, 158, 11, 0.2);
}

.user-avatar {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    border-radius: 50%;
    color: white;
    font-size: 1.5rem;
    box-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);
}

.status-dot {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 12px;
    height: 12px;
    background: #10b981;
    border: 2px solid white;
    border-radius: 50%;
    animation: statusPulse 2s infinite;
}

@keyframes statusPulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.user-name {
    font-weight: 700;
    color: #292524;
    font-size: 0.9rem;
    line-height: 1;
}

.user-role {
    font-size: 0.75rem;
    color: #78716c;
}

.user-dropdown-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem;
}

.user-dropdown-avatar {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f59e0b, #f97316);
    border-radius: 50%;
    color: white;
    font-size: 2rem;
}

/* Responsive */
@media (max-width: 1199px) {
    .brand-name {
        font-size: 1.25rem;
    }
    
    .brand-tagline {
        font-size: 0.65rem;
    }
    
    .nav-icon {
        font-size: 1.1rem;
    }
    
    .nav-text {
        font-size: 0.85rem;
    }
}

@media (max-width: 991px) {
    .navbar-content {
        flex-wrap: wrap;
    }
    
    .navbar-menu-wrapper {
        width: 100%;
        flex-direction: column;
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .nav-menu {
        flex-direction: column;
        width: 100%;
        gap: 0.75rem;
    }
    
    .nav-link {
        width: 100%;
        justify-content: flex-start;
    }
    
    .user-menu {
        width: 100%;
        margin-left: 0;
    }
    
    .user-profile {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .brand-icon {
        width: 40px;
        height: 40px;
        font-size: 1.5rem;
    }
    
    .brand-name {
        font-size: 1.1rem;
    }
    
    .brand-tagline {
        display: none;
    }
}
</style>
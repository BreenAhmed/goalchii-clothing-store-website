<?php
// E-commerce Website Management Dashboard
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'E-Commerce Dashboard';

// Initialize variables
$total_blog_posts = 0;
$total_products = 0;
$unread_messages = 0;
$draft_posts = 0;
$total_orders = 0;
$new_orders = 0;
$recent_messages = [];
$recent_posts = [];
$recent_products = [];
$recent_orders = [];
$error = null;

// Get statistics
try {
    $pdo = getDBConnection();
    
    // Check if is_ecommerce column exists
    $columnExists = false;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_ecommerce'");
        $columnExists = $stmt->fetch() !== false;
    } catch (Exception $e) {
        // Column doesn't exist
    }
    
    // Get counts
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_requests (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            order_number VARCHAR(50) NOT NULL,
            customer_name VARCHAR(150) NOT NULL,
            customer_email VARCHAR(150) NOT NULL,
            customer_phone VARCHAR(30),
            city VARCHAR(100),
            postal_code VARCHAR(30),
            country VARCHAR(100) DEFAULT 'Iraq',
            shipping_address TEXT,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_method VARCHAR(50) DEFAULT 'cash_on_delivery',
            customer_notes TEXT,
            items_json LONGTEXT,
            status ENUM('new','processing','completed','cancelled') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_number (order_number),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $total_blog_posts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn() ?: 0;
    
    // Only count e-commerce products (not inventory products) if column exists
    if ($columnExists) {
        $total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND is_ecommerce = 1")->fetchColumn() ?: 0;
    } else {
        $total_products = 0; // Column doesn't exist, show 0 until migration is run
    }
    
    $unread_messages = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn() ?: 0;
    $draft_posts = $pdo->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'")->fetchColumn() ?: 0;
    $total_orders = $pdo->query("SELECT COUNT(*) FROM order_requests")->fetchColumn() ?: 0;
    $new_orders = $pdo->query("SELECT COUNT(*) FROM order_requests WHERE status = 'new'")->fetchColumn() ?: 0;
    
    // Recent contact messages
    $recent_messages = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5")->fetchAll() ?: [];
    
    // Recent blog posts
    $recent_posts = $pdo->query("SELECT bp.*, u.full_name as author_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id ORDER BY bp.created_at DESC LIMIT 5")->fetchAll() ?: [];
    
    // Recent e-commerce products (only for website posting, not inventory)
    if ($columnExists) {
        $recent_products = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND p.is_ecommerce = 1 ORDER BY p.created_at DESC LIMIT 5")->fetchAll() ?: [];
    } else {
        $recent_products = []; // Column doesn't exist, show empty until migration is run
    }

    $recent_orders = $pdo->query("SELECT * FROM order_requests ORDER BY status = 'new' DESC, created_at DESC LIMIT 6")->fetchAll() ?: [];
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="border-left: 6px solid #8b5cf6; background: linear-gradient(135deg, rgba(139, 92, 246, 0.05), rgba(236, 72, 153, 0.05));">
                <div class="card-body py-3">
                    <h1 class="page-title mb-0 d-inline">
                        🌐 E-Commerce Website Dashboard
                    </h1>
                    <p class="text-muted mb-0 mt-2">
                        <i class="bi bi-calendar-check me-2"></i><?php echo date('l, F j, Y'); ?> - Manage your website content
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php 
    // Check if migration is needed
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'is_ecommerce'");
        $needsMigration = !$stmt->fetch();
    } catch (Exception $e) {
        $needsMigration = true;
    }
    
    if ($needsMigration): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Database Migration Required:</strong> The <code>is_ecommerce</code> column is missing. 
            <a href="run-migration.php" class="alert-link">Run migration now</a> to enable e-commerce product management.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white" style="background: linear-gradient(135deg, #10b981, #22d3ee); position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">🛒 Orders</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($total_orders); ?></h2>
                            <small class="text-white-50"><?php echo number_format($new_orders); ?> new</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-bag-check stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="manage-orders.php" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>Manage orders</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-success" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">📰 Published Posts</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($total_blog_posts); ?></h2>
                            <small class="text-white-50">Blog articles</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-newspaper stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="manage-blog.php" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>Manage blog</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-warning" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">💬 New Messages</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($unread_messages); ?></h2>
                            <small class="text-white-50">Unread inquiries</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-envelope stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="manage-messages.php" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>View messages</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-secondary" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">📝 Draft Posts</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($draft_posts); ?></h2>
                            <small class="text-white-50">Pending articles</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-file-text stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="manage-blog.php?status=draft" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>View drafts</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Statistics Row -->
    <div class="row mb-4">
        <div class="col-md-12 mb-3">
            <div class="card stat-card text-white" style="background: linear-gradient(135deg, #8b5cf6, #ec4899); position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">🛍️ Published Products</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($total_products); ?></h2>
                            <small class="text-white-50">Active products on website</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-box-seam stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="manage-ecommerce-products.php" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>Manage products</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightning-fill me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2 col-sm-4 col-6">
                            <a href="add-blog-post.php" class="btn btn-outline-success w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-pencil-square fs-3 mb-2"></i>
                                <span>New Post</span>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <a href="manage-about.php" class="btn btn-outline-info w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-info-circle-fill fs-3 mb-2"></i>
                                <span>About Page</span>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <a href="website-settings.php" class="btn btn-outline-warning w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-gear-fill fs-3 mb-2"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <a href="add-ecommerce-product.php" class="btn btn-outline-purple w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-box-seam fs-3 mb-2"></i>
                                <span>Add Product</span>
                            </a>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <a href="store/index.php" target="_blank" class="btn btn-outline-primary w-100 d-flex flex-column align-items-center py-3">
                                <i class="bi bi-globe fs-3 mb-2"></i>
                                <span>View Site</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bag-heart me-2"></i>Recent Orders</h5>
                    <a href="manage-orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (!empty($recent_orders)): ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <?php $items = json_decode($order['items_json'] ?? '[]', true) ?: []; ?>
                            <div class="border-bottom pb-3 mb-3 <?php echo $order['status'] === 'new' ? 'bg-light p-3 rounded' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-0">#<?php echo htmlspecialchars($order['order_number']); ?></h6>
                                        <p class="text-muted mb-1 small">
                                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($order['customer_name']); ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-<?php echo $order['status'] === 'new' ? 'primary' : ($order['status'] === 'processing' ? 'warning text-dark' : ($order['status'] === 'completed' ? 'success' : 'secondary')); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-2 small">
                                    <i class="bi bi-clock me-1"></i><?php echo formatDate($order['created_at'], 'M j, Y - g:i A'); ?>
                                </p>
                                <p class="mb-1 fw-semibold">
                                    <?php echo number_format($order['total_amount']); ?> د.ع
                                </p>
                                <?php if (!empty($items)): ?>
                                    <p class="text-muted small mb-2">
                                        <?php echo count($items); ?> item<?php echo count($items) > 1 ? 's' : ''; ?> • <?php echo htmlspecialchars($items[0]['product_name']); ?><?php echo count($items) > 1 ? ' +' . (count($items) - 1) : ''; ?>
                                    </p>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <a href="manage-orders.php#order-<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                        Manage
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-bag-x fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No orders yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Products -->
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Recent Products</h5>
                    <a href="manage-ecommerce-products.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (!empty($recent_products)): ?>
                        <?php foreach ($recent_products as $product): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex align-items-start gap-2 mb-2">
                                    <?php 
                                    // Get primary image from product_images table if available
                                    $displayImage = null;
                                    try {
                                        $tableCheck = $pdo->query("SHOW TABLES LIKE 'product_images'")->fetch();
                                        if ($tableCheck) {
                                            $imgStmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY display_order ASC LIMIT 1");
                                            $imgStmt->execute([$product['id']]);
                                            $primaryImg = $imgStmt->fetch();
                                            if ($primaryImg) {
                                                $displayImage = $primaryImg['image_url'];
                                            }
                                        }
                                    } catch (Exception $e) {
                                        // Table doesn't exist or error
                                    }
                                    
                                    // Fallback to main image_url
                                    if (!$displayImage && $product['image_url']) {
                                        $displayImage = $product['image_url'];
                                    }
                                    
                                    if ($displayImage): 
                                        // Check if it's a full URL or local path
                                        if (!preg_match('/^https?:\/\//', $displayImage)) {
                                            // It's a local file path
                                            $displayImage = '/' . ltrim($displayImage, '/');
                                        }
                                    ?>
                                        <img src="<?php echo htmlspecialchars($displayImage); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;"
                                             onerror="this.parentElement.innerHTML='<div class=\'bg-secondary text-white d-flex align-items-center justify-content-center\' style=\'width: 60px; height: 60px; border-radius: 8px;\'><i class=\'bi bi-image\'></i></div>'">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px; border-radius: 8px;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </h6>
                                            <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </div>
                                        <p class="text-muted mb-1 small">
                                            <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                            <span class="ms-2"><i class="bi bi-currency-exchange me-1"></i><?php echo formatCurrency($product['price']); ?></span>
                                        </p>
                                        <p class="text-muted mb-2 small">
                                            <i class="bi bi-calendar3 me-1"></i><?php echo formatDate($product['created_at'], 'M j, Y'); ?>
                                        </p>
                                        <div class="d-flex gap-2">
                                            <a href="edit-ecommerce-product.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <a href="store/products.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-box fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No products yet</p>
                            <a href="add-ecommerce-product.php" class="btn btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Add First Product
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Recent Messages</h5>
                    <a href="manage-messages.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (!empty($recent_messages)): ?>
                        <?php foreach ($recent_messages as $msg): ?>
                            <div class="border-bottom pb-3 mb-3 <?php echo !$msg['is_read'] ? 'bg-light p-3 rounded' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($msg['name']); ?>
                                        <?php if (!$msg['is_read']): ?>
                                            <span class="badge bg-primary ms-2">New</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted"><?php echo formatDate($msg['created_at'], 'M j, Y'); ?></small>
                                </div>
                                <p class="text-muted mb-1 small">
                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($msg['email']); ?>
                                </p>
                                <?php if ($msg['subject']): ?>
                                    <p class="fw-bold mb-1">Subject: <?php echo htmlspecialchars($msg['subject']); ?></p>
                                <?php endif; ?>
                                <p class="mb-2 small"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . (strlen($msg['message']) > 100 ? '...' : ''); ?></p>
                                <a href="view-message.php?id=<?php echo $msg['id']; ?>" class="btn btn-sm btn-primary">
                                    View Message
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No messages yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Blog Posts -->
        <div class="col-xl-3 col-lg-6 mb-4">
            <div class="card modern-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-newspaper me-2"></i>Recent Blog Posts</h5>
                    <a href="manage-blog.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <?php if (!empty($recent_posts)): ?>
                        <?php foreach ($recent_posts as $post): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">
                                        <?php echo htmlspecialchars($post['title_ar'] ?? $post['title_en'] ?? 'Untitled'); ?>
                                    </h6>
                                    <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($post['status']); ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-1 small">
                                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?>
                                    <span class="ms-2"><i class="bi bi-calendar3 me-1"></i><?php echo formatDate($post['created_at'], 'M j, Y'); ?></span>
                                </p>
                                <?php if ($post['excerpt_ar'] ?? $post['excerpt_en']): ?>
                                    <p class="mb-2 small"><?php echo htmlspecialchars(substr($post['excerpt_ar'] ?? $post['excerpt_en'], 0, 80)) . '...'; ?></p>
                                <?php endif; ?>
                                <div class="d-flex gap-2">
                                    <a href="edit-blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <?php if ($post['status'] === 'published'): ?>
                                        <a href="store/blog-detail.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-journal-x fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No blog posts yet</p>
                            <a href="add-blog-post.php" class="btn btn-primary mt-2">
                                <i class="bi bi-plus-circle"></i> Add First Post
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Links -->
    <div class="row">
        <div class="col-12">
            <div class="card modern-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Website Content Management</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="manage-blog.php" class="card text-decoration-none border h-100 hover-lift">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                            <i class="bi bi-newspaper fs-3 text-success"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Manage Blog</h6>
                                            <small class="text-muted">Create and publish articles</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="manage-messages.php" class="card text-decoration-none border h-100 hover-lift">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                            <i class="bi bi-chat-dots fs-3 text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Customer Messages</h6>
                                            <small class="text-muted">View and respond to inquiries</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="manage-about.php" class="card text-decoration-none border h-100 hover-lift">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                            <i class="bi bi-info-circle fs-3 text-info"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">About Page</h6>
                                            <small class="text-muted">Update company information</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="website-settings.php" class="card text-decoration-none border h-100 hover-lift">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                            <i class="bi bi-gear fs-3 text-warning"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Website Settings</h6>
                                            <small class="text-muted">Contact info and social media</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <div class="col-md-4">
                            <a href="manage-ecommerce-products.php" class="card text-decoration-none border h-100 hover-lift">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-purple bg-opacity-10 p-3 rounded me-3" style="background: rgba(139, 92, 246, 0.1);">
                                            <i class="bi bi-box-seam fs-3" style="color: #8b5cf6;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Manage E-Commerce Products</h6>
                                            <small class="text-muted">Post products on website</small>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

.btn-outline-purple {
    color: #8b5cf6;
    border-color: #8b5cf6;
}

.btn-outline-purple:hover {
    background-color: #8b5cf6;
    border-color: #8b5cf6;
    color: white;
}
</style>

<?php include 'includes/footer.php'; ?>

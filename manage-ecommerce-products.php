<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Manage E-Commerce Products';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (verifyCSRFToken($_GET['csrf'])) {
        $productId = (int)$_GET['delete'];
        try {
            $stmt = db()->prepare("UPDATE products SET is_active = 0 WHERE id = ? AND is_ecommerce = 1");
            $stmt->execute([$productId]);
            setFlashMessage('success', 'Product deleted successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting product.');
        }
    } else {
        setFlashMessage('error', 'Invalid request.');
    }
    redirect('manage-ecommerce-products.php');
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Build query - ONLY e-commerce products
$query = "
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1 AND p.is_ecommerce = 1
";

$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.name_ar LIKE ? OR p.name_ku LIKE ? OR p.product_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId) {
    $query .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

if ($filter == 'featured') {
    $query .= " AND p.featured = 1";
}

$query .= " ORDER BY p.created_at DESC";

try {
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = db()->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading products.');
    $products = [];
    $categories = [];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="page-title mb-0">
                🛍️ E-Commerce Products
            </h1>
            <p class="text-muted">Manage products for your website store (separate from inventory)</p>
        </div>
        <div class="col-auto">
            <a href="add-ecommerce-product.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle me-2"></i> Add New Product
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="manage-ecommerce-products.php" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Product name or code..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter" class="form-label">Filter</label>
                    <select class="form-select" id="filter" name="filter">
                        <option value="">All Products</option>
                        <option value="featured" <?php echo $filter == 'featured' ? 'selected' : ''; ?>>Featured Only</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-box fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No e-commerce products found.</p>
                        <a href="add-ecommerce-product.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add First Product
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100">
                    <?php 
                    // Get primary image from product_images table if available
                    $displayImage = null;
                    try {
                        $tableCheck = db()->query("SHOW TABLES LIKE 'product_images'")->fetch();
                        if ($tableCheck) {
                            $imgStmt = db()->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_primary = 1 ORDER BY display_order ASC LIMIT 1");
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
                             class="card-img-top" style="height: 200px; object-fit: cover;"
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'">
                    <?php else: ?>
                        <div class="bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-image fs-1 text-white-50"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="card-title mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <?php
                                $langBadges = [];
                                if (!empty($product['name_ar']) || !empty($product['description_ar'])) {
                                    $langBadges[] = 'AR';
                                }
                                if (!empty($product['name_ku']) || !empty($product['description_ku'])) {
                                    $langBadges[] = 'KU';
                                }
                                if (!empty($langBadges)):
                                ?>
                                    <div class="mt-1">
                                        <?php foreach ($langBadges as $badge): ?>
                                            <span class="badge bg-light text-dark border me-1"><?php echo $badge; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($product['featured']): ?>
                                <span class="badge bg-warning">⭐ Featured</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted small mb-2">
                            <i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                        </p>
                        <p class="mb-2">
                            <strong class="text-primary"><?php echo formatCurrency($product['price']); ?></strong>
                            <?php if ($product['old_price']): ?>
                                <small class="text-muted text-decoration-line-through ms-2"><?php echo formatCurrency($product['old_price']); ?></small>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted small mb-3">
                            <i class="bi bi-calendar3 me-1"></i><?php echo formatDate($product['created_at'], 'M j, Y'); ?>
                        </p>
                        <div class="d-flex gap-2">
                            <a href="edit-ecommerce-product.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="store/products.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-primary flex-fill" target="_blank">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <a href="manage-ecommerce-products.php?delete=<?php echo $product['id']; ?>&csrf=<?php echo generateCSRFToken(); ?>" 
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Are you sure you want to delete this product?');">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


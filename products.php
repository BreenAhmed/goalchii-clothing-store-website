<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Products';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (verifyCSRFToken($_GET['csrf'])) {
        $productId = (int)$_GET['delete'];
        try {
            // Only allow deleting inventory products (not e-commerce products)
            $stmt = db()->prepare("UPDATE products SET is_active = 0 WHERE id = ? AND (is_ecommerce = 0 OR is_ecommerce IS NULL)");
            $stmt->execute([$productId]);
            if ($stmt->rowCount() > 0) {
                setFlashMessage('success', 'Product deleted successfully.');
            } else {
                setFlashMessage('error', 'Cannot delete e-commerce products from inventory section.');
            }
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting product.');
        }
    } else {
        setFlashMessage('error', 'Invalid request.');
    }
    redirect('products.php');
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Build query - Only show inventory products (exclude e-commerce products)
$query = "
    SELECT p.*, c.name as category_name,
           COALESCE(SUM(ps.quantity), 0) as total_quantity,
           COUNT(DISTINCT ps.size) as size_count
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_sizes ps ON p.id = ps.product_id
    WHERE p.is_active = 1
    AND (p.is_ecommerce = 0 OR p.is_ecommerce IS NULL)
";

$params = [];

if ($search) {
    $query .= " AND (p.name LIKE ? OR p.product_code LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryId) {
    $query .= " AND p.category_id = ?";
    $params[] = $categoryId;
}

$query .= " GROUP BY p.id";

if ($filter == 'low-stock') {
    $query .= " HAVING total_quantity < 10";
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
                👕 Products Inventory
            </h1>
            <p class="text-muted">Manage your sports clothing collection</p>
        </div>
        <div class="col-auto">
            <a href="add-product.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle me-2"></i> Add New Product
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="products.php" class="row g-3">
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
                        <option value="low-stock" <?php echo $filter == 'low-stock' ? 'selected' : ''; ?>>Low Stock Only</option>
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
    
    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($products)): ?>
                <p class="text-center text-muted py-4">No products found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Cost</th>
                                <th>Sizes</th>
                                <th>Total Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if ($product['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <i class="bi bi-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td><?php echo formatCurrency($product['cost']); ?></td>
                                <td><?php echo $product['size_count']; ?> sizes</td>
                                <td>
                                    <span class="badge bg-<?php echo $product['total_quantity'] < 10 ? 'danger' : ($product['total_quantity'] < 50 ? 'warning' : 'success'); ?>">
                                        <?php echo $product['total_quantity']; ?> pcs
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="view-product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" 
                                           class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="products.php?delete=<?php echo $product['id']; ?>&csrf=<?php echo generateCSRFToken(); ?>" 
                                           class="btn btn-danger" title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this product?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

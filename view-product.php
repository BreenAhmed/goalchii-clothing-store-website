<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'View Product';

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('products.php');
}

// Get product data
try {
    $stmt = db()->prepare("
        SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        setFlashMessage('error', 'Product not found.');
        redirect('products.php');
    }
    
    // Get product sizes
    $stmt = db()->prepare("SELECT * FROM product_sizes WHERE product_id = ? ORDER BY size");
    $stmt->execute([$productId]);
    $productSizes = $stmt->fetchAll();
    
    // Calculate total stock
    $totalStock = 0;
    foreach ($productSizes as $size) {
        $totalStock += $size['quantity'];
    }
    
    // Get inventory logs
    $stmt = db()->prepare("
        SELECT il.*, u.full_name as created_by_name
        FROM inventory_logs il
        LEFT JOIN users u ON il.created_by = u.id
        WHERE il.product_id = ?
        ORDER BY il.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$productId]);
    $inventoryLogs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading product.');
    redirect('products.php');
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Product Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="img-fluid rounded">
                            <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" 
                                     style="height: 300px;">
                                    <i class="bi bi-image" style="font-size: 4rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-muted mb-3">Code: <?php echo htmlspecialchars($product['product_code']); ?></p>
                            
                            <dl class="row">
                                <dt class="col-sm-4">Category:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></dd>
                                
                                <dt class="col-sm-4">Selling Price:</dt>
                                <dd class="col-sm-8"><strong><?php echo formatCurrency($product['price']); ?></strong></dd>
                                
                                <dt class="col-sm-4">Cost Price:</dt>
                                <dd class="col-sm-8"><?php echo formatCurrency($product['cost']); ?></dd>
                                
                                <dt class="col-sm-4">Profit Margin:</dt>
                                <dd class="col-sm-8">
                                    <?php 
                                    $profit = $product['price'] - $product['cost'];
                                    $profitMargin = ($profit / $product['price']) * 100;
                                    ?>
                                    <?php echo formatCurrency($profit); ?> 
                                    <span class="text-muted">(<?php echo number_format($profitMargin, 1); ?>%)</span>
                                </dd>
                                
                                <dt class="col-sm-4">Total Stock:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?php echo $totalStock < 10 ? 'danger' : ($totalStock < 50 ? 'warning' : 'success'); ?> fs-6">
                                        <?php echo $totalStock; ?> pieces
                                    </span>
                                </dd>
                            </dl>
                            
                            <?php if ($product['description']): ?>
                            <hr>
                            <h5>Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Product
                    </a>
                    <a href="products.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
            
            <!-- Stock by Size -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Stock by Size</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($productSizes as $size): ?>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="text-center">
                                <div class="fs-1 fw-bold <?php echo $size['quantity'] == 0 ? 'text-danger' : ($size['quantity'] < 5 ? 'text-warning' : 'text-success'); ?>">
                                    <?php echo $size['quantity']; ?>
                                </div>
                                <div class="text-muted">Size <?php echo htmlspecialchars($size['size']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Inventory History -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Inventory History</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($inventoryLogs)): ?>
                        <p class="text-muted mb-0">No inventory history available.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Quantity</th>
                                        <th>Reason</th>
                                        <th>By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventoryLogs as $log): ?>
                                    <tr>
                                        <td><?php echo formatDate($log['created_at'], 'M d, Y g:i A'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $log['movement_type'] == 'in' ? 'success' : ($log['movement_type'] == 'out' ? 'danger' : 'info'); ?>">
                                                <?php echo ucfirst($log['movement_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['size']); ?></td>
                                        <td><?php echo $log['movement_type'] == 'in' ? '+' : '-'; ?><?php echo $log['quantity']; ?></td>
                                        <td><?php echo htmlspecialchars($log['reason'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($log['created_by_name'] ?? 'System'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="d-grid gap-2">
                        <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Product
                        </a>
                        <a href="add-product.php" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Add New Product
                        </a>
                        <button class="btn btn-info" onclick="window.print();">
                            <i class="bi bi-printer"></i> Print Details
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Product Stats -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product Statistics</h5>
                    <dl>
                        <dt>Created</dt>
                        <dd><?php echo formatDate($product['created_at'], 'M d, Y'); ?></dd>
                        
                        <dt>Last Updated</dt>
                        <dd><?php echo formatDate($product['updated_at'], 'M d, Y'); ?></dd>
                        
                        <dt>Available Sizes</dt>
                        <dd><?php echo count($productSizes); ?> sizes</dd>
                        
                        <dt>Stock Value</dt>
                        <dd><?php echo formatCurrency($totalStock * $product['cost']); ?> (cost)</dd>
                        <dd><?php echo formatCurrency($totalStock * $product['price']); ?> (retail)</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Edit Product';
$errors = [];

// Get product ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    setFlashMessage('error', 'Invalid product ID.');
    redirect('products.php');
}

// Get product data
try {
    $stmt = db()->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        setFlashMessage('error', 'Product not found.');
        redirect('products.php');
    }
    
    // Get product sizes
    $stmt = db()->prepare("SELECT * FROM product_sizes WHERE product_id = ?");
    $stmt->execute([$productId]);
    $productSizes = [];
    while ($row = $stmt->fetch()) {
        $productSizes[$row['size']] = $row['quantity'];
    }
    
    // Get categories
    $stmt = db()->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading product.');
    redirect('products.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $productCode = sanitize($_POST['product_code']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $categoryId = (int)$_POST['category_id'];
        $price = (float)$_POST['price'];
        $cost = (float)$_POST['cost'];
        $imageUrl = sanitize($_POST['image_url']);
        
        // Validate required fields
        if (empty($productCode)) $errors[] = 'Product code is required.';
        if (empty($name)) $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        if ($cost <= 0) $errors[] = 'Cost must be greater than 0.';
        
        // Validate sizes
        $newSizes = [];
        if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            foreach ($_POST['sizes'] as $index => $size) {
                if (!empty($size) && isset($_POST['quantities'][$index])) {
                    $quantity = (int)$_POST['quantities'][$index];
                    if ($quantity < 0) {
                        $errors[] = "Quantity for size $size cannot be negative.";
                    } else {
                        $newSizes[$size] = $quantity;
                    }
                }
            }
        }
        
        if (empty($newSizes)) {
            $errors[] = 'At least one size with quantity is required.';
        }
        
        // Check if product code already exists (excluding current product)
        if (empty($errors)) {
            try {
                $stmt = db()->prepare("SELECT id FROM products WHERE product_code = ? AND id != ?");
                $stmt->execute([$productCode, $productId]);
                if ($stmt->fetch()) {
                    $errors[] = 'Product code already exists.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
        
        // Update product if no errors
        if (empty($errors)) {
            try {
                db()->beginTransaction();
                
                // Update product
                $stmt = db()->prepare("
                    UPDATE products 
                    SET product_code = ?, name = ?, description = ?, category_id = ?, 
                        price = ?, cost = ?, image_url = ?
                    WHERE id = ?
                ");
                $stmt->execute([$productCode, $name, $description, $categoryId ?: null, 
                               $price, $cost, $imageUrl, $productId]);
                
                // Update sizes
                foreach ($newSizes as $size => $newQuantity) {
                    $oldQuantity = isset($productSizes[$size]) ? $productSizes[$size] : 0;
                    
                    if (isset($productSizes[$size])) {
                        // Update existing size
                        $stmt = db()->prepare("
                            UPDATE product_sizes 
                            SET quantity = ? 
                            WHERE product_id = ? AND size = ?
                        ");
                        $stmt->execute([$newQuantity, $productId, $size]);
                    } else {
                        // Insert new size
                        $stmt = db()->prepare("
                            INSERT INTO product_sizes (product_id, size, quantity) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$productId, $size, $newQuantity]);
                    }
                    
                    // Log inventory change if quantity changed (handle different schema formats)
                    if ($newQuantity != $oldQuantity) {
                        try {
                            $tableExists = db()->query("SHOW TABLES LIKE 'inventory_logs'")->fetch();
                            if ($tableExists) {
                                $checkColumns = db()->query("SHOW COLUMNS FROM inventory_logs")->fetchAll(PDO::FETCH_COLUMN);
                                $difference = $newQuantity - $oldQuantity;
                                
                                if (in_array('movement_type', $checkColumns) && in_array('created_by', $checkColumns)) {
                                    // Schema 1: movement_type, quantity, created_by
                                    $movementType = $difference > 0 ? 'in' : 'out';
                                    $stmt = db()->prepare("
                                        INSERT INTO inventory_logs (product_id, size, movement_type, quantity, reason, created_by)
                                        VALUES (?, ?, ?, ?, ?, ?)
                                    ");
                                    $stmt->execute([$productId, $size, $movementType, abs($difference), 
                                                  'Stock adjustment', $_SESSION['user_id']]);
                                } elseif (in_array('quantity_change', $checkColumns) && in_array('changed_by', $checkColumns)) {
                                    // Schema 2: quantity_change, old_quantity, new_quantity, changed_by
                                    $stmt = db()->prepare("
                                        INSERT INTO inventory_logs (product_id, size, quantity_change, old_quantity, new_quantity, reason, changed_by)
                                        VALUES (?, ?, ?, ?, ?, ?, ?)
                                    ");
                                    $stmt->execute([$productId, $size, $difference, $oldQuantity, $newQuantity, 
                                                  'Stock adjustment', $_SESSION['user_id']]);
                                }
                            }
                        } catch (PDOException $logError) {
                            // If inventory logging fails, continue anyway (non-critical)
                            error_log('Inventory logging failed: ' . $logError->getMessage());
                        }
                    }
                }
                
                // Remove sizes that are no longer in the new list
                foreach ($productSizes as $size => $quantity) {
                    if (!isset($newSizes[$size])) {
                        $stmt = db()->prepare("DELETE FROM product_sizes WHERE product_id = ? AND size = ?");
                        $stmt->execute([$productId, $size]);
                        
                        // Log removal (handle different schema formats)
                        if ($quantity > 0) {
                            try {
                                $tableExists = db()->query("SHOW TABLES LIKE 'inventory_logs'")->fetch();
                                if ($tableExists) {
                                    $checkColumns = db()->query("SHOW COLUMNS FROM inventory_logs")->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    if (in_array('movement_type', $checkColumns) && in_array('created_by', $checkColumns)) {
                                        // Schema 1: movement_type, quantity, created_by
                                        $stmt = db()->prepare("
                                            INSERT INTO inventory_logs (product_id, size, movement_type, quantity, reason, created_by)
                                            VALUES (?, ?, 'out', ?, ?, ?)
                                        ");
                                        $stmt->execute([$productId, $size, $quantity, 'Size removed', $_SESSION['user_id']]);
                                    } elseif (in_array('quantity_change', $checkColumns) && in_array('changed_by', $checkColumns)) {
                                        // Schema 2: quantity_change, old_quantity, new_quantity, changed_by
                                        $stmt = db()->prepare("
                                            INSERT INTO inventory_logs (product_id, size, quantity_change, old_quantity, new_quantity, reason, changed_by)
                                            VALUES (?, ?, ?, ?, 0, ?, ?)
                                        ");
                                        $stmt->execute([$productId, $size, -$quantity, $quantity, 'Size removed', $_SESSION['user_id']]);
                                    }
                                }
                            } catch (PDOException $logError) {
                                // If inventory logging fails, continue anyway (non-critical)
                                error_log('Inventory logging failed: ' . $logError->getMessage());
                            }
                        }
                    }
                }
                
                db()->commit();
                setFlashMessage('success', 'Product updated successfully.');
                redirect('products.php');
            } catch (PDOException $e) {
                db()->rollBack();
                // Show actual error for debugging (remove in production)
                $errors[] = 'Error updating product: ' . $e->getMessage();
                // Log error for debugging
                error_log('Product update error: ' . $e->getMessage());
            }
        }
    }
}

// Standard sizes
$standardSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
?>

<?php 
$additionalJS = ['assets/js/product-form.js'];
include 'includes/header.php'; 
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="products.php">Products</a></li>
                    <li class="breadcrumb-item active">Edit Product</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Edit Product</h4>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="edit-product.php?id=<?php echo $productId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="product_code" class="form-label">Product Code *</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" 
                                       required value="<?php echo htmlspecialchars($product['product_code']); ?>">
                                <small class="form-text text-muted">Unique identifier for the product</small>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   required value="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Selling Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="0.01" required value="<?php echo $product['price']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost" name="cost" 
                                           step="0.01" min="0.01" required value="<?php echo $product['cost']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   placeholder="https://example.com/image.jpg"
                                   value="<?php echo htmlspecialchars($product['image_url']); ?>">
                            <small class="form-text text-muted">Enter a URL for the product image</small>
                            <?php if ($product['image_url']): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="Current image" class="img-thumbnail" style="max-width: 200px;">
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Sizes and Stock</h5>
                        
                        <div id="sizes-container">
                            <?php foreach ($standardSizes as $size): ?>
                            <div class="row mb-2 size-row">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="sizes[]" value="<?php echo $size; ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="quantities[]" 
                                           placeholder="Quantity" min="0" 
                                           value="<?php echo isset($productSizes[$size]) ? $productSizes[$size] : 0; ?>">
                                </div>
                                <div class="col-md-4">
                                    <?php if (isset($productSizes[$size]) && $productSizes[$size] > 0): ?>
                                    <span class="text-muted">Current: <?php echo $productSizes[$size]; ?> pcs</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Product
                            </button>
                            <a href="products.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Product Information</h5>
                    <dl>
                        <dt>Created</dt>
                        <dd><?php echo formatDate($product['created_at'], 'M d, Y g:i A'); ?></dd>
                        
                        <dt>Last Updated</dt>
                        <dd><?php echo formatDate($product['updated_at'], 'M d, Y g:i A'); ?></dd>
                        
                        <dt>Total Stock</dt>
                        <dd><?php echo array_sum($productSizes); ?> pieces</dd>
                    </dl>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Stock Adjustment Tips</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> Changes in quantity will be logged</li>
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> Set quantity to 0 to mark as out of stock</li>
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> Remove all quantities to delete a size</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Add Product';
$errors = [];

// Get categories
try {
    $stmt = db()->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
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
        $sizes = [];
        if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            foreach ($_POST['sizes'] as $index => $size) {
                if (!empty($size) && isset($_POST['quantities'][$index])) {
                    $quantity = (int)$_POST['quantities'][$index];
                    if ($quantity < 0) {
                        $errors[] = "Quantity for size $size cannot be negative.";
                    } else {
                        $sizes[$size] = $quantity;
                    }
                }
            }
        }
        
        if (empty($sizes)) {
            $errors[] = 'At least one size with quantity is required.';
        }
        
        // Check if product code already exists
        if (empty($errors)) {
            try {
                $stmt = db()->prepare("SELECT id FROM products WHERE product_code = ?");
                $stmt->execute([$productCode]);
                if ($stmt->fetch()) {
                    $errors[] = 'Product code already exists.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Database error. Please try again.';
            }
        }
        
        // Insert product if no errors
        if (empty($errors)) {
            try {
                db()->beginTransaction();
                
                // Insert product
                $stmt = db()->prepare("
                    INSERT INTO products (product_code, name, description, category_id, price, cost, image_url) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$productCode, $name, $description, $categoryId ?: null, $price, $cost, $imageUrl]);
                $productId = db()->lastInsertId();
                
                // Insert sizes
                $stmt = db()->prepare("
                    INSERT INTO product_sizes (product_id, size, quantity) 
                    VALUES (?, ?, ?)
                ");
                foreach ($sizes as $size => $quantity) {
                    $stmt->execute([$productId, $size, $quantity]);
                }
                
                // Log inventory addition (try different schema formats)
                // This is optional - if it fails, the product will still be added
                try {
                    // Check if inventory_logs table exists
                    $tableExists = db()->query("SHOW TABLES LIKE 'inventory_logs'")->fetch();
                    if ($tableExists) {
                        // Get table columns
                        $checkColumns = db()->query("SHOW COLUMNS FROM inventory_logs")->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (in_array('movement_type', $checkColumns) && in_array('created_by', $checkColumns)) {
                            // Schema 1: movement_type, quantity, created_by
                            $stmt = db()->prepare("
                                INSERT INTO inventory_logs (product_id, size, movement_type, quantity, reason, created_by)
                                VALUES (?, ?, 'in', ?, 'Initial stock', ?)
                            ");
                            foreach ($sizes as $size => $quantity) {
                                if ($quantity > 0) {
                                    $stmt->execute([$productId, $size, $quantity, $_SESSION['user_id']]);
                                }
                            }
                        } elseif (in_array('quantity_change', $checkColumns) && in_array('changed_by', $checkColumns)) {
                            // Schema 2: quantity_change, old_quantity, new_quantity, changed_by
                            $stmt = db()->prepare("
                                INSERT INTO inventory_logs (product_id, size, quantity_change, old_quantity, new_quantity, reason, changed_by)
                                VALUES (?, ?, ?, 0, ?, 'Initial stock', ?)
                            ");
                            foreach ($sizes as $size => $quantity) {
                                if ($quantity > 0) {
                                    $stmt->execute([$productId, $size, $quantity, $quantity, $_SESSION['user_id']]);
                                }
                            }
                        }
                    }
                } catch (PDOException $logError) {
                    // If inventory logging fails, continue anyway (non-critical)
                    error_log('Inventory logging failed: ' . $logError->getMessage());
                }
                
                db()->commit();
                setFlashMessage('success', 'Product added successfully.');
                redirect('products.php');
            } catch (PDOException $e) {
                db()->rollBack();
                // Show actual error for debugging (remove in production)
                $errors[] = 'Error adding product: ' . $e->getMessage();
                // Log error for debugging
                error_log('Product addition error: ' . $e->getMessage());
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

<div class="container-fluid px-4 py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="products.php"><i class="bi bi-box-seam me-1"></i> Products</a></li>
            <li class="breadcrumb-item active">Add Product</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Add New Product</h4>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="add-product.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="product_code" class="form-label">Product Code *</label>
                                <input type="text" class="form-control" id="product_code" name="product_code" 
                                       required value="<?php echo isset($_POST['product_code']) ? htmlspecialchars($_POST['product_code']) : ''; ?>">
                                <small class="form-text text-muted">Unique identifier for the product</small>
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price" class="form-label">Selling Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="0.01" min="0.01" required 
                                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="cost" class="form-label">Cost Price *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost" name="cost" 
                                           step="0.01" min="0.01" required 
                                           value="<?php echo isset($_POST['cost']) ? $_POST['cost'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image URL</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   placeholder="https://example.com/image.jpg"
                                   value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>">
                            <small class="form-text text-muted">Enter a URL for the product image</small>
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
                                           placeholder="Quantity" min="0" value="0">
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Product
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
                    <h5 class="card-title">Tips</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Product code must be unique</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Set accurate prices for profit calculation</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Add initial stock quantities for each size</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Use clear product images</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quick script for image preview
document.getElementById('image_url').addEventListener('change', function() {
    const url = this.value;
    if (url) {
        // You could add an image preview here
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';
require_once 'includes/upload-handler.php';

// Require login
requireLogin();

$pageTitle = 'Add E-Commerce Product';
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
        $nameAr = sanitize($_POST['name_ar'] ?? '');
        $nameKu = sanitize($_POST['name_ku'] ?? '');
        $description = sanitize($_POST['description']);
        $descriptionAr = sanitize($_POST['description_ar'] ?? '');
        $descriptionKu = sanitize($_POST['description_ku'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $price = (float)$_POST['price'];
        $oldPrice = isset($_POST['old_price']) && $_POST['old_price'] ? (float)$_POST['old_price'] : null;
        $imageUrl = sanitize($_POST['image_url'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $metaTitle = sanitize($_POST['meta_title'] ?? '');
        $metaDescription = sanitize($_POST['meta_description'] ?? '');
        $metaKeywords = sanitize($_POST['meta_keywords'] ?? '');
        
        // Validate required fields
        if (empty($productCode)) $errors[] = 'Product code is required.';
        if (empty($name)) $errors[] = 'Product name is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        
        // Get selected sizes
        $selectedSizes = [];
        if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
            $selectedSizes = array_filter($_POST['sizes']); // Remove empty values
        }
        
        if (empty($selectedSizes)) {
            $errors[] = 'Please select at least one size.';
        }
        
        // Validate that at least one image is uploaded
        $hasImages = false;
        $validImageCount = 0;
        if (isset($_FILES['product_images_upload']) && is_array($_FILES['product_images_upload']['name'])) {
            // Count only non-empty file inputs
            foreach ($_FILES['product_images_upload']['name'] as $index => $filename) {
                if (!empty($filename) && $_FILES['product_images_upload']['error'][$index] === UPLOAD_ERR_OK) {
                    $validImageCount++;
                }
            }
            
            if ($validImageCount > 0) {
                $hasImages = true;
                // Limit to 10 images
                if ($validImageCount > 10) {
                    $errors[] = 'Maximum 10 images allowed per product.';
                }
            }
        }
        
        if (!$hasImages) {
            $errors[] = 'Please upload at least one product image.';
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
                
                // Insert e-commerce product (is_ecommerce = 1, cost = 0 for e-commerce products)
                $stmt = db()->prepare("
                    INSERT INTO products (
                        product_code, name, name_ar, name_ku, description, description_ar, description_ku,
                        category_id, price, old_price, cost, image_url,
                        featured, meta_title, meta_description, meta_keywords,
                        is_ecommerce, is_active
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, 1, 1)
                ");
                $stmt->execute([
                    $productCode,
                    $name,
                    $nameAr ?: null,
                    $nameKu ?: null,
                    $description,
                    $descriptionAr ?: null,
                    $descriptionKu ?: null,
                    $categoryId ?: null,
                    $price,
                    $oldPrice,
                    $imageUrl,
                    $featured, $metaTitle ?: null, $metaDescription ?: null, $metaKeywords ?: null
                ]);
                $productId = db()->lastInsertId();
                
                // Insert sizes (quantity = 0 for e-commerce products, just to indicate availability)
                $stmt = db()->prepare("INSERT INTO product_sizes (product_id, size, quantity) VALUES (?, ?, 0)");
                foreach ($selectedSizes as $size) {
                    $stmt->execute([$productId, $size]);
                }
                
                // Now upload and insert product images AFTER product is created
                $productImages = [];
                if (isset($_FILES['product_images_upload']) && is_array($_FILES['product_images_upload']['name'])) {
                    $totalFiles = count($_FILES['product_images_upload']['name']);
                    $uploadedCount = 0;
                    
                    // Loop through ALL file inputs (not just non-empty ones)
                    for ($i = 0; $i < $totalFiles && $uploadedCount < 10; $i++) {
                        // Check if this specific file input has a file
                        if (!empty($_FILES['product_images_upload']['name'][$i]) && 
                            $_FILES['product_images_upload']['error'][$i] === UPLOAD_ERR_OK) {
                            
                            $uploadResult = uploadProductImage([
                                'name' => $_FILES['product_images_upload']['name'][$i],
                                'type' => $_FILES['product_images_upload']['type'][$i],
                                'tmp_name' => $_FILES['product_images_upload']['tmp_name'][$i],
                                'error' => $_FILES['product_images_upload']['error'][$i],
                                'size' => $_FILES['product_images_upload']['size'][$i]
                            ], $productId);
                            
                            if ($uploadResult['success']) {
                                $productImages[] = $uploadResult['path'];
                                $uploadedCount++;
                            } else {
                                // Log error but don't stop the process
                                error_log('Image upload error for product ' . $productId . ': ' . $uploadResult['error']);
                            }
                        }
                    }
                }
                
                // Insert product images into database (check if table exists)
                if (!empty($productImages)) {
                    try {
                        $tableCheck = db()->query("SHOW TABLES LIKE 'product_images'")->fetch();
                        if ($tableCheck) {
                            $stmt = db()->prepare("INSERT INTO product_images (product_id, image_url, display_order, is_primary) VALUES (?, ?, ?, ?)");
                            foreach ($productImages as $index => $imgUrl) {
                                $isPrimary = ($index === 0) ? 1 : 0; // First image is primary
                                $stmt->execute([$productId, $imgUrl, $index, $isPrimary]);
                            }
                        }
                    } catch (PDOException $e) {
                        // Table doesn't exist yet, skip image insertion
                        error_log('Product images table not found: ' . $e->getMessage());
                    }
                }
                
                db()->commit();
                setFlashMessage('success', 'E-commerce product added successfully.');
                redirect('manage-ecommerce-products.php');
            } catch (PDOException $e) {
                db()->rollBack();
                $errors[] = 'Error adding product: ' . $e->getMessage();
                error_log('E-commerce product addition error: ' . $e->getMessage());
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="ecommerce-dashboard.php"><i class="bi bi-globe me-1"></i> E-Commerce Dashboard</a></li>
            <li class="breadcrumb-item"><a href="manage-ecommerce-products.php">E-Commerce Products</a></li>
            <li class="breadcrumb-item active">Add Product</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">🛍️ Add New E-Commerce Product</h4>
                    <p class="text-muted">Add a product to display on your website store (separate from inventory products)</p>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="add-ecommerce-product.php" enctype="multipart/form-data">
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
                        
                        <div class="row mb-3">
                            <div class="col-lg-4">
                                <label for="name" class="form-label">Product Name (English) *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            <div class="col-lg-4">
                                <label for="name_ar" class="form-label">Product Name (Arabic)</label>
                                <input type="text" class="form-control" id="name_ar" name="name_ar" 
                                       value="<?php echo isset($_POST['name_ar']) ? htmlspecialchars($_POST['name_ar']) : ''; ?>">
                            </div>
                            <div class="col-lg-4">
                                <label for="name_ku" class="form-label">Product Name (Kurdish)</label>
                                <input type="text" class="form-control" id="name_ku" name="name_ku" 
                                       value="<?php echo isset($_POST['name_ku']) ? htmlspecialchars($_POST['name_ku']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-lg-4">
                                <label for="description" class="form-label">Description (English)</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            <div class="col-lg-4">
                                <label for="description_ar" class="form-label">Description (Arabic)</label>
                                <textarea class="form-control" id="description_ar" name="description_ar" rows="3"><?php echo isset($_POST['description_ar']) ? htmlspecialchars($_POST['description_ar']) : ''; ?></textarea>
                            </div>
                            <div class="col-lg-4">
                                <label for="description_ku" class="form-label">Description (Kurdish)</label>
                                <textarea class="form-control" id="description_ku" name="description_ku" rows="3"><?php echo isset($_POST['description_ku']) ? htmlspecialchars($_POST['description_ku']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label">Price *</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="price" name="price" 
                                           step="1" min="1" required 
                                           value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                                    <span class="input-group-text">د.ع</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="old_price" class="form-label">Old Price (Optional)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="old_price" name="old_price" 
                                           step="1" min="1"
                                           value="<?php echo isset($_POST['old_price']) ? $_POST['old_price'] : ''; ?>">
                                    <span class="input-group-text">د.ع</span>
                                </div>
                                <small class="form-text text-muted">For showing discounts</small>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mt-4 pt-3">
                                    <input class="form-check-input" type="checkbox" id="featured" name="featured" value="1"
                                           <?php echo (isset($_POST['featured']) && $_POST['featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="featured">
                                        Featured Product
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Main Image URL (Legacy)</label>
                            <input type="url" class="form-control" id="image_url" name="image_url" 
                                   placeholder="https://example.com/image.jpg"
                                   value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>"
                                   onchange="previewImage(this.value, 'main-preview')">
                            <small class="form-text text-muted">Main product image (for backward compatibility)</small>
                            <div id="main-preview" class="mt-2" style="display: none;">
                                <img id="main-preview-img" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                                <p class="text-muted small mt-2">Main Image Preview</p>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5 class="mb-3">Product Images Gallery (Up to 10 images) *</h5>
                        <p class="text-muted small mb-3">Upload product images directly. Maximum file size: 5MB per image. Formats: JPEG, PNG, GIF, or WebP.</p>
                        <div id="product-images-container">
                            <?php 
                            $imageCount = isset($_FILES['product_images_upload']['name']) ? count(array_filter($_FILES['product_images_upload']['name'])) : 1;
                            if ($imageCount < 1) $imageCount = 1;
                            for ($i = 0; $i < min($imageCount, 10); $i++): 
                            ?>
                            <div class="mb-3 image-input-row border p-3 rounded" data-index="<?php echo $i; ?>">
                                <label class="form-label fw-bold">Image <?php echo $i + 1; ?></label>
                                
                                <!-- File Upload Option -->
                                <div class="mb-2">
                                    <input type="file" class="form-control product-image-upload" 
                                           name="product_images_upload[]" 
                                           accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                                           onchange="previewUploadedImage(this, 'upload-preview-<?php echo $i; ?>')"
                                           <?php echo $i === 0 ? 'required' : ''; ?>>
                                    <small class="form-text text-muted">Max 5MB - JPEG, PNG, GIF, or WebP</small>
                                </div>
                                
                                <!-- Preview Container -->
                                <div class="mt-2">
                                    <div id="upload-preview-<?php echo $i; ?>" style="display: none;">
                                        <img src="" alt="Upload Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                                        <p class="text-success small mt-1">✓ File ready to upload</p>
                                    </div>
                                </div>
                                
                                <?php if ($i > 0): ?>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeImageRow(this)">
                                        <i class="bi bi-trash"></i> Remove
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addImageRow()" id="add-image-btn">
                            <i class="bi bi-plus-circle"></i> Add Another Image
                        </button>
                        <small class="form-text text-muted d-block mt-2">Upload up to 10 product images. The first image will be used as the primary image.</small>
                        
                        <hr>
                        
                        <h5 class="mb-3">Available Sizes *</h5>
                        <div class="row mb-3">
                            <?php 
                            $standardSizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
                            foreach ($standardSizes as $size): 
                            ?>
                            <div class="col-md-3 col-sm-4 col-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sizes[]" 
                                           value="<?php echo $size; ?>" 
                                           id="size_<?php echo $size; ?>"
                                           <?php echo (isset($_POST['sizes']) && in_array($size, $_POST['sizes'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="size_<?php echo $size; ?>">
                                        <?php echo $size; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="form-text text-muted">Select all sizes available for this product</small>
                        
                        <hr>
                        
                        <h5 class="mb-3">SEO Settings (Optional)</h5>
                        
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                   value="<?php echo isset($_POST['meta_title']) ? htmlspecialchars($_POST['meta_title']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" rows="2"><?php echo isset($_POST['meta_description']) ? htmlspecialchars($_POST['meta_description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="meta_keywords" class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" id="meta_keywords" name="meta_keywords" 
                                   placeholder="keyword1, keyword2, keyword3"
                                   value="<?php echo isset($_POST['meta_keywords']) ? htmlspecialchars($_POST['meta_keywords']) : ''; ?>">
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save E-Commerce Product
                            </button>
                            <a href="manage-ecommerce-products.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-2">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Info</h5>
                    <p class="small text-muted">E-commerce products are separate from inventory products and are displayed on your website store.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let imageCount = <?php echo isset($_FILES['product_images_upload']['name']) ? count(array_filter($_FILES['product_images_upload']['name'])) : 1; ?>;

function previewUploadedImage(input, previewId) {
    const previewDiv = document.getElementById(previewId);
    if (!previewDiv) return;
    
    const previewImg = previewDiv.querySelector('img');
    if (!previewImg) return;
    
    if (input.files && input.files[0]) {
        // Check file size (5MB)
        if (input.files[0].size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller file.');
            input.value = '';
            previewDiv.style.display = 'none';
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(input.files[0].type)) {
            alert('Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed.');
            input.value = '';
            previewDiv.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewDiv.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        previewDiv.style.display = 'none';
    }
}

function addImageRow() {
    if (imageCount >= 10) {
        alert('Maximum 10 images allowed per product.');
        document.getElementById('add-image-btn').style.display = 'none';
        return;
    }
    
    const container = document.getElementById('product-images-container');
    const newRow = document.createElement('div');
    newRow.className = 'mb-3 image-input-row border p-3 rounded';
    newRow.setAttribute('data-index', imageCount);
    newRow.innerHTML = `
        <label class="form-label fw-bold">Image ${imageCount + 1}</label>
        
        <div class="mb-2">
            <input type="file" class="form-control product-image-upload" 
                   name="product_images_upload[]" 
                   accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                   onchange="previewUploadedImage(this, 'upload-preview-${imageCount}')">
            <small class="form-text text-muted">Max 5MB - JPEG, PNG, GIF, or WebP</small>
        </div>
        
        <div class="mt-2">
            <div id="upload-preview-${imageCount}" style="display: none;">
                <img src="" alt="Upload Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                <p class="text-success small mt-1">✓ File ready to upload</p>
            </div>
        </div>
        
        <div class="mt-2">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeImageRow(this)">
                <i class="bi bi-trash"></i> Remove
            </button>
        </div>
    `;
    container.appendChild(newRow);
    imageCount++;
    
    if (imageCount >= 10) {
        document.getElementById('add-image-btn').style.display = 'none';
    }
}

function removeImageRow(btn) {
    const row = btn.closest('.image-input-row');
    const previewId = 'preview-' + row.getAttribute('data-index');
    const previewDiv = document.getElementById(previewId);
    if (previewDiv) previewDiv.remove();
    row.remove();
    imageCount--;
    
    // Update indices and show add button if under limit
    document.querySelectorAll('.image-input-row').forEach((row, index) => {
        row.setAttribute('data-index', index);
        const label = row.querySelector('.form-label.fw-bold');
        if (label) {
            label.textContent = `Image ${index + 1}`;
        }
        
        // Update upload input
        const uploadInput = row.querySelector('.product-image-upload');
        if (uploadInput) {
            uploadInput.setAttribute('onchange', `previewUploadedImage(this, 'upload-preview-${index}')`);
        }
        
        // Update preview ID
        const uploadPreview = row.querySelector('[id^="upload-preview-"]');
        if (uploadPreview) {
            uploadPreview.id = `upload-preview-${index}`;
        }
    });
    
    if (imageCount < 10) {
        document.getElementById('add-image-btn').style.display = 'inline-block';
    }
}

// Preview on page load
document.addEventListener('DOMContentLoaded', function() {
    if (imageCount >= 10) {
        document.getElementById('add-image-btn').style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>


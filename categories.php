<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Categories';

// Handle add category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            if (!empty($name)) {
                try {
                    $stmt = db()->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    setFlashMessage('success', 'Category added successfully.');
                } catch (PDOException $e) {
                    setFlashMessage('error', 'Error adding category.');
                }
            } else {
                setFlashMessage('error', 'Category name is required.');
            }
        } elseif ($action == 'edit' && isset($_POST['category_id'])) {
            $categoryId = (int)$_POST['category_id'];
            $name = sanitize($_POST['name']);
            $description = sanitize($_POST['description']);
            
            if (!empty($name)) {
                try {
                    $stmt = db()->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $categoryId]);
                    setFlashMessage('success', 'Category updated successfully.');
                } catch (PDOException $e) {
                    setFlashMessage('error', 'Error updating category.');
                }
            } else {
                setFlashMessage('error', 'Category name is required.');
            }
        }
    } else {
        setFlashMessage('error', 'Invalid request.');
    }
    redirect('categories.php');
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (verifyCSRFToken($_GET['csrf'])) {
        $categoryId = (int)$_GET['delete'];
        try {
            // Check if category has products
            $stmt = db()->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ? AND is_active = 1");
            $stmt->execute([$categoryId]);
            $count = $stmt->fetch()['count'];
            
            if ($count > 0) {
                setFlashMessage('error', 'Cannot delete category with products. Please reassign products first.');
            } else {
                $stmt = db()->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$categoryId]);
                setFlashMessage('success', 'Category deleted successfully.');
            }
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting category.');
        }
    } else {
        setFlashMessage('error', 'Invalid request.');
    }
    redirect('categories.php');
}

// Get all categories with product count
try {
    $stmt = db()->query("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        GROUP BY c.id
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="page-title mb-0">
                🏷️ Product Categories
            </h1>
            <p class="text-muted">Organize your products into categories</p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-circle me-2"></i> Add Category
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <p class="text-center text-muted py-4">No categories found. Add your first category!</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Products</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
                                        <td>
                                            <a href="products.php?category=<?php echo $category['id']; ?>">
                                                <?php echo $category['product_count']; ?> products
                                            </a>
                                        </td>
                                        <td><?php echo formatDate($category['created_at'], 'M d, Y'); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#editCategoryModal"
                                                    data-id="<?php echo $category['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                    data-description="<?php echo htmlspecialchars($category['description'] ?? ''); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($category['product_count'] == 0): ?>
                                            <a href="categories.php?delete=<?php echo $category['id']; ?>&csrf=<?php echo generateCSRFToken(); ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirmDelete('Are you sure you want to delete this category?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete category with products">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Category Management Tips</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> Categories help organize your products</li>
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> Use descriptive names for categories</li>
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> Categories with products cannot be deleted</li>
                        <li class="mb-2"><i class="bi bi-info-circle text-info"></i> You can filter products by category</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title">Add New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categories.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="category_id" id="edit_category_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle edit modal
document.getElementById('editCategoryModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const id = button.getAttribute('data-id');
    const name = button.getAttribute('data-name');
    const description = button.getAttribute('data-description');
    
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
});
</script>

<?php include 'includes/footer.php'; ?>

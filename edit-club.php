<?php
// Edit Club
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Edit Club';
$errors = [];
$club = null;

// Get club ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    setFlashMessage('Invalid club ID', 'error');
    redirect('manage-clubs.php');
}

// Get club data
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM clubs WHERE id = ?");
    $stmt->execute([$id]);
    $club = $stmt->fetch();
    
    if (!$club) {
        setFlashMessage('Club not found', 'error');
        redirect('manage-clubs.php');
    }
} catch (PDOException $e) {
    setFlashMessage('Error loading club', 'error');
    redirect('manage-clubs.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $name = sanitize($_POST['name'] ?? '');
        $name_ar = sanitize($_POST['name_ar'] ?? '');
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $logo_url = sanitize($_POST['logo_url'] ?? '');
        $website_url = sanitize($_POST['website_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($name)) {
            $errors[] = 'Club name is required.';
        }
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    UPDATE clubs 
                    SET name = ?, name_ar = ?, category_id = ?, logo_url = ?, website_url = ?, 
                        display_order = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $name_ar ?: null, $category_id, $logo_url ?: null, $website_url ?: null, 
                    $display_order, $is_active, $id
                ]);
                
                setFlashMessage('Club updated successfully', 'success');
                redirect('manage-clubs.php');
            } catch (PDOException $e) {
                $errors[] = 'Error updating club: ' . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-pencil me-2"></i>Edit Club
            </h1>
            <p class="text-muted">Edit club information</p>
        </div>
        <a href="manage-clubs.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Clubs
        </a>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card modern-card">
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Club Name (English) *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($club['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="name_ar" class="form-label">Club Name (Arabic)</label>
                            <input type="text" class="form-control" id="name_ar" name="name_ar" 
                                   value="<?php echo htmlspecialchars($club['name_ar'] ?? ''); ?>"
                                   dir="rtl">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">-- Select Category --</option>
                                <?php
                                try {
                                    $pdo = getDBConnection();
                                    $categories = $pdo->query("
                                        SELECT * FROM categories 
                                        WHERE is_active = 1 
                                        ORDER BY display_order ASC, name ASC
                                    ")->fetchAll();
                                    foreach ($categories as $category) {
                                        $selected = ($club['category_id'] == $category['id']) ? 'selected' : '';
                                        echo '<option value="' . $category['id'] . '" ' . $selected . '>';
                                        echo htmlspecialchars($category['name']);
                                        if ($category['name_ar']) {
                                            echo ' (' . htmlspecialchars($category['name_ar']) . ')';
                                        }
                                        echo '</option>';
                                    }
                                } catch (Exception $e) {
                                    // Categories table might not exist yet
                                }
                                ?>
                            </select>
                            <small class="form-text text-muted">Optional: Select a category for this club</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="logo_url" class="form-label">Logo URL</label>
                            <input type="url" class="form-control" id="logo_url" name="logo_url" 
                                   value="<?php echo htmlspecialchars($club['logo_url'] ?? ''); ?>"
                                   placeholder="https://example.com/logo.png">
                            <small class="form-text text-muted">Enter the URL of the club logo image</small>
                            <?php if ($club['logo_url']): ?>
                                <div class="mt-2">
                                    <img src="<?php echo htmlspecialchars($club['logo_url']); ?>" 
                                         alt="Logo Preview" 
                                         style="max-width: 200px; max-height: 100px; object-fit: contain; border: 1px solid var(--gray-300); border-radius: 8px; padding: 10px; background: var(--gray-100);"
                                         onerror="this.style.display='none'">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label for="website_url" class="form-label">Website URL</label>
                            <input type="url" class="form-control" id="website_url" name="website_url" 
                                   value="<?php echo htmlspecialchars($club['website_url'] ?? ''); ?>"
                                   placeholder="https://example.com">
                            <small class="form-text text-muted">Optional: Link to the club's website</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" class="form-control" id="display_order" name="display_order" 
                                       value="<?php echo $club['display_order']; ?>" min="0">
                                <small class="form-text text-muted">Lower numbers appear first</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?php echo $club['is_active'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_active">
                                        Active (Show on website)
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-2"></i>Update Club
                            </button>
                            <a href="manage-clubs.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card modern-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-info-circle me-2"></i>Information
                    </h5>
                    <p class="text-muted">
                        Update club information. Changes will be reflected on the homepage immediately.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


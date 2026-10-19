<?php
// Add Slider
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Add Slider';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $title_ar = sanitize($_POST['title_ar'] ?? '');
        $title_en = sanitize($_POST['title_en'] ?? '');
        $subtitle_ar = sanitize($_POST['subtitle_ar'] ?? '');
        $subtitle_en = sanitize($_POST['subtitle_en'] ?? '');
        $image_url = sanitize($_POST['image_url'] ?? '');
        $button_text_ar = sanitize($_POST['button_text_ar'] ?? '');
        $button_text_en = sanitize($_POST['button_text_en'] ?? '');
        $button_link = sanitize($_POST['button_link'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Validate required fields
        if (empty($image_url)) {
            $errors[] = 'Image URL is required.';
        }
        
        if (empty($errors)) {
            try {
                $pdo = getDBConnection();
                $stmt = $pdo->prepare("
                    INSERT INTO sliders 
                    (title_ar, title_en, subtitle_ar, subtitle_en, image_url, button_text_ar, button_text_en, button_link, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title_ar, $title_en, $subtitle_ar, $subtitle_en, $image_url,
                    $button_text_ar, $button_text_en, $button_link, $display_order, $is_active
                ]);
                
                setFlashMessage('Slider added successfully', 'success');
                redirect('manage-sliders.php');
            } catch (PDOException $e) {
                $errors[] = 'Error adding slider: ' . $e->getMessage();
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
                <i class="bi bi-plus-circle me-2"></i>Add New Slider
            </h1>
            <p class="text-muted">Add a new homepage hero banner</p>
        </div>
        <a href="manage-sliders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Sliders
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

    <div class="card modern-card">
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title (Arabic)</label>
                        <input type="text" name="title_ar" class="form-control" value="<?php echo htmlspecialchars($_POST['title_ar'] ?? ''); ?>" placeholder="عنوان السلايدر">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Title (English)</label>
                        <input type="text" name="title_en" class="form-control" value="<?php echo htmlspecialchars($_POST['title_en'] ?? ''); ?>" placeholder="Slider Title">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subtitle (Arabic)</label>
                        <input type="text" name="subtitle_ar" class="form-control" value="<?php echo htmlspecialchars($_POST['subtitle_ar'] ?? ''); ?>" placeholder="نص فرعي">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subtitle (English)</label>
                        <input type="text" name="subtitle_en" class="form-control" value="<?php echo htmlspecialchars($_POST['subtitle_en'] ?? ''); ?>" placeholder="Subtitle text">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Image URL <span class="text-danger">*</span></label>
                    <input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($_POST['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg" required>
                    <small class="text-muted">Enter the full URL of the slider image</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Button Text (Arabic)</label>
                        <input type="text" name="button_text_ar" class="form-control" value="<?php echo htmlspecialchars($_POST['button_text_ar'] ?? ''); ?>" placeholder="تسوق الآن">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Button Text (English)</label>
                        <input type="text" name="button_text_en" class="form-control" value="<?php echo htmlspecialchars($_POST['button_text_en'] ?? ''); ?>" placeholder="Shop Now">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Button Link</label>
                    <input type="url" name="button_link" class="form-control" value="<?php echo htmlspecialchars($_POST['button_link'] ?? ''); ?>" placeholder="https://example.com/products">
                    <small class="text-muted">Where the button should link to (optional)</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="<?php echo htmlspecialchars($_POST['display_order'] ?? 0); ?>" min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-check mt-4">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?php echo (isset($_POST['is_active']) || !isset($_POST['csrf_token'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Add Slider
                    </button>
                    <a href="manage-sliders.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


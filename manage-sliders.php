<?php
// Manage Sliders
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Manage Sliders';

// Handle delete
if (isset($_POST['delete_slider'])) {
    $id = (int)$_POST['slider_id'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM sliders WHERE id = ?")->execute([$id]);
        setFlashMessage('Slider deleted successfully', 'success');
        redirect('manage-sliders.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to delete slider', 'error');
    }
}

// Handle toggle active
if (isset($_POST['toggle_active'])) {
    $id = (int)$_POST['slider_id'];
    $is_active = (int)$_POST['is_active'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("UPDATE sliders SET is_active = ? WHERE id = ?")->execute([$is_active, $id]);
        setFlashMessage('Slider status updated', 'success');
        redirect('manage-sliders.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to update status', 'error');
    }
}

// Get all sliders
try {
    $pdo = getDBConnection();
    $sliders = $pdo->query("SELECT * FROM sliders ORDER BY display_order ASC, created_at DESC")->fetchAll();
} catch (Exception $e) {
    $sliders = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-images me-2"></i>Manage Sliders
            </h1>
            <p class="text-muted">Manage homepage hero banners</p>
        </div>
        <a href="add-slider.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Slider
        </a>
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
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card modern-card">
        <div class="card-body">
            <?php if (!empty($sliders)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sliders as $slider): ?>
                                <tr>
                                    <td>
                                        <img src="<?php echo htmlspecialchars($slider['image_url']); ?>" 
                                             alt="Slider" 
                                             style="width: 100px; height: 60px; object-fit: cover; border-radius: 8px;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($slider['title_ar'] ?? $slider['title_en'] ?? 'No Title'); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($slider['subtitle_ar'] ?? $slider['subtitle_en'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo $slider['display_order']; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="slider_id" value="<?php echo $slider['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $slider['is_active'] ? 0 : 1; ?>">
                                            <button type="submit" name="toggle_active" class="badge bg-<?php echo $slider['is_active'] ? 'success' : 'secondary'; ?> border-0" style="cursor: pointer;">
                                                <?php echo $slider['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo formatDate($slider['created_at'], 'M j, Y'); ?></td>
                                    <td>
                                        <a href="edit-slider.php?id=<?php echo $slider['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this slider?');">
                                            <input type="hidden" name="slider_id" value="<?php echo $slider['id']; ?>">
                                            <button type="submit" name="delete_slider" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-images fs-1 text-muted"></i>
                    <h3 class="mt-3">No Sliders</h3>
                    <p class="text-muted">Start by adding a new slider</p>
                    <a href="add-slider.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Slider
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

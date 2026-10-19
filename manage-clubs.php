<?php
// Manage Clubs
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Manage Clubs';

// Handle delete
if (isset($_POST['delete_club'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'error');
        redirect('manage-clubs.php');
    }
    $id = (int)$_POST['club_id'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM clubs WHERE id = ?")->execute([$id]);
        setFlashMessage('Club deleted successfully', 'success');
        redirect('manage-clubs.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to delete club', 'error');
    }
}

// Handle toggle active
if (isset($_POST['toggle_active'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setFlashMessage('Invalid request. Please try again.', 'error');
        redirect('manage-clubs.php');
    }
    $id = (int)$_POST['club_id'];
    $is_active = (int)$_POST['is_active'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("UPDATE clubs SET is_active = ? WHERE id = ?")->execute([$is_active, $id]);
        setFlashMessage('Club status updated', 'success');
        redirect('manage-clubs.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to update status', 'error');
    }
}

// Get all clubs
try {
    $pdo = getDBConnection();
    $clubs = $pdo->query("
        SELECT c.*, cat.name as category_name, cat.name_ar as category_name_ar 
        FROM clubs c 
        LEFT JOIN categories cat ON c.category_id = cat.id 
        ORDER BY display_order ASC, created_at DESC
    ")->fetchAll();
} catch (Exception $e) {
    $clubs = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-trophy me-2"></i>Manage Clubs
            </h1>
            <p class="text-muted">Manage "Our Clubs" section on homepage</p>
        </div>
        <a href="add-club.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Club
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
            <?php if (!empty($clubs)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Logo</th>
                                <th>Name</th>
                                <th>Name (Arabic)</th>
                                <th>Category</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clubs as $club): ?>
                                <tr>
                                    <td>
                                        <?php if ($club['logo_url']): ?>
                                            <img src="<?php echo htmlspecialchars($club['logo_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($club['name']); ?>" 
                                                 style="width: 80px; height: 50px; object-fit: contain; border-radius: 8px; background: var(--gray-100); padding: 5px;">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 50px; background: var(--gray-200); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--gray-500);">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($club['name']); ?></strong>
                                        <?php if ($club['website_url']): ?>
                                            <br><small class="text-muted">
                                                <a href="<?php echo htmlspecialchars($club['website_url']); ?>" target="_blank" class="text-decoration-none">
                                                    <i class="bi bi-link-45deg"></i> Website
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($club['name_ar'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($club['category_name']): ?>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($club['category_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $club['display_order']; ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $club['is_active'] ? 0 : 1; ?>">
                                            <button type="submit" name="toggle_active" class="btn btn-sm <?php echo $club['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                                <?php echo $club['is_active'] ? '<i class="bi bi-check-circle"></i> Active' : '<i class="bi bi-x-circle"></i> Inactive'; ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($club['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="edit-club.php?id=<?php echo $club['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this club?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <input type="hidden" name="club_id" value="<?php echo $club['id']; ?>">
                                                <button type="submit" name="delete_club" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-trophy" style="font-size: 3rem; color: var(--gray-400);"></i>
                    <p class="text-muted mt-3">No clubs found. Add your first club to get started.</p>
                    <a href="add-club.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add First Club
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


<?php
// Manage Blog Posts
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Manage Blog';

// Handle delete
if (isset($_POST['delete_post'])) {
    $id = (int)$_POST['post_id'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM blog_posts WHERE id = ?")->execute([$id]);
        setFlashMessage('Post deleted successfully', 'success');
        redirect('manage-blog.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to delete post', 'error');
    }
}

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get blog posts
try {
    $pdo = getDBConnection();
    
    $where = $status_filter !== 'all' ? "WHERE bp.status = :status" : "";
    
    $sql = "SELECT bp.*, u.full_name as author_name 
            FROM blog_posts bp 
            LEFT JOIN users u ON bp.author_id = u.id 
            $where
            ORDER BY bp.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($status_filter !== 'all') {
        $stmt->bindValue(':status', $status_filter);
    }
    $stmt->execute();
    $posts = $stmt->fetchAll();
} catch (Exception $e) {
    $posts = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-newspaper me-2"></i>Manage Blog
            </h1>
            <p class="text-muted">Create, edit and publish blog posts</p>
        </div>
        <a href="add-blog-post.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Post
        </a>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter Tabs -->
    <div class="d-flex gap-2 mb-3">
        <a href="?status=all" class="btn btn-<?php echo $status_filter === 'all' ? 'primary' : 'outline-primary'; ?>">
            All
        </a>
        <a href="?status=published" class="btn btn-<?php echo $status_filter === 'published' ? 'primary' : 'outline-primary'; ?>">
            Published
        </a>
        <a href="?status=draft" class="btn btn-<?php echo $status_filter === 'draft' ? 'primary' : 'outline-primary'; ?>">
            Drafts
        </a>
    </div>

    <div class="card modern-card">
        <div class="card-body">
            <?php if (!empty($posts)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td>
                                        <?php
                                            $displayTitle = $post['title_ar'] ?? $post['title_ku'] ?? $post['title_en'] ?? 'Untitled';
                                            $excerptSource = $post['excerpt_ar'] ?? $post['excerpt_ku'] ?? $post['excerpt_en'] ?? '';
                                            $excerptPreview = '';
                                            if (!empty($excerptSource)) {
                                                $limit = 60;
                                                if (function_exists('mb_substr')) {
                                                    $excerptPreview = mb_substr($excerptSource, 0, $limit);
                                                    if (mb_strlen($excerptSource) > $limit) {
                                                        $excerptPreview .= '...';
                                                    }
                                                } else {
                                                    $excerptPreview = substr($excerptSource, 0, $limit);
                                                    if (strlen($excerptSource) > $limit) {
                                                        $excerptPreview .= '...';
                                                    }
                                                }
                                            }
                                            $langBadges = [];
                                            if (!empty($post['title_ar']) || !empty($post['content_ar'])) {
                                                $langBadges[] = 'AR';
                                            }
                                            if (!empty($post['title_en']) || !empty($post['content_en'])) {
                                                $langBadges[] = 'EN';
                                            }
                                            if (!empty($post['title_ku']) || !empty($post['content_ku'])) {
                                                $langBadges[] = 'KU';
                                            }
                                        ?>
                                        <strong><?php echo htmlspecialchars($displayTitle); ?></strong><br>
                                        <?php if ($excerptPreview): ?>
                                            <small class="text-muted d-block"><?php echo htmlspecialchars($excerptPreview); ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($langBadges)): ?>
                                            <div class="mt-1">
                                                <?php foreach ($langBadges as $badge): ?>
                                                    <span class="badge bg-light text-dark border me-1"><?php echo $badge; ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($post['views']); ?></td>
                                    <td><?php echo formatDate($post['created_at'], 'M j, Y'); ?></td>
                                    <td>
                                        <a href="edit-blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <a href="store/blog-detail.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                            <button type="submit" name="delete_post" class="btn btn-sm btn-danger">
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
                    <i class="bi bi-newspaper fs-1 text-muted"></i>
                    <h3 class="mt-3">No Blog Posts</h3>
                    <p class="text-muted">Start writing your first blog post</p>
                    <a href="add-blog-post.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Post
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

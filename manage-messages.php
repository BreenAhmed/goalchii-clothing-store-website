<?php
// Manage Contact Messages
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Customer Messages';

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $id = (int)$_POST['message_id'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
        setFlashMessage('Message marked as read', 'success');
        redirect('manage-messages.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to update message', 'error');
    }
}

// Handle delete
if (isset($_POST['delete_message'])) {
    $id = (int)$_POST['message_id'];
    try {
        $pdo = getDBConnection();
        $pdo->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
        setFlashMessage('Message deleted successfully', 'success');
        redirect('manage-messages.php');
    } catch (Exception $e) {
        setFlashMessage('Failed to delete message', 'error');
    }
}

// Get messages
try {
    $pdo = getDBConnection();
    $messages = $pdo->query("SELECT * FROM contact_messages ORDER BY is_read ASC, created_at DESC")->fetchAll();
} catch (Exception $e) {
    $messages = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-envelope me-2"></i>Customer Messages
            </h1>
            <p class="text-muted">View and manage customer inquiries</p>
        </div>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card modern-card">
        <div class="card-body">
            <?php if (!empty($messages)): ?>
                <div class="messages-list">
                    <?php foreach ($messages as $msg): ?>
                        <div class="border rounded p-3 mb-3 <?php echo !$msg['is_read'] ? 'bg-light border-primary' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0">
                                    <?php echo htmlspecialchars($msg['name']); ?>
                                    <?php if (!$msg['is_read']): ?>
                                        <span class="badge bg-primary ms-2">New</span>
                                    <?php endif; ?>
                                </h5>
                                <small class="text-muted"><?php echo formatDate($msg['created_at'], 'M j, Y g:i A'); ?></small>
                            </div>
                            
                            <div class="mb-2">
                                <span class="text-muted small">
                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($msg['email']); ?>
                                </span>
                                <?php if ($msg['phone']): ?>
                                    <span class="text-muted small ms-3">
                                        <i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($msg['phone']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($msg['subject']): ?>
                                <div class="mb-2">
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <?php if (!$msg['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" name="mark_read" class="btn btn-sm btn-primary">
                                            <i class="bi bi-check2 me-1"></i>Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-reply me-1"></i>Reply
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                    <button type="submit" name="delete_message" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h3 class="mt-3">No Messages</h3>
                    <p class="text-muted">No customer messages have been received yet</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

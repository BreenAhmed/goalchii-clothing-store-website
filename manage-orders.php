<?php
// Manage E-commerce Orders
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

requireLogin();

$pageTitle = 'Customer Orders';
$statusOptions = [
    'new' => 'New',
    'processing' => 'Processing',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

try {
    $pdo = getDBConnection();

    // Ensure orders table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_requests (
            id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            order_number VARCHAR(50) NOT NULL,
            customer_name VARCHAR(150) NOT NULL,
            customer_email VARCHAR(150) NOT NULL,
            customer_phone VARCHAR(30),
            city VARCHAR(100),
            postal_code VARCHAR(30),
            country VARCHAR(100) DEFAULT 'Iraq',
            shipping_address TEXT,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
            shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            payment_method VARCHAR(50) DEFAULT 'cash_on_delivery',
            customer_notes TEXT,
            items_json LONGTEXT,
            status ENUM('new','processing','completed','cancelled') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_order_number (order_number),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Exception $e) {
    setFlashMessage('Failed to initialize orders table: ' . $e->getMessage(), 'error');
    redirect('ecommerce-dashboard.php');
}

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? 'new';

    if ($orderId && array_key_exists($newStatus, $statusOptions)) {
        try {
            $stmt = $pdo->prepare("UPDATE order_requests SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $orderId]);

            setFlashMessage('Order status updated successfully', 'success');
            redirect('manage-orders.php');
        } catch (Exception $e) {
            setFlashMessage('Failed to update order status', 'error');
        }
    }
}

// Handle delete
if (isset($_POST['delete_order'])) {
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($orderId) {
        try {
            $stmt = $pdo->prepare("DELETE FROM order_requests WHERE id = ?");
            $stmt->execute([$orderId]);

            setFlashMessage('Order deleted successfully', 'success');
            redirect('manage-orders.php');
        } catch (Exception $e) {
            setFlashMessage('Failed to delete order', 'error');
        }
    }
}

// Fetch orders
try {
    $orders = $pdo->query("SELECT * FROM order_requests ORDER BY status = 'new' DESC, created_at DESC")->fetchAll();
} catch (Exception $e) {
    $orders = [];
    $error = $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="page-title mb-0">
                <i class="bi bi-bag-check me-2"></i>Customer Orders
            </h1>
            <p class="text-muted mb-0">Review and manage orders placed through the website</p>
        </div>
        <div class="badge bg-primary bg-opacity-25 text-primary px-3 py-2">
            <i class="bi bi-bell-fill me-2"></i>
            New Orders: <?php echo number_format($pdo->query("SELECT COUNT(*) FROM order_requests WHERE status = 'new'")->fetchColumn() ?: 0); ?>
        </div>
    </div>

    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle me-2"></i>
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card modern-card">
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-bag-x fs-1 text-muted"></i>
                    <h3 class="mt-3">No Orders Yet</h3>
                    <p class="text-muted">Customer orders will appear here once placed through the storefront.</p>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <?php $items = json_decode($order['items_json'] ?? '[]', true) ?: []; ?>
                        <div id="order-<?php echo $order['id']; ?>" class="border rounded p-3 mb-3 <?php echo $order['status'] === 'new' ? 'border-primary bg-light' : 'border-light'; ?>">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h5 class="mb-0">#<?php echo htmlspecialchars($order['order_number']); ?></h5>
                                        <?php if ($order['status'] === 'new'): ?>
                                            <span class="badge bg-primary">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-calendar3 me-1"></i><?php echo formatDate($order['created_at'], 'M j, Y g:i A'); ?>
                                    </div>
                                </div>
                                <div>
                                    <strong>Total:</strong>
                                    <span class="fs-5 text-primary fw-bold"><?php echo number_format($order['total_amount']); ?> د.ع</span>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-5">
                                    <div class="mb-2">
                                        <strong><i class="bi bi-person me-2"></i>Customer</strong>
                                        <p class="mb-0"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p class="mb-0">
                                            <i class="bi bi-envelope me-1"></i>
                                            <a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>">
                                                <?php echo htmlspecialchars($order['customer_email']); ?>
                                            </a>
                                        </p>
                                        <?php if (!empty($order['customer_phone'])): ?>
                                            <p class="mb-0">
                                                <i class="bi bi-telephone me-1"></i>
                                                <a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>">
                                                    <?php echo htmlspecialchars($order['customer_phone']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-2">
                                        <strong><i class="bi bi-geo-alt me-2"></i>Shipping Address</strong>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                                        <p class="mb-0 text-muted small">
                                            <?php echo htmlspecialchars($order['city'] ?: ''); ?>
                                            <?php if ($order['postal_code']): ?>
                                                • <?php echo htmlspecialchars($order['postal_code']); ?>
                                            <?php endif; ?>
                                            <?php if ($order['country']): ?>
                                                • <?php echo htmlspecialchars($order['country']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <div class="mb-2">
                                        <strong><i class="bi bi-wallet2 me-2"></i>Payment</strong>
                                        <p class="mb-0 text-capitalize"><?php echo str_replace('_', ' ', htmlspecialchars($order['payment_method'])); ?></p>
                                        <p class="mb-0 text-muted small">
                                            Subtotal: <?php echo number_format($order['subtotal']); ?> د.ع •
                                            Shipping: <?php echo number_format($order['shipping_cost']); ?> د.ع
                                        </p>
                                    </div>

                                    <?php if (!empty($order['customer_notes'])): ?>
                                        <div class="mb-2">
                                            <strong><i class="bi bi-chat-dots me-2"></i>Customer Notes</strong>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-7">
                                    <strong><i class="bi bi-list-check me-2"></i>Items</strong>
                                    <div class="mt-2">
                                        <?php if (empty($items)): ?>
                                            <p class="text-muted mb-0">No items recorded.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Product</th>
                                                            <th class="text-center">Size</th>
                                                            <th class="text-center">Qty</th>
                                                            <th class="text-end">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($items as $item): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <?php if (!empty($item['image'])): ?>
                                                                            <?php 
                                                                                $itemImage = $item['image'];
                                                                                if ($itemImage && !preg_match('/^https?:\/\//', $itemImage)) {
                                                                                    $itemImage = '../' . ltrim($itemImage, '/');
                                                                                }
                                                                            ?>
                                                                            <img src="<?php echo htmlspecialchars($itemImage); ?>"
                                                                                 alt=""
                                                                                 style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px;"
                                                                                 onerror="this.style.display='none'">
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <div class="fw-semibold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                                            <?php if (!empty($item['product_code'])): ?>
                                                                                <div class="text-muted small">
                                                                                    SKU: <?php echo htmlspecialchars($item['product_code']); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td class="text-center"><?php echo htmlspecialchars($item['size']); ?></td>
                                                                <td class="text-center"><?php echo (int)($item['quantity']); ?></td>
                                                                <td class="text-end"><?php echo number_format($item['total']); ?> د.ع</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex justify-content-end align-items-center gap-2 mt-4 flex-wrap">
                                        <form method="POST" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" style="min-width: 160px;">
                                                <?php foreach ($statusOptions as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo $order['status'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                                <i class="bi bi-check2-circle me-1"></i>Update
                                            </button>
                                        </form>

                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this order?');">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="delete_order" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>


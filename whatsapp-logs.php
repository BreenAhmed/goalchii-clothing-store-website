<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';
require_once 'includes/whatsapp-helper.php';

// Require login
requireLogin();

$pageTitle = 'WhatsApp Logs';

// Handle resend request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_order'])) {
    $orderNumber = $_POST['order_number'];
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM order_requests WHERE order_number = ?");
        $stmt->execute([$orderNumber]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $items = json_decode($order['items_json'], true);
            
            $orderData = [
                'order_number' => $order['order_number'],
                'created_at' => $order['created_at'],
                'customer_name' => $order['customer_name'],
                'customer_email' => $order['customer_email'],
                'customer_phone' => $order['customer_phone'],
                'shipping_address' => $order['shipping_address'],
                'city' => $order['city'],
                'postal_code' => $order['postal_code'],
                'country' => $order['country'],
                'subtotal' => $order['subtotal'],
                'shipping_cost' => $order['shipping_cost'],
                'total_amount' => $order['total_amount'],
                'payment_method' => $order['payment_method'],
                'customer_notes' => $order['customer_notes'],
                'items' => $items
            ];
            
            $result = sendOrderWhatsAppMessage($order['customer_phone'], $orderData);
            
            if ($result['success']) {
                setFlashMessage('WhatsApp message sent successfully!', 'success');
            } else {
                setFlashMessage('Failed to send WhatsApp message: ' . ($result['error'] ?? 'Unknown error'), 'error');
            }
        }
    } catch (Exception $e) {
        setFlashMessage('Error: ' . $e->getMessage(), 'error');
    }
    
    header('Location: whatsapp-logs.php');
    exit;
}

// Get WhatsApp logs
try {
    $pdo = getDBConnection();
    
    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    
    // Get total count
    $totalLogs = $pdo->query("SELECT COUNT(*) FROM whatsapp_logs")->fetchColumn();
    $totalPages = ceil($totalLogs / $perPage);
    
    // Get logs with order details
    $stmt = $pdo->prepare("
        SELECT wl.*, or2.customer_name, or2.customer_email 
        FROM whatsapp_logs wl
        LEFT JOIN order_requests or2 ON wl.order_number = or2.order_number
        ORDER BY wl.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $logs = [];
    $totalPages = 0;
}

// Get WhatsApp configuration status
$whatsappConfig = getWhatsAppConfig();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - GOALCHII Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-5">
        <div class="row mb-4">
            <div class="col">
                <h1><i class="bi bi-whatsapp"></i> <?php echo $pageTitle; ?></h1>
            </div>
        </div>

        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show">
                <?php 
                echo $_SESSION['flash_message']; 
                unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- WhatsApp Configuration Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-gear"></i> WhatsApp Configuration</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Current Method:</strong>
                        <span class="badge bg-primary"><?php echo strtoupper($whatsappConfig['method']); ?></span>
                    </div>
                    <div class="col-md-4">
                        <strong>WhatsApp Business API:</strong>
                        <?php if ($whatsappConfig['api_configured']): ?>
                            <span class="badge bg-success">Configured</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Not Configured</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Twilio API:</strong>
                        <?php if ($whatsappConfig['twilio_configured']): ?>
                            <span class="badge bg-success">Configured</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Not Configured</span>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <p class="mb-0 text-muted">
                    <i class="bi bi-info-circle"></i> 
                    To configure WhatsApp API, edit the <code>config/whatsapp-config.php</code> file.
                </p>
            </div>
        </div>

        <!-- Statistics -->
        <?php
        try {
            $stats = $pdo->query("
                SELECT 
                    COUNT(*) as total_messages,
                    SUM(success) as successful_messages,
                    COUNT(*) - SUM(success) as failed_messages
                FROM whatsapp_logs
            ")->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $stats = ['total_messages' => 0, 'successful_messages' => 0, 'failed_messages' => 0];
        }
        ?>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Messages</h5>
                        <h2><?php echo number_format($stats['total_messages']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Successful</h5>
                        <h2><?php echo number_format($stats['successful_messages']); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Failed</h5>
                        <h2><?php echo number_format($stats['failed_messages']); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Message Logs</h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> No WhatsApp messages sent yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Date/Time</th>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Phone</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo $log['id']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?></td>
                                        <td>
                                            <a href="manage-orders.php?order=<?php echo urlencode($log['order_number']); ?>">
                                                <?php echo htmlspecialchars($log['order_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['customer_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($log['phone_number']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo strtoupper($log['method']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($log['success']): ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle"></i> Sent
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" title="<?php echo htmlspecialchars($log['error_message']); ?>">
                                                    <i class="bi bi-x-circle"></i> Failed
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    onclick="viewMessage(<?php echo $log['id']; ?>)">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="order_number" value="<?php echo htmlspecialchars($log['order_number']); ?>">
                                                <button type="submit" name="resend_order" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-arrow-clockwise"></i> Resend
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">WhatsApp Message Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="messageContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewMessage(logId) {
            const modal = new bootstrap.Modal(document.getElementById('messageModal'));
            const content = document.getElementById('messageContent');
            
            // Show loading
            content.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
            modal.show();
            
            // Fetch message details
            fetch('ajax/get-whatsapp-message.php?id=' + logId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        content.innerHTML = `
                            <div class="mb-3">
                                <strong>Phone Number:</strong> ${data.log.phone_number}
                            </div>
                            <div class="mb-3">
                                <strong>Method:</strong> <span class="badge bg-info">${data.log.method}</span>
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong> 
                                ${data.log.success ? '<span class="badge bg-success">Sent</span>' : '<span class="badge bg-danger">Failed</span>'}
                            </div>
                            ${data.log.error_message ? `<div class="mb-3"><strong>Error:</strong> <span class="text-danger">${data.log.error_message}</span></div>` : ''}
                            <div class="mb-3">
                                <strong>Message:</strong>
                                <pre class="bg-light p-3" style="white-space: pre-wrap;">${data.log.message}</pre>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class="alert alert-danger">Failed to load message details.</div>';
                    }
                })
                .catch(error => {
                    content.innerHTML = '<div class="alert alert-danger">Error loading message details.</div>';
                });
        }
    </script>
</body>
</html>




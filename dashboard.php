<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Dashboard';

// Get statistics
try {
    // Total products
    $stmt = db()->query("SELECT COUNT(*) as total FROM products WHERE is_active = 1");
    $totalProducts = $stmt->fetch()['total'];
    
    // Low stock items (less than 10 pieces total) - only for active products
    $stmt = db()->query("
        SELECT COUNT(*) as total
        FROM (
            SELECT p.id
            FROM products p
            LEFT JOIN product_sizes ps ON p.id = ps.product_id
            WHERE p.is_active = 1
            GROUP BY p.id
            HAVING COALESCE(SUM(ps.quantity), 0) < 10
        ) as low_stock_products
    ");
    $result = $stmt->fetch();
    $lowStock = $result ? (int)$result['total'] : 0;
    
    // If no products exist, set to 0
    if ($totalProducts == 0) {
        $lowStock = 0;
    }
    
    // Today's income
    $stmt = db()->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE type = 'income' AND transaction_date = CURDATE()
    ");
    $stmt->execute();
    $todayIncome = $stmt->fetch()['total'];
    
    // This month's profit
    $stmt = db()->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income,
            COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense
        FROM transactions 
        WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $monthData = $stmt->fetch();
    $monthProfit = $monthData['income'] - $monthData['expense'];
    
    // Recent transactions
    $stmt = db()->query("
        SELECT t.*, tc.name as category_name 
        FROM transactions t
        LEFT JOIN transaction_categories tc ON t.category_id = tc.id
        ORDER BY t.created_at DESC 
        LIMIT 5
    ");
    $recentTransactions = $stmt->fetchAll();
    
    // Low stock products
    $stmt = db()->query("
        SELECT p.*, COALESCE(SUM(ps.quantity), 0) as total_quantity
        FROM products p
        LEFT JOIN product_sizes ps ON p.id = ps.product_id
        WHERE p.is_active = 1
        GROUP BY p.id
        HAVING COALESCE(SUM(ps.quantity), 0) < 10
        ORDER BY total_quantity ASC
        LIMIT 5
    ");
    $lowStockProducts = $stmt->fetchAll();
    
    // Ensure low stock count matches the actual list
    if (empty($lowStockProducts)) {
        $lowStock = 0;
    }
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading dashboard data.');
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <!-- Welcome Section with Emoji -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card" style="border-left: 6px solid #f59e0b; background: linear-gradient(135deg, rgba(245, 158, 11, 0.05), rgba(236, 72, 153, 0.05));">
                <div class="card-body py-3">
                    <h1 class="page-title mb-0 d-inline">
                        👋 Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                    </h1>
                    <p class="text-muted mb-0 mt-2">
                        <i class="bi bi-calendar-check me-2"></i><?php echo date('l, F j, Y'); ?> 🎯
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-primary" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">📦 Total Products</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($totalProducts); ?></h2>
                            <small class="text-white-50">In stock</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-box-seam stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="products.php" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>View all products</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-warning" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">⚠️ Low Stock Alert</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($lowStock); ?></h2>
                            <small class="text-white-50">Items running low</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-exclamation-triangle stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="products.php?filter=low-stock" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>View items</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-success" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50">💰 Today's Income</h6>
                            <h2 class="mb-0 fw-bold"><?php echo formatCurrency($todayIncome); ?></h2>
                            <small class="text-white-50">Earned today</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-cash-stack stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="transactions.php?date=today" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>View details</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white <?php echo $monthProfit >= 0 ? 'bg-info' : 'bg-danger'; ?>" style="position: relative; overflow: hidden;">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <h6 class="card-title mb-1 text-white-50"><?php echo $monthProfit >= 0 ? '📈' : '📉'; ?> This Month</h6>
                            <h2 class="mb-0 fw-bold"><?php echo formatCurrency(abs($monthProfit)); ?></h2>
                            <small class="text-white-50"><?php echo $monthProfit >= 0 ? 'Profit' : 'Loss'; ?></small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="bi bi-graph-<?php echo $monthProfit >= 0 ? 'up' : 'down'; ?>-arrow stat-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 py-2">
                    <a href="accounting.php" class="text-white text-decoration-none d-flex align-items-center justify-content-between">
                        <span>View report</span>
                        <i class="bi bi-arrow-right-circle"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Transactions -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <span class="gradient-text">💳 Recent Transactions</span>
                    </h5>
                    <a href="transactions.php" class="btn btn-sm btn-primary">
                        <i class="bi bi-eye me-1"></i> View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo formatDate($transaction['transaction_date'], 'M d'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['type'] == 'income' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Uncategorized'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                                    <td class="text-end <?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>
                                        <?php echo formatCurrency($transaction['amount']); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Low Stock Alert -->
        <div class="col-lg-4 mb-4">
            <div class="card" style="border-top: 4px solid #f59e0b;">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <span style="font-size: 1.25rem;">⚠️</span> Low Stock Alert
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStockProducts)): ?>
                        <p class="text-muted mb-0">No low stock items.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($lowStockProducts as $product): ?>
                            <a href="edit-product.php?id=<?php echo $product['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($product['product_code']); ?></small>
                                    </div>
                                    <span class="badge bg-danger"><?php echo $product['total_quantity']; ?> left</span>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

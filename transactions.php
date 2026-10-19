<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Transactions';

// Handle delete action
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (verifyCSRFToken($_GET['csrf'])) {
        $transactionId = (int)$_GET['delete'];
        try {
            $stmt = db()->prepare("DELETE FROM transactions WHERE id = ?");
            $stmt->execute([$transactionId]);
            setFlashMessage('success', 'Transaction deleted successfully.');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Error deleting transaction.');
        }
    } else {
        setFlashMessage('error', 'Invalid request.');
    }
    redirect('transactions.php');
}

// Get filter parameters
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterDay = isset($_GET['day']) ? (int)$_GET['day'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "
    SELECT t.*, tc.name as category_name, u.full_name as created_by_name
    FROM transactions t
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE 1=1
";

$params = [];

if ($filterType && in_array($filterType, ['income', 'expense'])) {
    $query .= " AND t.type = ?";
    $params[] = $filterType;
}

if ($filterCategory) {
    $query .= " AND t.category_id = ?";
    $params[] = $filterCategory;
}

if ($filterYear && $filterMonth && $filterDay) {
    $query .= " AND t.transaction_date = ?";
    $params[] = sprintf('%04d-%02d-%02d', $filterYear, $filterMonth, $filterDay);
} elseif ($filterYear && $filterMonth) {
    $query .= " AND YEAR(t.transaction_date) = ? AND MONTH(t.transaction_date) = ?";
    $params[] = $filterYear;
    $params[] = $filterMonth;
} elseif ($filterYear) {
    $query .= " AND YEAR(t.transaction_date) = ?";
    $params[] = $filterYear;
}

if ($search) {
    $query .= " AND (t.description LIKE ? OR t.reference_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Special filter for today
if (isset($_GET['date']) && $_GET['date'] == 'today') {
    $query .= " AND t.transaction_date = CURDATE()";
}

$query .= " ORDER BY t.transaction_date DESC, t.created_at DESC";

try {
    $stmt = db()->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = db()->query("SELECT * FROM transaction_categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Calculate totals
    $totalIncome = 0;
    $totalExpense = 0;
    foreach ($transactions as $trans) {
        if ($trans['type'] == 'income') {
            $totalIncome += $trans['amount'];
        } else {
            $totalExpense += $trans['amount'];
        }
    }
    $netTotal = $totalIncome - $totalExpense;
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading transactions.');
    $transactions = [];
    $categories = [];
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="page-title mb-0">
                💳 All Transactions
            </h1>
            <p class="text-muted">View and manage your financial records</p>
        </div>
        <div class="col-auto">
            <a href="add-transaction.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle me-2"></i> Add Transaction
            </a>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="transactions.php" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Description or reference..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <option value="income" <?php echo $filterType == 'income' ? 'selected' : ''; ?>>Income</option>
                        <option value="expense" <?php echo $filterType == 'expense' ? 'selected' : ''; ?>>Expense</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">Category</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $filterCategory == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="month" class="form-label">Month</label>
                    <select class="form-select" id="month" name="month">
                        <option value="">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo $filterMonth == $m ? 'selected' : ''; ?>>
                            <?php echo getMonthName($m); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <?php if (!empty($transactions)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title mb-0">Total Income</h6>
                    <h4 class="mb-0"><?php echo formatCurrency($totalIncome); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6 class="card-title mb-0">Total Expense</h6>
                    <h4 class="mb-0"><?php echo formatCurrency($totalExpense); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo $netTotal >= 0 ? 'bg-info' : 'bg-warning'; ?> text-white">
                <div class="card-body">
                    <h6 class="card-title mb-0">Net Total</h6>
                    <h4 class="mb-0"><?php echo formatCurrency(abs($netTotal)); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title mb-0">Transactions</h6>
                    <h4 class="mb-0"><?php echo count($transactions); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Transactions Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <p class="text-center text-muted py-4">No transactions found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Reference</th>
                                <th>Payment Method</th>
                                <th class="text-end">Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo formatDate($transaction['transaction_date'], 'M d, Y'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['type'] == 'income' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($transaction['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Uncategorized'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['reference_number'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($transaction['payment_method'] ?? '-'); ?></td>
                                <td class="text-end <?php echo $transaction['type'] == 'income' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $transaction['type'] == 'income' ? '+' : '-'; ?>
                                    <?php echo formatCurrency($transaction['amount']); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="edit-transaction.php?id=<?php echo $transaction['id']; ?>" 
                                           class="btn btn-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="transactions.php?delete=<?php echo $transaction['id']; ?>&csrf=<?php echo generateCSRFToken(); ?>" 
                                           class="btn btn-danger" title="Delete"
                                           onclick="return confirmDelete('Are you sure you want to delete this transaction?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-secondary">
                                <th colspan="6">Totals</th>
                                <th class="text-end">
                                    <span class="text-success">+<?php echo formatCurrency($totalIncome); ?></span><br>
                                    <span class="text-danger">-<?php echo formatCurrency($totalExpense); ?></span><br>
                                    <strong class="<?php echo $netTotal >= 0 ? 'text-info' : 'text-warning'; ?>">
                                        <?php echo formatCurrency(abs($netTotal)); ?>
                                    </strong>
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="mt-3">
                    <button class="btn btn-secondary" onclick="exportTableToCSV('transactions-table', 'transactions-<?php echo date('Y-m-d'); ?>.csv')">
                        <i class="bi bi-download"></i> Export to CSV
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

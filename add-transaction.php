<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Add Transaction';
$errors = [];

// Get categories
try {
    $stmt = db()->query("SELECT * FROM transaction_categories ORDER BY type, name");
    $categories = $stmt->fetchAll();
    
    // Separate categories by type
    $incomeCategories = [];
    $expenseCategories = [];
    foreach ($categories as $cat) {
        if ($cat['type'] == 'income') {
            $incomeCategories[] = $cat;
        } else {
            $expenseCategories[] = $cat;
        }
    }
} catch (PDOException $e) {
    $incomeCategories = [];
    $expenseCategories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get form data
        $type = sanitize($_POST['type']);
        $transactionDate = sanitize($_POST['transaction_date']);
        $categoryId = (int)$_POST['category_id'];
        $amount = (float)$_POST['amount'];
        $description = sanitize($_POST['description']);
        $paymentMethod = sanitize($_POST['payment_method']);
        $referenceNumber = sanitize($_POST['reference_number']);
        
        // Validate required fields
        if (!in_array($type, ['income', 'expense'])) {
            $errors[] = 'Invalid transaction type.';
        }
        if (empty($transactionDate)) {
            $errors[] = 'Transaction date is required.';
        }
        if ($amount <= 0) {
            $errors[] = 'Amount must be greater than 0.';
        }
        
        // Insert transaction if no errors
        if (empty($errors)) {
            try {
                $stmt = db()->prepare("
                    INSERT INTO transactions 
                    (transaction_date, type, category_id, description, amount, payment_method, reference_number, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $transactionDate,
                    $type,
                    $categoryId ?: null,
                    $description,
                    $amount,
                    $paymentMethod,
                    $referenceNumber,
                    $_SESSION['user_id']
                ]);
                
                setFlashMessage('success', 'Transaction added successfully.');
                redirect('transactions.php');
            } catch (PDOException $e) {
                $errors[] = 'Error adding transaction. Please try again.';
            }
        }
    }
}

// Default to today's date
$defaultDate = date('Y-m-d');
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4 py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="accounting.php"><i class="bi bi-calculator me-1"></i> Accounting</a></li>
            <li class="breadcrumb-item"><a href="transactions.php">Transactions</a></li>
            <li class="breadcrumb-item active">Add Transaction</li>
        </ol>
    </nav>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Add New Transaction</h4>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="add-transaction.php">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Transaction Type *</label>
                                <select class="form-select" id="type" name="type" required onchange="updateCategories()">
                                    <option value="">Select Type</option>
                                    <option value="income" <?php echo (isset($_POST['type']) && $_POST['type'] == 'income') ? 'selected' : ''; ?>>
                                        Income
                                    </option>
                                    <option value="expense" <?php echo (isset($_POST['type']) && $_POST['type'] == 'expense') ? 'selected' : ''; ?>>
                                        Expense
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">Transaction Date *</label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       required value="<?php echo isset($_POST['transaction_date']) ? $_POST['transaction_date'] : $defaultDate; ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <optgroup label="Income Categories" id="income-categories" style="display: none;">
                                        <?php foreach ($incomeCategories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Expense Categories" id="expense-categories" style="display: none;">
                                        <?php foreach ($expenseCategories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="amount" class="form-label">Amount *</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="amount" name="amount" 
                                           step="0.01" min="0.01" required 
                                           value="<?php echo isset($_POST['amount']) ? $_POST['amount'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Enter transaction details..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <input type="text" class="form-control" id="payment_method" name="payment_method" 
                                       placeholder="Cash, Card, Transfer, etc."
                                       value="<?php echo isset($_POST['payment_method']) ? htmlspecialchars($_POST['payment_method']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                       placeholder="Invoice #, Receipt #, etc."
                                       value="<?php echo isset($_POST['reference_number']) ? htmlspecialchars($_POST['reference_number']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Transaction
                            </button>
                            <a href="transactions.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Quick Tips</h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Record all business transactions</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Use categories for better reporting</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Add reference numbers for tracking</li>
                        <li class="mb-2"><i class="bi bi-check-circle text-success"></i> Be consistent with payment methods</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title">Recent Transactions</h5>
                    <?php
                    try {
                        $stmt = db()->prepare("
                            SELECT t.*, tc.name as category_name
                            FROM transactions t
                            LEFT JOIN transaction_categories tc ON t.category_id = tc.id
                            WHERE t.created_by = ?
                            ORDER BY t.created_at DESC
                            LIMIT 5
                        ");
                        $stmt->execute([$_SESSION['user_id']]);
                        $recentTrans = $stmt->fetchAll();
                        
                        if (empty($recentTrans)) {
                            echo '<p class="text-muted mb-0">No recent transactions.</p>';
                        } else {
                            echo '<div class="list-group list-group-flush">';
                            foreach ($recentTrans as $trans) {
                                echo '<div class="list-group-item px-0">';
                                echo '<div class="d-flex justify-content-between align-items-center">';
                                echo '<div>';
                                echo '<h6 class="mb-0">' . htmlspecialchars($trans['description'] ?? $trans['category_name'] ?? 'Transaction') . '</h6>';
                                echo '<small class="text-muted">' . formatDate($trans['transaction_date'], 'M d') . '</small>';
                                echo '</div>';
                                echo '<strong class="' . ($trans['type'] == 'income' ? 'text-success' : 'text-danger') . '">';
                                echo ($trans['type'] == 'income' ? '+' : '-') . formatCurrency($trans['amount']);
                                echo '</strong>';
                                echo '</div>';
                                echo '</div>';
                            }
                            echo '</div>';
                        }
                    } catch (PDOException $e) {
                        echo '<p class="text-muted mb-0">Error loading recent transactions.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateCategories() {
    const type = document.getElementById('type').value;
    const incomeCategories = document.getElementById('income-categories');
    const expenseCategories = document.getElementById('expense-categories');
    
    if (type === 'income') {
        incomeCategories.style.display = 'block';
        expenseCategories.style.display = 'none';
    } else if (type === 'expense') {
        incomeCategories.style.display = 'none';
        expenseCategories.style.display = 'block';
    } else {
        incomeCategories.style.display = 'none';
        expenseCategories.style.display = 'none';
    }
}

// Initialize on page load
updateCategories();
</script>

<?php include 'includes/footer.php'; ?>

<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Edit Transaction';
$errors = [];

// Get transaction ID
$transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$transactionId) {
    setFlashMessage('error', 'Invalid transaction ID.');
    redirect('transactions.php');
}

// Get transaction data
try {
    $stmt = db()->prepare("SELECT * FROM transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        setFlashMessage('error', 'Transaction not found.');
        redirect('transactions.php');
    }
    
    // Get categories
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
    setFlashMessage('error', 'Error loading transaction.');
    redirect('transactions.php');
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
        
        // Update transaction if no errors
        if (empty($errors)) {
            try {
                $stmt = db()->prepare("
                    UPDATE transactions 
                    SET transaction_date = ?, type = ?, category_id = ?, description = ?, 
                        amount = ?, payment_method = ?, reference_number = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $transactionDate,
                    $type,
                    $categoryId ?: null,
                    $description,
                    $amount,
                    $paymentMethod,
                    $referenceNumber,
                    $transactionId
                ]);
                
                setFlashMessage('success', 'Transaction updated successfully.');
                redirect('transactions.php');
            } catch (PDOException $e) {
                $errors[] = 'Error updating transaction. Please try again.';
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="accounting.php">Accounting</a></li>
                    <li class="breadcrumb-item"><a href="transactions.php">Transactions</a></li>
                    <li class="breadcrumb-item active">Edit Transaction</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Edit Transaction</h4>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="edit-transaction.php?id=<?php echo $transactionId; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Transaction Type *</label>
                                <select class="form-select" id="type" name="type" required onchange="updateCategories()">
                                    <option value="">Select Type</option>
                                    <option value="income" <?php echo $transaction['type'] == 'income' ? 'selected' : ''; ?>>
                                        Income
                                    </option>
                                    <option value="expense" <?php echo $transaction['type'] == 'expense' ? 'selected' : ''; ?>>
                                        Expense
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="transaction_date" class="form-label">Transaction Date *</label>
                                <input type="date" class="form-control" id="transaction_date" name="transaction_date" 
                                       required value="<?php echo $transaction['transaction_date']; ?>">
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
                                                <?php echo $transaction['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <optgroup label="Expense Categories" id="expense-categories" style="display: none;">
                                        <?php foreach ($expenseCategories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $transaction['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
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
                                           step="0.01" min="0.01" required value="<?php echo $transaction['amount']; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Enter transaction details..."><?php echo htmlspecialchars($transaction['description']); ?></textarea>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <input type="text" class="form-control" id="payment_method" name="payment_method" 
                                       placeholder="Cash, Card, Transfer, etc."
                                       value="<?php echo htmlspecialchars($transaction['payment_method']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="reference_number" class="form-label">Reference Number</label>
                                <input type="text" class="form-control" id="reference_number" name="reference_number" 
                                       placeholder="Invoice #, Receipt #, etc."
                                       value="<?php echo htmlspecialchars($transaction['reference_number']); ?>">
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Transaction
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
                    <h5 class="card-title">Transaction Information</h5>
                    <dl>
                        <dt>Created</dt>
                        <dd><?php echo formatDate($transaction['created_at'], 'M d, Y g:i A'); ?></dd>
                        
                        <dt>Last Updated</dt>
                        <dd><?php echo formatDate($transaction['updated_at'], 'M d, Y g:i A'); ?></dd>
                        
                        <dt>Created By</dt>
                        <dd>
                            <?php
                            try {
                                $stmt = db()->prepare("SELECT full_name FROM users WHERE id = ?");
                                $stmt->execute([$transaction['created_by']]);
                                $user = $stmt->fetch();
                                echo htmlspecialchars($user['full_name'] ?? 'Unknown');
                            } catch (PDOException $e) {
                                echo 'Unknown';
                            }
                            ?>
                        </dd>
                    </dl>
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

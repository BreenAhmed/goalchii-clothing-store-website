<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Require login
requireLogin();

$pageTitle = 'Accounting Dashboard';

// Get current month and year from query params or default to current
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Validate month and year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2000 || $year > 2100) $year = date('Y');

// Get first and last day of the month
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$lastDay = mktime(23, 59, 59, $month, $daysInMonth, $year);
$firstDayOfWeek = date('w', $firstDay);

// Get transactions for the month
try {
    $stmt = db()->prepare("
        SELECT 
            DAY(transaction_date) as day,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense,
            COUNT(*) as transaction_count
        FROM transactions 
        WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?
        GROUP BY DAY(transaction_date)
    ");
    $stmt->execute([$month, $year]);
    
    $dailyData = [];
    while ($row = $stmt->fetch()) {
        $dailyData[$row['day']] = [
            'income' => $row['income'],
            'expense' => $row['expense'],
            'count' => $row['transaction_count'],
            'profit' => $row['income'] - $row['expense']
        ];
    }
    
    // Get monthly totals
    $stmt = db()->prepare("
        SELECT 
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense,
            COUNT(*) as transaction_count
        FROM transactions 
        WHERE MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?
    ");
    $stmt->execute([$month, $year]);
    $monthlyTotals = $stmt->fetch();
    $monthlyProfit = $monthlyTotals['total_income'] - $monthlyTotals['total_expense'];
    
    // Get top expense categories for the month
    $stmt = db()->prepare("
        SELECT 
            tc.name as category_name,
            SUM(t.amount) as total_amount,
            COUNT(t.id) as transaction_count
        FROM transactions t
        LEFT JOIN transaction_categories tc ON t.category_id = tc.id
        WHERE t.type = 'expense' AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
        GROUP BY t.category_id
        ORDER BY total_amount DESC
        LIMIT 5
    ");
    $stmt->execute([$month, $year]);
    $topExpenses = $stmt->fetchAll();
    
    // Get top income sources for the month
    $stmt = db()->prepare("
        SELECT 
            tc.name as category_name,
            SUM(t.amount) as total_amount,
            COUNT(t.id) as transaction_count
        FROM transactions t
        LEFT JOIN transaction_categories tc ON t.category_id = tc.id
        WHERE t.type = 'income' AND MONTH(t.transaction_date) = ? AND YEAR(t.transaction_date) = ?
        GROUP BY t.category_id
        ORDER BY total_amount DESC
        LIMIT 5
    ");
    $stmt->execute([$month, $year]);
    $topIncome = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage('error', 'Error loading accounting data.');
    $dailyData = [];
    $monthlyTotals = ['total_income' => 0, 'total_expense' => 0, 'transaction_count' => 0];
    $monthlyProfit = 0;
    $topExpenses = [];
    $topIncome = [];
}

// Previous and next month calculations
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>

<?php 
$additionalCSS = ['assets/css/accounting.css'];
include 'includes/header.php'; 
?>

<div class="container-fluid px-4 py-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="page-title mb-0">
                📊 Accounting Dashboard
            </h1>
            <p class="text-muted">Track your income and expenses with ease</p>
        </div>
        <div class="col-auto">
            <a href="add-transaction.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle me-2"></i> Add Transaction
            </a>
        </div>
    </div>
    
    <!-- Monthly Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title mb-0">Total Income</h5>
                    <h3 class="mb-0"><?php echo formatCurrency($monthlyTotals['total_income']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title mb-0">Total Expenses</h5>
                    <h3 class="mb-0"><?php echo formatCurrency($monthlyTotals['total_expense']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white <?php echo $monthlyProfit >= 0 ? 'bg-info' : 'bg-warning'; ?>">
                <div class="card-body">
                    <h5 class="card-title mb-0">Net Profit</h5>
                    <h3 class="mb-0"><?php echo formatCurrency(abs($monthlyProfit)); ?></h3>
                    <?php if ($monthlyProfit < 0): ?>
                    <small>Loss</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title mb-0">Transactions</h5>
                    <h3 class="mb-0"><?php echo number_format($monthlyTotals['transaction_count']); ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Calendar View -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0"><?php echo getMonthName($month) . ' ' . $year; ?></h5>
                        </div>
                        <div class="col-auto">
                            <div class="btn-group" role="group">
                                <a href="accounting.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                                <a href="accounting.php" class="btn btn-sm btn-outline-secondary">Today</a>
                                <a href="accounting.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="calendar-table">
                        <thead>
                            <tr>
                                <th>Sun</th>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th>Sat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <?php
                                // Fill in empty cells before the first day
                                for ($i = 0; $i < $firstDayOfWeek; $i++) {
                                    echo '<td class="text-muted"></td>';
                                }
                                
                                // Fill in the days of the month
                                $currentDay = 1;
                                $currentWeekDay = $firstDayOfWeek;
                                
                                while ($currentDay <= $daysInMonth) {
                                    $isToday = ($currentDay == date('j') && $month == date('n') && $year == date('Y'));
                                    $hasData = isset($dailyData[$currentDay]);
                                    
                                    $classes = 'calendar-day';
                                    if ($isToday) $classes .= ' today';
                                    if ($hasData) $classes .= ' has-data';
                                    
                                    echo '<td class="' . $classes . '" onclick="viewDayTransactions(' . $year . ', ' . $month . ', ' . $currentDay . ')">';
                                    echo '<div class="day-number">' . $currentDay . '</div>';
                                    
                                    if ($hasData) {
                                        $dayData = $dailyData[$currentDay];
                                        if ($dayData['income'] > 0) {
                                            echo '<div class="day-income">+' . formatCurrency($dayData['income']) . '</div>';
                                        }
                                        if ($dayData['expense'] > 0) {
                                            echo '<div class="day-expense">-' . formatCurrency($dayData['expense']) . '</div>';
                                        }
                                    }
                                    
                                    echo '</td>';
                                    
                                    $currentDay++;
                                    $currentWeekDay++;
                                    
                                    if ($currentWeekDay == 7 && $currentDay <= $daysInMonth) {
                                        echo '</tr><tr>';
                                        $currentWeekDay = 0;
                                    }
                                }
                                
                                // Fill in empty cells after the last day
                                while ($currentWeekDay < 7 && $currentWeekDay != 0) {
                                    echo '<td class="text-muted"></td>';
                                    $currentWeekDay++;
                                }
                                ?>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Top Income Sources -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Top Income Sources</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($topIncome)): ?>
                        <p class="text-muted mb-0">No income recorded this month.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topIncome as $income): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($income['category_name'] ?? 'Uncategorized'); ?></h6>
                                        <small class="text-muted"><?php echo $income['transaction_count']; ?> transactions</small>
                                    </div>
                                    <strong class="text-success"><?php echo formatCurrency($income['total_amount']); ?></strong>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Top Expenses -->
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">Top Expense Categories</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($topExpenses)): ?>
                        <p class="text-muted mb-0">No expenses recorded this month.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topExpenses as $expense): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($expense['category_name'] ?? 'Uncategorized'); ?></h6>
                                        <small class="text-muted"><?php echo $expense['transaction_count']; ?> transactions</small>
                                    </div>
                                    <strong class="text-danger"><?php echo formatCurrency($expense['total_amount']); ?></strong>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDayTransactions(year, month, day) {
    window.location.href = `transactions.php?year=${year}&month=${month}&day=${day}`;
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$month = date('Y-m');

// -----------------------------
// TASK COUNTS (CURRENT MONTH)
// -----------------------------
$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'in progress') AS in_progress,
        SUM(status = 'completed') AS completed,
        SUM(status = 'in progress' AND due_date < CURDATE()) AS overdue
    FROM task
    WHERE user_id = ?
      AND DATE_FORMAT(due_date, '%Y-%m') = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$taskCounts = $stmt->get_result()->fetch_assoc();

// -----------------------------
// FINANCE SUMMARY (THIS MONTH)
// -----------------------------
$income = 0;
$expense = 0;

$sql = "
    SELECT type, SUM(amount) AS total
    FROM transaction_table
    WHERE user_id = ?
      AND DATE_FORMAT(txn_date_time, '%Y-%m') = ?
    GROUP BY type
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    if ($row['type'] === 'income') $income = $row['total'];
    if ($row['type'] === 'expense') $expense = $row['total'];
}

$balance = $income - $expense;

// -----------------------------
// EXPENSE BY CATEGORY
// -----------------------------
$categories = [];
$categoryAmounts = [];

$sql = "
    SELECT c.category_name, SUM(t.amount) AS total
    FROM transaction_table t
    LEFT JOIN category c ON t.category_id = c.category_id
    WHERE t.user_id = ?
      AND t.type = 'expense'
      AND DATE_FORMAT(t.txn_date_time, '%Y-%m') = ?
    GROUP BY c.category_id, c.category_name
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $month);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $categories[] = $row['category_name'] ?: 'Uncategorized';
    $categoryAmounts[] = (float)$row['total'];
}

// -----------------------------
// RECENT TRANSACTIONS
// -----------------------------
$recentTransactions = [];
$sql = "
    SELECT description, amount, type, txn_date_time
    FROM transaction_table
    WHERE user_id = ?
    ORDER BY txn_date_time DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recentTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// -----------------------------
// UPCOMING TASKS
// -----------------------------
$upcomingTasks = [];
$sql = "
    SELECT task_id, title, due_date
    FROM task
    WHERE user_id = ?
      AND status = 'in progress'
    ORDER BY due_date ASC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcomingTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle task completion from dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_task_id'])) {
    $task_id = (int)$_POST['complete_task_id'];
    $sql_update = "UPDATE task SET status = 'completed' WHERE task_id = ? AND user_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $task_id, $user_id);
    $stmt_update->execute();
    header("Location: dashboard.php?task_completed=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PlanWise</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/nav-bar.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

<div class="container-fluid px-4 py-3">

    <!-- NAV BAR -->
    <?php include 'nav-bar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="content-box">

        <!-- SUCCESS MESSAGE -->
        <?php if (isset($_GET['task_completed'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ✓ Task marked as completed!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- DASHBOARD HEADER -->
        <div class="dashboard-header">
            <h2 class="dashboard-title">Welcome Back! 👋</h2>
            <p class="dashboard-subtitle">Here's your overview for <?= date('F Y') ?></p>
        </div>

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card task-card">
                <div class="kpi-icon">📋</div>
                <div class="kpi-content">
                    <p class="kpi-label">Total Tasks</p>
                    <h3 class="kpi-value"><?= $taskCounts['total'] ?></h3>
                </div>
            </div>

            <div class="kpi-card progress-card">
                <div class="kpi-icon">⚙️</div>
                <div class="kpi-content">
                    <p class="kpi-label">In Progress</p>
                    <h3 class="kpi-value"><?= $taskCounts['in_progress'] ?></h3>
                </div>
            </div>

            <div class="kpi-card completed-card">
                <div class="kpi-icon">✅</div>
                <div class="kpi-content">
                    <p class="kpi-label">Completed</p>
                    <h3 class="kpi-value"><?= $taskCounts['completed'] ?></h3>
                </div>
            </div>

            <div class="kpi-card overdue-card">
                <div class="kpi-icon">⚠️</div>
                <div class="kpi-content">
                    <p class="kpi-label">Overdue</p>
                    <h3 class="kpi-value"><?= $taskCounts['overdue'] ?></h3>
                </div>
            </div>

            <div class="kpi-card expense-card">
                <div class="kpi-icon">💸</div>
                <div class="kpi-content">
                    <p class="kpi-label">This Month Expense</p>
                    <h3 class="kpi-value">RM <?= number_format($expense, 2) ?></h3>
                </div>
            </div>

            <div class="kpi-card balance-card">
                <div class="kpi-icon">💰</div>
                <div class="kpi-content">
                    <p class="kpi-label"> Net Balance</p>
                    <h3 class="kpi-value <?= $balance >= 0 ? 'positive' : 'negative' ?>">RM <?= number_format($balance, 2) ?></h3>
                </div>
            </div>
        </div>

        <!-- CHARTS AND ACTIVITIES ROW -->
        <div class="row mt-5">

            <!-- EXPENSE BREAKDOWN CHART -->
            <div class="col-lg-6 mb-4">
                <div class="chart-card">
                    <h4 class="chart-title">Expense Breakdown</h4>
                    <div class="chart-container">
                        <?php if (count($categories) > 0): ?>
                            <canvas id="expenseChart"></canvas>
                        <?php else: ?>
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <p class="text-muted">No expense data for this month</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RECENT TRANSACTIONS -->
            <div class="col-lg-6 mb-4">
                <div class="activity-card">
                    <h4 class="activity-title">Recent Transactions</h4>
                    <div class="activity-list">
                        <?php if (count($recentTransactions) > 0): ?>
                            <?php foreach ($recentTransactions as $t): ?>
                                <div class="activity-item">
                                    <div class="activity-left">
                                        <span class="activity-icon">
                                            <?= $t['type'] === 'income' ? '📈' : '📉' ?>
                                        </span>
                                        <div class="activity-text">
                                            <p class="activity-desc"><?= htmlspecialchars($t['description']) ?></p>
                                            <p class="activity-date"><?= date('M d, Y', strtotime($t['txn_date_time'])) ?></p>
                                        </div>
                                    </div>
                                    <p class="activity-amount <?= $t['type'] === 'income' ? 'income' : 'expense' ?>">
                                        <?= $t['type'] === 'income' ? '+' : '-' ?>RM <?= number_format($t['amount'], 2) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No recent transactions</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <!-- UPCOMING TASKS -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="upcoming-card">
                    <h4 class="upcoming-title">Upcoming Tasks</h4>
                    <div class="upcoming-list">
                        <?php if (count($upcomingTasks) > 0): ?>
                            <?php foreach ($upcomingTasks as $t): ?>
                                <form method="POST" class="upcoming-item">
                                    <div class="upcoming-check">
                                        <input type="checkbox" class="form-check-input" onchange="this.form.submit()" title="Mark as completed">
                                        <input type="hidden" name="complete_task_id" value="<?= $t['task_id'] ?>">
                                    </div>
                                    <div class="upcoming-content">
                                        <p class="upcoming-task"><?= htmlspecialchars($t['title']) ?></p>
                                        <p class="upcoming-due">Due: <?= date('M d, Y', strtotime($t['due_date'])) ?></p>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No upcoming tasks</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Expense Chart
const ctx = document.getElementById('expenseChart');
if (ctx) {
    const hasData = <?= json_encode(count($categories) > 0) ?>;
    
    if (hasData) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($categories) ?>,
                datasets: [{
                    data: <?= json_encode($categoryAmounts) ?>,
                    backgroundColor: [
                        '#FFC9BA',
                        '#FFB399',
                        '#FF9D83',
                        '#FF876D',
                        '#FF7157',
                        '#84994F',
                        '#A72703',
                        '#D4AF37'
                    ],
                    borderColor: '#FFF',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { 
                                family: "'Inter', sans-serif", 
                                size: 12, 
                                weight: 500 
                            },
                            color: '#565D6D',
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += 'RM ' + context.parsed.toFixed(2);
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
}
</script>

</body>
</html>
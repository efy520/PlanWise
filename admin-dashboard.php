<?php
session_start();
include 'db_connection.php';

// Check if user is admin (hardcoded for now)
$is_admin = true;

if (!$is_admin) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ============================================
// KPI 1: TOTAL REGISTERED USERS
// ============================================
$sql = "SELECT COUNT(*) AS total_users FROM users";
$result = $conn->query($sql);
$total_users = $result->fetch_assoc()['total_users'];

// ============================================
// KPI 2: TOTAL TASKS CREATED
// ============================================
$sql = "SELECT COUNT(*) AS total_tasks FROM task";
$result = $conn->query($sql);
$total_tasks = $result->fetch_assoc()['total_tasks'];

// ============================================
// KPI 3: TASK COMPLETION RATE (%)
// ============================================
$sql = "
    SELECT 
        COUNT(*) AS total_tasks,
        SUM(status = 'completed') AS completed_tasks
    FROM task
";
$result = $conn->query($sql);
$task_stats = $result->fetch_assoc();
$total_tasks_all = $task_stats['total_tasks'];
$completed_tasks = $task_stats['completed_tasks'];
$completion_rate = $total_tasks_all > 0 ? round(($completed_tasks / $total_tasks_all) * 100, 1) : 0;

// ============================================
// KPI 4: TOTAL SYSTEM EXPENSE (CURRENT MONTH)
// ============================================
$current_month = date('Y-m');
$sql = "
    SELECT SUM(amount) AS total_expense
    FROM transaction_table
    WHERE type = 'expense'
      AND DATE_FORMAT(txn_date_time, '%Y-%m') = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();
$total_expense = $result->fetch_assoc()['total_expense'] ?? 0;

// ============================================
// KPI 5: MOST ACTIVE USER (BY TASK COUNT)
// ============================================
$sql = "
    SELECT 
        u.username,
        COUNT(t.task_id) AS task_count
    FROM users u
    LEFT JOIN task t ON u.user_id = t.user_id
    GROUP BY u.user_id, u.username
    ORDER BY task_count DESC
    LIMIT 1
";
$result = $conn->query($sql);
$most_active = $result->fetch_assoc();
$most_active_user = $most_active['username'] ?? 'N/A';
$most_active_count = $most_active['task_count'] ?? 0;

// ============================================
// KPI 6: TOTAL QUOTES
// ============================================
$sql = "SELECT COUNT(*) AS total_quotes FROM quote";
$result = $conn->query($sql);
$total_quotes = $result->fetch_assoc()['total_quotes'];

// ============================================
// CHART DATA: TASK STATUS DISTRIBUTION
// ============================================
$sql = "
    SELECT 
        status,
        COUNT(*) AS count
    FROM task
    GROUP BY status
";
$result = $conn->query($sql);
$task_statuses = [];
$task_status_counts = [];
while ($row = $result->fetch_assoc()) {
    $task_statuses[] = ucfirst($row['status']);
    $task_status_counts[] = $row['count'];
}

// ============================================
// CHART DATA: TOP 5 EXPENSE CATEGORIES
// ============================================
$sql = "
    SELECT 
        c.category_name,
        SUM(t.amount) AS total
    FROM transaction_table t
    LEFT JOIN category c ON t.category_id = c.category_id
    WHERE t.type = 'expense'
      AND DATE_FORMAT(t.txn_date_time, '%Y-%m') = ?
    GROUP BY c.category_id, c.category_name
    ORDER BY total DESC
    LIMIT 5
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $current_month);
$stmt->execute();
$result = $stmt->get_result();
$expense_categories = [];
$expense_amounts = [];
while ($row = $result->fetch_assoc()) {
    $expense_categories[] = $row['category_name'] ?: 'Uncategorized';
    $expense_amounts[] = (float)$row['total'];
}

// ============================================
// RECENT ACTIVITIES (LAST 5 TRANSACTIONS)
// ============================================
$sql = "
    SELECT 
        u.username,
        t.description,
        t.amount,
        t.type,
        t.txn_date_time
    FROM transaction_table t
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.txn_date_time DESC
    LIMIT 5
";
$result = $conn->query($sql);
$recent_activities = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PlanWise</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/admin-dashboard.css">
</head>

<body>

<div class="admin-detail-container">
    
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="images/logo.png" alt="PlanWise Logo" class="logo">
        </div>
          <!-- Logout Button -->
         <div class="btn"><a href="admin-dashboard.php?logout=1" class="btn-logout-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            LOG OUT
        </a></div>
        
        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-link active">Dashboard</a>
            <a href="quote.php" class="nav-link">Quote</a>
            <a href="user-detail.php" class="nav-link">User Detail</a>
        </nav>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content-area">
        
        <!-- HEADER -->
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Admin Dashboard</h1>
                <p class="admin-subtitle">System Overview & Analytics</p>
            </div>
            <div class="header-date">
                📅 <?= date('F d, Y') ?>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            
            <!-- Card 1: Total Users -->
            <div class="kpi-card users-card">
                <div class="kpi-icon">👥</div>
                <div class="kpi-info">
                    <p class="kpi-label">Total Users</p>
                    <h3 class="kpi-value"><?= $total_users ?></h3>
                    <p class="kpi-subtitle">Registered Users</p>
                </div>
            </div>

            <!-- Card 2: Total Tasks -->
            <div class="kpi-card tasks-card">
                <div class="kpi-icon">📋</div>
                <div class="kpi-info">
                    <p class="kpi-label">Total Tasks</p>
                    <h3 class="kpi-value"><?= $total_tasks ?></h3>
                    <p class="kpi-subtitle">Created in System</p>
                </div>
            </div>

            <!-- Card 3: Completion Rate -->
            <div class="kpi-card completion-card">
                <div class="kpi-icon">✅</div>
                <div class="kpi-info">
                    <p class="kpi-label">Completion Rate</p>
                    <h3 class="kpi-value"><?= $completion_rate ?>%</h3>
                    <p class="kpi-subtitle"><?= $completed_tasks ?>/<?= $total_tasks_all ?> Completed</p>
                </div>
            </div>

            <!-- Card 4: Total Expense -->
            <div class="kpi-card expense-card">
                <div class="kpi-icon">💸</div>
                <div class="kpi-info">
                    <p class="kpi-label">This Month Expense</p>
                    <h3 class="kpi-value">RM <?= number_format($total_expense, 2) ?></h3>
                    <p class="kpi-subtitle"><?= date('F Y') ?></p>
                </div>
            </div>

            <!-- Card 5: Most Active User -->
            <div class="kpi-card active-user-card">
                <div class="kpi-icon">⭐</div>
                <div class="kpi-info">
                    <p class="kpi-label">Most Active User</p>
                    <h3 class="kpi-value"><?= htmlspecialchars($most_active_user) ?></h3>
                    <p class="kpi-subtitle"><?= $most_active_count ?> Tasks Created</p>
                </div>
            </div>

            <!-- Card 6: Total Quotes -->
            <div class="kpi-card quotes-card">
                <div class="kpi-icon">💬</div>
                <div class="kpi-info">
                    <p class="kpi-label">Total Quotes</p>
                    <h3 class="kpi-value"><?= $total_quotes ?></h3>
                    <p class="kpi-subtitle">Motivational Quotes</p>
                </div>
            </div>
        </div>

        <!-- CHARTS ROW -->
        <div class="charts-row">
            
            <!-- Task Status Distribution Chart -->
            <div class="chart-card">
                <h4 class="chart-title">Task Status Distribution</h4>
                <div class="chart-container">
                    <?php if (count($task_statuses) > 0): ?>
                        <canvas id="taskStatusChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted">No task data available</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Expense Categories Chart -->
            <div class="chart-card">
                <h4 class="chart-title">Top Expense Categories (This Month)</h4>
                <div class="chart-container">
                    <?php if (count($expense_categories) > 0): ?>
                        <canvas id="expenseCategoryChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted">No expense data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RECENT ACTIVITIES -->
        <div class="activity-card">
            <h4 class="activity-title">Recent Transactions</h4>
            <div class="activity-list">
                <?php if (count($recent_activities) > 0): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-left">
                            <span class="activity-icon">
                                <?= $activity['type'] === 'income' ? '📈' : ($activity['type'] === 'expense' ? '📉' : '🔄') ?>
                            </span>
                            <div class="activity-text">
                                <p class="activity-user"><?= htmlspecialchars($activity['username']) ?></p>
                                <p class="activity-desc"><?= htmlspecialchars($activity['description']) ?></p>
                                <p class="activity-date"><?= date('M d, Y H:i', strtotime($activity['txn_date_time'])) ?></p>
                            </div>
                        </div>
                        <p class="activity-amount <?= $activity['type'] ?>">
                            <?= $activity['type'] === 'income' ? '+' : '-' ?>RM <?= number_format($activity['amount'], 2) ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Task Status Chart
const taskStatusCtx = document.getElementById('taskStatusChart');
if (taskStatusCtx) {
    new Chart(taskStatusCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($task_statuses) ?>,
            datasets: [{
                data: <?= json_encode($task_status_counts) ?>,
                backgroundColor: [
                    '#84994F',
                    '#2196F3',
                    '#FFA500'
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
                        font: { family: "'Inter', sans-serif", size: 12, weight: 500 },
                        color: '#565D6D',
                        padding: 15
                    }
                }
            }
        }
    });
}

// Expense Category Chart
const expenseCategoryCtx = document.getElementById('expenseCategoryChart');
if (expenseCategoryCtx) {
    new Chart(expenseCategoryCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($expense_categories) ?>,
            datasets: [{
                label: 'Amount (RM)',
                data: <?= json_encode($expense_amounts) ?>,
                backgroundColor: '#FFC9BA',
                borderColor: '#FF876D',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        font: { family: "'Inter', sans-serif", size: 12, weight: 500 },
                        color: '#565D6D'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount (RM)'
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>

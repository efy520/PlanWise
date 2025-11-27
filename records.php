<?php
session_start();
include 'db_connection.php';

// -------------------------------------------
// CHECK LOGIN SESSION
// -------------------------------------------
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// -------------------------------------------
// FETCH MOTIVATIONAL QUOTE
// -------------------------------------------
$sql_quote = "SELECT quote_text FROM quote WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
$result_quote = $conn->query($sql_quote);

$quote_text = "No quote available";
if ($result_quote && $result_quote->num_rows > 0) {
    $quote_text = $result_quote->fetch_assoc()['quote_text'];
}


// -------------------------------------------
// FETCH ALL TRANSACTIONS (REAL DATA)
// -------------------------------------------
// JOIN Category + Account names
$sql = "
    SELECT 
        t.transaction_id,
        t.type,
        t.amount,
        t.description,
        t.txn_date_time,
        
        c.category_name,
        c.category_type,
        
        a1.account_name AS source_account,
        a2.account_name AS destination_account

    FROM transaction_table t
    LEFT JOIN category c 
        ON t.category_id = c.category_id
    LEFT JOIN account a1 
        ON t.source_account_id = a1.account_id
    LEFT JOIN account a2 
        ON t.destination_account_id = a2.account_id
    WHERE t.user_id = ?
    ORDER BY t.txn_date_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$records = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Records - PlanWise</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/records.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-fluid px-4 py-3">

    <!-- TOP NAV -->
    <nav class="navbar-custom mb-3">
        <div class="logo-container">
            <img src="images/logo.png" class="logo" alt="PlanWise">
        </div>

        <div class="nav-menu">
            <a href="task.php" class="nav-item">To-Do</a>
            <a href="records.php" class="nav-item active">Finance</a>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="profile.php" class="nav-item">Profile</a>
        </div>
    </nav>

    <!-- QUOTE -->
    <div class="quote-box mb-3">
        <p class="quote-text">"<?php echo htmlspecialchars($quote_text); ?>"</p>
    </div>

    <!-- TABS -->
    <div class="tabs-container mb-3">
        <button class="tab-button active">Records</button>
        <button class="tab-button" onclick="window.location='finance-cat.php'">Finance Settings</button>
        <button class="tab-button" onclick="window.location='budgets.php'">Budgets</button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content-box">

       <div class="d-flex justify-content-between align-items-center mb-4">

    <!-- LEFT side filters -->
    <div class="d-flex flex-wrap gap-3">
        <select class="records-select"><option>Newest</option></select>
        <select class="records-select"><option>Month</option></select>
        <select class="records-select"><option>Category</option></select>
        <select class="records-select"><option>Account</option></select>
        <select class="records-select"><option>Type</option></select>
    </div>

    <!-- RIGHT side add button -->
    <button class="btn-add-record" onclick="window.location='t-income.php'">+</button>

</div>


        <!-- TABLE -->
        <div class="records-table-container">
            <table class="records-table">
                <thead>
                    <tr>
                        <th>DATE</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Account</th>
                        <th>Time</th>
                        <th>Description</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($records->num_rows == 0): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">No records found.</td>
                    </tr>
                <?php else: ?>

                    <?php while ($r = $records->fetch_assoc()): ?>

                        <?php
                        // Format date + time
                        $date = date('d/m/Y', strtotime($r['txn_date_time']));
                        $time = date('h:i A', strtotime($r['txn_date_time']));

                        // Determine account display
                        if ($r['type'] === 'transfer') {
                            $account_display = $r['source_account'] . " → " . $r['destination_account'];
                        } else {
                            $account_display = $r['source_account'];
                        }
                        ?>

                        <tr>
                            <td><?= $date ?></td>

                            <td>RM <?= number_format($r['amount'], 2) ?></td>

                            <td class="type-<?= strtolower($r['type']) ?>">
                                <?= ucfirst($r['type']) ?>
                            </td>

                            <td><?= $r['category_name'] ? $r['category_name'] : '-' ?></td>

                            <td><?= $account_display ?></td>

                            <td><?= $time ?></td>

                            <td><?= htmlspecialchars($r['description']) ?></td>
                        </tr>

                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

</body>
</html>

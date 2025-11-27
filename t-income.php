<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ---------------------------------------------------------
   FETCH QUOTE
--------------------------------------------------------- */
$sql_quote = "SELECT quote_text FROM quote WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
$res_q = $conn->query($sql_quote);
$quote_text = $res_q->num_rows > 0 ? $res_q->fetch_assoc()['quote_text'] : "";

/* ---------------------------------------------------------
   FETCH ACCOUNTS
--------------------------------------------------------- */
$sql_acc = "SELECT account_id, account_name FROM account WHERE user_id = ? AND is_active = 1";
$stmt_acc = $conn->prepare($sql_acc);
$stmt_acc->bind_param("i", $user_id);
$stmt_acc->execute();
$accounts = $stmt_acc->get_result();

/* ---------------------------------------------------------
   FETCH INCOME CATEGORIES
--------------------------------------------------------- */
$sql_cat = "SELECT category_id, category_name FROM category 
            WHERE user_id = ? AND category_type = 'income'";
$stmt_cat = $conn->prepare($sql_cat);
$stmt_cat->bind_param("i", $user_id);
$stmt_cat->execute();
$categories = $stmt_cat->get_result();

/* ---------------------------------------------------------
   HANDLE FORM SUBMISSION
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $account_id = $_POST['account_id'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $datetime = $_POST['datetime'];

    $sql_ins = "INSERT INTO transaction_table 
        (user_id, category_id, source_account_id, destination_account_id, txn_date_time, type, description, amount)
        VALUES (?, ?, ?, NULL, ?, 'income', ?, ?)";

    $stmt = $conn->prepare($sql_ins);
    $stmt->bind_param("iiissd", $user_id, $category_id, $account_id, $datetime, $description, $amount);

    if ($stmt->execute()) {
        header("Location: records.php?added=1");
        exit();
    } else {
        $error = "Failed to save transaction.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Income - PlanWise</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/t-income.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-fluid px-3 px-md-4 py-4">

    <!-- TOP TABS -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <a href="records.php" class="btn-back">← Back</a>
            <div class="nav-menu">
                <a href="t-income.php" class="nav-item active">Income</a>
                <a href="t-expense.php" class="nav-item">Expense</a>
                <a href="t-transfer.php" class="nav-item">Transfer</a>
            </div>
            <div style="width: 102px;"></div>
        </div>
    </div>

    <!-- FORM BOX -->
    <div class="row">
        <div class="col-12 col-lg-10 offset-lg-1">
            <div class="txn-box">

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">

                    <!-- ACCOUNT + CATEGORY -->
                    <div class="row mb-4 g-3">
                        <div class="col-12 col-md-6">
                            <select name="account_id" class="txn-select form-select" required>
                                
                                <?php while ($a = $accounts->fetch_assoc()): ?>
                                    <option value="<?= $a['account_id'] ?>"><?= $a['account_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <select name="category_id" class="txn-select form-select" required>
            
                                <?php while ($c = $categories->fetch_assoc()): ?>
                                    <option value="<?= $c['category_id'] ?>"><?= $c['category_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="mb-4">
                        <textarea name="description" class="txn-desc form-control" placeholder="Notes" required></textarea>
                    </div>

                    <!-- AMOUNT + DATE + TIME -->
                    <div class="row mb-4 g-3">

                        <div class="col-12 col-md-4">
                            <input type="number" step="0.01" name="amount" class="txn-amount form-control" placeholder="0.00" required>
                        </div>

                        <div class="col-12 col-md-8">
                            <input type="datetime-local" name="datetime" class="txn-datetime form-control" required>
                        </div>

                    </div>

                    <!-- SAVE BUTTON -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-save">Save</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>

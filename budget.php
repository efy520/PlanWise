<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ---------------------------------------------------------
   FETCH MOTIVATIONAL QUOTE
--------------------------------------------------------- */
$sql_quote = "SELECT quote_text FROM quote WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
$res_q = $conn->query($sql_quote);
$quote_text = $res_q->num_rows > 0 ? $res_q->fetch_assoc()['quote_text'] : "";

/* ---------------------------------------------------------
   GET SELECTED MONTH
--------------------------------------------------------- */
$month = isset($_GET['month']) ? $_GET['month'] : date("F");
$month_num = date("m", strtotime($month));

/* ---------------------------------------------------------
   FETCH BUDGETED CATEGORIES (ONLY EXPENSE)
--------------------------------------------------------- */
$sql_bud = "
    SELECT 
        b.budget_id,
        b.category_id,
        c.category_name,
        b.limit_amount,

        (SELECT COALESCE(SUM(t.amount), 0)
         FROM transaction_table t
         WHERE t.category_id = b.category_id
         AND t.user_id = b.user_id
         AND t.type = 'expense'
         AND MONTH(t.txn_date_time) = ?) AS spent
    FROM budget b
    JOIN category c ON b.category_id = c.category_id
    WHERE b.user_id = ?
    AND c.category_type = 'expense'
    ORDER BY c.category_name ASC
";

$stmt_b = $conn->prepare($sql_bud);
$stmt_b->bind_param("ii", $month_num, $user_id);
$stmt_b->execute();
$budgeted = $stmt_b->get_result();

/* ---------------------------------------------------------
   FETCH NON-BUDGETED EXPENSE CATEGORIES
--------------------------------------------------------- */
$sql_non = "
    SELECT category_id, category_name
    FROM category
    WHERE user_id = ?
    AND category_type = 'expense'
    AND is_active = 1
    AND category_id NOT IN (SELECT category_id FROM budget WHERE user_id = ?)
";

$stmt_non = $conn->prepare($sql_non);
$stmt_non->bind_param("ii", $user_id, $user_id);
$stmt_non->execute();
$non_budgeted = $stmt_non->get_result();

/* ---------------------------------------------------------
   HANDLE NEW BUDGET
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['set_budget'])) {
    $cat_id = $_POST['category_id'];
    $limit = $_POST['limit_amount'];

    $sql_add = "INSERT INTO budget (user_id, category_id, month, limit_amount) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql_add);
    $stmt->bind_param("iisd", $user_id, $cat_id, $month_num, $limit);
    $stmt->execute();

    header("Location: budget.php?month=$month");
    exit();
}

/* ---------------------------------------------------------
   HANDLE UPDATE BUDGET
--------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['update_budget'])) {
    $budget_id = $_POST['budget_id'];
    $limit = $_POST['limit_amount'];

    $sql_up = "UPDATE budget SET limit_amount = ? WHERE budget_id = ?";
    $stmt = $conn->prepare($sql_up);
    $stmt->bind_param("di", $limit, $budget_id);
    $stmt->execute();

    header("Location: budget.php?month=$month");
    exit();
}

/* ---------------------------------------------------------
   HANDLE REMOVE BUDGET
--------------------------------------------------------- */
if (isset($_GET['remove'])) {
    $bid = $_GET['remove'];
    $conn->query("DELETE FROM budget WHERE budget_id = $bid");
    header("Location: budget.php?month=$month");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Budgets - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/budget.css">
</head>
<body>

<div class="container-fluid px-4 py-3">

    <!-- NAVBAR -->
    <nav class="navbar-custom mb-3">
        <img src="images/logo.png" class="logo">

        <div class="nav-menu">
            <a href="task.php" class="nav-item">To-Do</a>
            <a href="records.php" class="nav-item">Finance</a>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="profile.php" class="nav-item">Profile</a>
        </div>
    </nav>

    <!-- QUOTE BOX -->
    <div class="quote-box mb-3">
        <p class="quote-text">"<?php echo htmlspecialchars($quote_text); ?>"</p>
    </div>

    <!-- TABS -->
    <div class="tabs-container mb-3">
        <button class="tab-button" onclick="window.location='records.php'">Records</button>
        <button class="tab-button" onclick="window.location='finance-acc.php'">Finance Settings</button>
        <button class="tab-button active">Budgets</button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content-box">

        <!-- MONTH DROPDOWN -->
        <div class="d-flex justify-content-end mb-3">
            <form method="GET">
                <select name="month" class="month-select" onchange="this.form.submit()">
                    <?php foreach (range(1,12) as $m): ?>
                        <option value="<?= date("F", mktime(0,0,0,$m,1)) ?>"
                            <?= $month == date("F", mktime(0,0,0,$m,1)) ? "selected" : "" ?>>
                            <?= date("F", mktime(0,0,0,$m,1)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <!-- BUDGETED -->
        <h5 class="budget-section-title">Budgeted categories</h5>

        <?php while ($b = $budgeted->fetch_assoc()): ?>
            <?php 
                $remaining = $b['limit_amount'] - $b['spent'];
                $percent = ($b['limit_amount'] > 0) 
                    ? min(100, ($b['spent'] / $b['limit_amount']) * 100)
                    : 0;
            ?>

            <div class="budget-item">
                <div class="budget-info">
                    <strong><?= $b['category_name'] ?></strong><br>
                    Limit: RM <?= number_format($b['limit_amount'], 2) ?><br>
                    Spent: RM <?= number_format($b['spent'], 2) ?><br>
                    Remaining: RM <?= number_format($remaining, 2) ?>
                </div>

                <div class="menu-container">
                    <button class="menu-button">•••</button>

                    <div class="menu-box">
                        <a href="#" class="menu-item edit-btn"
                           data-id="<?= $b['budget_id'] ?>"
                           data-name="<?= $b['category_name'] ?>"
                           data-limit="<?= $b['limit_amount'] ?>">Change limit</a>

                        <a href="budget.php?remove=<?= $b['budget_id'] ?>" class="menu-item">Remove budget</a>
                    </div>
                </div>
            </div>

            <!-- GREEN BAR -->
            <div class="limit-bar">
                <div class="limit-bar-fill" style="width: <?= $percent ?>%;"></div>
            </div>

        <?php endwhile; ?>

        <!-- NON-BUDGETED -->
        <h5 class="budget-section-title mt-4">Not budgeted this month</h5>

        <?php while ($c = $non_budgeted->fetch_assoc()): ?>
            <div class="budget-item">
                <strong><?= $c['category_name'] ?></strong>
                <button class="set-budget-btn"
                        data-id="<?= $c['category_id'] ?>"
                        data-name="<?= $c['category_name'] ?>">SET BUDGET</button>
            </div>
        <?php endwhile; ?>
    </div>
</div>


<!-- SET BUDGET POPUP -->
<div id="setPopup" class="popup-overlay">
    <div class="popup-box">
        <h4>Set budget</h4>
        <form method="POST">
            <input type="hidden" name="category_id" id="set_cat_id">
            <div>Name: <input type="text" id="set_cat_name" disabled></div>
            <div>Limit: <input type="number" step="0.01" name="limit_amount" required></div>
            <button name="set_budget" class="popup-save">Save</button>
        </form>
    </div>
</div>

<!-- EDIT BUDGET POPUP -->
<div id="editPopup" class="popup-overlay">
    <div class="popup-box">
        <h4>Edit budget</h4>
        <form method="POST">
            <input type="hidden" name="budget_id" id="edit_budget_id">
            <div>Name: <input type="text" id="edit_cat_name" disabled></div>
            <div>Limit: <input type="number" step="0.01" name="limit_amount" id="edit_limit" required></div>
            <button name="update_budget" class="popup-save">Save</button>
        </form>
    </div>
</div>

<script>
/* Menu toggle */
document.querySelectorAll(".menu-button").forEach(btn => {
    btn.addEventListener("click", () => {
        btn.nextElementSibling.classList.toggle("open");
    });
});

/* Open Set Budget popup */
document.querySelectorAll(".set-budget-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.getElementById("set_cat_id").value = btn.dataset.id;
        document.getElementById("set_cat_name").value = btn.dataset.name;
        document.getElementById("setPopup").style.display = "flex";
    });
});

/* Open Edit Budget popup */
document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.getElementById("edit_budget_id").value = btn.dataset.id;
        document.getElementById("edit_cat_name").value = btn.dataset.name;
        document.getElementById("edit_limit").value = btn.dataset.limit;
        document.getElementById("editPopup").style.display = "flex";
    });
});

/* Close on outside click */
document.querySelectorAll(".popup-overlay").forEach(p => {
    p.addEventListener("click", (e) => {
        if (e.target === p) p.style.display = "none";
    });
});
</script>

</body>
</html>

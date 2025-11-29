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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budgets - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts (Inter) - IMPORTANT: Load before custom CSS -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS - Load in correct order -->
    <link rel="stylesheet" href="css/nav-bar.css">
    <link rel="stylesheet" href="css/budget.css">
</head>
<body>

<div class="container-fluid px-4 py-3">

    <!-- Include Navigation Bar -->
    <?php include 'nav-bar.php'; ?>

    <!-- TABS: Records, Finance Settings, Budgets -->
    <div class="tabs-container mb-3">
        <button class="tab-button" onclick="window.location='records.php'">Records</button>
        <button class="tab-button" onclick="window.location='finance-acc.php'">Finance Settings</button>
        <button class="tab-button active" onclick="window.location='budget.php'">Budgets</button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content-box">

        <!-- MONTH DROPDOWN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="budget-section-title">Budgeted categories</h5>
            <form method="GET" class="d-inline">
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

        <!-- BUDGETED CATEGORIES -->
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
                    <span class="budget-details">Limit: RM <?= number_format($b['limit_amount'], 2) ?></span><br>
                    <span class="budget-details">Spent: RM <?= number_format($b['spent'], 2) ?></span><br>
                    <span class="budget-details">Remaining: RM <?= number_format($remaining, 2) ?></span>
                </div>

                <div class="menu-container">
                    <button class="menu-button" onclick="toggleMenu(this)">•••</button>

                    <div class="menu-box">
                        <a href="#" class="menu-item edit-btn"
                           onclick="openEditModal(<?= $b['budget_id'] ?>, '<?= $b['category_name'] ?>', <?= $b['limit_amount'] ?>); return false;">Change limit</a>

                        <a href="budget.php?remove=<?= $b['budget_id'] ?>" class="menu-item" onclick="return confirm('Are you sure you want to remove this budget?');">Remove budget</a>
                    </div>
                </div>
            </div>

            <!-- GREEN PROGRESS BAR -->
            <div class="limit-bar">
                <div class="limit-bar-fill" style="width: <?= $percent ?>%;"></div>
            </div>

        <?php endwhile; ?>

        <!-- NON-BUDGETED CATEGORIES -->
        <h5 class="budget-section-title mt-5 mb-3">Not budgeted this month</h5>

        <?php if ($non_budgeted->num_rows == 0): ?>
            <p class="text-muted">All expense categories have budgets set for this month.</p>
        <?php else: ?>
            <?php while ($c = $non_budgeted->fetch_assoc()): ?>
                <div class="budget-item-simple">
                    <strong><?= $c['category_name'] ?></strong>
                    <button class="set-budget-btn"
                            onclick="openSetModal(<?= $c['category_id'] ?>, '<?= $c['category_name'] ?>')">
                        SET BUDGET
                    </button>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>


<!-- SET BUDGET MODAL (Bootstrap Style) -->
<div class="modal fade" id="setBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content budget-modal">
            <div class="modal-header budget-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="budget-icon-circle">!</div>
                    <h5 class="modal-title mb-0">Set budget</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body budget-modal-body">
                <form method="POST" id="setBudgetForm">
                    <input type="hidden" name="set_budget" value="1">
                    <input type="hidden" name="category_id" id="set_cat_id">
                    
                    <div class="mb-3">
                        <label for="set_cat_name" class="form-label-budget">Name</label>
                        <input type="text" class="form-control-budget" id="set_cat_name" readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label for="set_limit" class="form-label-budget">Limit</label>
                        <input type="number" step="0.01" class="form-control-budget" name="limit_amount" id="set_limit" placeholder="200" required>
                    </div>
                    
                    <button type="submit" class="btn-save-budget">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- EDIT BUDGET MODAL (Bootstrap Style) -->
<div class="modal fade" id="editBudgetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content budget-modal">
            <div class="modal-header budget-modal-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="budget-icon-circle">!</div>
                    <h5 class="modal-title mb-0">Edit budget</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body budget-modal-body">
                <form method="POST" id="editBudgetForm">
                    <input type="hidden" name="update_budget" value="1">
                    <input type="hidden" name="budget_id" id="edit_budget_id">
                    
                    <div class="mb-3">
                        <label for="edit_cat_name" class="form-label-budget">Name</label>
                        <input type="text" class="form-control-budget" id="edit_cat_name" readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_limit" class="form-label-budget">Limit</label>
                        <input type="number" step="0.01" class="form-control-budget" name="limit_amount" id="edit_limit" required>
                    </div>
                    
                    <button type="submit" class="btn-save-budget">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle three dots menu
function toggleMenu(button) {
    // Close all other open menus
    document.querySelectorAll('.menu-box').forEach(menu => {
        if (menu !== button.nextElementSibling) {
            menu.classList.remove('open');
        }
    });
    
    // Toggle current menu
    button.nextElementSibling.classList.toggle('open');
}

// Close menus when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('menu-button')) {
        document.querySelectorAll('.menu-box').forEach(menu => {
            menu.classList.remove('open');
        });
    }
});

// Open Set Budget modal
function openSetModal(categoryId, categoryName) {
    document.getElementById('set_cat_id').value = categoryId;
    document.getElementById('set_cat_name').value = categoryName;
    document.getElementById('set_limit').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('setBudgetModal'));
    modal.show();
}

// Open Edit Budget modal
function openEditModal(budgetId, categoryName, currentLimit) {
    document.getElementById('edit_budget_id').value = budgetId;
    document.getElementById('edit_cat_name').value = categoryName;
    document.getElementById('edit_limit').value = currentLimit;
    
    const modal = new bootstrap.Modal(document.getElementById('editBudgetModal'));
    modal.show();
}
</script>

</body>
</html>
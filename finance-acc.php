<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch motivational quote
$sql_quote = "SELECT quote_text FROM quote WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
$result_quote = $conn->query($sql_quote);
$quote_text = "No quote available";
if ($result_quote && $result_quote->num_rows > 0) {
    $quote_text = $result_quote->fetch_assoc()['quote_text'];
}

// Handle Add New Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $account_name = trim($_POST['account_name']);
    
    if (!empty($account_name)) {
        $sql = "INSERT INTO account (user_id, account_name, is_active) VALUES (?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $account_name);
        $stmt->execute();
        header("Location: finance-acc.php?added=1");
        exit();
    }
}

// Handle Delete Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account_id'])) {
    $account_id = (int)$_POST['delete_account_id'];
    $sql = "DELETE FROM account WHERE account_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    header("Location: finance-acc.php?deleted=1");
    exit();
}

// Handle Ignore Account (set is_active = 0)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ignore_account_id'])) {
    $account_id = (int)$_POST['ignore_account_id'];
    $sql = "UPDATE account SET is_active = 0 WHERE account_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    header("Location: finance-acc.php?ignored=1");
    exit();
}

// Handle Edit Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_account'])) {
    $account_id = (int)$_POST['account_id'];
    $account_name = trim($_POST['account_name']);
    
    if (!empty($account_name)) {
        $sql = "UPDATE account SET account_name = ? WHERE account_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $account_name, $account_id, $user_id);
        $stmt->execute();
        header("Location: finance-acc.php?edited=1");
        exit();
    }
}

// Handle Restore Account (set is_active = 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_account_id'])) {
    $account_id = (int)$_POST['restore_account_id'];
    $sql = "UPDATE account SET is_active = 1 WHERE account_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $account_id, $user_id);
    $stmt->execute();
    header("Location: finance-acc.php?restored=1");
    exit();
}

// Fetch Active Accounts with Balance
$account_sql = "
    SELECT 
        a.account_id, 
        a.account_name,
        COALESCE(SUM(CASE 
            WHEN t.type = 'income' AND t.source_account_id = a.account_id THEN t.amount 
            WHEN t.type = 'expense' AND t.source_account_id = a.account_id THEN -t.amount 
            WHEN t.type = 'transfer' AND t.source_account_id = a.account_id THEN -t.amount
            WHEN t.type = 'transfer' AND t.destination_account_id = a.account_id THEN t.amount
            ELSE 0 
        END), 0) AS balance
    FROM account a
    LEFT JOIN transaction_table t ON (t.user_id = a.user_id AND (t.source_account_id = a.account_id OR t.destination_account_id = a.account_id))
    WHERE a.user_id = ? AND a.is_active = 1
    GROUP BY a.account_id, a.account_name, a.user_id
    ORDER BY a.account_name
";
$stmt = $conn->prepare($account_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$active_accounts = $result->fetch_all(MYSQLI_ASSOC);

// Fetch Ignored Accounts (is_active = 0)
$ignored_sql = "SELECT account_id, account_name FROM account WHERE user_id = ? AND is_active = 0";
$stmt = $conn->prepare($ignored_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ignored_accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Settings - PlanWise</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/finance-acc.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-fluid px-4 py-3">

  <?php include 'nav-bar.php'; ?>
    <!-- TABS: Records, Finance Settings, Budgets -->
    <div class="tabs-container mb-3">
        <button class="tab-button" onclick="window.location='records.php'">Records</button>
        <button class="tab-button active">Finance Settings</button>
        <button class="tab-button" onclick="window.location='budget.php'">Budgets</button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content-box">

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Account added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Account deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['edited'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Account updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['ignored'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Account ignored successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['restored'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Account restored successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Account/Category Toggle -->
        <div class="category-tabs-container mb-4">
            <button class="category-tab active">Account</button>
            <button class="category-tab" onclick="window.location='finance-cat.php'">Category</button>
        </div>

        <!-- ACCOUNT TAB CONTENT -->
        <div id="accountContent">
            <!-- Accounts Section -->
            <div class="category-section mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="category-title">Accounts</h3>
                    <button class="btn-add-category" onclick="showAddModal()">ADD NEW ACCOUNT</button>
                </div>
                
                <div class="categories-list">
                    <?php if (count($active_accounts) > 0): ?>
                        <?php foreach ($active_accounts as $account): ?>
                            <div class="category-item">
                                <div>
                                    <span class="category-name"><?php echo htmlspecialchars($account['account_name']); ?></span>
                                    <div class="account-balance">RM <?php echo isset($account['balance']) ? number_format((float)$account['balance'], 2) : '0.00'; ?></div>
                                </div>
                                <div class="dropdown">
                                    <button class="btn-dots" data-bs-toggle="dropdown">⋯</button>
                                    <ul class="dropdown-menu category-dropdown">
                                        <li><a class="dropdown-item" href="#" onclick="showEditModal(<?php echo $account['account_id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>')">Edit</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="showDeleteModal(<?php echo $account['account_id']; ?>, '<?php echo htmlspecialchars($account['account_name']); ?>')">Delete</a></li>
                                        <li>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="ignore_account_id" value="<?php echo $account['account_id']; ?>">
                                                <button type="submit" class="dropdown-item" style="border: none; background: none; cursor: pointer;">Ignore</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No accounts yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ignored Accounts Section -->
            <div class="category-section">
                <h3 class="category-title">Ignored Accounts</h3>
                <p class="text-muted mb-3">Restore ignored accounts to use them again in transactions.</p>
                
                <div class="categories-list">
                    <?php if (count($ignored_accounts) > 0): ?>
                        <?php foreach ($ignored_accounts as $account): ?>
                            <div class="category-item">
                                <span class="category-name"><?php echo htmlspecialchars($account['account_name']); ?></span>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="restore_account_id" value="<?php echo $account['account_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Restore</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No ignored accounts.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="add_account" value="1">
                    
                    <div class="mb-3">
                        <label for="account_name" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="account_name" name="account_name" placeholder="e.g., Cash, Savings, Credit Card" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary flex-fill">Add Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deleteAccountName"></span>"?</p>
                <form method="POST">
                    <input type="hidden" name="delete_account_id" id="deleteAccountId">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger flex-fill">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Account Modal -->
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit_account" value="1">
                    <input type="hidden" name="account_id" id="editAccountId" value="">
                    
                    <div class="mb-3">
                        <label for="edit_account_name" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="edit_account_name" name="account_name" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary flex-fill">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showAddModal() {
    const modal = new bootstrap.Modal(document.getElementById('addAccountModal'));
    modal.show();
}

function showDeleteModal(accountId, accountName) {
    document.getElementById('deleteAccountId').value = accountId;
    document.getElementById('deleteAccountName').textContent = accountName;
    const modal = new bootstrap.Modal(document.getElementById('deleteAccountModal'));
    modal.show();
}

function showEditModal(accountId, accountName) {
    document.getElementById('editAccountId').value = accountId;
    document.getElementById('edit_account_name').value = accountName;
    const modal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    modal.show();
}
</script>

</body>
</html>

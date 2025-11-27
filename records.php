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
// HANDLE EDIT TRANSACTION
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaction'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    $amount = (float)$_POST['amount'];
    $description = trim($_POST['description']);
    $txn_date_time = $_POST['txn_date_time'];
    $category_id = (int)$_POST['category_id'];
    $source_account_id = (int)$_POST['source_account_id'];
    $destination_account_id = !empty($_POST['destination_account_id']) ? (int)$_POST['destination_account_id'] : null;
    
    $sql_update = "UPDATE transaction_table 
                   SET amount = ?, description = ?, txn_date_time = ?, category_id = ?, 
                       source_account_id = ?, destination_account_id = ?
                   WHERE transaction_id = ? AND user_id = ?";
    
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("dssiiiii", $amount, $description, $txn_date_time, $category_id, 
                      $source_account_id, $destination_account_id, $transaction_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: records.php?updated=1");
        exit();
    }
}

// -------------------------------------------
// HANDLE DELETE TRANSACTION
// -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_transaction'])) {
    $transaction_id = (int)$_POST['transaction_id'];
    
    $sql_delete = "DELETE FROM transaction_table WHERE transaction_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("ii", $transaction_id, $user_id);
    
    if ($stmt->execute()) {
        header("Location: records.php?deleted=1");
        exit();
    }
}

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
// JOIN Category + Account names + include IDs
$sql = "
    SELECT 
        t.transaction_id,
        t.type,
        t.amount,
        t.description,
        t.txn_date_time,
        t.category_id,
        t.source_account_id,
        t.destination_account_id,
        
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
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// -------------------------------------------
// FETCH ACCOUNTS AND CATEGORIES FOR MODAL
// -------------------------------------------
$sql_accounts = "SELECT account_id, account_name FROM account WHERE user_id = ? AND is_active = 1 ORDER BY account_name";
$stmt_accounts = $conn->prepare($sql_accounts);
$stmt_accounts->bind_param("i", $user_id);
$stmt_accounts->execute();
$accounts = $stmt_accounts->get_result()->fetch_all(MYSQLI_ASSOC);

$sql_categories = "SELECT category_id, category_name, category_type FROM category WHERE user_id = ? AND is_active = 1 ORDER BY category_name";
$stmt_categories = $conn->prepare($sql_categories);
$stmt_categories->bind_param("i", $user_id);
$stmt_categories->execute();
$categories = $stmt_categories->get_result()->fetch_all(MYSQLI_ASSOC);
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
<?php include 'nav-bar.php'; ?>
    <!-- TOP NAV
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

   

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Transaction updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Transaction deleted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tabs-container mb-3">
        <button class="tab-button active">Records</button>
        <button class="tab-button" onclick="window.location='finance-acc.php'">Finance Settings</button>
        <button class="tab-button" onclick="window.location='budget.php'">Budgets</button>
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
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($records) == 0): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">No records found.</td>
                    </tr>
                <?php else: ?>

                    <?php foreach ($records as $r): ?>

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

                            <td>
                                <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($r)); ?>)">Edit</button>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>

            </table>
        </div>

    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editTransactionForm">
                    <input type="hidden" name="edit_transaction" value="1">
                    <input type="hidden" name="transaction_id" id="editTransactionId" value="">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_type" class="form-label">Type</label>
                            <input type="text" class="form-control" id="edit_type" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_category" class="form-label">Category</label>
                            <select class="form-control" id="edit_category" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_source_account" class="form-label">From Account</label>
                            <select class="form-control" id="edit_source_account" name="source_account_id" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['account_id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_datetime" class="form-label">Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_datetime" name="txn_date_time" required>
                        </div>
                    </div>
                    
                    <input type="hidden" name="destination_account_id" id="edit_destination_account" value="">

                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary flex-fill">Save Changes</button>
                        <button type="button" class="btn btn-danger flex-fill" onclick="deleteTransaction()">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Transfer Edit Modal -->
<div class="modal fade" id="editTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editTransferForm">
                    <input type="hidden" name="edit_transaction" value="1">
                    <input type="hidden" name="transaction_id" id="editTransferId" value="">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_transfer_type" class="form-label">Type</label>
                            <input type="text" class="form-control" id="edit_transfer_type" value="Transfer" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_transfer_amount" class="form-label">Amount</label>
                            <input type="number" step="0.01" class="form-control" id="edit_transfer_amount" name="amount" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_transfer_from" class="form-label">From Account</label>
                            <select class="form-control" id="edit_transfer_from" name="source_account_id" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['account_id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_transfer_to" class="form-label">To Account</label>
                            <select class="form-control" id="edit_transfer_to" name="destination_account_id" required>
                                <option value="">Select Account</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= $acc['account_id'] ?>"><?= htmlspecialchars($acc['account_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="edit_transfer_datetime" class="form-label">Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_transfer_datetime" name="txn_date_time" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_transfer_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_transfer_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary flex-fill">Save Changes</button>
                        <button type="button" class="btn btn-danger flex-fill" onclick="deleteTransaction()">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this transaction?</p>
            </div>
            <div class="modal-footer">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete_transaction" value="1">
                    <input type="hidden" name="transaction_id" id="deleteTransactionId" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openEditModal(transaction) {
    console.log('Edit button clicked, transaction:', transaction);
    
    try {
        // Convert datetime format from 'YYYY-MM-DD HH:MM:SS' to 'YYYY-MM-DDTHH:MM'
        const datetime = transaction.txn_date_time.replace(' ', 'T').substring(0, 16);
        
        // Check if it's a transfer
        if (transaction.type === 'transfer') {
            console.log('Opening transfer modal');
            document.getElementById('editTransferId').value = transaction.transaction_id;
            document.getElementById('edit_transfer_amount').value = transaction.amount;
            document.getElementById('edit_transfer_from').value = transaction.source_account_id;
            document.getElementById('edit_transfer_to').value = transaction.destination_account_id;
            document.getElementById('edit_transfer_datetime').value = datetime;
            document.getElementById('edit_transfer_description').value = transaction.description;
            
            const modal = new bootstrap.Modal(document.getElementById('editTransferModal'));
            modal.show();
        } else {
            // Income or Expense
            console.log('Opening income/expense modal');
            document.getElementById('editTransactionId').value = transaction.transaction_id;
            document.getElementById('edit_type').value = transaction.type.charAt(0).toUpperCase() + transaction.type.slice(1);
            document.getElementById('edit_amount').value = transaction.amount;
            document.getElementById('edit_category').value = transaction.category_id;
            document.getElementById('edit_source_account').value = transaction.source_account_id;
            document.getElementById('edit_destination_account').value = transaction.destination_account_id || '';
            document.getElementById('edit_datetime').value = datetime;
            document.getElementById('edit_description').value = transaction.description;
            
            const modal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
            modal.show();
        }
        
        console.log('Modal shown successfully');
    } catch (error) {
        console.error('Error showing modal:', error);
        alert('Error opening edit form: ' + error.message);
    }
}

function deleteTransaction() {
    let transactionId = '';
    
    // Check which modal is open to get the correct transaction ID
    const editModal = document.getElementById('editTransactionModal');
    const transferModal = document.getElementById('editTransferModal');
    
    if (editModal.classList.contains('show')) {
        transactionId = document.getElementById('editTransactionId').value;
        bootstrap.Modal.getInstance(editModal).hide();
    } else if (transferModal.classList.contains('show')) {
        transactionId = document.getElementById('editTransferId').value;
        bootstrap.Modal.getInstance(transferModal).hide();
    }
    
    document.getElementById('deleteTransactionId').value = transactionId;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    deleteModal.show();
}
</script>

</body>
</html>

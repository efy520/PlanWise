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

// Handle Add New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $category_type = $_POST['category_type'];
    
    if (!empty($category_name)) {
        $sql = "INSERT INTO category (user_id, category_name, category_type, is_active) VALUES (?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $category_name, $category_type);
        $stmt->execute();
        header("Location: finance-cat.php?added=1");
        exit();
    }
}

// Handle Delete Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category_id'])) {
    $category_id = (int)$_POST['delete_category_id'];
    $sql = "DELETE FROM category WHERE category_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    header("Location: finance-cat.php?deleted=1");
    exit();
}

// Handle Ignore Category (set is_active = 0)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ignore_category_id'])) {
    $category_id = (int)$_POST['ignore_category_id'];
    $sql = "UPDATE category SET is_active = 0 WHERE category_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    header("Location: finance-cat.php?ignored=1");
    exit();
}

// Handle Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $category_id = (int)$_POST['category_id'];
    $category_name = trim($_POST['category_name']);
    
    if (!empty($category_name)) {
        $sql = "UPDATE category SET category_name = ? WHERE category_id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $category_name, $category_id, $user_id);
        $stmt->execute();
        header("Location: finance-cat.php?edited=1");
        exit();
    }
}

// Handle Restore Category (set is_active = 1)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_category_id'])) {
    $category_id = (int)$_POST['restore_category_id'];
    $sql = "UPDATE category SET is_active = 1 WHERE category_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $category_id, $user_id);
    $stmt->execute();
    header("Location: finance-cat.php?restored=1");
    exit();
}

// Fetch Income Categories
$income_sql = "SELECT category_id, category_name FROM category WHERE user_id = ? AND category_type = 'income' AND is_active = 1";
$stmt = $conn->prepare($income_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$income_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Expense Categories
$expense_sql = "SELECT category_id, category_name FROM category WHERE user_id = ? AND category_type = 'expense' AND is_active = 1";
$stmt = $conn->prepare($expense_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expense_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch Ignored Categories (is_active = 0)
$ignored_sql = "SELECT category_id, category_name, category_type FROM category WHERE user_id = ? AND is_active = 0";
$stmt = $conn->prepare($ignored_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ignored_categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Settings - PlanWise</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/finance-cat.css">
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
            <a href="records.php" class="nav-item">Finance</a>
            <a href="dashboard.php" class="nav-item">Dashboard</a>
            <a href="profile.php" class="nav-item">Profile</a>
        </div>
    </nav>

    <!-- QUOTE -->
    <div class="quote-box mb-3">
        <p class="quote-text">"<?php echo htmlspecialchars($quote_text); ?>"</p>
    </div>

    <!-- TABS: Records, Finance Settings, Budgets -->
    <div class="tabs-container mb-3">
        <button class="tab-button" onclick="window.location='records.php'">Records</button>
        <button class="tab-button active">Finance Settings</button>
        <button class="tab-button" onclick="window.location='budgets.php'">Budgets</button>
    </div>

    <!-- MAIN CONTENT -->
    <div class="content-box">

        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Category added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Category deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['edited'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Category updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['ignored'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Category ignored successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['restored'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Category restored successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Account/Category Toggle -->
        <div class="category-tabs-container mb-4">
            <button class="category-tab" id="accountTab" onclick="showTab('account')">Account</button>
            <button class="category-tab active" id="categoryTab" onclick="showTab('category')">Category</button>
        </div>

        <!-- CATEGORY TAB CONTENT -->
        <div id="categoryContent">
            <!-- Income Categories Section -->
            <div class="category-section mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="category-title">Income Categories</h3>
                    <button class="btn-add-category" onclick="showAddModal('income')">ADD NEW CATEGORY</button>
                </div>
                
                <div class="categories-list">
                    <?php if (count($income_categories) > 0): ?>
                        <?php foreach ($income_categories as $category): ?>
                            <div class="category-item">
                                <span class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                <div class="dropdown">
                                    <button class="btn-dots" data-bs-toggle="dropdown">⋯</button>
                                    <ul class="dropdown-menu category-dropdown">
                                        <li><a class="dropdown-item" href="#" onclick="showEditModal(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">Edit</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="showDeleteModal(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">Delete</a></li>
                                        <li>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="ignore_category_id" value="<?php echo $category['category_id']; ?>">
                                                <button type="submit" class="dropdown-item" style="border: none; background: none; cursor: pointer;">Ignore</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No income categories yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expense Categories Section -->
            <div class="category-section">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="category-title">Expense Categories</h3>
                    <button class="btn-add-category" onclick="showAddModal('expense')">ADD NEW CATEGORY</button>
                </div>
                
                <div class="categories-list">
                    <?php if (count($expense_categories) > 0): ?>
                        <?php foreach ($expense_categories as $category): ?>
                            <div class="category-item">
                                <span class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                <div class="dropdown">
                                    <button class="btn-dots" data-bs-toggle="dropdown">⋯</button>
                                    <ul class="dropdown-menu category-dropdown">
                                        <li><a class="dropdown-item" href="#" onclick="showEditModal(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">Edit</a></li>
                                        <li><a class="dropdown-item" href="#" onclick="showDeleteModal(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars($category['category_name']); ?>')">Delete</a></li>
                                        <li>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="ignore_category_id" value="<?php echo $category['category_id']; ?>">
                                                <button type="submit" class="dropdown-item" style="border: none; background: none; cursor: pointer;">Ignore</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No expense categories yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Ignored Categories Section -->
            <div class="category-section">
                <h3 class="category-title">Ignored Categories</h3>
                <p class="text-muted mb-3">Restore ignored categories to use them again in transactions.</p>
                
                <div class="categories-list">
                    <?php if (count($ignored_categories) > 0): ?>
                        <?php foreach ($ignored_categories as $category): ?>
                            <div class="category-item">
                                <div>
                                    <span class="category-name"><?php echo htmlspecialchars($category['category_name']); ?></span>
                                    <span class="badge bg-secondary ms-2"><?php echo ucfirst($category['category_type']); ?></span>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="restore_category_id" value="<?php echo $category['category_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Restore</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No ignored categories.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ACCOUNT TAB CONTENT (hidden by default) -->
        <div id="accountContent" style="display: none;">
            <p class="text-muted">Account management coming soon.</p>

    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="add_category" value="1">
                    <input type="hidden" name="category_type" id="categoryType" value="">
                    
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary flex-fill">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete "<span id="deleteCategoryName"></span>"?</p>
                <form method="POST">
                    <input type="hidden" name="delete_category_id" id="deleteCategoryId">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger flex-fill">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="edit_category" value="1">
                    <input type="hidden" name="category_id" id="editCategoryId" value="">
                    
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
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
function showAddModal(type) {
    document.getElementById('categoryType').value = type;
    const modal = new bootstrap.Modal(document.getElementById('addCategoryModal'));
    modal.show();
}

function showDeleteModal(categoryId, categoryName) {
    document.getElementById('deleteCategoryId').value = categoryId;
    document.getElementById('deleteCategoryName').textContent = categoryName;
    const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}

function showEditModal(categoryId, categoryName) {
    document.getElementById('editCategoryId').value = categoryId;
    document.getElementById('edit_category_name').value = categoryName;
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function showTab(tab) {
    const accountTab = document.getElementById('accountTab');
    const categoryTab = document.getElementById('categoryTab');
    const accountContent = document.getElementById('accountContent');
    const categoryContent = document.getElementById('categoryContent');
    
    if (tab === 'account') {
        accountTab.classList.add('active');
        categoryTab.classList.remove('active');
        accountContent.style.display = 'block';
        categoryContent.style.display = 'none';
    } else {
        categoryTab.classList.add('active');
        accountTab.classList.remove('active');
        categoryContent.style.display = 'block';
        accountContent.style.display = 'none';
    }
}
</script>

</body>
</html>

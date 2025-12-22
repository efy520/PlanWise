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
// AUTO-MARK OVERDUE TASKS AS INCOMPLETE 
// -------------------------------------------
$update_sql = "
    UPDATE task 
    SET status = 'incomplete'
    WHERE user_id = ?
      AND status = 'in progress'
      AND due_date < CURDATE()
";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $user_id);
$update_stmt->execute();

// -------------------------------------------
// FETCH RANDOM ACTIVE QUOTE
// -------------------------------------------
$sql_quote = "SELECT quote_text FROM quote WHERE is_active = 1 ORDER BY RAND() LIMIT 1";
$result_quote = $conn->query($sql_quote);

$quote_text = "No quote available";
if ($result_quote && $result_quote->num_rows > 0) {
    $row = $result_quote->fetch_assoc(); 
    $quote_text = $row['quote_text'];
}

// -------------------------------------------
// GET FILTER VALUES FROM URL
// -------------------------------------------
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

// -------------------------------------------
// FETCH ALL TASKS FOR LOGGED-IN USER WITH FILTERS
// -------------------------------------------
$sql_task = "SELECT * FROM task WHERE user_id = ?";

$params = [$user_id];
$param_types = "i";

// Add search filter
if (!empty($search_query)) {
    $sql_task .= " AND (title LIKE ? OR description LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

// Add status filter
if (!empty($filter_status) && $filter_status !== 'all') {
    $sql_task .= " AND status = ?";
    $params[] = $filter_status;
    $param_types .= "s";
}

$sql_task .= " ORDER BY due_date ASC";

$stmt = $conn->prepare($sql_task);
$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result_task = $stmt->get_result();

$tasks = [];
$today = date('Y-m-d');

while ($row = $result_task->fetch_assoc()) { 


    $tasks[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Task - PlanWise</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/nav-bar.css">
    <link rel="stylesheet" href="css/task.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-fluid px-4 py-3">

    <!-- NAV BAR -->
    <?php include 'nav-bar.php'; ?>

    <!-- TABS -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="tabs-container">
                <button class="tab-button active">Task</button>
                <button class="tab-button" onclick="window.location='calendar.php'">Calendar</button>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT BOX -->
    <div class="row">
        <div class="col-12">
            <div class="content-box">

                <!-- SEARCH + FILTER + ADD BUTTON -->
                <form method="GET" action="task.php" id="filterForm">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <input 
                                type="text" 
                                class="form-control search-input" 
                                placeholder="🔍 Search task list"
                                name="search"
                                id="searchInput"
                                value="<?= htmlspecialchars($search_query) ?>"
                            >
                        </div>

                        <div class="col-md-4">
                            <select class="form-select filter-select" name="status" id="statusFilter" onchange="document.getElementById('filterForm').submit()">
                                <option value="all" <?= empty($filter_status) || $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                                <option value="in progress" <?= $filter_status === 'in progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="completed" <?= $filter_status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="incomplete" <?= $filter_status === 'incomplete' ? 'selected' : '' ?>>Incomplete (Overdue)</option>
                            </select>
                        </div>

                        <div class="col-md-4 text-end">
                            <?php if (!empty($search_query) || !empty($filter_status)): ?>
                                <button type="button" class="btn btn-sm btn-secondary me-2" onclick="clearFilters()">
                                    Clear Filters
                                </button>
                            <?php endif; ?>
                            <button type="button" class="btn-add-task" onclick="window.location='createTask.php'">
                                Add New Task
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Show active filters -->
                <?php if (!empty($search_query) || !empty($filter_status)): ?>
                    <div class="mb-3">
                        <small class="text-muted">
                            Showing 
                            <?php if (!empty($search_query)): ?>
                                results for "<strong><?= htmlspecialchars($search_query) ?></strong>"
                            <?php endif; ?>
                            <?php if (!empty($filter_status)): ?>
                                with status "<strong><?= ucfirst($filter_status) ?></strong>"
                            <?php endif; ?>
                            (<?= count($tasks) ?> task<?= count($tasks) !== 1 ? 's' : '' ?> found)
                        </small>
                    </div>
                <?php endif; ?>

                <!-- TASK TABLE -->
                <div class="task-table-container">
                    <table class="task-table">
                        <thead>
                            <tr>
                                <th style="width: 5%"></th>
                                <th style="width: 30%">TASK NAME</th>
                                <th style="width: 15%">STATUS</th>
                                <th style="width: 15%">DUE DATE</th>
                                <th style="width: 35%">DESCRIPTION</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (empty($tasks)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <?php if (!empty($search_query) || !empty($filter_status)): ?>
                                        No tasks match your search criteria. <a href="task.php">Clear filters</a>
                                    <?php else: ?>
                                        No tasks found.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>

                            <?php foreach ($tasks as $task): ?>
                            <tr>

                                <!-- EDIT BUTTON -->
                                <td>
                                    <a href="edit-task.php?task_id=<?php echo $task['task_id']; ?>" class="btn-edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                </td>

                                <!-- TITLE -->
                                <td class="task-name">
                                    <?php 
                                    // Highlight search term in title
                                    if (!empty($search_query)) {
                                        $highlighted = preg_replace(
                                            '/(' . preg_quote($search_query, '/') . ')/i',
                                            '<mark>$1</mark>',
                                            htmlspecialchars($task['title'])
                                        );
                                        echo $highlighted;
                                    } else {
                                        echo htmlspecialchars($task['title']);
                                    }
                                    ?>
                                </td>

                                <!-- STATUS BADGE -->
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $task['status'])); ?>">
                                        <?php echo htmlspecialchars($task['status']); ?>
                                    </span>
                                </td>

                                <!-- DUE DATE -->
                                <td class="due-date">
                                    <?php echo htmlspecialchars($task['due_date']); ?>
                                </td>

                                <!-- DESCRIPTION -->
                                <td class="description">
                                    <?php 
                                    // Highlight search term in description
                                    if (!empty($search_query)) {
                                        $highlighted = preg_replace(
                                            '/(' . preg_quote($search_query, '/') . ')/i',
                                            '<mark>$1</mark>',
                                            htmlspecialchars($task['description'])
                                        );
                                        echo $highlighted;
                                    } else {
                                        echo htmlspecialchars($task['description']);
                                    }
                                    ?>
                                </td>

                            </tr>
                            <?php endforeach; ?>

                        <?php endif; ?>

                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Real-time search (debounced)
let searchTimeout;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        document.getElementById('filterForm').submit();
    }, 500); // Wait 500ms after user stops typing
});

// Clear filters function
function clearFilters() {
    window.location.href = 'task.php';
}

// Highlight functionality for search results
document.addEventListener('DOMContentLoaded', function() {
    const searchQuery = "<?= addslashes($search_query) ?>";
    if (searchQuery) {
        // Add highlight style
        const style = document.createElement('style');
        style.textContent = `
            mark {
                background-color: #FFFF00;
                color: #000;
                padding: 2px 4px;
                border-radius: 3px;
                font-weight: 600;
            }
        `;
        document.head.appendChild(style);
    }
});
</script>

</body>
</html>
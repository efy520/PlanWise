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
// FETCH ALL TASKS FOR LOGGED-IN USER
// -------------------------------------------
$sql_task = "SELECT * FROM task WHERE user_id = ? ORDER BY due_date ASC";
$stmt = $conn->prepare($sql_task);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_task = $stmt->get_result();

$tasks = [];
$today = date('Y-m-d');

while ($row = $result_task->fetch_assoc()) {

    // Auto-mark overdue tasks as incomplete
    if ($row['status'] !== 'completed' && $row['due_date'] < $today) {
        $row['status'] = 'incomplete';
    }

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
    <div class="tabs-container">
    <button class="tab-button active">Task</button>
</div>


    <!-- MAIN CONTENT BOX -->
    <div class="row">
        <div class="col-12">
            <div class="content-box">

                <!-- SEARCH + FILTER + ADD BUTTON -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <input type="text" class="form-control search-input" placeholder="🔍 Search task list">
                    </div>

                    <div class="col-md-4">
                        <select class="form-select filter-select">
                            <option selected>All Status</option>
                            <option value="incomplete">Incomplete</option>
                            <option value="in progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>

                    <div class="col-md-4 text-end">
                        <button class="btn-add-task" onclick="window.location='createTask.php'">
                            Add New Task
                        </button>
                    </div>
                </div>

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
                                <td colspan="5" class="text-center">No tasks found.</td>
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
                                    <?php echo htmlspecialchars($task['title']); ?>
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
                                    <?php echo htmlspecialchars($task['description']); ?>
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

</body>
</html>
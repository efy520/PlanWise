<?php
session_start();
include 'db_connection.php';

// Ensure task_id exists
if (!isset($_GET['task_id'])) {
    header("Location: task.php");
    exit();
}

$task_id = $_GET['task_id'];
$user_id = $_SESSION['user_id'];

// Fetch task
$sql = "SELECT * FROM task WHERE task_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $task_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Task not found.";
    exit();
}
$task = $result->fetch_assoc();   
$task['status'] = strtolower($task['status']);


// UPDATE TASK
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    // Check if due date is in the past and set warning message
    $today = date('Y-m-d');
    $warning_message = "";
    if ($due_date < $today) {
       $warning_message = "⚠ This task will remain overdue until the due date is updated.";

    }

    $sql = "UPDATE task SET title=?, description=?, due_date=?, status=? WHERE task_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $title, $description, $due_date, $status, $task_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Task updated successfully!";
header("Location: task.php");
exit();

    } else {
        $error_message = "Update failed.";
    }
}

// DELETE TASK
if (isset($_POST['delete'])) {
    $sql = "DELETE FROM task WHERE task_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $task_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Task deleted successfully!";
        header("Location: task.php");
        exit();
    } else {
        $error_message = "Delete failed.";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/edit-task.css">
</head>

<body>

<div class="container mt-4">

    <a href="task.php" class="back-btn">← Back</a>

    <div class="edit-box">

        <h1 class="title-text">Edit Task</h1>

        <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <?php if(isset($warning_message)): ?>
            <div class="alert alert-warning"><?= $warning_message ?></div>
        <?php endif; ?>

        <form method="POST">

            <label class="label-text">Task name</label>
            <input type="text" class="form-control input-field" name="title"
                   value="<?= htmlspecialchars($task['title']); ?>" required>

            <label class="label-text">Description</label>
            <textarea class="form-control input-field" name="description" required><?= htmlspecialchars($task['description']); ?></textarea>

            <!-- Start date + Due date -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="label-text">Start date</label>
                    <input type="text" class="form-control input-field"
                           value="<?= date('d/m/Y', strtotime($task['created_date'])); ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="label-text">Due date</label>
                    <input type="date" class="form-control input-field"
                           name="due_date" value="<?= $task['due_date']; ?>" required>
                </div>
            </div>

            <label class="label-text mt-3">Status</label>
            <select class="form-select input-field" name="status" id="statusSelect" required>
                <option value="in progress" <?= $task['status']=='in progress'?'selected':'' ?>>In Progress</option>
                <option value="completed" <?= $task['status']=='completed'?'selected':'' ?>>Completed</option>
            </select>

            <div class="button-row mt-4">
                <a href="task.php" class="btn-cancel">Cancel</a>

                <button type="submit" name="delete" class="btn-delete">Delete</button>

                <button type="submit" name="update" class="btn-update">Update</button>
            </div>

        </form>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dueDateInput = document.querySelector('input[name="due_date"]');
    const statusSelect = document.getElementById('statusSelect');

    function checkOverdueWarning() {
        const warningId = 'dateWarning';
        const existing = document.getElementById(warningId);

        if (!dueDateInput || !statusSelect) return;

        const today = new Date();
        today.setHours(0,0,0,0);

        const selectedDate = new Date(dueDateInput.value);
        selectedDate.setHours(0,0,0,0);

        const isInProgress = statusSelect.value === 'in progress';
        const isPast = selectedDate < today;

        if (isInProgress && isPast) {
            if (!existing) {
                const alertDiv = document.createElement('div');
                alertDiv.id = warningId;
                alertDiv.className = 'alert alert-warning mt-2';
                alertDiv.innerHTML = '⚠ This task will remain overdue until the due date is updated.';
                statusSelect.parentElement.appendChild(alertDiv);
            }
        } else {
            if (existing) existing.remove();
        }
    }

    dueDateInput.addEventListener('change', checkOverdueWarning);
    statusSelect.addEventListener('change', checkOverdueWarning);

    checkOverdueWarning(); // run once on load
});
</script>


</body>
</html>

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

// UPDATE TASK
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    $sql = "UPDATE task SET title=?, description=?, due_date=?, status=? WHERE task_id=? AND user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $title, $description, $due_date, $status, $task_id, $user_id);

    if ($stmt->execute()) {
        header("Location: task.php?updated=1");
        exit();
    } else {
        $error_message = "Update failed.";
    }
}

// DELETE TASK
if (isset($_POST['delete'])) {
    $sql_delete = "DELETE FROM task WHERE task_id=? AND user_id=?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $task_id, $user_id);

    if ($stmt_delete->execute()) {
        header("Location: task.php?deleted=1");
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
                           value="<?= $task['created_date']; ?>" readonly>
                </div>

                <div class="col-md-6">
                    <label class="label-text">Due date</label>
                    <input type="date" class="form-control input-field"
                           name="due_date" value="<?= $task['due_date']; ?>" required>
                </div>
            </div>

            <label class="label-text mt-3">Status</label>
            <select class="form-select input-field" name="status" required>
                <option value="in progress" <?= $task['status']=='in progress'?'selected':'' ?>>In Progress</option>
                <option value="completed" <?= $task['status']=='completed'?'selected':'' ?>>Completed</option>
                <option value="incomplete" <?= $task['status']=='incomplete'?'selected':'' ?>>Incomplete</option>
            </select>

            <div class="button-row mt-4">
                <a href="task.php" class="btn-cancel">Cancel</a>

                <button type="submit" name="delete" class="btn-delete">Delete</button>

                <button type="submit" name="update" class="btn-update">Update</button>
            </div>

        </form>

    </div>

</div>

</body>
</html>

<?php
session_start();
include 'db_connection.php';

// Block access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$success_msg = "";
$error_msg = "";

// When submitting the form
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    // Default status for new tasks
    $status = "in progress";

    // Insert task
    $sql = "INSERT INTO task (user_id, title, description, due_date, status, created_date)
            VALUES (?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issss", $user_id, $title, $description, $due_date, $status);

    if ($stmt->execute()) {
        header("Location: task.php");
        exit();
    } else {
        $error_msg = "Failed to create task.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - PlanWise</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/createTask.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="create-container">

    <a href="task.php" class="back-btn">← Back</a>

    <h1 class="title-text">Create Task</h1>

    <!-- Error message -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <!-- Create Task Form -->
    <form action="createTask.php" method="POST">

        <label class="label-text">Task name</label>
        <input type="text" name="title" class="input-box" placeholder="Type name" required>

        <label class="label-text">Description</label>
        <textarea name="description" class="input-box" placeholder="Give some examples" rows="3"></textarea>

        <label class="label-text">Due date</label>
        <input type="date" name="due_date" class="date-box" required>

        <div class="button-row">
            <a href="task.php" class="btn-cancel">Cancel</a>
            <button type="submit" class="btn-create">Create</button>
        </div>

    </form>

</div>

</body>
</html>

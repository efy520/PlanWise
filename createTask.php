<?php
session_start();
include 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $status = "pending";

    if (!empty($title) && !empty($due_date)) {

        $sql = "INSERT INTO task (user_id, title, description, due_date, created_date, status)
                VALUES (?, ?, ?, ?, NOW(), ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $title, $description, $due_date, $status);

        if ($stmt->execute()) {
            header("Location: task.php");
            exit();
        } else {
            $error = "Failed to create task.";
        }

    } else {
        $error = "Title and Due Date are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Task - PlanWise</title>

    <link rel="stylesheet" href="css/createTask.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container">

    <a href="task.php" class="back-btn">← Back</a>

    <h1 class="create-title">Create Task</h1>

    <form action="createTask.php" method="POST" class="task-form">

        <?php if (!empty($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <label class="label">Task name</label>
        <input type="text" name="title" class="input-field" placeholder="Type name" required>

        <label class="label">Description</label>
        <textarea name="description" class="textarea-field" placeholder="Give some examples"></textarea>

        <label class="label">Due date</label>
        <input type="date" name="due_date" class="date-field" required>

        <div class="button-row">
            <button type="button" onclick="window.location='task.php'" class="cancel-btn">Cancel</button>
            <button type="submit" class="create-btn">Create</button>
        </div>

    </form>

</div>

</body>
</html>

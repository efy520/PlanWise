<?php
session_start();
include 'db_connection.php';

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Autofill due date from calendar (if provided)
$autoDue = isset($_GET['due_date']) ? $_GET['due_date'] : "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];

    $created_date = date('Y-m-d');      // system-controlled
    $status = "in progress";            // system-controlled

    // -------------------------------
    // SERVER-SIDE VALIDATION
    // -------------------------------
    if (empty($title) || empty($description) || empty($due_date)) {
        $error_message = "All fields are required.";
    } else {
        $today = date('Y-m-d');
        if ($due_date < $today) {
            $error_message = "Due date cannot be earlier than today.";
        }
    }

    // -------------------------------
    // INSERT TASK (ONLY IF VALID)
    // -------------------------------
    if (!isset($error_message)) {
        $sql = "INSERT INTO task 
                (user_id, title, description, due_date, created_date, status)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssss",
            $user_id,
            $title,
            $description,
            $due_date,
            $created_date,
            $status
        );

        if ($stmt->execute()) {
            header("Location: task.php?created=1");
            exit();
        } else {
            $error_message = "Failed to create task. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Task</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/createTask.css">
</head>

<body>

<div class="container mt-4">

    <a href="task.php" class="back-btn">← Back</a>

    <div class="create-box">

        <h1 class="title-text">Create Task</h1>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <!-- Task Title -->
            <label class="label-text">Task name</label>
            <input type="text"
                   class="form-control input-field"
                   name="title"
                   placeholder="Type name"
                   required>

            <!-- Description -->
            <label class="label-text mt-3">Description</label>
            <textarea class="form-control input-field"
                      name="description"
                      placeholder="Give some examples"
                      required></textarea>

            <!-- Dates -->
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="label-text">Start date</label>
                    <input type="text"
                           class="form-control input-field"
                           value="<?= date('Y-m-d'); ?>"
                           readonly>
                </div>

                <div class="col-md-6">
                    <label class="label-text">Due date</label>
                    <input type="date"
                           class="form-control input-field"
                           name="due_date"
                           value="<?= htmlspecialchars($autoDue) ?>"
                           required>
                </div>
            </div>

            <!-- Buttons -->
            <div class="button-row mt-4">
                <a href="task.php" class="btn-cancel">Cancel</a>
                <button type="submit" class="btn-create">Create</button>
            </div>

        </form>

    </div>
</div>

</body>
</html>

<?php
session_start();
// Redirect if not logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

// require_once 'php_includes/db_connection.php';

// // --- 1. GET THE TASK ID ---
// We need to know WHICH task we are editing.
// In a real app, we would get this from the URL: edit-task.php?task_id=5
$task_id = isset($_GET['task_id']) ? $_GET['task_id'] : null;

// --- 2. FETCH EXISTING DATA (Placeholder) ---
// Ideally, you run a SQL query here: SELECT * FROM Task WHERE task_id = $task_id
// For now, I will create dummy data so you can see the form filled out.
$task = [
    'name' => 'Design team’s 3rd-quarter review',
    'description' => 'Review the quarterly performance and set goals for Q4.',
    'start_date' => '2023-12-01',
    'due_date' => '2023-12-05',
    'status' => 'Incomplete'
];

// --- 3. HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // This is where you would write the UPDATE SQL query
    // $sql = "UPDATE Task SET ... WHERE task_id = $task_id";
    
    // After saving, redirect back to the task list
    header("Location: task.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/edit-task.css">
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <main class="card shadow-sm border-0 p-4 p-md-5 rounded-4">
                    
                    <!-- Header Section with Back Button -->
                    <div class="d-flex align-items-center mb-4">
                        <!-- Back Button (Link to task.php) -->
                        <a href="task.php" class="back-btn me-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 40 40" fill="none">
                                <path d="M34 20L6 20" stroke="white" stroke-width="2.4" stroke-miterlimit="10"/>
                                <path d="M15 29L6 20L15 11" stroke="white" stroke-width="2.4" stroke-miterlimit="10" stroke-linecap="square"/>
                            </svg>
                        </a>
                        <h1 class="page-title mb-0">Edit task</h1>
                    </div>

                    <!-- Edit Task Form -->
                    <form action="edit-task.php" method="POST">
                        
                        <!-- Task Name -->
                        <div class="mb-3">
                            <label for="task_name" class="form-label">Task name</label>
                            <input type="text" class="form-control" id="task_name" name="task_name" 
                                   value="<?php echo $task['name']; ?>" placeholder="Type name">
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <!-- Textarea for longer text -->
                            <textarea class="form-control description-box" id="description" name="description" rows="3" placeholder="Give some examples"><?php echo $task['description']; ?></textarea>
                        </div>

                        <!-- Dates Row -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">Start date</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $task['start_date']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="due_date" class="form-label">Due date</label>
                                <div class="input-group">
                                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $task['due_date']; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-5">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option <?php echo ($task['status'] == 'Incomplete') ? 'selected' : ''; ?>>Incomplete</option>
                                <option <?php echo ($task['status'] == 'In progress') ? 'selected' : ''; ?>>In progress</option>
                                <option <?php echo ($task['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option <?php echo ($task['status'] == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <!-- Left: Cancel -->
                            <a href="task.php" class="btn btn-cancel">Cancel</a>
                            
                            <!-- Right: Delete and Edit -->
                            <div class="d-flex gap-3">
                                <!-- Delete Button (Ideally this would be a form submission or modal trigger) -->
                                <button type="button" class="btn btn-delete">Delete</button>
                                
                                <!-- Save/Edit Button -->
                                <button type="submit" class="btn btn-edit">Edit</button>
                            </div>
                        </div>

                    </form>
                </main>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
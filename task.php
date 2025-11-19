<?php
session_start();
// Check if user is logged in, if not, redirect to login page
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit;
// }

// // Include database connection (needed to pull tasks and quote)
// require_once 'php_includes/db_connection.php'; 

// $user_id = $_SESSION['user_id'];
// $username = $_SESSION['username'];
// $motivational_quote = "Hustle in silence and let your success make the noise"; // Placeholder quote

// --- TO DO: Fetch Tasks from Database ---
// Later, you will write a query here: 
// $tasks_sql = "SELECT * FROM Task WHERE user_id = $user_id ORDER BY due_date ASC";
// $tasks_result = $conn->query($tasks_sql);

// --- TO DO: Fetch Random Quote from Database ---
// Later, you will write a query here to pull one random quote from the D7 Quote table.
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/task.css"> 
    
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

    <!-- Main Application Container -->
    <div class="container-fluid">
        <div class="row">
            
            <!-- --- Main Content Area (Task Page) --- -->
            <main class="col-12 px-0">
                
                <!-- Fixed Header/Nav Bar (PlanWise Logo & Links) -->
                <nav class="app-nav d-flex justify-content-between align-items-center py-3 px-4 shadow-sm bg-white">
                    <!-- Logo and Brand Name -->
                    <div class="d-flex align-items-center logo-section">
                        <img src="images/logo.png" alt="PlanWise Logo" class="nav-logo">
                        <!-- Navigation Links -->
                        <div class="d-none d-md-flex nav-links gap-4">
                            <a href="#" class="nav-link-item active">To-Do</a>
                            <a href="#" class="nav-link-item">Finance</a>
                            <a href="#" class="nav-link-item">Dashboard</a>
                            <a href="#" class="nav-link-item">Profile</a>
                        </div>
                    </div>
                    
                    <!-- User Info & Logout -->
                    <div class="user-info text-end">
                        <span class="fw-semibold" style="color: #4E56C0;"><?php echo htmlspecialchars($username); ?></span> 
                        <small><a href="logout.php" class="text-muted text-decoration-none d-block">Logout</a></small>
                    </div>
                </nav>

                <!-- Page Content Area -->
                <div class="container mt-4 mb-5">
                    
                    <!-- 1. Motivational Quote -->
                    <div class="quote-box mb-4 p-3 rounded-3" style="background-color: #ffffff;">
                        <h5 class="mb-0 fst-italic quote-text">"<?php echo $motivational_quote; ?>"</h5>
                        <!-- The unknown quote writer has been removed -->
                    </div>

                    <!-- 2. Task/Calendar Tabs -->
                    <div class="tab-controls d-flex rounded-3 overflow-hidden shadow-sm mb-4">
                        <a href="#" class="tab-button active">Task</a>
                        <a href="calendar.php" class="tab-button">Calendar</a>
                    </div>

                    <!-- 3. Search and Action Row -->
                    <div class="row align-items-center mb-4">
                        <!-- Search Box -->
                        <div class="col-lg-5 col-md-6 mb-3 mb-md-0">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 search-icon">
                                    <!-- Search Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#BDC1CA" class="bi bi-search" viewBox="0 0 16 16">
                                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.397l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zM6.5 11a4.5 4.5 0 1 1 0-9 4.5 4.5 0 0 1 0 9z"/>
                                    </svg>
                                </span>
                                <input type="text" class="form-control border-start-0" placeholder="Search task list">
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-lg-3 col-md-3 mb-3 mb-md-0">
                            <select class="form-select bg-white status-dropdown">
                                <option selected>All Status</option>
                                <option>Completed</option>
                                <option>In progress</option>
                                <option>Pending</option>
                                <option>Incomplete</option>
                            </select>
                        </div>

                        <!-- Add New Task Button -->
                        <div class="col-lg-4 col-md-3">
                            <button class="btn w-100 add-task-btn" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                                Add New Task
                            </button>
                        </div>
                    </div>

                    <!-- 4. Task List Table -->
                    <div class="task-table-wrapper rounded-3 shadow-sm overflow-hidden bg-white">
                        <table class="table task-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 30%;">TASK NAME</th>
                                    <th style="width: 15%;">STATUS</th>
                                    <th style="width: 15%;">DUE DATE</th>
                                    <th style="width: 40%;">DESCRIPTION</th> 
                                </tr>
                            </thead>
                            <tbody>
                                <!-- --- PHP LOOP START (Demo Data) --- -->
                                <?php 
                                    $demo_tasks = [
                                        ['id' => 1, 'name' => 'Set up payment method', 'status' => 'Incomplete', 'date' => '07/12/2023', 'desc' => 'Gather financial information for a smooth setup'],
                                        ['id' => 2, 'name' => 'Freelancer contract signing', 'status' => 'Incomplete', 'date' => '05/12/2023', 'desc' => 'Review and sign contract'],
                                        ['id' => 3, 'name' => 'Design team quarterly review', 'status' => 'Completed', 'date' => '11/12/2023', 'desc' => 'Finalize Q3 presentation'],
                                        ['id' => 4, 'name' => 'Complete Monthly Reports', 'status' => 'In progress', 'date' => '12/12/2023', 'desc' => 'Create a concise, impactful presentation with key metrics'],
                                        ['id' => 5, 'name' => 'Prepare Presentation for Board', 'status' => 'Pending', 'date' => '15/12/2023', 'desc' => 'Reviewing the quarter financial results.'],
                                        ['id' => 6, 'name' => 'Review and Approve Budget', 'status' => 'Completed', 'date' => '17/12/2023', 'desc' => 'Finalize 2024 budget allocation.'],
                                    ];
                                    
                                    foreach ($demo_tasks as $task) :
                                        $status_class = str_replace(' ', '-', strtolower($task['status']));
                                ?>
                                <tr>
                                    <td class="task-name-col">
                                        <!-- NOW A CLICKABLE LINK TO THE EDIT PAGE -->
                                        <a href="edit-task.php?task_id=<?php echo $task['id']; ?>" class="edit-link me-2">
                                            <!-- Edit Icon (Pen) -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil-fill edit-icon" viewBox="0 0 16 16">
                                                <path d="M12.854.146a.5.5 0 0 0-.707 0L10.5 1.793 14.207 5.5l1.647-1.646a.5.5 0 0 0 0-.708l-3-3zm-9.01 7.15L3 12.5v3h3.5l6.5-6.5-3.5-3.5-6.5 6.5z"/>
                                            </svg>
                                        </a>
                                        <?php echo htmlspecialchars($task['name']); ?>
                                    </td>
                                    <td class="status-col">
                                        <span class="status-badge status-<?php echo $status_class; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['date']); ?></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($task['desc']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <!-- --- PHP LOOP END --- -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>

        </div>
    </div>
    
    <!-- Bootstrap JS (must be at the end) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
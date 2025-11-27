<?php
session_start();
include 'db_connection.php';

// Ensure user logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Handle deletion from modal (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task_id'])) {
    $del_id = (int) $_POST['delete_task_id'];
    $stmt = $conn->prepare("DELETE FROM task WHERE task_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    // redirect back to same month to avoid resubmission
    $m = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
    $y = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
    header("Location: calendar.php?m={$m}&y={$y}&deleted=1");
    exit();
}

// determine month/year to show
$month = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n'); // 1-12
$year  = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');

// normalize month/year
if ($month < 1) { $month = 12; $year -= 1; }
if ($month > 12) { $month = 1; $year += 1; }

// compute first day of month and number of days
$firstOfMonth = new DateTime("{$year}-{$month}-01");
$daysInMonth = (int)$firstOfMonth->format('t');
$startWeekday = (int)$firstOfMonth->format('w'); // 0 (Sun) - 6 (Sat)

// fetch tasks for this month for this user
$startMonth = $firstOfMonth->format('Y-m-01');
$endMonthDT = clone $firstOfMonth;
$endMonthDT->modify('last day of this month');
$endMonth = $endMonthDT->format('Y-m-d');

$sql = "SELECT task_id, title, description, due_date, status FROM task 
        WHERE user_id = ? AND due_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $startMonth, $endMonth);
$stmt->execute();
$res = $stmt->get_result();

$tasks = [];
// index tasks by yyyy-mm-dd
while ($row = $res->fetch_assoc()) {
    $d = $row['due_date'];
    if (!isset($tasks[$d])) $tasks[$d] = [];
    $tasks[$d][] = $row;
}

// Today's date for highlight
$today = date('Y-m-d');

// month name
$monthName = $firstOfMonth->format('F');

// prepare prev/next links
$prevDT = clone $firstOfMonth;
$prevDT->modify('-1 month');
$nextDT = clone $firstOfMonth;
$nextDT->modify('+1 month');

$prevM = (int)$prevDT->format('n'); $prevY = (int)$prevDT->format('Y');
$nextM = (int)$nextDT->format('n'); $nextY = (int)$nextDT->format('Y');

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Calendar - PlanWise</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap (for modal + responsive grid) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Your calendar styles -->
    <link rel="stylesheet" href="css/calendar.css">
</head>
<body>
<div class="container-fluid px-4 py-3">

    <!-- TOP NAVIGATION BAR -->
    <div class="row mb-3">
        <div class="col-12">
            <nav class="navbar-custom">
                <div class="logo-container">
                    <img src="images/logo.png" alt="PlanWise Logo" class="logo">
                </div>

                <div class="nav-menu">
                    <a href="task.php" class="nav-item active">To-Do</a>
                    <a href="finance.php" class="nav-item">Finance</a>
                    <a href="dashboard.php" class="nav-item">Dashboard</a>
                    <a href="profile.php" class="nav-item">Profile</a>
                </div>
            </nav>
        </div>
        
    </div>



   <!-- QUOTE BOX -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="quote-box">
                <p class="quote-text">
                    "<?php echo htmlspecialchars($quote_text); ?>"
                </p>
            </div>
        </div>
    </div>
    <!-- calendar header: month & navigation -->
    <div class="calendar-header d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
            <a href="calendar.php?m=<?= $prevM ?>&y=<?= $prevY ?>" class="btn btn-sm btn-light">&lt;</a>
            <div class="month-name">
                <strong><?= htmlspecialchars($monthName . ' ' . $year) ?></strong>
            </div>
            <a href="calendar.php?m=<?= $nextM ?>&y=<?= $nextY ?>" class="btn btn-sm btn-light">&gt;</a>
        </div>
<div>
            <a href="createTask.php" id="btnNewTask" class="btn btn-new-task">+ New Task</a>
        </div>
    </div>

 <!-- TABS -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="tabs-container">
                 <button class="tab-button" onclick="window.location='task.php'">Task</button>
                 <button class="tab-button active">Calendar</button>
               
            </div>
        </div>
    </div>
   

    <!-- Calendar box -->
    <div class="calendar-wrap p-4">
        <!-- week day labels -->
        <div class="weekdays d-flex">
            <?php
            $weekdays = ['SUN','MON','TUE','WED','THU','FRI','SAT'];
            foreach ($weekdays as $wd) {
                echo "<div class='weekday'>$wd</div>";
            }
            ?>
        </div>

        <!-- grid -->
        <div class="calendar-grid">
            <?php
            // blank cells before first day
            $cell = 0;
            for ($i = 0; $i < $startWeekday; $i++, $cell++) {
                echo "<div class='day empty'></div>";
            }

            // days of month
            for ($day = 1; $day <= $daysInMonth; $day++, $cell++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $isToday = $dateStr === $today;
                $hasTask = isset($tasks[$dateStr]) && count($tasks[$dateStr]) > 0;

                // CSS classes
                $classes = 'day';
                if ($isToday) $classes .= ' today';
                if ($hasTask) $classes .= ' has-task';

                // display day number; show gold dot if has task
                $dotHtml = $hasTask ? "<span class='task-dot' aria-hidden='true'></span>" : "";
                // clickable — data-date attribute for JS
                echo "<div class='{$classes}' data-date='{$dateStr}' tabindex='0'>";
                echo "<div class='date-row'><span class='date-number'>{$day}</span>{$dotHtml}</div>";
                echo "</div>";
            }

            // trailing blanks to finish last week (optional)
            while (($cell % 7) !== 0) {
                echo "<div class='day empty'></div>";
                $cell++;
            }
            ?>
        </div>
    </div>
</div>

<!-- Modal: show tasks on selected day - UPDATED DESIGN -->
<div class="modal fade" id="dayModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content task-modal">
      <!-- Modal Header with orange circle icon -->
      <div class="modal-header task-modal-header">
        <div class="d-flex align-items-center gap-2">
          <div class="task-icon-circle">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
              <path d="M9 11l3 3L22 4"></path>
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
            </svg>
          </div>
          <h5 class="modal-title mb-0">Task</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- Modal Body with task list -->
      <div class="modal-body task-modal-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <div class="task-date-label">Date</div>
            <div class="task-date-value" id="modalDate"></div>
          </div>
        </div>

        <!-- Task Name Header -->
        <div class="task-section-header mb-2">Task Name</div>
        
        <!-- Tasks List -->
        <div id="modalTasksList" class="tasks-list">
            <!-- populated by JS -->
        </div>
      </div>
      
      <!-- Modal Footer - Add Task button -->
      <div class="modal-footer task-modal-footer">
        <a id="modalAddTask" class="btn btn-add-task-modal">Add Task</a>
        <button type="button" class="btn btn-close-modal" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- tasks data embedded for JS -->
<script>
    // tasksByDate: { "YYYY-MM-DD": [ {task_id, title, description, status, due_date}, ... ] }
    const tasksByDate = <?= json_encode($tasks, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
    const userId = <?= json_encode($user_id) ?>;
</script>

<!-- Bootstrap + small JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function(){
    const dayCells = document.querySelectorAll('.calendar-grid .day:not(.empty)');
    const modal = new bootstrap.Modal(document.getElementById('dayModal'));
    const modalDateElem = document.getElementById('modalDate');
    const modalTasksList = document.getElementById('modalTasksList');
    const modalAddTask = document.getElementById('modalAddTask');

    function renderTasksFor(dateStr) {
        modalTasksList.innerHTML = '';
        const arr = tasksByDate[dateStr] || [];
        
        if (arr.length === 0) {
            modalTasksList.innerHTML = '<p class="text-muted text-center py-3">No tasks for this day.</p>';
            return;
        }
        
        // Create task items matching Figma design
        arr.forEach(t => {
            const taskItem = document.createElement('div');
            taskItem.className = 'task-item';
            
            // Create action buttons with colors from Figma
            taskItem.innerHTML = `
                <div class="task-item-content">
                    <div class="task-title">${escapeHtml(t.title)}</div>
                    <div class="task-description">${escapeHtml(t.description || '')}</div>
                </div>
                <div class="task-actions">
                    <a href="edit-task.php?task_id=${encodeURIComponent(t.task_id)}" class="btn-task-action btn-edit-yellow" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form method="POST" style="display:inline-block;margin:0;">
                        <input type="hidden" name="delete_task_id" value="${escapeHtml(t.task_id)}">
                        <button type="submit" class="btn-task-action btn-complete-green" name="mark_done" title="Complete">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    </form>
                    <form method="POST" style="display:inline-block;margin:0;">
                        <input type="hidden" name="delete_task_id" value="${escapeHtml(t.task_id)}">
                        <button type="submit" class="btn-task-action btn-delete-red" onclick="return confirm('Delete this task?');" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            `;
            modalTasksList.appendChild(taskItem);
        });
    }

    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    dayCells.forEach(cell=>{
        cell.addEventListener('click', () => {
            const dateStr = cell.getAttribute('data-date');
            modalDateElem.textContent = dateStr;
            renderTasksFor(dateStr);
            modalAddTask.href = 'createTask.php?due_date=' + encodeURIComponent(dateStr);
            modal.show();
        });
        cell.addEventListener('keydown', (e)=>{
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                cell.click();
            }
        });
    });

})();
</script>

</body>
</html>
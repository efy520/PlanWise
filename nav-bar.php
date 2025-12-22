<?php
// Make sure db is loaded BEFORE include
if (!isset($conn)) {
    include 'db_connection.php';
}

// Use session quote if available, otherwise fetch one
$quote_text = isset($_SESSION['quote_text']) ? $_SESSION['quote_text'] : "No quote available";

// Detect current file
$current = basename($_SERVER['PHP_SELF']);
?>

<!-- NAV BAR ONLY (NO <html>, NO <head>, NO <body>) -->
<nav class="navbar-custom mb-3">
    <img src="images/logo.png" class="logo">

    <div class="nav-menu">
        <a href="task.php" class="nav-item <?= $current == 'task.php' ? 'active' : '' ?>">To-Do</a>

        <a href="records.php"
           class="nav-item <?= 
                ($current == 'records.php' ||
                 $current == 'finance-acc.php' ||
                 $current == 'budget.php' ||
                 str_contains($current, 't-'))
                 ? 'active' : '' ?>">
            Finance
        </a>

        <a href="dashboard.php" class="nav-item <?= $current == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="profile.php" class="nav-item <?= $current == 'profile.php' ? 'active' : '' ?>">Profile</a>
    </div>
</nav>

<!-- QUOTE BOX -->
<div class="quote-box mb-3">
    <p class="quote-text">"<?php echo htmlspecialchars($quote_text); ?>"</p>
</div>

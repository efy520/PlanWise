<?php
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
<html>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/task.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar-custom mb-3">
    <img src="images/logo.png" class="logo">

    <div class="nav-menu">
        <a href="task.php" 
           class="nav-item <?= $currentPage == 'task.php' ? 'active' : '' ?>">To-Do</a>

        <a href="records.php" 
           class="nav-item <?= 
                ($currentPage == 'records.php' ||
                 $currentPage == 'budget.php' ||
                 $currentPage == 'finance-acc.php' ||
                 str_contains($currentPage, 't-')  // t-income, t-expense, t-transfer
                ) 
                ? 'active' : '' 
            ?>">Finance</a>

        <a href="dashboard.php" 
           class="nav-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>

        <a href="profile.php" 
           class="nav-item <?= $currentPage == 'profile.php' ? 'active' : '' ?>">Profile</a>
    </div>
</nav>

    

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

</html>
<?php
// Start session
session_start();

// Connect to database
include 'db_connection.php';

// Check if user is admin (hardcoded - only admin can access)
$is_admin = true; // Hardcoded for now

if (!$is_admin) {
    header("Location: login.php");
    exit();
}

// Fetch all users from database
$sql = "SELECT user_id, username, email, phone, created_date FROM users ORDER BY created_date DESC";
$result = $conn->query($sql);

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for User Detail page -->
    <link rel="stylesheet" href="css/user-detail.css">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jsPDF library for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>

<body>

<!-- Main container with sidebar and content -->
<div class="user-detail-container">
    
    <!-- Left Sidebar - Red -->
    <div class="sidebar">
        <!-- Logo -->
        <div class="logo-container">
            <img src="images/logo.png" alt="PlanWise Logo" class="logo">
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-link">Dashboard</a>
            <a href="quote.php" class="nav-link">Quote</a>
            <a href="user-detail.php" class="nav-link active">User Detail</a>
        </nav>
    </div>
    
    <!-- Right Content Area -->
    <div class="content-area">
        
        <!-- Header Section -->
        <div class="content-header">
            <div>
                <h1 class="page-title">User Details</h1>
                <p class="page-section">Section 2</p>
            </div>
            <button class="btn-export-pdf" onclick="exportToPDF()">Export to PDF</button>
        </div>
        
        <!-- Users Table -->
        <div class="users-table-container">
            <table class="users-table" id="usersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
    
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Export table to PDF
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Add title
    doc.setFontSize(18);
    doc.text('User Details Report', 14, 20);
    
    // Add date
    doc.setFontSize(11);
    doc.text('Generated on: ' + new Date().toLocaleDateString(), 14, 28);
    
    // Get table data
    const table = document.getElementById('usersTable');
    const rows = [];
    
    // Get table headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    
    // Get table body data
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push(td.textContent);
        });
        if (row.length > 0) {
            rows.push(row);
        }
    });
    
    // Add table to PDF
    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 35,
        theme: 'grid',
        headStyles: {
            fillColor: [167, 39, 3], // Red color #A72703
            textColor: [255, 255, 255],
            fontStyle: 'bold'
        },
        styles: {
            fontSize: 10,
            cellPadding: 5
        }
    });
    
    // Save PDF
    doc.save('user-details-' + new Date().getTime() + '.pdf');
}
</script>

</body>
</html>
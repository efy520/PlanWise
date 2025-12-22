<?php
session_start();
include 'db_connection.php';

// TEMP: hardcoded admin access
$is_admin = true;
if (!$is_admin) {
    header("Location: login.php");
    exit();
}

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch users
$sql = "SELECT username, email, phone, gender, created_date 
        FROM users 
        ORDER BY created_date DESC";
$result = $conn->query($sql);

$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Stats
$sql_stats = "
    SELECT 
        COUNT(*) AS total_users,
        SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) AS total_male,
        SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) AS total_female
    FROM users
";
$stats = $conn->query($sql_stats)->fetch_assoc();

$total_users  = $stats['total_users'] ?? 0;
$total_male   = $stats['total_male'] ?? 0;
$total_female = $stats['total_female'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Details - Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet" href="css/user-detail.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
</head>

<body>

<div class="user-detail-container">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="logo-container">
            <img src="images/logo.png" class="logo">
        </div>

        <!-- Logout Button -->
         <div class="btn"><a href="quote.php?logout=1" class="btn-logout-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            LOG OUT
        </a></div>

        <nav class="sidebar-nav">
            <a href="admin-dashboard.php" class="nav-link">Dashboard</a>
            <a href="quote.php" class="nav-link">Quote</a>
            <a href="user-detail.php" class="nav-link active">User Detail</a>
        </nav>
    </div>

    <!-- CONTENT -->
    <div class="content-area">

        <div class="content-header">
            <h1 class="page-title">User Details</h1>
            <button class="btn-export-pdf" onclick="exportToPDF()">Export to PDF</button>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card total-card">
                <div class="stat-icon">👥</div>
                <div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-value"><?= $total_users ?></div>
                </div>
            </div>
            <div class="stat-card male-card">
                <div class="stat-icon">♂️</div>
                <div>
                    <div class="stat-label">Total Male</div>
                    <div class="stat-value"><?= $total_male ?></div>
                </div>
            </div>
            <div class="stat-card female-card">
                <div class="stat-icon">♀️</div>
                <div>
                    <div class="stat-label">Total Female</div>
                    <div class="stat-value"><?= $total_female ?></div>
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <div class="users-table-container">
            <table class="users-table" id="usersTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Created date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= ucfirst($u['gender'] ?? 'Unknown') ?></td>
                        <td><?= date('d/m/Y', strtotime($u['created_date'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    doc.setFontSize(18);
    doc.text("User Details Report", 14, 18);

    doc.setFontSize(11);
    doc.text("Generated on: " + new Date().toLocaleDateString(), 14, 26);

    const table = document.getElementById("usersTable");
    const headers = [];
    const rows = [];

    table.querySelectorAll("thead th").forEach(th => {
        headers.push(th.textContent.trim());
    });

    table.querySelectorAll("tbody tr").forEach(tr => {
        const row = [];
        tr.querySelectorAll("td").forEach((td, i) => {
            row.push(td.textContent.trim());
        });
        rows.push(row);
    });

    doc.autoTable({
        head: [headers],
        body: rows,
        startY: 32,
        theme: "grid",

        styles: {
            fontSize: 10,
            cellPadding: 2,
            valign: "top" // 🔥 IMPORTANT
        },

        columnStyles: {
            3: { cellWidth: 22, valign: "top" } // Gender column
        },

        headStyles: {
            fillColor: [167, 39, 3],
            textColor: 255,
            fontStyle: "bold"
        }
    });

    doc.save("user-details.pdf");
}
</script>

</body>
</html>

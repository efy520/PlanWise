<?php
// Start session
session_start();

// Connect to database
include 'db_connection.php';

// Check if user is admin (hardcoded - only admin can access)
// You can check against user_id or role from database
$is_admin = true; // Hardcoded for now - you can add proper admin check

if (!$is_admin) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle delete quote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_quote_id'])) {
    $quote_id = (int)$_POST['delete_quote_id'];
    $delete_sql = "DELETE FROM quote WHERE quote_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $quote_id);
    $stmt->execute();
    header("Location: quote.php?deleted=1");
    exit();
}

// Handle toggle status (Active/Not Active)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_id'])) {
    $quote_id = (int)$_POST['toggle_status_id'];
    
    // Get current status
    $status_sql = "SELECT is_active FROM quote WHERE quote_id = ?";
    $stmt = $conn->prepare($status_sql);
    $stmt->bind_param("i", $quote_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Toggle status (1 to 0, or 0 to 1)
    $new_status = $row['is_active'] ? 0 : 1;
    
    // Update status
    $update_sql = "UPDATE quote SET is_active = ? WHERE quote_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ii", $new_status, $quote_id);
    $stmt->execute();
    
    header("Location: quote.php?status_updated=1");
    exit();
}

// Fetch all quotes from database
$sql = "SELECT quote_id, quote_text, is_active FROM quote ORDER BY quote_id ASC";
$result = $conn->query($sql);

$quotes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $quotes[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motivational Quotes - Admin - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for Quote page -->
    <link rel="stylesheet" href="css/quote.css">
    
    <!-- Google Fonts (Inter & Montserrat) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- Main container with sidebar and content -->
<div class="quote-container">
    
    <!-- Left Sidebar - Red -->
    <div class="sidebar">
        <!-- Logo -->
        <div class="logo-container">
            <img src="images/logo.png" alt="PlanWise Logo" class="logo">
        </div>
        
        <!-- Logout Button -->
        <a href="quote.php?logout=1" class="btn-logout-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            LOG OUT
        </a>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="quote.php" class="nav-link active">Quote</a>
            <a href="user-detail.php" class="nav-link">User Detail</a>
        </nav>
    </div>
    
    <!-- Right Content Area -->
    <div class="content-area">
        
        <!-- Header Section -->
        <div class="content-header">
            <h1 class="page-title">Motivational Quotes</h1>
            <a href="new-quote.php" class="btn-add-new">+ Add New</a>
        </div>
        
        <!-- Success Messages -->
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Quote deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status_updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Quote status updated successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                New quote added successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Quotes Table -->
        <div class="quotes-table-container">
            <table class="quotes-table">
                <thead>
                    <tr>
                        <th style="width: 10%">No.</th>
                        <th style="width: 50%">Quote</th>
                        <th style="width: 20%">Status</th>
                        <th style="width: 20%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($quotes) > 0): ?>
                        <?php foreach ($quotes as $index => $quote): ?>
                        <tr>
                            <!-- Number -->
                            <td><?php echo $index + 1; ?></td>
                            
                            <!-- Quote Text -->
                            <td class="quote-text"><?php echo htmlspecialchars($quote['quote_text']); ?></td>
                            
                            <!-- Status Badge with Toggle -->
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="toggle_status_id" value="<?php echo $quote['quote_id']; ?>">
                                    <button type="submit" class="status-badge <?php echo $quote['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $quote['is_active'] ? 'Active' : 'Not Active'; ?>
                                    </button>
                                </form>
                            </td>
                            
                            <!-- Delete Action -->
                            <td>
                                <button class="btn-delete" onclick="showDeleteModal(<?php echo $quote['quote_id']; ?>, '<?php echo htmlspecialchars(addslashes($quote['quote_text'])); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 6H5H21" stroke="#BC4141" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M8 6V4C8 3.46957 8.21071 2.96086 8.58579 2.58579C8.96086 2.21071 9.46957 2 10 2H14C14.5304 2 15.0391 2.21071 15.4142 2.58579C15.7893 2.96086 16 3.46957 16 4V6M19 6V20C19 20.5304 18.7893 21.0391 18.4142 21.4142C18.0391 21.7893 17.5304 22 17 22H7C6.46957 22 5.96086 21.7893 5.58579 21.4142C5.21071 21.0391 5 20.5304 5 20V6H19Z" stroke="#BC4141" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10 11V17" stroke="#BC4141" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14 11V17" stroke="#BC4141" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-4">No quotes available. Click "Add New" to create one.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
    </div>
    
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content delete-modal">
            <div class="modal-body text-center p-5">
                <h2 class="delete-modal-title mb-4">Are you sure to Delete?</h2>
                <p class="delete-modal-quote mb-4" id="deleteQuoteText"></p>
                
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="delete_quote_id" id="deleteQuoteId">
                    <div class="d-flex gap-3 justify-content-center">
                        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-delete-confirm">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Function to show delete modal with quote details
function showDeleteModal(quoteId, quoteText) {
    document.getElementById('deleteQuoteId').value = quoteId;
    document.getElementById('deleteQuoteText').textContent = '"' + quoteText + '"';
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

</body>
</html>
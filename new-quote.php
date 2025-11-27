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

// Handle form submission to add new quote
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quote_text = trim($_POST['quote_text']);
    
    // Validate input
    if (empty($quote_text)) {
        $error = "Please enter a quote.";
    } else {
        // Insert new quote into database (default is_active = 1)
        $sql = "INSERT INTO quote (quote_text, is_active) VALUES (?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $quote_text);
        
        if ($stmt->execute()) {
            // Redirect back to quote.php with success message
            header("Location: quote.php?added=1");
            exit();
        } else {
            $error = "Failed to add quote. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Quote - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for New Quote page -->
    <link rel="stylesheet" href="css/new-quote.css">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="new-quote-container">
    
    <!-- Header with Back button and title -->
    <div class="header-section">
        <a href="quote.php" class="btn-back">← Back</a>
        <h1 class="page-title">New Quote</h1>
    </div>
    
    <!-- Form Container -->
    <div class="form-container">
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- New Quote Form -->
        <form method="POST" action="new-quote.php">
            
            <!-- Quote Input Label -->
            <div class="mb-4">
                <label for="quote_text" class="form-label-custom">Enter quote</label>
                <textarea 
                    class="form-control quote-textarea" 
                    id="quote_text" 
                    name="quote_text" 
                    rows="4" 
                    placeholder="Type your motivational quote here..."
                    required><?php echo isset($_POST['quote_text']) ? htmlspecialchars($_POST['quote_text']) : ''; ?></textarea>
            </div>
            
            <!-- Action Buttons -->
            <div class="button-group">
                <a href="quote.php" class="btn-cancel-large">Cancel</a>
                <button type="submit" class="btn-add-large">Add</button>
            </div>
            
        </form>
        
    </div>
    
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
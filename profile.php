<?php
// Start session
session_start();

// Connect to database
include 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT username, email, phone, gender FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - PlanWise</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for Profile page -->
    <link rel="stylesheet" href="css/profile.css">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="profile-container">
    
    <!-- Back Button -->
    <div class="back-button-container">
        <a href="dashboard.php" class="btn-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
        </a>
    </div>
    
    <!-- Profile Card -->
    <div class="profile-card">
        
        <!-- Username Title -->
        <h2 class="profile-username"><?php echo htmlspecialchars($user['username']); ?></h2>
        
        <!-- User Details Form -->
        <form>
            <!-- Phone -->
            <div class="mb-3">
                <label for="phone" class="form-label-profile">Phone</label>
                <div class="input-with-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9095A0" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <input type="tel" class="form-control-profile" id="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                </div>
            </div>
            
            <!-- Email -->
            <div class="mb-3">
                <label for="email" class="form-label-profile">Email</label>
                <div class="input-with-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#9095A0" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" class="form-control-profile" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                </div>
            </div>
            
            <!-- Gender -->
            <div class="mb-4">
                <label class="form-label-profile">Gender</label>
                <div class="gender-options">
                    <div class="form-check-inline">
                        <input class="form-check-input-profile" type="radio" name="gender" id="male" value="Male" <?php echo ($user['gender'] === 'Male') ? 'checked' : ''; ?> disabled>
                        <label class="form-check-label-profile" for="male">Male</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input-profile" type="radio" name="gender" id="female" value="Female" <?php echo ($user['gender'] === 'Female') ? 'checked' : ''; ?> disabled>
                        <label class="form-check-label-profile" for="female">Female</label>
                    </div>
                </div>
            </div>
            
            <!-- Logout Button -->
            <a href="profile.php?logout=1" class="btn-logout">LOG OUT</a>
            
        </form>
        
    </div>
    
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
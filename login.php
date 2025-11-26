<?php
// Start a new session or resume existing session
session_start();

// Connect to database (currently commented out)
// include 'db_connection.php';

// Check if user is already logged in (currently commented out)
// if (isset($_SESSION['user_id'])) {
//     header("Location: dashboard.php");
//     exit();
// }

// Check if form was submitted via POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Get form data from POST request
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Validate user credentials (currently commented out)
    // $sql = "SELECT * FROM users WHERE email = ?";
    
    // Placeholder message - this will be replaced with actual login logic
    $error_message = "Login logic not yet implemented.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlanWise for Personal Assistance</title>
    
    <!-- Bootstrap CSS - provides responsive grid system and pre-built components -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for Login page - our own styling to customize the look -->
    <link rel="stylesheet" href="css/login.css">
    
    <!-- Google Fonts (Inter) - loads the Inter font family for modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<!-- Main container - fluid means full width -->
<div class="container-fluid">
    <!-- Row with centered content vertically and horizontally -->
    <div class="row justify-content-center align-items-center min-vh-100">
        
        <!-- Column sizing for responsive design -->
        <div class="col-lg-5 col-md-7 col-11">
            
            <!-- Main login card container -->
            <main class="login-card">
                
                <!-- Logo -->
                <div class="text-center mb-4">
                    <img src="images/logo.png" alt="PlanWise Logo" class="logo">
                </div>
                
                <!-- "First Time?" text -->
                <p class="signup-prompt text-center">First Time?</p>
                
                <!-- Sign Up button/link -->
                <div class="text-center mb-4">
                    <a href="sign-up.php" class="signup-button">
                        Sign Up
                    </a>
                </div>
                
                <!-- Divider text -->
                <p class="divider-text text-center mb-4">OR LOG IN WITH</p>
                
                <!-- Login Form -->
                <form action="login.php" method="POST">
                    
                    <!-- Email input field -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                    </div>
                    
                    <!-- Password input field with eye icon to toggle visibility -->
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control border-end-0" id="password" name="password" placeholder="Enter password" required>
                            
                        </div>
                    </div>
                    
                    <!-- Continue/Login button - w-100 makes it full width -->
                    <button type="submit" class="btn btn-login w-100">Continue</button>
                    
                </form>
                
            </main>
        </div>
    </div>
</div>

<!-- JavaScript function to toggle password visibility -->
<script>
function togglePassword() {
    // Get the password input element
    const passwordInput = document.getElementById('password');
    // Toggle between 'password' (hidden) and 'text' (visible)
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
}
</script>

</body>
</html>
<?php
// --- PHP Session Start ---
// This MUST be on line 1, before any HTML.
session_start();

// --- PHP Login Logic ---
// This code block will only run when the user clicks the "Continue" button.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // --- THIS IS WHERE YOU WILL CONNECT TO YOUR DATABASE ---
    // We are now including our new connection file!
    require_once 'php_includes/db_connection.php';
    
    // Get the data the user typed in
    $email = $_POST['email'];
    $password = $_POST['password'];
    $error_message = ""; // Start with no error

    // --- TO-DO: Database Query ---
    // 1. You will write a SQL query to find the user by their email.
    // 2. You will check if the password matches (using password_verify()).
    // 3. If it matches, you will:
    //    $_SESSION['user_id'] = $user_id_from_db;
    //    $_SESSION['username'] = $username_from_db;
    //    header("Location: dashboard.php"); // Send them to the dashboard
    //    exit;
    // 4. If it does not match:
    //    $error_message = "Invalid email or password";

    // For now, we will just show a demo error:
    $error_message = "Invalid email or password (demo)";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlanWise</title>
    
    <!-- 
      --- CSS LINKS ---
      1. This links to the Bootstrap CSS file (for responsive layout)
      2. This links to your CUSTOM CSS file (for matching your Figma design)
    -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- This is linking to the correct "login.css" file -->
    <link rel="stylesheet" href="css/login.css">
    
    <!-- This links to the "Inter" font from Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

    <!-- 
      --- BOOTSTRAP RESPONSIVE LAYOUT ---
      This is the main container that holds everything.
      "min-vh-100" = Make it at least 100% of the viewport height.
      "d-flex align-items-center justify-content-center" = This is the magic that centers your login box.
    -->
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <!-- 
              HERE IS THE RESPONSIVE PART:
              "col-lg-6" = On Large screens (laptops), make this box 6/12 columns wide.
              "col-md-8" = On Medium screens (tablets), make this box 8/12 columns wide.
              "col-11"   = On Small screens (phones), make this box 11/12 columns wide.
            -->
            <div class="col-lg-6 col-md-8 col-11">
                
                <!-- 
                  This is the main white box. We use Bootstrap's "card" component.
                  We are using "pt-3" for less padding on top.
                -->
                <main class="card shadow-sm border-0 pt-3 pb-5 px-4 px-md-5">
                    <div class="card-body">

                        <!-- 
                          1. The Logo
                          We are using "mb-2" for less margin-bottom (less space below).
                        -->
                        <img src="images/logo.png" alt="PlanWise Logo" class="logo mb-2">

                        <!-- 
                          2. The "Sign Up" Box 
                          The "First Time?" text is now OUTSIDE this box.
                        -->
                        
                        <!-- This text is from your Figma file, now with its own class -->
                        <p class="signup-prompt">First Time?</p>
                        
                        <div class="signup-box text-white rounded-4 p-3 text-center mb-4">
                            <!-- This will link to your registration page. '#' is a placeholder -->
                            <a href="sign-in.php" class="signup-button">
                            Sign Up
                            </a>
                        </div>

                        <!-- 3. The "OR LOG IN WITH" Divider -->
                        <div class="divider">
                            <hr>
                            <span>OR LOG IN WITH</span>
                            <hr>
                        </div>

                        <!-- 4. The Main Login Form -->
                        <form class="login-form" method="POST" action="login.php">
                            
                            <!-- Email Field -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <!-- This is a Bootstrap "Input Group" -->
                                <div class="input-group">
                                    <!-- This is your SVG icon from Figma -->
                                    <span class="input-group-text">
                                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M3.06445 4.75085L11.9999 12.8693L20.9353 4.75085" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10"/>
                                            <path d="M19.74 4.26001L4.26004 4.26001C3.31011 4.26001 2.54004 5.03008 2.54004 5.98001L2.54004 18.02C2.54004 18.9699 3.31011 19.74 4.26004 19.74L19.74 19.74C20.69 19.74 21.46 18.9699 21.46 18.02L21.46 5.98001C21.46 5.03008 20.69 4.26001 19.74 4.26001Z" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="square"/>
                                        </svg>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
                                </div>
                            </div>
                            
                            <!-- Password Field -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <!-- Lock Icon -->
                                    <span class="input-group-text">
                                        <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M18.0198 11.13L5.97977 11.13C5.02984 11.13 4.25977 11.9001 4.25977 12.85L4.25977 19.73C4.25977 20.6799 5.02984 21.45 5.97977 21.45L18.0198 21.45C18.9697 21.45 19.7398 20.6799 19.7398 19.73V12.85C19.7398 11.9001 18.9697 11.13 18.0198 11.13Z" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="square"/>
                                            <path d="M11.9998 18.0101C12.9497 18.0101 13.7198 17.24 13.7198 16.2901C13.7198 15.3401 12.9497 14.5701 11.9998 14.5701C11.0499 14.5701 10.2798 15.3401 10.2798 16.2901C10.2798 17.24 11.0499 18.0101 11.9998 18.0101Z" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="square"/>
                                            <path d="M16.2997 7.72003V6.86003C16.3231 4.5091 14.4366 2.58413 12.0857 2.56003H11.9997C9.64877 2.53664 7.72381 4.4231 7.69971 6.77403V7.72003" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                    <!-- Eye Icon -->
                                    <button type="button" class="input-group-text input-icon-right" id="togglePassword">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                                            <path d="M2.86078 13.0174C2.43345 12.4064 2.43345 11.5936 2.86078 10.9826C4.21356 9.0674 7.54434 5.12 12 5.12C16.4557 5.12 19.7864 9.0674 21.1392 10.9826C21.5665 11.5936 21.5665 12.4064 21.1392 13.0174C19.7864 14.9326 16.4557 18.88 12 18.88C7.54434 18.88 4.21356 14.9326 2.86078 13.0174Z" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="round"/>
                                            <path d="M12.0001 15.4401C13.8999 15.4401 15.4401 13.8999 15.4401 12.0001C15.4401 10.1002 13.8999 8.56006 12.0001 8.56006C10.1002 8.56006 8.56006 10.1002 8.56006 12.0001C8.56006 13.8999 10.1002 15.4401 12.0001 15.4401Z" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="square"/>
                                            <path d="M3.3999 20.6L20.5999 3.40002" stroke="#BDC1CA" stroke-width="2.064" stroke-miterlimit="10" stroke-linecap="square"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- "Continue" Button -->
                            <!-- We use our custom class "btn-gold" from login.css -->
                            <button type="submit" class="btn btn-gold w-100">Continue</button>
                        
                        </form>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- 
      --- Your Custom JavaScript ---
      (We can add this file later to make the password "eye" icon work) 
    -->
    <!-- <script src="js/login.js"></script> -->
</body>
</html>
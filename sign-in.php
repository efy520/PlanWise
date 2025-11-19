<?php
//start session
session_start();
//connect to database
// include 'db_connection.php';

// //check if user is already logged in
// if (isset($_SESSION['user_id'])) {
//     header("Location: dashboard.php"); //redirect to dashboard if logged in
//     exit();
// }

if($_SERVER["REQUEST_METHOD"]=="POST"){

    // Get from data 
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $gender = $_POST['gender'];

    // // Insert user data into database
    // $sql = "INSERT INTO users (username, phone, email, password
  
    //For now, just a placeholder message
    $error_message = "Sign up logic not yet implemented.";
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PlanWise for Personal Assistance</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS for Sign Up -->
    <link rel="stylesheet" href="css/sign-up.css">
    
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-light">
    
<div class="container">
<div class="row justify-content-center align-items-center min-vh-100 py-5">

 <!-- Container is wider than the login page, but still centered. -->
            <div class="col-lg-6 col-md-8 col-11">
                
                <main class="card shadow-sm border-0 px-4 px-md-5 py-4">
                    <div class="card-body">

                        <!-- Header -->
                        <h1 class="signup-title text-center mb-4">Sign up</h1>

                        <!-- Form -->
                        <form action="sign-up.php" method="POST">
                            
                            <!-- Username -->
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                            </div>

                            <!-- Phone -->
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" placeholder="Enter phone number" required>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="example.@gmail.com" required>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control border-end-0" id="password" name="password" placeholder="Enter at least 8+ characters" required>
                                    <!-- Simple Eye Icon (using Feather Icons style SVG for lightweight) -->
                                    <span class="input-group-text bg-transparent border-start-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#BDC1CA" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye cursor-pointer">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </span>
                                </div>
                            </div>

                            <!-- Gender (Radio Buttons) -->
                             <!--mb-4 means margin bottom-->
                            <div class="mb-4"> 
                                <label class="form-label d-block">Gender</label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required>
                                        <label class="form-check-label" for="male">
                                            Male
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="female" value="Female" required>
                                        <label class="form-check-label" for="female">
                                            Female
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Sign Up Button -->
                            <button type="submit" class="btn btn-gold w-100 mb-4">Sign up</button>

                            <!-- Login Link -->
                            <p class="text-center login-link">
                                Already have an account? <a href="login.php">Log in</a>
                            </p>

                        </form>
                    </div>
                </main>
            </div>
</div>

</div>
</body>
</html>
<?php
session_start();
include 'db_connection.php';

$error_message = "";

// When login form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query user by username
    $sql = "SELECT * FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Check password hash
        if (password_verify($password, $user['password'])) {

            // Save session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Redirect to task.php
            header("Location: task.php");
            exit();

        } else {
            $error_message = "Incorrect password.";
        }

    } else {
        $error_message = "Username not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PlanWise</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-lg-5 col-md-7 col-11">

            <main class="login-card">

                <div class="text-center mb-4">
                    <img src="images/logo.png" alt="PlanWise Logo" class="logo">
                </div>

                <p class="signup-prompt text-center">First Time?</p>

                <div class="text-center mb-4">
                    <a href="sign-up.php" class="signup-button">Sign Up</a>
                </div>

                <p class="divider-text text-center mb-4">OR LOG IN WITH</p>

                <!-- Error Message -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">

                    <!-- Username -->
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            name="username" 
                            placeholder="Enter username"
                            value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                            required
                        >
                    </div>

                    <!-- Password -->
                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input 
                                type="password" 
                                class="form-control border-end-0" 
                                id="password" 
                                name="password" 
                                placeholder="Enter password"
                                required
                            >

                            <!-- Eye Icon -->
                            <span class="input-group-text bg-transparent border-start-0" onclick="togglePassword()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                     fill="none" stroke="#9095A0" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="feather feather-eye cursor-pointer">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-login w-100">Continue</button>

                </form>

            </main>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const p = document.getElementById('password');
    p.type = (p.type === 'password') ? 'text' : 'password';
}
</script>

</body>
</html>

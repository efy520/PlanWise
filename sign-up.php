<?php
session_start();
include 'db_connection.php';

$error_message = "";

// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST['username']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $gender = $_POST['gender'];

    // Validate simple required fields
    if (!empty($username) && !empty($phone) && !empty($email) && !empty($password)) {

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Default role: normal user
        $role = "normal user";

        // Insert into DB
        $sql = "INSERT INTO users (email, username, password, gender, phone, role, created_date) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $email, $username, $hashedPassword, $gender, $phone, $role);

        if ($stmt->execute()) {
            // Redirect to login after successful registration
            header("Location: login.php?registered=1");
            exit();
        } else {
            $error_message = "Registration failed. Email may already exist.";
        }

    } else {
        $error_message = "All fields are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - PlanWise for Personal Assistance</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sign-up.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

<div class="container-fluid">
    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-lg-5 col-md-7 col-11">

            <main class="signup-card">

                <h1 class="signup-title text-center mb-4">Sign up</h1>

                <!-- Error message -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="sign-up.php" method="POST">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control border-end-0" id="password" name="password" required>
                            <span class="input-group-text bg-transparent border-start-0" onclick="togglePassword()">
                                👁
                            </span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label d-block">Gender</label>
                        <div class="d-flex gap-5">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="male" value="male" required>
                                <label class="form-check-label" for="male">Male</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gender" id="female" value="female" required>
                                <label class="form-check-label" for="female">Female</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-signup w-100 mb-4">Sign up</button>

                    <p class="text-center login-link">
                        Already have an account? <a href="login.php">Log in</a>
                    </p>

                </form>

            </main>

        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    passwordInput.type = (passwordInput.type === 'password') ? 'text' : 'password';
}
</script>

</body>
</html>

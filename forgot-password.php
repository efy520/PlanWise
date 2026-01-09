<?php
session_start();
include 'db_connection.php';

$error = "";
$step = 1;

if (isset($_POST['check_username'])) {
    $username = trim($_POST['username']);

    // ✅ Empty check FIRST
    if ($username === "") {
        $error = "Please insert your username.";
        $step = 1;
    } else {

        $sql = "SELECT user_id, security_question, security_answer_hash 
                FROM users 
                WHERE username = ? 
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            $_SESSION['fp_user_id'] = $user['user_id'];
            $_SESSION['fp_answer_hash'] = $user['security_answer_hash'];
            $_SESSION['fp_question'] = $user['security_question'];

            $step = 2;
        } else {
            $error = "Username not found.";
            $step = 1;
        }
    }
}


// VERIFY ANSWER
if (isset($_POST['verify_answer'])) {

    // Guard: session wajib ada
    if (!isset($_SESSION['fp_answer_hash'], $_SESSION['fp_user_id'])) {
        session_destroy();
        header("Location: forgot_password.php");
        exit();
    }

    $answer = trim($_POST['answer']);

    if (password_verify($answer, $_SESSION['fp_answer_hash'])) {

        // ambil password lama (untuk block same password nanti)
        $sql = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['fp_user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        $_SESSION['fp_old_password_hash'] = $row['password'];

        $step = 3; // terus ke reset password

    } else {
        $error = "Wrong answer.";
        // ⚠️ TAK perlu set $step = 2
        // default memang step 2
    }
}

if (isset($_POST['reset_password'])) {

    // 🔒 GUARD – pastikan flow betul
    if (
        !isset($_SESSION['fp_user_id']) ||
        !isset($_SESSION['fp_old_password_hash'])
    ) {
        session_destroy();
        header("Location: forgot_password.php");
        exit();
    }

    $new_password_plain = trim($_POST['new_password']);
    $old_password_hash  = $_SESSION['fp_old_password_hash'];
    $user_id = $_SESSION['fp_user_id'];

    // ❌ block password sama
    if (password_verify($new_password_plain, $old_password_hash)) {
        $error = "New password cannot be the same as your old password.";
        $step = 3;
    } else {

        $new_password_hash = password_hash($new_password_plain, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $new_password_hash, $user_id);
        $stmt->execute();

        session_destroy();
        header("Location: login.php?reset=success");
        exit();
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PlanWise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="CSS/forgot_password.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
            
            <!-- Card Container -->
            <div class="card shadow-lg border-0 forgot-password-card">
                <div class="card-body p-5">
                    
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">Forgot Password</h2>
                        <p class="text-muted">Recover your account securely</p>
                    </div>

                    <!-- Error Alert -->
                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Step 1: Username Verification -->
                    <?php if ($step === 1): ?>
                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" id="username" name="username" 
                                       placeholder="Enter your username" required autofocus>
                            </div>
                            <small class="text-muted d-block mt-2">Enter the username associated with your account</small>
                        </div>
                        <button type="submit" name="check_username" class="btn btn-primary btn-lg w-100 fw-semibold mt-3">
                            Continue
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Step 2: Security Question -->
                    <?php if ($step === 2): ?>
                    <form method="POST" novalidate>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="bi bi-info-circle"></i> Answer your security question to verify your identity.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block mb-3">
                                <i class="bi bi-question-circle text-warning"></i> 
                                <?= htmlspecialchars($_SESSION['fp_question']) ?>
                            </label>
                            <input type="text" class="form-control form-control-lg" name="answer" 
                                   placeholder="Enter your answer" required autofocus>
                            <small class="text-muted d-block mt-2">Your answer is case-sensitive</small>
                        </div>
                        
                        <button type="submit" name="verify_answer" class="btn btn-primary btn-lg w-100 fw-semibold">
                            Verify Answer
                        </button>
                    </form>
                    <?php endif; ?>

                    <!-- Step 3: Password Reset -->
                    <?php if ($step === 3): ?>
                    <form method="POST" novalidate id="resetForm">
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> Identity verified! Now set your new password.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input type="password" class="form-control border-start-0" id="new_password" 
                                       name="new_password" placeholder="Enter new password" required autofocus>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="reset_password" class="btn btn-success btn-lg fw-semibold">
                                <i class="bi bi-check-lg"></i> Reset Password
                            </button>
                        </div>
                    </form>

                    <script>
                        document.getElementById('togglePassword').addEventListener('click', function() {
                            const passwordInput = document.getElementById('new_password');
                            const icon = this.querySelector('i');
                            if (passwordInput.type === 'password') {
                                passwordInput.type = 'text';
                                icon.classList.remove('bi-eye');
                                icon.classList.add('bi-eye-slash');
                            } else {
                                passwordInput.type = 'password';
                                icon.classList.remove('bi-eye-slash');
                                icon.classList.add('bi-eye');
                            }
                        });
                    </script>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Footer Link -->
            <div class="text-center mt-4">
                <p class="text-muted">
                    Remember your password? <a href="login.php" class="text-primary fw-semibold text-decoration-none">Log in</a>
                </p>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
session_start();
include 'db_connection.php';
function createDefaultCategories($conn, $user_id) {

    $default_expense = [
        ['name' => 'Shopping', 'group' => 'Shopping'],
        ['name' => 'Health',   'group' => 'Health'],
        ['name' => 'Food',     'group' => 'Food & Drink'],
        ['name' => 'Bills',    'group' => 'Bills'],
        ['name' => 'Petrol',   'group' => 'Transport'],
    ];

    $default_income = [
        ['name' => 'Salary', 'group' => 'Primary Income'],
        ['name' => 'Duit Poket', 'group' => 'Side Income'],
    ];

    foreach ($default_expense as $cat) {
        $sql = "INSERT INTO category
            (user_id, category_name, category_type, category_group, is_active)
            VALUES (?, ?, 'expense', ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $cat['name'], $cat['group']);
        $stmt->execute();
    }

    foreach ($default_income as $cat) {
        $sql = "INSERT INTO category
            (user_id, category_name, category_type, category_group, is_active)
            VALUES (?, ?, 'income', ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $cat['name'], $cat['group']);
        $stmt->execute();
    }
}

function getGenderFromIC($ic) {
    $lastDigit = (int) substr(str_replace('-', '', $ic), -1);
    return ($lastDigit % 2 === 0) ? 'female' : 'male';
}

function getAgeFromIC($ic) {
    $ic = str_replace('-', '', $ic);

    $yy = (int) substr($ic, 0, 2);
    $mm = (int) substr($ic, 2, 2);
    $dd = (int) substr($ic, 4, 2);

    $birthYear = ($yy > (int)date('y')) ? 1900 + $yy : 2000 + $yy;

    // 🔥 VALIDATE TARIKH
    if (!checkdate($mm, $dd, $birthYear)) {
        return false; // tarikh tak sah
    }

    $birthDate = new DateTime("$birthYear-$mm-$dd");
    $today = new DateTime();

    return $today->diff($birthDate)->y;
}


$error_message = "";


// When form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {


$username = trim($_POST['username']);
$phone    = trim($_POST['phone']);
$ic       = trim($_POST['ic']);
$email    = trim($_POST['email']);
$password = trim($_POST['password']);

$security_question = trim($_POST['question']);
$security_answer   = trim($_POST['answer']);

//  Block username admin style
if ($username !== "" && $username[0] === '$') {
    $error_message = "Username cannot start with '$'. Please choose another username.";
}

//  Phone validation (only check if no previous error)
if (empty($error_message) && !preg_match('/^\d{10}$/', $phone)) {
    $error_message = "Invalid Malaysian phone number. It should be 10 digits.";
}

//  Security Q/A
if (empty($error_message) && (empty($security_question) || empty($security_answer))) {
    $error_message = "Security question and answer are required.";
}

//  IC format
if (empty($error_message) && !preg_match('/^\d{6}-\d{2}-\d{4}$/', $ic)) {
    $error_message = "Invalid IC format. Use XXXXXX-XX-XXXX.";
}


    // Validate simple required fields
   if (
    empty($error_message) &&
    !empty($username) &&
    !empty($phone) &&
    !empty($ic) &&
    !empty($email) &&
    !empty($password)
)
 {

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Default role: normal user
        $role = "normal";

        // Insert into DB
        $sql = "INSERT INTO users 
(email, username, password, gender, age, ic_hash, security_question, security_answer_hash, phone, role, created_date)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())

";

        $stmt = $conn->prepare($sql);
       $stmt->bind_param(
    "ssssisssss",
    $email,
    $username,
    $hashedPassword,
    $gender,
    $age,
    $ic_hash,
    $security_question,
    $security_answer_hash,
    $phone,
    $role
);



        try {
            if ($stmt->execute()) {
                // Get the newly created user_id
                $new_user_id = $conn->insert_id;
                
                // Create default categories for this new user
                createDefaultCategories($conn, $new_user_id);
                
                // Redirect to login after successful registration
                header("Location: login.php?registered=1");
                exit();
            }
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email') !== false) {
                $error_message = "This email is already registered. Please use a different email or try logging in.";
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }

    } elseif (empty($error_message)) {
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
                        <input
    type="tel"
    class="form-control"
    id="phone"
    name="phone"
    required
    pattern="\d{10}"
    maxlength="10"
    inputmode="numeric"
    placeholder="e.g. 0123456789"
    oninput="this.value = this.value.replace(/[^0-9]/g, '')"
/>
                    </div>
<div class="mb-3">
    
<label for="ic" class="form-label">IC Number</label>
   <input
    type="text"
    class="form-control"
    id="ic"
    name="ic"
    placeholder="e.g. 050101-03-0109"
    maxlength="14"
    required
    oninput="formatIC(this)"
>

</div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control border-end-0" id="password" name="password" minlength="4" required>
                            <span class="input-group-text bg-transparent border-start-0" onclick="togglePassword()">
                                👁
                            </span>
                        </div>
                    </div>

                     <div class="mb-3">
                    <label>Security Question</label>
<select name="question" class="form-control" required>
    <option value="">-- Select a question --</option>
    <option value="What is your favourite food?">What is your favourite food?</option>
    <option value="What is your favourite color?">What is your favourite color?</option>
</select>


<label>Answer</label>
<input type="text" name="answer" class="form-control" required>
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

function formatIC(input) {
    // buang semua bukan nombor
    let value = input.value.replace(/\D/g, '');

    // max 12 digit sahaja
    if (value.length > 12) {
        value = value.slice(0, 12);
    }

    // format ikut panjang
    if (value.length > 6 && value.length <= 8) {
        value = value.slice(0,6) + '-' + value.slice(6);
    } else if (value.length > 8) {
        value = value.slice(0,6) + '-' + value.slice(6,8) + '-' + value.slice(8);
    }

    input.value = value;
}
</script>


</body>
</html>
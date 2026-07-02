<?php
session_start();
require_once '../config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

// Handle student registration.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $fullName = mysqli_real_escape_string($conn, trim($_POST['full_name'] ?? ''));
        $matricNo = mysqli_real_escape_string($conn, trim($_POST['matric_no'] ?? ''));
        $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $department = mysqli_real_escape_string($conn, trim($_POST['department'] ?? ''));
        $level = mysqli_real_escape_string($conn, trim($_POST['level'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($fullName === '' || $matricNo === '' || $email === '' || $department === '' || $level === '' || $password === '' || $confirmPassword === '') {
            $errors[] = 'All fields are required.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!in_array($level, ['100', '200', '300', '400', '500'], true)) {
            $errors[] = 'Please select a valid level.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (empty($errors)) {
            $stmt = mysqli_prepare($conn, 'SELECT id FROM students WHERE matric_no = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $matricNo);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            $exists = mysqli_stmt_num_rows($stmt) > 0;
            mysqli_stmt_close($stmt);

            if ($exists) {
                $errors[] = 'Matric number already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn, 'INSERT INTO students (full_name, matric_no, email, department, level, password) VALUES (?, ?, ?, ?, ?, ?)');
                mysqli_stmt_bind_param($stmt, 'ssssss', $fullName, $matricNo, $email, $department, $level, $passwordHash);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header('Location: login.php?registered=1');
                    exit();
                }

                mysqli_stmt_close($stmt);
                $errors[] = 'Registration could not be completed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Chrisland University</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<main class="login-page">
    <section class="login-card">
        <div class="text-center mb-3">
            <img src="../photo_2026-07-01_03-44-37.jpg" alt="Chrisland University Logo" class="logo-img">
        </div>
        <h1 class="portal-title">CHRISLAND UNIVERSITY (CLU)</h1>
        <h2 class="h5 text-center mb-4">Student Registration</h2>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>

        <form method="post" action="register.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name as registered with the university" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="matric_no" class="form-label">Matric Number</label>
                <input type="text" class="form-control" id="matric_no" name="matric_no" placeholder="Enter your unique matriculation number" value="<?php echo htmlspecialchars($_POST['matric_no'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your official university email address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="department" class="form-label">Department</label>
                <input type="text" class="form-control" id="department" name="department" placeholder="Enter your department of study" value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="level" class="form-label">Level</label>
                <select class="form-select" id="level" name="level" required>
                    <option value="">Select your level</option>
                    <?php foreach (['100', '200', '300', '400', '500'] as $levelOption): ?>
                        <option value="<?php echo $levelOption; ?>" <?php echo (($_POST['level'] ?? '') === $levelOption) ? 'selected' : ''; ?>><?php echo $levelOption; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Create a secure password (min. 6 characters)" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter your password to confirm" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Account</button>
        </form>

        <p class="text-center mt-4 mb-0">Already registered? <a href="login.php">Login</a></p>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

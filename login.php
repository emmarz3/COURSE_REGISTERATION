<?php
session_start();
require_once '../config/database.php';

if (!empty($_SESSION['user_id']) && !empty($_SESSION['role'])) {
    header($_SESSION['role'] === 'admin' ? 'Location: ../admin/dashboard.php' : 'Location: ../student/dashboard.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$activeTab = 'student';

// Handle login submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $activeTab = $role === 'admin' ? 'admin' : 'student';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid form token. Please try again.';
    } elseif ($role === 'student') {
        $matricNo = mysqli_real_escape_string($conn, trim($_POST['matric_no'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($matricNo === '' || $password === '') {
            $error = 'Matric number and password are required.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id, full_name, matric_no, password FROM students WHERE matric_no = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $matricNo);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $student = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($student && password_verify($password, $student['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $student['id'];
                $_SESSION['name'] = $student['full_name'];
                $_SESSION['matric_no'] = $student['matric_no'];
                $_SESSION['role'] = 'student';
                header('Location: ../student/dashboard.php');
                exit();
            }

            $error = 'Invalid student login credentials.';
        }
    } else {
        $username = mysqli_real_escape_string($conn, trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            $stmt = mysqli_prepare($conn, 'SELECT id, name, username, password FROM admins WHERE username = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $admin['id'];
                $_SESSION['name'] = $admin['name'] ?: $admin['username'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['role'] = 'admin';
                header('Location: ../admin/dashboard.php');
                exit();
            }

            $error = 'Invalid admin login credentials.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chrisland University</title>
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
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-3" id="loginTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'student' ? 'active' : ''; ?>" id="student-tab" data-bs-toggle="tab" data-bs-target="#student-login" type="button" role="tab">Student Login</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $activeTab === 'admin' ? 'active' : ''; ?>" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-login" type="button" role="tab">Admin Login</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade <?php echo $activeTab === 'student' ? 'show active' : ''; ?>" id="student-login" role="tabpanel">
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="role" value="student">
                    <div class="mb-3">
                        <label for="matric_no" class="form-label">Matric Number</label>
                        <input type="text" class="form-control" id="matric_no" name="matric_no" placeholder="Enter your matriculation number" required>
                    </div>
                    <div class="mb-3">
                        <label for="student_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="student_password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>

            <div class="tab-pane fade <?php echo $activeTab === 'admin' ? 'show active' : ''; ?>" id="admin-login" role="tabpanel">
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="role" value="admin">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Enter your admin username" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="admin_password" name="password" placeholder="Enter your admin password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>

        <p class="text-center mt-4 mb-0">New student? <a href="register.php">Create an account</a></p>
    </section>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

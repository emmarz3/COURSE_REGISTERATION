<?php
session_start();
require_once '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$studentId = (int) $_SESSION['user_id'];
$message = '';
$error = '';

// Process profile updates.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid form token. Please try again.';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, 'UPDATE students SET email = ?, password = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'ssi', $email, $passwordHash, $studentId);
        } else {
            $stmt = mysqli_prepare($conn, 'UPDATE students SET email = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $email, $studentId);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = 'Profile updated successfully.';
        } else {
            $error = 'Profile could not be updated. Please try again.';
        }
        mysqli_stmt_close($stmt);
    }
}

$stmt = mysqli_prepare($conn, 'SELECT full_name, matric_no, department, level, email FROM students WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$activePage = 'profile';
require_once '../includes/header.php';
require_once '../includes/student_sidebar.php';
?>
<main class="main-content">
    <h1 class="page-title">Profile</h1>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <section class="card-panel">
        <h2 class="h5 mb-3">Student Details</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-6"><strong>Name:</strong> <?php echo htmlspecialchars($student['full_name'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Matric No:</strong> <?php echo htmlspecialchars($student['matric_no'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Department:</strong> <?php echo htmlspecialchars($student['department'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Level:</strong> <?php echo htmlspecialchars($student['level'] ?? ''); ?></div>
            <div class="col-md-6"><strong>Email:</strong> <?php echo htmlspecialchars($student['email'] ?? ''); ?></div>
        </div>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </section>
</main>
<?php require_once '../includes/footer.php'; ?>

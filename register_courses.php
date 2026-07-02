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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid form token. Please try again.';
    } elseif ($action === 'register') {
        $courseId = (int) ($_POST['course_id'] ?? 0);

        $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM registrations WHERE student_id = ? AND course_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $studentId, $courseId);
        mysqli_stmt_execute($stmt);
        $exists = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] > 0;
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, 'SELECT units FROM courses WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $courseId);
        mysqli_stmt_execute($stmt);
        $course = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, 'SELECT COALESCE(SUM(c.units), 0) AS total_units FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $studentId);
        mysqli_stmt_execute($stmt);
        $currentUnits = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_units'];
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = 'You have already registered this course.';
        } elseif (!$course) {
            $error = 'Selected course could not be found.';
        } elseif (($currentUnits + (int) $course['units']) > 24) {
            $error = 'Course registration cannot exceed 24 credit units.';
        } else {
            $status = 'Pending';
            $stmt = mysqli_prepare($conn, 'INSERT INTO registrations (student_id, course_id, status, date_registered) VALUES (?, ?, ?, NOW())');
            mysqli_stmt_bind_param($stmt, 'iis', $studentId, $courseId, $status);
            if (mysqli_stmt_execute($stmt)) {
                $message = 'Course registered successfully.';
            } else {
                $error = 'Course could not be registered. Please try again.';
            }
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'remove') {
        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $pending = 'Pending';
        $stmt = mysqli_prepare($conn, 'DELETE FROM registrations WHERE id = ? AND student_id = ? AND status = ?');
        mysqli_stmt_bind_param($stmt, 'iis', $registrationId, $studentId, $pending);
        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $message = 'Pending course removed successfully.';
        } else {
            $error = 'Only pending registrations can be removed.';
        }
        mysqli_stmt_close($stmt);
    }
}

$registeredCourses = [];
$availableCourses = [];
$totalUnits = 0;

$stmt = mysqli_prepare($conn, 'SELECT r.id AS registration_id, r.status, c.id AS course_id, c.course_title, c.course_code, c.units FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ? ORDER BY c.course_code');
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $registeredCourses[] = $row;
    $totalUnits += (int) $row['units'];
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, 'SELECT id, course_title, course_code, units FROM courses WHERE id NOT IN (SELECT course_id FROM registrations WHERE student_id = ?) ORDER BY course_code');
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $availableCourses[] = $row;
}
mysqli_stmt_close($stmt);

$activePage = 'register_courses';
require_once '../includes/header.php';
require_once '../includes/student_sidebar.php';
?>
<main class="main-content">
    <h1 class="page-title">COURSE REGISTRATION: 2025/2026 SESSION, 2ND SEMESTER</h1>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <section class="card-panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Registered Courses</h2>
            <a class="btn btn-success" href="../pdf/generate_slip.php"><i class="bi bi-download"></i> Course Registration Download</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Course Title</th><th>Code</th><th>Units</th><th>Status</th><th>Remove</th></tr></thead>
                <tbody>
                    <?php if (empty($registeredCourses)): ?><tr><td colspan="5" class="text-center">No courses registered yet.</td></tr><?php endif; ?>
                    <?php foreach ($registeredCourses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo (int) $course['units']; ?></td>
                            <td><span class="badge <?php echo $course['status'] === 'Approved' ? 'badge-success' : ($course['status'] === 'Rejected' ? 'badge-danger' : 'badge-warning'); ?>"><?php echo htmlspecialchars($course['status']); ?></span></td>
                            <td>
                                <?php if ($course['status'] === 'Pending'): ?>
                                    <form method="post" data-confirm="Remove this pending course?">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="registration_id" value="<?php echo (int) $course['registration_id']; ?>">
                                        <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="2" class="table-footer-total">Total Credit Units</td><td colspan="3" class="table-footer-total"><?php echo $totalUnits; ?></td></tr></tfoot>
            </table>
        </div>
    </section>

    <section class="card-panel">
        <h2 class="h5 mb-3">Available Courses</h2>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Course Title</th><th>Code</th><th>Units</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($availableCourses)): ?><tr><td colspan="4" class="text-center">No available courses.</td></tr><?php endif; ?>
                    <?php foreach ($availableCourses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo (int) $course['units']; ?></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="course_id" value="<?php echo (int) $course['id']; ?>">
                                    <button class="btn btn-primary btn-sm" type="submit">Register</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require_once '../includes/footer.php'; ?>

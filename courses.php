<?php
session_start();
require_once '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';
$editCourse = null;

// Handle course actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid form token. Please try again.';
    } elseif ($action === 'save') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $courseCode = mysqli_real_escape_string($conn, trim($_POST['course_code'] ?? ''));
        $courseTitle = mysqli_real_escape_string($conn, trim($_POST['course_title'] ?? ''));
        $units = (int) ($_POST['units'] ?? 0);
        $semester = mysqli_real_escape_string($conn, trim($_POST['semester'] ?? ''));
        $session = mysqli_real_escape_string($conn, trim($_POST['session'] ?? ''));

        if ($courseCode === '' || $courseTitle === '' || $units < 1 || $semester === '' || $session === '') {
            $error = 'All course fields are required.';
        } elseif ($courseId > 0) {
            $stmt = mysqli_prepare($conn, 'UPDATE courses SET course_code = ?, course_title = ?, units = ?, semester = ?, session = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'ssissi', $courseCode, $courseTitle, $units, $semester, $session, $courseId);
            $message = mysqli_stmt_execute($stmt) ? 'Course updated successfully.' : 'Course could not be updated.';
            mysqli_stmt_close($stmt);
        } else {
            $stmt = mysqli_prepare($conn, 'INSERT INTO courses (course_code, course_title, units, semester, session) VALUES (?, ?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'ssiss', $courseCode, $courseTitle, $units, $semester, $session);
            $message = mysqli_stmt_execute($stmt) ? 'Course added successfully.' : 'Course could not be added.';
            mysqli_stmt_close($stmt);
        }
    } elseif ($action === 'delete') {
        $courseId = (int) ($_POST['course_id'] ?? 0);
        $stmt = mysqli_prepare($conn, 'SELECT COUNT(*) AS total FROM registrations WHERE course_id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $courseId);
        mysqli_stmt_execute($stmt);
        $hasRegistrations = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'] > 0;
        mysqli_stmt_close($stmt);

        if ($hasRegistrations) {
            $error = 'This course cannot be deleted because it has active registrations.';
        } else {
            $stmt = mysqli_prepare($conn, 'DELETE FROM courses WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $courseId);
            $message = mysqli_stmt_execute($stmt) ? 'Course deleted successfully.' : 'Course could not be deleted.';
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_GET['edit'])) {
    $courseId = (int) $_GET['edit'];
    $stmt = mysqli_prepare($conn, 'SELECT id, course_code, course_title, units, semester, session FROM courses WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $courseId);
    mysqli_stmt_execute($stmt);
    $editCourse = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$courses = [];
$result = mysqli_query($conn, 'SELECT id, course_code, course_title, units, semester, session FROM courses ORDER BY course_code');
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
}

$activePage = 'courses';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<main class="main-content">
    <h1 class="page-title">Manage Courses</h1>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <section class="card-panel">
        <h2 class="h5 mb-3"><?php echo $editCourse ? 'Edit Course' : 'Add New Course'; ?></h2>
        <form method="post" class="row g-3">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="course_id" value="<?php echo (int) ($editCourse['id'] ?? 0); ?>">
            <div class="col-md-2"><label class="form-label" for="course_code">Course Code</label><input class="form-control" id="course_code" name="course_code" value="<?php echo htmlspecialchars($editCourse['course_code'] ?? ''); ?>" required></div>
            <div class="col-md-4"><label class="form-label" for="course_title">Course Title</label><input class="form-control" id="course_title" name="course_title" value="<?php echo htmlspecialchars($editCourse['course_title'] ?? ''); ?>" required></div>
            <div class="col-md-2"><label class="form-label" for="units">Units</label><input class="form-control" id="units" name="units" type="number" min="1" max="6" value="<?php echo htmlspecialchars($editCourse['units'] ?? ''); ?>" required></div>
            <div class="col-md-2"><label class="form-label" for="semester">Semester</label><input class="form-control" id="semester" name="semester" value="<?php echo htmlspecialchars($editCourse['semester'] ?? '2nd'); ?>" required></div>
            <div class="col-md-2"><label class="form-label" for="session">Session</label><input class="form-control" id="session" name="session" value="<?php echo htmlspecialchars($editCourse['session'] ?? '2025/2026'); ?>" required></div>
            <div class="col-12"><button class="btn btn-primary" type="submit"><?php echo $editCourse ? 'Update Course' : 'Add New Course'; ?></button></div>
        </form>
    </section>

    <section class="card-panel">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Code</th><th>Title</th><th>Units</th><th>Semester</th><th>Session</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($courses)): ?><tr><td colspan="6" class="text-center">No courses found.</td></tr><?php endif; ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                            <td><?php echo (int) $course['units']; ?></td>
                            <td><?php echo htmlspecialchars($course['semester']); ?></td>
                            <td><?php echo htmlspecialchars($course['session']); ?></td>
                            <td class="action-row">
                                <a class="btn btn-primary btn-sm" href="courses.php?edit=<?php echo (int) $course['id']; ?>">Edit</a>
                                <form method="post" data-confirm="Delete this course?">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="course_id" value="<?php echo (int) $course['id']; ?>">
                                    <button class="btn btn-danger btn-sm" type="submit">Delete</button>
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

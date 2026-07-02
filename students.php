<?php
session_start();
require_once '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

$search = mysqli_real_escape_string($conn, trim($_GET['search'] ?? ''));
$viewStudentId = (int) ($_GET['view'] ?? 0);
$students = [];
$studentCourses = [];
$viewStudent = null;

// Load students with optional search.
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($conn, 'SELECT s.id, s.full_name, s.matric_no, s.department, s.level, s.email, COUNT(r.id) AS registered_courses FROM students s LEFT JOIN registrations r ON r.student_id = s.id WHERE s.full_name LIKE ? OR s.matric_no LIKE ? GROUP BY s.id ORDER BY s.full_name');
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
} else {
    $stmt = mysqli_prepare($conn, 'SELECT s.id, s.full_name, s.matric_no, s.department, s.level, s.email, COUNT(r.id) AS registered_courses FROM students s LEFT JOIN registrations r ON r.student_id = s.id GROUP BY s.id ORDER BY s.full_name');
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
mysqli_stmt_close($stmt);

if ($viewStudentId > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT full_name, matric_no FROM students WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $viewStudentId);
    mysqli_stmt_execute($stmt);
    $viewStudent = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, 'SELECT c.course_code, c.course_title, c.units, c.semester, r.status, r.date_registered FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ? ORDER BY c.course_code');
    mysqli_stmt_bind_param($stmt, 'i', $viewStudentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $studentCourses[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$activePage = 'students';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<main class="main-content">
    <h1 class="page-title">Students</h1>
    <section class="card-panel">
        <form method="get" class="row g-3 align-items-end mb-3">
            <div class="col-md-6">
                <label class="form-label" for="search">Search by Name or Matric Number</label>
                <input class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Search</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Name</th><th>Matric No</th><th>Dept</th><th>Level</th><th>Email</th><th>Registered Courses</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($students)): ?><tr><td colspan="7" class="text-center">No students found.</td></tr><?php endif; ?>
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($student['matric_no']); ?></td>
                            <td><?php echo htmlspecialchars($student['department']); ?></td>
                            <td><?php echo htmlspecialchars($student['level']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo (int) $student['registered_courses']; ?></td>
                            <td><a class="btn btn-primary btn-sm" href="students.php?view=<?php echo (int) $student['id']; ?>&search=<?php echo urlencode($search); ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if ($viewStudent): ?>
        <section class="card-panel">
            <h2 class="h5 mb-3">Courses Registered by <?php echo htmlspecialchars($viewStudent['full_name']); ?> (<?php echo htmlspecialchars($viewStudent['matric_no']); ?>)</h2>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th>Code</th><th>Title</th><th>Units</th><th>Semester</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if (empty($studentCourses)): ?><tr><td colspan="6" class="text-center">No courses registered.</td></tr><?php endif; ?>
                        <?php foreach ($studentCourses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                                <td><?php echo (int) $course['units']; ?></td>
                                <td><?php echo htmlspecialchars($course['semester']); ?></td>
                                <td><span class="badge <?php echo $course['status'] === 'Approved' ? 'badge-success' : ($course['status'] === 'Rejected' ? 'badge-danger' : 'badge-warning'); ?>"><?php echo htmlspecialchars($course['status']); ?></span></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($course['date_registered']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php require_once '../includes/footer.php'; ?>

<?php
session_start();
require_once '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$statusFilter = mysqli_real_escape_string($conn, $_GET['status'] ?? 'All');
$allowedStatuses = ['All', 'Pending', 'Approved', 'Rejected'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'All';
}

$courses = [];
$totalUnits = 0;

// Load registered courses with optional status filter.
if ($statusFilter === 'All') {
    $stmt = mysqli_prepare($conn, 'SELECT c.course_title, c.course_code, c.units, c.semester, r.status, r.date_registered FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ? ORDER BY r.date_registered DESC');
    mysqli_stmt_bind_param($stmt, 'i', $studentId);
} else {
    $stmt = mysqli_prepare($conn, 'SELECT c.course_title, c.course_code, c.units, c.semester, r.status, r.date_registered FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ? AND r.status = ? ORDER BY r.date_registered DESC');
    mysqli_stmt_bind_param($stmt, 'is', $studentId, $statusFilter);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
    $totalUnits += (int) $row['units'];
}
mysqli_stmt_close($stmt);

$activePage = 'my_courses';
require_once '../includes/header.php';
require_once '../includes/student_sidebar.php';
?>
<main class="main-content">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="page-title mb-0">My Courses</h1>
        <a class="btn btn-success" href="../pdf/generate_slip.php"><i class="bi bi-file-earmark-pdf"></i> Export as PDF</a>
    </div>

    <section class="card-panel">
        <form method="get" class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Course Title</th><th>Code</th><th>Units</th><th>Status</th><th>Semester</th><th>Date Registered</th></tr></thead>
                <tbody>
                    <?php if (empty($courses)): ?><tr><td colspan="6" class="text-center">No courses found.</td></tr><?php endif; ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo (int) $course['units']; ?></td>
                            <td><span class="badge <?php echo $course['status'] === 'Approved' ? 'badge-success' : ($course['status'] === 'Rejected' ? 'badge-danger' : 'badge-warning'); ?>"><?php echo htmlspecialchars($course['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($course['semester']); ?></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($course['date_registered']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="2" class="table-footer-total">Total Units</td><td colspan="4" class="table-footer-total"><?php echo $totalUnits; ?></td></tr></tfoot>
            </table>
        </div>
    </section>
</main>
<?php require_once '../includes/footer.php'; ?>

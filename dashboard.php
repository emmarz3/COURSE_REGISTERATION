<?php
session_start();
require_once '../config/database.php';

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../auth/login.php');
    exit();
}

$studentId = (int) $_SESSION['user_id'];
$studentName = $_SESSION['name'] ?? 'Student';
$matricNo = $_SESSION['matric_no'] ?? '';
$totalCourses = 0;
$totalUnits = 0;
$pendingCount = 0;
$approvedCount = 0;
$recentCourses = [];

// Load dashboard statistics.
$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total_courses, COALESCE(SUM(c.units), 0) AS total_units FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$totalCourses = (int) ($stats['total_courses'] ?? 0);
$totalUnits = (int) ($stats['total_units'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT status, COUNT(*) AS total FROM registrations WHERE student_id = ? GROUP BY status");
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$statusResult = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($statusResult)) {
    if ($row['status'] === 'Pending') {
        $pendingCount = (int) $row['total'];
    }
    if ($row['status'] === 'Approved') {
        $approvedCount = (int) $row['total'];
    }
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT c.course_title, c.course_code, c.units, r.status, r.date_registered FROM registrations r INNER JOIN courses c ON c.id = r.course_id WHERE r.student_id = ? ORDER BY r.date_registered DESC LIMIT 5");
mysqli_stmt_bind_param($stmt, 'i', $studentId);
mysqli_stmt_execute($stmt);
$recentResult = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($recentResult)) {
    $recentCourses[] = $row;
}
mysqli_stmt_close($stmt);

$activePage = 'dashboard';
require_once '../includes/header.php';
require_once '../includes/student_sidebar.php';
?>
<main class="main-content">
    <section class="card-panel">
        <h1 class="page-title">Welcome, <?php echo htmlspecialchars($studentName); ?></h1>
        <p class="mb-0">Matric Number: <?php echo htmlspecialchars($matricNo); ?></p>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card"><span class="stat-number"><?php echo $totalCourses; ?></span><span class="stat-label">Total Registered Courses</span></div></div>
        <div class="col-md-4"><div class="stat-card"><span class="stat-number"><?php echo $totalUnits; ?></span><span class="stat-label">Total Credit Units</span></div></div>
        <div class="col-md-4"><div class="stat-card"><span class="stat-number"><?php echo $pendingCount; ?> / <?php echo $approvedCount; ?></span><span class="stat-label">Pending / Approved</span></div></div>
    </div>

    <section class="card-panel">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Recent Registered Courses</h2>
            <a class="btn btn-primary" href="register_courses.php"><i class="bi bi-journal-plus"></i> Go to Course Registration</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr><th>Course Title</th><th>Code</th><th>Units</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($recentCourses)): ?>
                        <tr><td colspan="5" class="text-center">No registered courses yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($recentCourses as $course): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($course['course_title']); ?></td>
                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                            <td><?php echo (int) $course['units']; ?></td>
                            <td><span class="badge <?php echo $course['status'] === 'Approved' ? 'badge-success' : 'badge-warning'; ?>"><?php echo htmlspecialchars($course['status']); ?></span></td>
                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($course['date_registered']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require_once '../includes/footer.php'; ?>

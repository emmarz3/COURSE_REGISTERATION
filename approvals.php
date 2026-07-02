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
$allowedStatuses = ['All', 'Pending', 'Approved', 'Rejected'];
$statusFilter = mysqli_real_escape_string($conn, $_GET['status'] ?? 'All');
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = 'All';
}

// Process approval actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid form token. Please try again.';
    } elseif ($action === 'approve' || $action === 'reject') {
        $registrationId = (int) ($_POST['registration_id'] ?? 0);
        $newStatus = $action === 'approve' ? 'Approved' : 'Rejected';
        $stmt = mysqli_prepare($conn, 'UPDATE registrations SET status = ? WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'si', $newStatus, $registrationId);
        $message = mysqli_stmt_execute($stmt) ? 'Registration updated successfully.' : 'Registration could not be updated.';
        mysqli_stmt_close($stmt);
    } elseif ($action === 'bulk_approve') {
        $selected = $_POST['selected'] ?? [];
        $ids = array_values(array_filter(array_map('intval', $selected)));

        if (empty($ids)) {
            $error = 'Select at least one registration to approve.';
        } else {
            $status = 'Approved';
            $updated = 0;
            $stmt = mysqli_prepare($conn, 'UPDATE registrations SET status = ? WHERE id = ?');
            foreach ($ids as $registrationId) {
                mysqli_stmt_bind_param($stmt, 'si', $status, $registrationId);
                if (mysqli_stmt_execute($stmt)) {
                    $updated++;
                }
            }
            mysqli_stmt_close($stmt);
            $message = $updated . ' registration(s) approved successfully.';
        }
    }
}

$registrations = [];
if ($statusFilter === 'All') {
    $stmt = mysqli_prepare($conn, 'SELECT r.id, r.status, r.date_registered, s.full_name, s.matric_no, c.course_title, c.course_code, c.units FROM registrations r INNER JOIN students s ON s.id = r.student_id INNER JOIN courses c ON c.id = r.course_id ORDER BY r.date_registered DESC');
} else {
    $stmt = mysqli_prepare($conn, 'SELECT r.id, r.status, r.date_registered, s.full_name, s.matric_no, c.course_title, c.course_code, c.units FROM registrations r INNER JOIN students s ON s.id = r.student_id INNER JOIN courses c ON c.id = r.course_id WHERE r.status = ? ORDER BY r.date_registered DESC');
    mysqli_stmt_bind_param($stmt, 's', $statusFilter);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $registrations[] = $row;
}
mysqli_stmt_close($stmt);

$activePage = 'approvals';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<main class="main-content">
    <h1 class="page-title">Registrations/Approvals</h1>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <section class="card-panel">
        <form method="get" class="row g-3 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label" for="status">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <?php foreach ($allowedStatuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo $statusFilter === $status ? 'selected' : ''; ?>><?php echo $status; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Filter</button>
            </div>
        </form>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="action" value="bulk_approve">
            <div class="mb-3">
                <button class="btn btn-success" type="submit">Approve Selected</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead><tr><th></th><th>Student Name</th><th>Matric No</th><th>Course</th><th>Units</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if (empty($registrations)): ?><tr><td colspan="8" class="text-center">No registrations found.</td></tr><?php endif; ?>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><input type="checkbox" name="selected[]" value="<?php echo (int) $registration['id']; ?>"></td>
                                <td><?php echo htmlspecialchars($registration['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($registration['matric_no']); ?></td>
                                <td><?php echo htmlspecialchars($registration['course_code'] . ' - ' . $registration['course_title']); ?></td>
                                <td><?php echo (int) $registration['units']; ?></td>
                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($registration['date_registered']))); ?></td>
                                <td><span class="badge <?php echo $registration['status'] === 'Approved' ? 'badge-success' : ($registration['status'] === 'Rejected' ? 'badge-danger' : 'badge-warning'); ?>"><?php echo htmlspecialchars($registration['status']); ?></span></td>
                                <td class="action-row">
                                    <button class="btn btn-success btn-sm" type="submit" formaction="approvals.php?status=<?php echo urlencode($statusFilter); ?>" formmethod="post" name="action" value="approve" onclick="this.form.registration_id.value='<?php echo (int) $registration['id']; ?>'">Approve</button>
                                    <button class="btn btn-danger btn-sm" type="submit" formaction="approvals.php?status=<?php echo urlencode($statusFilter); ?>" formmethod="post" name="action" value="reject" onclick="this.form.registration_id.value='<?php echo (int) $registration['id']; ?>'">Reject</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <input type="hidden" name="registration_id" value="">
        </form>
    </section>
</main>
<?php require_once '../includes/footer.php'; ?>

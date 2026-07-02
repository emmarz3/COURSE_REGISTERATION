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
$search = mysqli_real_escape_string($conn, trim($_GET['search'] ?? ''));

// Toggle payment status for a student.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $newStatus = ($_POST['new_status'] ?? '') === 'Paid' ? 'Paid' : 'Unpaid';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $session = '2025/2026';
        $amount = 0.00;
        $stmt = mysqli_prepare($conn, 'SELECT id FROM payments WHERE student_id = ? AND session = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'is', $studentId, $session);
        mysqli_stmt_execute($stmt);
        $payment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($payment) {
            $stmt = mysqli_prepare($conn, 'UPDATE payments SET status = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $newStatus, $payment['id']);
        } else {
            $stmt = mysqli_prepare($conn, 'INSERT INTO payments (student_id, session, amount, status) VALUES (?, ?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'isds', $studentId, $session, $amount, $newStatus);
        }

        $message = mysqli_stmt_execute($stmt) ? 'Payment status updated successfully.' : 'Payment status could not be updated.';
        mysqli_stmt_close($stmt);
    }
}

$payments = [];
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = mysqli_prepare($conn, "SELECT s.id AS student_id, s.full_name, s.matric_no, COALESCE(p.session, '2025/2026') AS session, COALESCE(p.amount, 0) AS amount, COALESCE(p.status, 'Unpaid') AS status FROM students s LEFT JOIN payments p ON p.student_id = s.id AND p.session = '2025/2026' WHERE s.full_name LIKE ? OR s.matric_no LIKE ? ORDER BY s.full_name");
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
} else {
    $stmt = mysqli_prepare($conn, "SELECT s.id AS student_id, s.full_name, s.matric_no, COALESCE(p.session, '2025/2026') AS session, COALESCE(p.amount, 0) AS amount, COALESCE(p.status, 'Unpaid') AS status FROM students s LEFT JOIN payments p ON p.student_id = s.id AND p.session = '2025/2026' ORDER BY s.full_name");
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $payments[] = $row;
}
mysqli_stmt_close($stmt);

$activePage = 'payments';
require_once '../includes/header.php';
require_once '../includes/admin_sidebar.php';
?>
<main class="main-content">
    <h1 class="page-title">Payments</h1>
    <?php if ($message !== ''): ?><div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <section class="card-panel">
        <form method="get" class="row g-3 align-items-end mb-3">
            <div class="col-md-6">
                <label class="form-label" for="search">Search by Student Name or Matric Number</label>
                <input class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Search</button>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead><tr><th>Name</th><th>Matric No</th><th>Session</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    <?php if (empty($payments)): ?><tr><td colspan="6" class="text-center">No students found.</td></tr><?php endif; ?>
                    <?php foreach ($payments as $payment): ?>
                        <?php $nextStatus = $payment['status'] === 'Paid' ? 'Unpaid' : 'Paid'; ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['matric_no']); ?></td>
                            <td><?php echo htmlspecialchars($payment['session']); ?></td>
                            <td><?php echo number_format((float) $payment['amount'], 2); ?></td>
                            <td><span class="badge <?php echo $payment['status'] === 'Paid' ? 'badge-success' : 'badge-warning'; ?>"><?php echo htmlspecialchars($payment['status']); ?></span></td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="student_id" value="<?php echo (int) $payment['student_id']; ?>">
                                    <input type="hidden" name="new_status" value="<?php echo $nextStatus; ?>">
                                    <button class="btn <?php echo $nextStatus === 'Paid' ? 'btn-success' : 'btn-danger'; ?> btn-sm" type="submit">Mark as <?php echo $nextStatus; ?></button>
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

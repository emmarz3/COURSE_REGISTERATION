<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$displayName = $_SESSION['name'] ?? 'User';
$displayMeta = $_SESSION['matric_no'] ?? ($_SESSION['username'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chrisland University Course Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<header class="top-navbar">
    <div class="navbar-brand-block">
        <img src="/course_registration/photo_2026-07-01_03-44-37.jpg" alt="Chrisland University Logo" class="logo-img">
        <span class="brand-title">CHRISLAND UNIVERSITY</span>
    </div>
    <div class="dropdown">
        <button class="btn user-menu dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle"></i>
            <span><?php echo htmlspecialchars($displayName); ?></span>
            <?php if ($displayMeta !== ''): ?>
                <small><?php echo htmlspecialchars($displayMeta); ?></small>
            <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text"><?php echo htmlspecialchars($displayName); ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
    </div>
</header>

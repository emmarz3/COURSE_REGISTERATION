<?php
$activePage = $activePage ?? '';
$adminLinks = [
    'dashboard' => ['Dashboard', 'bi-speedometer2', '../admin/dashboard.php'],
    'courses' => ['Manage Courses', 'bi-book', '../admin/courses.php'],
    'approvals' => ['Registrations/Approvals', 'bi-check2-square', '../admin/approvals.php'],
    'students' => ['Students', 'bi-people', '../admin/students.php'],
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="/course_registration/photo_2026-07-01_03-44-37.jpg" alt="Chrisland University Logo" class="logo-img" style="height: 28px; margin-right: 8px;">
        CHRISLAND UNIVERSITY
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($adminLinks as $key => $link): ?>
            <a class="<?php echo $activePage === $key ? 'active' : ''; ?>" href="<?php echo $link[2]; ?>">
                <i class="bi <?php echo $link[1]; ?>"></i>
                <span><?php echo $link[0]; ?></span>
            </a>
        <?php endforeach; ?>
        <a href="../auth/logout.php">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

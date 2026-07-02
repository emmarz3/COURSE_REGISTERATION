<?php
$activePage = $activePage ?? '';
$studentLinks = [
    'dashboard' => ['Dashboard', 'bi-speedometer2', '../student/dashboard.php'],
    'profile' => ['Profile', 'bi-person-badge', '../student/profile.php'],
    'register_courses' => ['Course Registration', 'bi-journal-plus', '../student/register_courses.php'],
    'my_courses' => ['My Courses', 'bi-journals', '../student/my_courses.php'],
];
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="/course_registration/photo_2026-07-01_03-44-37.jpg" alt="Chrisland University Logo" class="logo-img" style="height: 28px; margin-right: 8px;">
        CHRISLAND UNIVERSITY
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($studentLinks as $key => $link): ?>
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

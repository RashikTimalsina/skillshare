<!-- includes/layouts/sidebar.php -->
<aside class="sidebar">
    <ul>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li><a href="/dashboard/admin_dashboard.php">Dashboard</a></li>
            <li><a href="/pages/admin/users.php">Users</a></li>
            <li><a href="/pages/admin/skills.php">Skills</a></li>
            <li><a href="/pages/admin/categories.php">Categories</a></li>
        <?php elseif ($_SESSION['user_role'] === 'teacher'): ?>
            <li><a href="/dashboard/teacher_dashboard.php">Dashboard</a></li>
            <li><a href="/pages/teacher/skills.php">My Skills</a></li>
            <li><a href="/pages/teacher/enrollments.php">Enrollments</a></li>
        <?php elseif ($_SESSION['user_role'] === 'learner'): ?>
            <li><a href="/dashboard/learner_dashboard.php">Dashboard</a></li>
            <li><a href="/pages/learner/browse_skills.php">Browse Skills</a></li>
            <li><a href="/pages/learner/enrollments.php">My Enrollments</a></li>
        <?php endif; ?>
    </ul>
</aside>
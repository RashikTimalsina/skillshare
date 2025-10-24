<aside class="sidebar">
    <ul>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li><a href="../dashboard/admin_dashboard.php">Dashboard</a></li>
            <li><a href="../pages/users.php">Users</a></li>
            <li><a href="../pages/skills.php">Skills</a></li>
            <li><a href="../pages/categories.php">Categories</a></li>
            <li><a href="../pages/enrollments.php">Enrollments</a></li>
            <li><a href="../pages/feedbacks.php">Feedback</a></li>

        <?php elseif ($_SESSION['user_role'] === 'teacher'): ?>
            <li><a href="../dashboard/teacher_dashboard.php">Dashboard</a></li>
            <li><a href="../pages/skills.php">My Skills</a></li>
            <li><a href="../pages/profile.php">My Profile</a></li>
            <li><a href="../pages/enrollments.php">Enrollments</a></li>
            <li><a href="../pages/feedbacks.php">Feedback</a></li>

        <?php elseif ($_SESSION['user_role'] === 'learner'): ?>
            <li><a href="../dashboard/learner_dashboard.php">Dashboard</a></li>
            <li><a href="../pages/skills.php">My Skills</a></li>
            <li><a href="../pages/profile.php">My Profile</a></li>
            <li><a href="../pages/enrollments.php">My Enrollments</a></li>
            <li><a href="../pages/feedbacks.php">Feedback</a></li>
        <?php endif; ?>
    </ul>
    <ul class="bottom-links">
        <li><a href="../pages/logout.php">Logout</a></li>
    </ul>
</aside>
<aside class="sidebar">
    <ul>
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
            <li><a href="../dashboard/admin_dashboard.php"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
            <li><a href="../pages/users.php"><span class="material-symbols-outlined">group</span>Users</a></li>
            <li><a href="../pages/skills.php"><span class="material-symbols-outlined">lightbulb</span>Skills</a></li>
            <li><a href="../pages/categories.php"><span class="material-symbols-outlined">category</span>Categories</a></li>
            <li><a href="../pages/enrollments.php"><span class="material-symbols-outlined">assignment</span>Enrollments</a></li>
            <li><a href="../pages/feedbacks.php"><span class="material-symbols-outlined">feedback</span>Feedback</a></li>

        <?php elseif ($_SESSION['user_role'] === 'teacher'): ?>
            <li><a href="../dashboard/teacher_dashboard.php"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
            <li><a href="../pages/skills.php"><span class="material-symbols-outlined">lightbulb</span>Skills</a></li>
            <li><a href="../pages/teacher_skills.php"><span class="material-symbols-outlined">star</span>My Skills</a></li>
            <li><a href="../pages/enrollments.php"><span class="material-symbols-outlined">assignment</span>Enrollments</a></li>
            <li><a href="../pages/feedbacks.php"><span class="material-symbols-outlined">feedback</span>Feedback</a></li>

        <?php elseif ($_SESSION['user_role'] === 'learner'): ?>
            <li><a href="../dashboard/learner_dashboard.php"><span class="material-symbols-outlined">dashboard</span>Dashboard</a></li>
            <li><a href="../pages/learner_skills.php"><span class="material-symbols-outlined">star</span>My Skills</a></li>
            <li><a href="../pages/profile.php"><span class="material-symbols-outlined">person</span>My Profile</a></li>
            <li><a href="../pages/enrollments.php"><span class="material-symbols-outlined">assignment</span>My Enrollment</a></li>
            <li><a href="../pages/feedbacks.php"><span class="material-symbols-outlined">feedback</span>Feedback</a></li>
        <?php endif; ?>
    </ul>
</aside>

<!-- Include Google Material Symbols in head of layout or main file -->
<link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
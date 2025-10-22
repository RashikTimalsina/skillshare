<!-- includes/layouts/topbar.php -->
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" id="hamburger-btn">
            ☰
        </button>
        <h1 class="dashboard-title">
            <?php
            if ($_SESSION['user_role'] === 'admin') echo 'Admin Dashboard';
            elseif ($_SESSION['user_role'] === 'teacher') echo 'Teacher Dashboard';
            else echo 'Learner Dashboard';
            ?>
        </h1>
    </div>

    <div class="topbar-right">
        <div class="icon-placeholder">🔔</div>
        <div class="icon-placeholder">👤</div>
    </div>
</header>
<?php
// Fetch unread messages count for current user
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) AS total_unread 
              FROM messages 
              WHERE receiver_id = '$user_id' AND is_read = 'no'";
    $res = mysqli_query($conn, $query);
    if ($res && mysqli_num_rows($res) > 0) {
        $unread_count = mysqli_fetch_assoc($res)['total_unread'];
    }
}
?>
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" id="hamburger-btn">☰</button>
        <h1 class="dashboard-title">
            <?php
            if ($_SESSION['user_role'] === 'admin') echo 'Admin Dashboard';
            elseif ($_SESSION['user_role'] === 'teacher') echo 'Teacher Dashboard';
            else echo 'Learner Dashboard';
            ?>
        </h1>
    </div>

    <div class="topbar-right">
        <!-- Message Icon -->
        <div class="icon-placeholder">
            <a href="../pages/messages.php">💬</a>
            <?php if ($unread_count > 0): ?>
                <span class="badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </div>

        <!-- Profile Icon -->
        <div class="icon-placeholder">
            <a href="../pages/profile.php">👤</a>
        </div>
    </div>
</header>

<style>
    /* Simple badge for unread messages */
    .icon-placeholder .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: red;
        color: white;
        font-size: 12px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 50%;
    }
</style>
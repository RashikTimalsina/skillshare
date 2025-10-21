<!-- includes/layouts/topbar.php -->
<section class="topbar">
    <div class="left">
        <button id="menuToggle">☰</button>
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></span>
    </div>
    <div class="right">
        <a href="/auth/logout.php" class="logout-btn">Logout</a>
    </div>
</section>
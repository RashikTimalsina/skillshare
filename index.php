<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'includes/config.php';

// Force logout if coming back from a dashboard
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
}

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>SkillShare - Learn, Teach & Share</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>

<body>
    <!-- ===== TOPBAR ===== -->
    <header class="topbar">
        <div class="brand">SkillShare</div>
        <nav class="nav">
            <a href="auth/signup.php" style="color:var(--primary)"><b>Signup</b></a>
            <a href="auth/login.php" style="color:var(--primary)"><b>Login</b></a>
        </nav>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="container">
        <section class="hero">
            <div class="hero-left">
                <h1>Learn, Teach and<br>Share Skills Easily</h1>
                <p>Join thousands of learners and teachers worldwide</p>
                <div class="cta-row">
                    <a class="btn" href="pages/browse_skills.php">Explore Skills</a>
                    <a class="btn" href="auth/signup.php">Sign Up</a>
                </div>
            </div>
            <div class="placeholder-media">
                <img src="assets/images/hero.png" alt="SkillShare Hero Image">
            </div>
        </section>

       
        <!-- <div style="text-align:center;margin-top:80px"> <a class="btn" href="auth/signup.php">Get Started</a> </div> -->
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        © 2025 SkillShare System. All rights reserved.
    </footer>
</body>

</html>
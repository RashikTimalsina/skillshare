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
    <link rel="stylesheet" href="assets/css/pages/style.css" />
</head>

<body>
    <!-- ===== TOPBAR ===== -->
    <header class="topbar">
        <div class="brand">
            <a href="#">
                <img src="assets/images/skillshare-logo.png" alt="SkillShare" style="height:50px;  width: 100%;
  object-fit:contain;">
            </a>
        </div>
        <nav class="nav" style="margin-right:auto;">
            <a href="about.php" style="color:var(--primary)" ><b>About</b></a>
            <a href="contact.php" style="color:var(--primary)"><b>Contact</b></a>
        </nav>
        <nav class="nav">
            <a href="auth/signup.php" style="color:var(--primary)"><b>SignUp</b></a>
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
                    <a class="btn" href="pages/browse_skills.php" style="text-decoration:none">Explore Skills</a>
                    <a class="btn" href="auth/signup.php" style="text-decoration:none">Get Started</a>
                </div>
            </div>
            <div class="placeholder-media">
                <img src="assets/images/hero.png" alt="SkillShare Hero Image">
            </div>
        </section>
    </main>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        © 2026 SkillShare System. All rights reserved.
    </footer>
</body>

</html>
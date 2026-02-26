<!-- ======= ABOUT PAGE ========-- -->

<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'includes/config.php';

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>About - SkillShare System</title>
    <link rel="stylesheet" href="assets/css/pages/style.css" />
</head>

<body>

    <!-- ===== TOPBAR ===== -->
    <header class="topbar">
        <div class="brand">
            <a href="index.php">
                <img src="assets/images/skillshare-logo.png"
                    alt="SkillShare"
                    style="height:50px; width:100%; object-fit:contain;">
            </a>
        </div>
        <nav class="nav" style="margin-right:auto;">
            <a href="#" style="color:var(--primary)"><b>About</b></a>
            <a href="contact.php" style="color:var(--primary)"><b>Contact</b></a>
        </nav>
        <nav class="nav">
            <a href=" auth/signup.php" style="color:var(--primary)"><b>SignUp</b></a>
            <a href="auth/login.php" style="color:var(--primary)"><b>Login</b></a>
        </nav>
    </header>


    <!-- ===== MAIN CONTENT ===== -->
    <main class="container">

        <!-- ===== ABOUT INTRO SECTION ===== -->
        <section class="section">
            <h2>About SkillShare System</h2>
            <div class="cards">
                <div class="feature">
                    <h3>Our Mission</h3>
                    <p>
                        SkillShare System is built to connect learners and teachers
                        through structured skill-based collaboration. We aim to create
                        an ecosystem where knowledge is shared efficiently and transparently.
                    </p>
                </div>

                <div class="feature">
                    <h3>Our Vision</h3>
                    <p>
                        To become a scalable, role-based learning platform that enables
                        individuals to teach, learn, and grow in a secure and professional environment.
                    </p>
                </div>

                <div class="feature">
                    <h3>What We Offer</h3>
                    <p>
                        Role-based dashboards for Admin, Teachers, and Learners with
                        enrollment management, feedback systems, certification tracking,
                        and structured workflows.
                    </p>
                </div>
            </div>
        </section>


        <!-- ===== HOW IT WORKS SECTION ===== -->
        <section class="section">
            <h2>How It Works</h2>
            <div class="cards">

                <div class="feature">
                    <h3>1. Explore Skills</h3>
                    <p>
                        Browse available skills across multiple categories and
                        discover opportunities to learn or teach.
                    </p>
                </div>

                <div class="feature">
                    <h3>2. Enroll or Teach</h3>
                    <p>
                        Learners enroll in skills while teachers manage their
                        teaching status, content, and ratings.
                    </p>
                </div>

                <div class="feature">
                    <h3>3. Track Progress</h3>
                    <p>
                        Monitor enrollments, provide feedback, earn certifications,
                        and build credibility within the system.
                    </p>
                </div>

            </div>
        </section>


        <!-- ===== CALL TO ACTION ===== -->
        <section class="section">
            <h2>Start Your Journey Today</h2>
            <div class="cta-row" style="justify-content:center; margin-top:20px;">
                <a class="btn" href="pages/browse_skills.php" style="text-decoration:none">
                    Explore Skills
                </a>
                <a class="btn" href="auth/signup.php" style="text-decoration:none">
                    Create Account
                </a>
            </div>
        </section>

    </main>


    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        © 2026 SkillShare System. All rights reserved.
    </footer>

</body>

</html>
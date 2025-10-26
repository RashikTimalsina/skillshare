<?php
// index.php
session_start();

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
    
    <link rel="stylesheet" href="assets/css/style.css">
    

</head>

<body>
    <div class="topbar">
        <div class="brand">SkillShare</div>
        <div class="nav">
            <a href="auth/signup.php">Signup</a>
            <a href="auth/login.php">Login</a>
        </div>
    </div>

    <main class="container">
        <section class="hero">
            <div class="hero-left">
                <h1>Learn, Teach and <br>
                    Share Skills Easily</h1>
                <p>Connect with learners and teachers from around the world</p>
                <div class="cta-row">
                    <a class="btn" href="pages/browse_skills.php">Explore Skills</a>
                    <a class="btn" href="auth/signup.php">SignUp</a>
                </div>
            </div>
            <div class="placeholder-media">Hero image / illustration</div>
        </section>

        <section class="section">
            <h2>Why Choose Us?</h2>
            <div class="cards" style="margin-top:24px">
                <div class="feature">Easy Skill Sharing</div>
                <div class="feature">Connect with experts</div>
                <div class="feature">Learn at your pace</div>
                <div class="feature">Track Your Progress</div>
            </div>
        </section>

        <section class="section">
            <h2>How It Works ?</h2>
            <div class="cards" style="margin-top:24px">
                <div class="feature">Signup and create profile</div>
                <div class="feature">Browse and enroll in skills</div>
                <div class="feature">Learn or Teach Online</div>
                <div class="feature">Track through Progress</div>
            </div>

            <div style="text-align:center;margin-top:48px">
                <a class="btn" href="pages/browse_skills.php">Learn More</a>
            </div>
        </section>
    </main>

    <div class="footer">
        <a href="#">Privacy Policy</a>
        <a href="#">Contact</a>
        <a href="#">Terms</a>
    </div>
</body>

</html>
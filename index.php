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
    <style>
        :root {
            --bg: #ffffff;
            --muted: #f2f2f2;
            --accent: #2c6f6a;
            --text: #111;
            --card: #efefef;
            font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: var(--bg);
            color: var(--text);
            min-height: 100%;
        }

        .topbar {
            background: #f6f6f6;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e6e6e6;
        }

        .topbar .brand {
            font-weight: 600;
            font-size: 20px;
        }

        .topbar .nav a {
            margin-left: 18px;
            text-decoration: none;
            color: #222;
            font-weight: 500
        }

        .container {
            max-width: 1100px;
            margin: 48px auto;
            padding: 0 20px;
        }

        .hero {
            display: flex;
            gap: 40px;
            align-items: center
        }

        .hero-left {
            flex: 1
        }

        .hero h1 {
            font-size: 48px;
            margin: 0 0 16px
        }

        .hero p {
            font-size: 18px;
            color: #444;
            margin: 0 0 28px
        }

        .cta-row {
            display: flex;
            gap: 18px
        }

        .btn {
            background: var(--card);
            border: none;
            padding: 14px 26px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer
        }

        .placeholder-media {
            width: 360px;
            height: 220px;
            background: #eee;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999
        }

        .section {
            margin: 70px 0
        }

        .cards {
            display: flex;
            gap: 24px;
            flex-wrap: wrap
        }

        .feature {
            background: var(--card);
            padding: 36px;
            border-radius: 6px;
            min-width: 180px;
            text-align: center
        }

        .footer {
            background: #f6f6f6;
            padding: 18px 32px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: center;
            gap: 24px
        }

        @media (max-width:900px) {
            .hero {
                flex-direction: column
            }

            .placeholder-media {
                width: 100%;
                height: 200px
            }

            .hero h1 {
                font-size: 32px
            }
        }
    </style>
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
                <h1>Learn, Teach and Share Skills Easily</h1>
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
            <div class="cards" style="margin-top:18px">
                <div class="feature">Easy Skill Sharing</div>
                <div class="feature">Connect with experts</div>
                <div class="feature">Learn at your pace</div>
                <div class="feature">Track Your Progress</div>
            </div>
        </section>

        <section class="section">
            <h2>How It Works ?</h2>
            <div class="cards" style="margin-top:18px">
                <div class="feature">Signup and create profile</div>
                <div class="feature">Browse and enroll in skills</div>
                <div class="feature">Learn or Teach Online</div>
                <div class="feature">Track through progressions</div>
            </div>

            <div style="text-align:center;margin-top:40px">
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
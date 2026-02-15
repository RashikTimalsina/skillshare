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
    <title>Contact - SkillShare System</title>
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
            <a href="about.php" style="color:var(--primary)"><b>About</b></a>
            <a href="#" style="color:var(--primary)"><b>Contact</b></a>
        </nav>
        <nav class="nav">
            <a href="auth/signup.php" style="color:var(--primary)"><b>SignUp</b></a>
            <a href="auth/login.php" style="color:var(--primary)"><b>Login</b></a>
        </nav>
    </header>


    <!-- ===== MAIN CONTENT ===== -->
    <main class="container">

        <!-- ===== CONTACT INTRO ===== -->
        <section class="section">
            <h2>Contact Us</h2>
            <div class="cards">
                <div class="feature">
                    <h3>Email Support</h3>
                    <p>
                        For general inquiries, technical issues, or feedback,
                        reach us at:
                    </p>
                    <p style="margin-top:10px; font-weight:600;">
                        support@skillshare-system.com
                    </p>
                </div>

                <div class="feature">
                    <h3>System Inquiries</h3>
                    <p>
                        Questions regarding enrollments, certifications,
                        or teacher approvals can be directed to:
                    </p>
                    <p style="margin-top:10px; font-weight:600;">
                        admin@skillshare-system.com
                    </p>
                </div>

                <div class="feature">
                    <h3>Response Time</h3>
                    <p>
                        Our team typically responds within 24–48 business hours.
                        Please include relevant details to receive faster support.
                    </p>
                </div>
            </div>
        </section>


        <!-- ===== CONTACT FORM ===== -->
        <section class="section">
            <h2>Send Us a Message</h2>

            <div class="cards">
                <div class="feature" style="width:100%;">

                    <form action="#" method="POST" style="display:flex; flex-direction:column; gap:15px;">

                        <input type="text" name="name" placeholder="Your Full Name"
                            required
                            style="padding:12px; border:1px solid var(--muted); border-radius:6px;">

                        <input type="email" name="email" placeholder="Your Email Address"
                            required
                            style="padding:12px; border:1px solid var(--muted); border-radius:6px;">

                        <textarea name="message" rows="5"
                            placeholder="Write your message here..."
                            required
                            style="padding:12px; border:1px solid var(--muted); border-radius:6px; resize:none;"></textarea>

                        <button type="submit" class="btn" style="align-self:flex-start;">
                            Send Message
                        </button>

                    </form>

                </div>
            </div>
        </section>

    </main>


    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        © 2026 SkillShare System. All rights reserved.
    </footer>

</body>

</html>
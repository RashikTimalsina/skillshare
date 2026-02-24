<?php

session_start();

$user_name = $_SESSION['user_name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | SkillShare</title>
    <link rel="stylesheet" href="assets/css/layout.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: #f9f9f9;
        }

        .container {
            text-align: center;
            padding: 40px;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .container h1 {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .container p {
            font-size: 18px;
            margin-bottom: 30px;
        }

        .container a {
            text-decoration: none;
            color: #fff;
            background: #0d6efd;
            padding: 10px 25px;
            border-radius: 6px;
            transition: 0.3s;
        }

        .container a:hover {
            background: #084298;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>❌ Access Denied</h1>
        <p>Hello <?= htmlspecialchars($user_name) ?>, you are not authorized to view this page.</p>
        <a href="index.php">Go to Home</a>
    </div>
</body>

</html>
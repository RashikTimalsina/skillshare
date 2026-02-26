<!-- =================VIEW CERTIFICATES FOR LEARNER PAGE======================= -->

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../middlewares/session_check.php';
require_once '../middlewares/role_check.php';

// Allow learner & admin
allowRoles(['learner', 'admin']);

require_once '../models/Certificate.php';

$certificateId = $_GET['id'] ?? null;

if (!$certificateId) {
    die('Invalid certificate request.');
}

// Fetch certificate by calling getById method of Certificate model
$certificate = Certificate::getById($conn, $certificateId);

if (!$certificate) {
    die('Certificate not found.');
}

//  Learner can view only their own certificate
if ($_SESSION['user_role'] === 'learner' && $_SESSION['user_id'] != $certificate['learner_id']) {
    die('Unauthorized access.');
}
//  Learner can view only their own certificate
if ($_SESSION['user_role'] === 'learner' && $_SESSION['user_id'] != $certificate['learner_id']) {
    die('Unauthorized access.');
}

// Fetch the teacher who created/offered this skill
$teacherStmt = $conn->prepare("
    SELECT u.user_name
    FROM enrollments e
    JOIN teachers t ON e.skills_skill_id = t.skills_skill_id
    JOIN users u ON t.users_user_id = u.user_id
    WHERE e.users_user_id = ?
      AND e.skills_skill_id = ?
      AND e.status = 'completed'
    LIMIT 1
");
$teacherStmt->bind_param("ii", $certificate['learner_id'], $certificate['skill_id']);
$teacherStmt->execute();
$teacherRes = $teacherStmt->get_result();
$teacher = $teacherRes->fetch_assoc();
$teacherName = $certificate['issuer_name'] ?? 'Unknown Instructor';
$teacherStmt->close();

//  Get any active admin for signature
$adminStmt = $conn->prepare("SELECT user_name FROM users WHERE role='admin' LIMIT 1");
$adminStmt->execute();
$adminRes = $adminStmt->get_result();
$admin = $adminRes->fetch_assoc();
$adminName = $admin['user_name'] ?? 'Admin';
$adminStmt->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>

    <style>
        body {
            background: #f4f6f8;
            font-family: "Times New Roman", serif;
        }

        .certificate-wrapper {
            width: 900px;
            margin: 70px auto;
            background: #fff;
            padding: 50px;
            border: 3px solid #1e3a8a;
        }

        .certificate-header {
            text-align: center;
        }

        .certificate-header h1 {
            font-size: 40px;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .certificate-header p {
            font-size: 18px;
            margin-top: 0;
        }

        .certificate-body {
            text-align: center;
            margin-top: 40px;
        }

        .certificate-body h2 {
            font-size: 30px;
            margin-bottom: 10px;
        }

        .certificate-body p {
            font-size: 18px;
            margin: 12px 0;
        }

        .highlight {
            font-weight: bold;
            font-size: 22px;
        }

        .certificate-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }

        .footer-block {
            text-align: center;
        }

        .footer-block span {
            display: block;
            margin-top: 8px;
            font-size: 16px;
        }

        .footer-block img {
            display: block;
            margin: 0 auto;
        }

        .meta {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #555;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .certificate-wrapper,
            .certificate-wrapper * {
                visibility: visible;
            }

            .certificate-wrapper {
                margin: 0;
                padding: 0;
                width: 100%;
            }

            button {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="certificate-wrapper">

        <div class="certificate-header">
            <img src="../assets/images/logo.png" alt="SkillShare Logo" style="height:80px; width:auto;">
            <div>
                <h1 style="margin:0;">CERTIFICATE OF COMPLETION</h1>
                <p style="margin-top:5px;">This certifies that</p>
            </div>

            <div class="certificate-body">
                <h2 class="highlight"><?= htmlspecialchars($certificate['learner_name']) ?></h2>

                <p>has successfully completed the skill</p>

                <h2 class="highlight"><?= htmlspecialchars($certificate['skill_name']) ?></h2>

                <p>through the <b>Skill-Sharing-System</b> platform</p>

                <p><strong>Issued On:</strong> <?= date('d F Y', strtotime($certificate['issued_date'])) ?></p>
            </div>

            <div class="certificate-footer">
                <div class="footer-block">
                    <strong><?= htmlspecialchars($teacherName) ?></strong>
                    <span>Under the supervision</span>
                </div>

                <div class="footer-block">
                    <img src="../assets/images/mysign.png" alt="Admin Signature" style="width:150px; height:auto;">
                    <!-- <span><b>Admin Signature</b></span> -->
                </div>


                <div class="footer-block">
                    <strong>Certificate Code</strong>
                    <span><?= htmlspecialchars($certificate['certificate_code']) ?></span>
                </div>
            </div>

            <div class="meta">
                <!-- Copyright ©️ Well Reserved -->
            </div>
        </div>

        <div style="text-align:center; margin-bottom:20px;">
            <button onclick="window.print()" style="
        padding:10px 20px;
        font-size:16px;
        background-color:#28a745;;
        color:white;
        border:none;
        border-radius:5px;
        cursor:pointer;
    ">Download as PDF</button>
        </div>


</body>

</html>
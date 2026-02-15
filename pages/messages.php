<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('../includes/config.php');
require_once __DIR__ . '/../includes/functions.php';
include('../middlewares/session_check.php');

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'learner';

// ================= GET CONVERSATIONS =================
$conversations = [];
$stmt = $conn->prepare("
    SELECT u.user_id, u.user_name, u.role,
           (SELECT COUNT(*) 
            FROM messages m 
            WHERE m.sender_id = u.user_id 
              AND m.receiver_id = ? 
              AND m.is_read = 'no') AS unread_count
    FROM users u
    WHERE u.user_id != ?
      AND (
            (? = 'admin') -- admins see all
         OR (? = 'teacher' AND u.role != 'teacher') 
         OR (? = 'learner' AND u.role != 'admin') 
      )
    ORDER BY CASE WHEN u.role='admin' THEN 0 ELSE 1 END, u.user_name ASC
");
$stmt->bind_param("iisss", $user_id, $user_id, $user_role, $user_role, $user_role);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $conversations[] = $row;
$stmt->close();

// ================= AUTO-SELECT FIRST CONVERSATION =================
$active_receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;
if (!$active_receiver_id && !empty($conversations)) {
    $active_receiver_id = $conversations[0]['user_id'];
    header("Location: messages.php?receiver_id=$active_receiver_id");
    exit();
}

// ================= GET MESSAGES =================
$messages = [];
if ($active_receiver_id) {
    $stmt = $conn->prepare("
        SELECT m.*, u.role as sender_role
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.sent_at ASC
    ");
    $stmt->bind_param("iiii", $user_id, $active_receiver_id, $active_receiver_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $messages[] = $row;
    $stmt->close();

    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 'yes' 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 'no'
    ");
    $stmt->bind_param("ii", $active_receiver_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// ================= HANDLE SEND MESSAGE =================
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message_text'] ?? '');
    $media_path = null;

    // Prevent learners from messaging admins
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $receiver_id);
    $stmt->execute();
    $stmt->bind_result($receiver_role);
    $stmt->fetch();
    $stmt->close();

    if ($user_role === 'learner' && $receiver_role === 'admin') {
        $errors[] = "Learners cannot message admins directly.";
    } else {
        // Handle file upload
        if (!empty($_FILES['media']['name']) && $_FILES['media']['error'] === 0) {
            $file = $_FILES['media'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
            $max_size = 5 * 1024 * 1024;

            if ($file['size'] > $max_size) {
                $errors[] = "Media must be less than 5MB.";
            } elseif (!in_array($file['type'], $allowed_types)) {
                $errors[] = "Only JPEG, PNG, GIF, WEBP, or PDF allowed.";
            } else {
                $upload_dir = __DIR__ . '/../uploads/messages/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid() . '.' . $ext;
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Store relative path for DB
                    $media_path = 'uploads/messages/' . $filename;
                } else {
                    $errors[] = "Failed to upload media.";
                }
            }
        }

        // Insert into DB if text or media exists
        if (($message_text || $media_path) && empty($errors)) {
            $stmt = $conn->prepare("
                INSERT INTO messages (sender_id, receiver_id, message_text, media_path, is_read)
                VALUES (?, ?, ?, ?, 'no')
            ");
            $stmt->bind_param("iiss", $user_id, $receiver_id, $message_text, $media_path);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Only redirect if no errors, else show them
    if (empty($errors)) {
        header("Location: messages.php?receiver_id=$receiver_id");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | SkillShare</title>
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/pages/messages.css">
    <style>
        .message.admin {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
        }

        .message.teacher {
            background-color: #e2f0d9;
        }

        .chat-media {
            max-width: 200px;
            margin-top: 5px;
            border-radius: 4px;
        }

        .flash-error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper">
        <?php include('../includes/layouts/sidebar.php'); ?>
        <div class="main-content-wrapper">
            <?php include('../includes/layouts/topbar.php'); ?>
            <main class="main-content">
                <div class="messages-container">

                    <!-- Display errors -->
                    <?php if (!empty($errors)): ?>
                        <div class="flash-error">
                            <?php foreach ($errors as $err) echo "<p>$err</p>"; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Conversations List -->
                    <aside class="conversations-list">
                        <h2>Conversations</h2>
                        <ul>
                            <?php foreach ($conversations as $conv): ?>
                                <li class="<?= ($active_receiver_id == $conv['user_id']) ? 'active' : '' ?>">
                                    <a href="messages.php?receiver_id=<?= $conv['user_id'] ?>" style="text-decoration:none">
                                        <?= htmlspecialchars($conv['user_name']) ?><?php if ($conv['role'] === 'admin') echo ' (Admin)'; ?>
                                        <?php if ($conv['unread_count'] > 0): ?><span class="unread-count"><?= $conv['unread_count'] ?></span><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </aside>

                    <!-- Chat Window -->
                    <section class="chat-window">
                        <div class="chat-header">
                            <h3>
                                <?php
                                foreach ($conversations as $conv) {
                                    if ($conv['user_id'] == $active_receiver_id) {
                                        echo htmlspecialchars($conv['user_name']);
                                        if ($conv['role'] === 'admin') echo ' (Admin)';
                                        break;
                                    }
                                }
                                ?>
                            </h3>
                        </div>

                        <div class="chat-messages" id="chat-messages">
                            <?php foreach ($messages as $msg):
                                $class = ($msg['sender_id'] == $user_id) ? 'sent' : 'received';
                                if ($msg['sender_role'] === 'admin') $class .= ' admin';
                                elseif ($msg['sender_role'] === 'teacher') $class .= ' teacher';
                            ?>
                                <div class="message <?= $class ?>">
                                    <?php if ($msg['message_text']): ?><p><?= nl2br(htmlspecialchars($msg['message_text'])) ?></p><?php endif; ?>
                                    <?php if (!empty($msg['media_path'])):
                                        $ext = strtolower(pathinfo($msg['media_path'], PATHINFO_EXTENSION));
                                        if ($ext === 'pdf'): ?>
                                            <a href="<?= htmlspecialchars('../' . $msg['media_path']) ?>" target="_blank">Download File</a>
                                        <?php else: ?>
                                            <img src="<?= htmlspecialchars('../' . $msg['media_path']) ?>" class="chat-media" alt="media">
                                    <?php endif;
                                    endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" id="chat-form" enctype="multipart/form-data">
                            <input type="hidden" name="receiver_id" value="<?= $active_receiver_id ?>">
                            <input type="text" name="message_text" placeholder="Type your message...">
                            <input type="file" name="media" accept="image/*,application/pdf">
                            <button type="submit">Send</button>
                        </form>
                    </section>

                </div>
            </main>
            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById("chat-messages");
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>

</html>
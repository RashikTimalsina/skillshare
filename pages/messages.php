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
    SELECT u.user_id, u.user_name,
           (SELECT COUNT(*) 
            FROM messages m 
            WHERE m.sender_id = u.user_id 
              AND m.receiver_id = ? 
              AND m.is_read = 'no') AS unread_count
    FROM users u
    WHERE u.user_id != ? AND u.role != 'admin'
    ORDER BY u.user_name ASC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $conversations[] = $row;
}
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
        SELECT * 
        FROM messages
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY sent_at ASC
    ");
    $stmt->bind_param("iiii", $user_id, $active_receiver_id, $active_receiver_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();

    // Mark as read
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'], $_POST['message_text'])) {
    $receiver_id = intval($_POST['receiver_id']);
    $message_text = trim($_POST['message_text']);

    if ($message_text) {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, message_text, is_read) 
            VALUES (?, ?, ?, 'no')
        ");
        $stmt->bind_param("iis", $user_id, $receiver_id, $message_text);
        $stmt->execute();
        $stmt->close();

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
    <link rel="stylesheet" href="../assets/css/messages.css">
</head>

<body>
    <div class="layout-wrapper">
        <?php include('../includes/layouts/sidebar.php'); ?>
        <div class="main-content-wrapper">
            <?php include('../includes/layouts/topbar.php'); ?>
            <main class="main-content">
                <div class="messages-container">

                    <!-- Conversations List -->
                    <aside class="conversations-list">
                        <h2>Conversations</h2>
                        <ul>
                            <?php foreach ($conversations as $conv): ?>
                                <li class="<?= ($active_receiver_id == $conv['user_id']) ? 'active' : '' ?>">
                                    <a href="messages.php?receiver_id=<?= $conv['user_id'] ?>">
                                        <?= htmlspecialchars($conv['user_name']) ?>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                            <span class="unread-count"><?= $conv['unread_count'] ?></span>
                                        <?php endif; ?>
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
                                        break;
                                    }
                                }
                                ?>
                            </h3>
                        </div>

                        <div class="chat-messages" id="chat-messages">
                            <?php foreach ($messages as $msg): ?>
                                <div class="message <?= ($msg['sender_id'] == $user_id) ? 'sent' : 'received' ?>">
                                    <?= htmlspecialchars($msg['message_text']) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="POST" id="chat-form">
                            <input type="hidden" name="receiver_id" value="<?= $active_receiver_id ?>">
                            <input type="text" name="message_text" placeholder="Type your message..." required>
                            <button type="submit">Send</button>
                        </form>
                    </section>
                </div>
            </main>
            <?php include('../includes/layouts/footer.php'); ?>
        </div>
    </div>

    <script>
        // Scroll to latest message
        const chatMessages = document.getElementById("chat-messages");
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>

</html>
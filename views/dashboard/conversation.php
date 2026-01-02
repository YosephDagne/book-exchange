<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();
$book = new Book();

$currentUserId = $user->getCurrentUserId();
$otherUserId = intval($_GET['other_user_id'] ?? 0);
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : null;

if ($otherUserId <= 0) {
    redirect('/views/dashboard/messages.php');
}

// Ensure other user exists and is active
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT id, full_name, status FROM users WHERE id = ?');
$stmt->execute([$otherUserId]);
$other = $stmt->fetch();
if (!$other) {
    redirect('/views/dashboard/messages.php');
}

// Mark messages from other user as read
$message->markAsRead($otherUserId, $currentUserId);

// Handle posting a reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = sanitize($_POST['message'] ?? '');
    $attachBookId = !empty($_POST['book_id']) ? intval($_POST['book_id']) : null;

    if (!empty($body) || $attachBookId) {
        $res = $message->sendMessage($currentUserId, $otherUserId, $body ?: ' ', $attachBookId);
        if ($res['success']) {
            redirect('/views/dashboard/conversation.php?other_user_id=' . $otherUserId);
        } else {
            $error = $res['message'];
        }
    } else {
        $error = 'Message cannot be empty';
    }
}

$conversation = $message->getConversation($currentUserId, $otherUserId);

// Get user's available books for attachment
$userBooks = array_filter($book->getUserListings($currentUserId), function ($b) {
    return $b['status'] === 'approved' && $b['availability'] === 'available';
});
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chat with <?= htmlspecialchars($other['full_name']) ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* Enhanced Chat UI Styles */
        body {
            background-color: #f8f9fa;
        }

        .chat-wrapper {
            max-width: 900px;
            margin: 20px auto;
            height: calc(100vh - 140px);
            display: flex;
            flex-direction: column;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .chat-header {
            padding: 15px 25px;
            background: #fff;
            border-bottom: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
            background-color: #f0f2f5;
            /* Subtle WhatsApp-like grey */
            display: flex;
            flex-direction: column;
        }

        .message-row {
            display: flex;
            margin-bottom: 15px;
            width: 100%;
        }

        .message-row.sent {
            justify-content: flex-end;
        }

        .message-row.received {
            justify-content: flex-start;
        }

        .bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .sent .bubble {
            background-color: #007bff;
            color: white;
            border-bottom-right-radius: 2px;
        }

        .received .bubble {
            background-color: white;
            color: #333;
            border-bottom-left-radius: 2px;
        }

        .bubble-meta {
            font-size: 0.7rem;
            margin-bottom: 4px;
            display: block;
            opacity: 0.8;
        }

        .book-attachment {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 8px;
            margin-top: 8px;
            border: 1px solid #dee2e6;
            color: #333;
        }

        .sent .book-attachment {
            color: #333;
        }

        .chat-footer {
            padding: 20px;
            background: #fff;
            border-top: 1px solid #edf2f7;
        }

        .msg-input {
            border-radius: 25px;
            padding-left: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .action-btn {
            opacity: 0;
            transition: 0.2s;
            font-size: 0.8rem;
        }

        .bubble:hover .action-btn {
            opacity: 1;
        }

        /* Custom Scrollbar */
        .chat-body::-webkit-scrollbar {
            width: 5px;
        }

        .chat-body::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <div class="chat-wrapper">
            <div class="chat-header">
                <div class="d-flex align-items-center">
                    <a href="<?= site_url('views/dashboard/messages.php') ?>" class="text-dark me-3">
                        <i class="bi bi-arrow-left fs-4"></i>
                    </a>
                    <div>
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($other['full_name']) ?></h6>
                        <small class="text-muted">Direct Message</small>
                    </div>
                </div>

            </div>

            <div class="chat-body" id="chatContainer">
                <?php if (empty($conversation)): ?>
                    <div class="text-center my-auto">
                        <div class="bg-light d-inline-block p-4 rounded-circle mb-3">
                            <i class="bi bi-chat-heart text-primary fs-1"></i>
                        </div>
                        <p class="text-muted">No messages yet. Start your conversation with
                            <?= htmlspecialchars($other['full_name']) ?>.
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($conversation as $msg):
                        $isMe = ($msg['sender_id'] == $currentUserId);
                        ?>
                        <div class="message-row <?= $isMe ? 'sent' : 'received' ?>">
                            <div class="bubble">
                                <span class="bubble-meta">
                                    <?= $isMe ? 'You' : htmlspecialchars($msg['sender_name']) ?> •
                                    <?= timeAgo($msg['created_at']) ?>
                                </span>

                                <div class="message-text">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </div>

                                <?php if (!empty($msg['book_id'])):
                                    $attachedBook = $book->getBookById($msg['book_id']);
                                    if ($attachedBook && $attachedBook['status'] === 'approved'): ?>
                                        <div class="book-attachment shadow-sm">
                                            <div class="d-flex align-items-center">
                                                <img src="<?= site_url($attachedBook['image_path'] ?: 'assets/images/no-book.png') ?>"
                                                    style="width: 45px; height: 60px; object-fit: cover; border-radius: 4px;"
                                                    class="me-2">
                                                <div class="overflow-hidden">
                                                    <div class="fw-bold small text-truncate" style="max-width: 150px;">
                                                        <?= htmlspecialchars($attachedBook['title']) ?>
                                                    </div>
                                                    <div class="text-success small fw-bold">
                                                        <?= number_format($attachedBook['price'], 2) ?> ETB
                                                    </div>
                                                    <a href="<?= site_url('views/public/book_detail.php?id=' . $attachedBook['id']) ?>"
                                                        class="btn btn-sm btn-link p-0 text-primary small"
                                                        style="font-size: 0.7rem;">View Details</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; endif; ?>

                                <?php if ($isMe): ?>
                                    <div class="mt-1 action-btn">
                                        <hr class="my-1 opacity-25">
                                        <a href="<?= site_url('views/dashboard/edit_message.php?message_id=' . intval($msg['id'])) ?>"
                                            class="text-white me-2 text-decoration-none">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="<?= site_url('views/dashboard/delete_message.php') ?>"
                                            class="d-inline">
                                            <input type="hidden" name="message_id" value="<?= intval($msg['id']) ?>">
                                            <input type="hidden" name="other_user_id" value="<?= intval($otherUserId) ?>">
                                            <button type="submit" class="border-0 bg-transparent p-0 text-white"
                                                onclick="return confirm('Delete this message?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-footer">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small mb-2"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" id="replyForm">
                    <div class="row g-2 align-items-end">
                        <div class="col">
                            <div class="input-group">
                                <select name="book_id" class="form-select form-select-sm border-end-0 bg-light"
                                    style="max-width: 130px; border-radius: 20px 0 0 20px;">
                                    <option value="">Attach Book...</option>
                                    <?php foreach ($userBooks as $ub): ?>
                                        <option value="<?= $ub['id'] ?>" <?= ($bookId == $ub['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ub['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <textarea name="message" class="form-control msg-input border-start-0"
                                    placeholder="Type your message..." rows="1" required
                                    style="border-radius: 0 20px 20px 0;"></textarea>
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary rounded-circle shadow-sm"
                                style="width: 48px; height: 48px;">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.scrollTop = chatContainer.scrollHeight;

        // Auto-resize textarea
        const tx = document.getElementsByTagName("textarea");
        for (let i = 0; i < tx.length; i++) {
            tx[i].setAttribute("style", "height:" + (tx[i].scrollHeight) + "px;overflow-y:hidden;");
            tx[i].addEventListener("input", OnInput, false);
        }

        function OnInput() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + "px";
        }
    </script>
</body>

</html>
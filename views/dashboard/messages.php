<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();

$userId = $user->getCurrentUserId();
$conversations = $message->getUserConversations($userId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap");
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --secondary: #ec4899;
            --accent: #8b5cf6;
            --dark: #1e1b4b;
            --light: #f8fafc;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-full: 9999px;
            
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
            
            --shadow-sm: 0 4px 6px -1px rgba(0, 0, 0, 0.08), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --gradient-primary: linear-gradient(135deg, var(--primary), var(--accent));
            --gradient-success: linear-gradient(135deg, var(--success), #059669);
            --gradient-danger: linear-gradient(135deg, var(--danger), #dc2626);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-50);
            color: white;
            min-height: 100vh;
            line-height: 1.6;
            padding-top: 80px;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Messages Container - Responsive */
        .messages-container {
            max-width: 1200px;
            margin: 2rem auto;
            width: 95%;
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            border: 1px solid var(--gray-200);
        }

        /* Header */
        .messages-header {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .messages-header h1 {
            margin: 0;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 1.8rem;
            letter-spacing: -0.5px;
            color: white;
        }

        .header-icon {
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            backdrop-filter: blur(10px);
            color: white;
        }

        /* Buttons */
        .new-message-btn {
            background: white;
            color: var(--primary);
            border: none;
            padding: 10px 25px;
            border-radius: var(--radius-md);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all var(--transition-base);
            text-decoration: none;
            box-shadow: var(--shadow-sm);
        }

        .new-message-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: var(--primary-hover);
        }

        /* Search Box */
        .search-box {
            position: relative;
            margin-bottom: 2rem;
            padding: 0 1rem;
        }

        .search-box i {
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            z-index: 10;
        }

        .search-box input {
            padding-left: 50px;
            border-radius: var(--radius-md);
            border: 2px solid var(--gray-200);
            height: 50px;
            font-size: 1rem;
            transition: all var(--transition-base);
            background: var(--gray-50);
            color: var(--gray-800);
        }

        .search-box input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            background: white;
            color: var(--gray-900);
        }

        .search-box input::placeholder {
            color: var(--gray-500);
        }

        /* Conversations List */
        .conversations-list {
            padding: 0;
            max-height: calc(100vh - 300px);
            overflow-y: auto;
            background: white;
        }

        /* Conversation Cards */
        .conversation-card {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            transition: all var(--transition-base);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            background: white;
        }

        .conversation-card:hover {
            background: var(--gray-50);
            transform: translateX(5px);
        }

        .conversation-card.active {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1), white);
            border-left: 4px solid var(--primary);
        }

        .conversation-card.unread {
            background: rgba(139, 92, 246, 0.05);
        }

        .conversation-card.unread .message-preview {
            font-weight: 600;
            color: var(--gray-900);
        }

        /* User Avatar */
        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: var(--radius-full);
            object-fit: cover;
            border: 3px solid var(--gray-200);
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--gray-500);
            flex-shrink: 0;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: var(--radius-full);
            object-fit: cover;
        }

        /* Conversation Info */
        .conversation-info {
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .book-title {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .book-title i {
            color: var(--gray-500);
        }

        .message-preview {
            font-size: 0.95rem;
            color: var(--gray-700);
            margin-bottom: 8px;
            line-height: 1.4;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Conversation Meta */
        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .message-time {
            font-size: 0.8rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .unread-badge {
            background: var(--gradient-success);
            color: white;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* No Messages State */
        .no-messages {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray-600);
            background: white;
        }

        .no-messages-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
            color: var(--primary);
        }

        .no-messages h3 {
            margin-bottom: 1rem;
            color: var(--gray-800);
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
        }

        .no-messages p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
            color: var(--gray-600);
        }

        .start-chat-btn {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: var(--radius-md);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all var(--transition-base);
            box-shadow: var(--shadow-md);
        }

        .start-chat-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        /* Conversation Actions */
        .conversation-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .action-btn {
            padding: 6px 15px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        /* Online Indicator */
        .online-indicator {
            width: 8px;
            height: 8px;
            background: var(--success);
            border-radius: 50%;
            display: inline-block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Date Separator */
        .date-separator {
            background: var(--gray-50);
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .date-separator small {
            font-weight: 600;
            color: var(--gray-700);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }

        /* Scrollbar Styling */
        .conversations-list::-webkit-scrollbar {
            width: 6px;
        }

        .conversations-list::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: var(--radius-full);
        }

        .conversations-list::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: var(--radius-full);
        }

        .conversations-list::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }

        /* Modal */
        .modal-content {
            border-radius: var(--radius-lg);
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
            color: var(--gray-800);
        }

        .modal-body {
            color: var(--gray-700);
        }

        /* Footer */
        .footer {
            background: var(--gray-50);
            color: var(--gray-700);
            margin-top: 3rem;
        }

        /* ========================= */
        /* RESPONSIVE BREAKPOINTS */
        /* ========================= */

        /* Large Desktops (≥1200px) */
        @media (min-width: 1200px) {
            .messages-container {
                width: 85%;
            }
        }

        /* Tablets (768px - 1199px) */
        @media (max-width: 1199px) {
            .messages-container {
                width: 90%;
                margin: 1.5rem auto;
            }
            
            .messages-header {
                padding: 1.25rem 1.5rem;
            }
            
            .conversation-card {
                padding: 1rem 1.25rem;
            }
        }

        /* Small Tablets (576px - 767px) */
        @media (max-width: 767px) {
            body {
                padding-top: 70px;
                background: var(--gray-50);
            }
            
            .messages-container {
                width: 100%;
                margin: 0;
                border-radius: 0;
                min-height: calc(100vh - 70px);
                box-shadow: none;
                border: none;
            }
            
            .messages-header {
                padding: 1rem;
                position: sticky;
                top: 0;
                z-index: 100;
            }
            
            .messages-header h1 {
                font-size: 1.5rem;
            }
            
            .header-icon {
                width: 40px;
                height: 40px;
                font-size: 1.25rem;
            }
            
            .new-message-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .search-box {
                margin-bottom: 1rem;
                padding: 0 1rem;
            }
            
            .search-box i {
                left: 25px;
            }
            
            .search-box input {
                height: 45px;
            }
            
            .conversations-list {
                max-height: calc(100vh - 250px);
            }
        }

        /* Mobile Phones (≤575px) */
        @media (max-width: 575px) {
            .conversation-card {
                padding: 0.875rem 1rem;
            }
            
            .user-avatar {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }
            
            .user-name {
                font-size: 0.95rem;
            }
            
            .message-preview {
                font-size: 0.875rem;
            }
            
            .conversation-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .conversation-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .action-btn {
                padding: 5px 12px;
                font-size: 0.8rem;
            }
            
            .unread-badge {
                font-size: 0.65rem;
                padding: 3px 8px;
            }
            
            .date-separator {
                padding: 0.625rem 1rem;
            }
            
            .no-messages {
                padding: 3rem 1rem;
            }
            
            .no-messages-icon {
                font-size: 3rem;
            }
            
            .no-messages h3 {
                font-size: 1.25rem;
            }
            
            .no-messages p {
                font-size: 1rem;
            }
        }

        /* Extra Small Phones (≤375px) */
        @media (max-width: 375px) {
            .messages-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }
            
            .header-left {
                width: 100%;
                justify-content: space-between;
            }
            
            .new-message-btn {
                width: 100%;
                justify-content: center;
            }
            
            .conversation-info {
                margin-left: 0.75rem;
            }
            
            .book-title {
                flex-wrap: wrap;
            }
            
            .action-btn span {
                display: none;
            }
            
            .action-btn i {
                margin: 0;
            }
        }

        /* Print Styles */
        @media print {
            .messages-header,
            .search-box,
            .conversation-actions,
            .new-message-btn {
                display: none !important;
            }
            
            .messages-container {
                box-shadow: none;
                border: none;
                margin: 0;
            }
            
            .conversation-card {
                break-inside: avoid;
                border: 1px solid var(--gray-300);
            }
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="messages-container">
        <!-- Messages Header -->
        <div class="messages-header">
            <div class="header-left">
                <div class="header-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <h1>Messages</h1>
            </div>
            <a href="<?= site_url('views/dashboard/compose_message.php') ?>" class="new-message-btn">
                <i class="bi bi-plus-circle"></i> New Message
            </a>
        </div>

        <!-- Search Box -->
        <div class="p-3 p-md-4">
            <div class="search-box">
                <i class="bi bi-search"></i>
                <input type="text" class="form-control" placeholder="Search conversations..." id="searchConversations">
            </div>
        </div>

        <!-- Conversations List -->
        <?php if (empty($conversations)): ?>
            <div class="no-messages">
                <div class="no-messages-icon">
                    <i class="bi bi-chat"></i>
                </div>
                <h3>No messages yet</h3>
                <p>Start a conversation by sending a book request or message to another user.</p>
                <a href="<?= site_url('views/dashboard/compose_message.php') ?>" class="start-chat-btn">
                    <i class="bi bi-chat-left-text"></i> Start Your First Conversation
                </a>
            </div>
        <?php else: ?>
            <div class="conversations-list">
                <?php
                $currentDate = null;
                foreach ($conversations as $conv):
                    $otherId = $conv['other_user_id'];
                    $convUrl = site_url("views/dashboard/conversation.php?other_user_id={$otherId}");
                    $isUnread = isset($conv['unread_count']) && $conv['unread_count'] > 0;

                    // Get conversation date
                    $convDate = date('Y-m-d', strtotime($conv['last_message_time']));

                    // Check if we need to show date separator
                    if ($convDate !== $currentDate):
                        $currentDate = $convDate;
                        $displayDate = date('Y-m-d') === $convDate ? 'Today' :
                            (date('Y-m-d', strtotime('-1 day')) === $convDate ? 'Yesterday' :
                                date('F j, Y', strtotime($convDate)));
                        ?>
                        <div class="date-separator">
                            <small><?= $displayDate ?></small>
                        </div>
                    <?php endif; ?>

                    <div class="conversation-card <?= $isUnread ? 'unread' : '' ?>"
                        onclick="window.location.href='<?= $convUrl ?>'">
                        <div class="d-flex gap-3">
                            <!-- User Avatar -->
                            <div class="user-avatar">
                                <?php if (!empty($conv['profile_pic'])): ?>
                                    <img src="<?= htmlspecialchars($conv['profile_pic']) ?>"
                                        alt="<?= htmlspecialchars($conv['other_user_name']) ?>"
                                        loading="lazy">
                                <?php else: ?>
                                    <i class="bi bi-person-circle"></i>
                                <?php endif; ?>
                            </div>

                            <!-- Conversation Info -->
                            <div class="conversation-info">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div class="user-name">
                                        <?= htmlspecialchars($conv['other_user_name']) ?>
                                        <?php if (rand(0, 1)): // Simulating online status - replace with actual data ?>
                                            <span class="online-indicator" title="Online"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-time">
                                        <i class="bi bi-clock"></i>
                                        <?= timeAgo($conv['last_message_time']) ?>
                                    </div>
                                </div>

                                <?php if ($conv['book_title']): ?>
                                    <div class="book-title">
                                        <i class="bi bi-book"></i>
                                        <span class="text-truncate">Re: <?= htmlspecialchars($conv['book_title']) ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="message-preview">
                                    <?= htmlspecialchars($conv['last_message']) ?>
                                </div>

                                <div class="conversation-meta">
                                    <?php if ($isUnread): ?>
                                        <span class="unread-badge">
                                            <i class="bi bi-envelope-fill"></i> <span>New Message</span>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="bi bi-envelope-check"></i> <span>Read</span>
                                        </span>
                                    <?php endif; ?>

                                    <div class="conversation-actions">
                                        <a href="<?= $convUrl ?>" class="action-btn btn btn-outline-primary btn-sm">
                                            <i class="bi bi-chat-left-text"></i> <span>Reply</span>
                                        </a>
                                        <button type="button" class="action-btn btn btn-outline-danger btn-sm"
                                            onclick="event.stopPropagation(); deleteConversation(<?= $otherId ?>)">
                                            <i class="bi bi-trash"></i> <span>Delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Conversation Modal -->
    <div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trash me-2"></i> Delete Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-chat-square-dots text-danger display-4 mb-3"></i>
                    <h5 class="text-dark">Delete conversation?</h5>
                    <p class="text-muted">This will remove all messages with this user from your inbox. This action
                        cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteConversation">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchConversations')?.addEventListener('input', function (e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const cards = document.querySelectorAll('.conversation-card');
            const dateSeparators = document.querySelectorAll('.date-separator');
            let visibleCards = 0;

            cards.forEach(card => {
                const userName = card.querySelector('.user-name').textContent.toLowerCase();
                const bookTitle = card.querySelector('.book-title')?.textContent.toLowerCase() || '';
                const messagePreview = card.querySelector('.message-preview').textContent.toLowerCase();

                if (userName.includes(searchTerm) || bookTitle.includes(searchTerm) || messagePreview.includes(searchTerm)) {
                    card.style.display = 'flex';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Hide date separators if no cards visible under them
            dateSeparators.forEach(separator => {
                let nextElement = separator.nextElementSibling;
                let hasVisibleCards = false;

                while (nextElement && !nextElement.classList.contains('date-separator')) {
                    if (nextElement.classList.contains('conversation-card') && nextElement.style.display !== 'none') {
                        hasVisibleCards = true;
                        break;
                    }
                    nextElement = nextElement.nextElementSibling;
                }

                separator.style.display = hasVisibleCards ? 'block' : 'none';
            });
        });

        // Delete conversation functionality
        let conversationToDelete = null;

        function deleteConversation(userId) {
            conversationToDelete = userId;
            const modal = new bootstrap.Modal(document.getElementById('deleteConversationModal'));
            modal.show();
        }

        document.getElementById('confirmDeleteConversation')?.addEventListener('click', function () {
            if (conversationToDelete) {
                // Show loading state
                const deleteBtn = this;
                const originalText = deleteBtn.innerHTML;
                deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                deleteBtn.disabled = true;

                // Send AJAX request to delete conversation
                fetch('<?= site_url("views/dashboard/delete_conversation.php") ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `other_user_id=${conversationToDelete}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close modal
                            bootstrap.Modal.getInstance(document.getElementById('deleteConversationModal')).hide();
                            // Remove conversation card with animation
                            const card = document.querySelector(`.conversation-card[onclick*="other_user_id=${conversationToDelete}"]`);
                            if (card) {
                                card.style.opacity = '0';
                                card.style.transform = 'translateX(-100%)';
                                setTimeout(() => {
                                    card.remove();
                                    // Check if no conversations left
                                    if (document.querySelectorAll('.conversation-card').length === 0) {
                                        location.reload();
                                    }
                                }, 300);
                            }
                        } else {
                            alert('Error deleting conversation: ' + (data.message || 'Unknown error'));
                            deleteBtn.innerHTML = originalText;
                            deleteBtn.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting conversation');
                        deleteBtn.innerHTML = originalText;
                        deleteBtn.disabled = false;
                    });
            }
        });

        // Mark conversation as read on hover
        document.querySelectorAll('.conversation-card.unread').forEach(card => {
            card.addEventListener('mouseenter', function () {
                this.classList.remove('unread');
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function (e) {
            if (e.key === '/' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                document.getElementById('searchConversations')?.focus();
            }

            if (e.key === 'n' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                window.location.href = '<?= site_url("views/dashboard/compose_message.php") ?>';
            }
        });

        // Add active state to conversation cards on click
        document.querySelectorAll('.conversation-card').forEach(card => {
            card.addEventListener('click', function () {
                document.querySelectorAll('.conversation-card').forEach(c => {
                    c.classList.remove('active');
                });
                this.classList.add('active');
                
                // Mark as read if unread
                if (this.classList.contains('unread')) {
                    this.classList.remove('unread');
                }
            });
        });

        // Touch support for mobile
        let touchStartX = 0;
        document.querySelectorAll('.conversation-card').forEach(card => {
            card.addEventListener('touchstart', function(e) {
                touchStartX = e.touches[0].clientX;
            });
            
            card.addEventListener('touchend', function(e) {
                const touchEndX = e.changedTouches[0].clientX;
                const diff = touchStartX - touchEndX;
                
                // If swipe left more than 50px, show delete options
                if (diff > 50) {
                    this.style.transform = 'translateX(-100px)';
                } else if (diff < -50) {
                    this.style.transform = 'translateX(0)';
                }
            });
        });

        // Load more conversations on scroll
        let isLoading = false;
        const conversationsList = document.querySelector('.conversations-list');
        
        if (conversationsList) {
            conversationsList.addEventListener('scroll', function() {
                if (this.scrollTop + this.clientHeight >= this.scrollHeight - 100 && !isLoading) {
                    // Load more conversations
                    // Implement infinite scroll logic here
                }
            });
        }
    </script>
</body>

</html>
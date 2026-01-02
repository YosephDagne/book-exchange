<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();
$admin = new Admin();
$bookClass = new Book();

$currentUserId = $user->getCurrentUserId();

// Fetch users and filter out the current user and only include active users
$users = $admin->getAllUsers();
$recipients = array_filter($users, function ($u) use ($currentUserId) {
    return $u['id'] != $currentUserId && isset($u['status']) && $u['status'] === 'active';
});

// Get approved books to optionally attach to message (limit recent 50)
$books = $bookClass->getBooks();

$errors = [];
$success = '';

// Pre-fill when opened via query (reply)
$prefillReceiver = intval($_GET['to'] ?? 0);
$prefillBook = intval($_GET['book_id'] ?? 0);
$prefillSubject = sanitize($_GET['subject'] ?? '');

// Get prefill recipient info if available
$prefillRecipientInfo = null;
if ($prefillReceiver > 0) {
    foreach ($recipients as $r) {
        if ($r['id'] == $prefillReceiver) {
            $prefillRecipientInfo = $r;
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If using the autocomplete field, the selected id is in receiver_id hidden field
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $recipient_label = trim($_POST['recipient_label'] ?? '');
    $book_id = !empty($_POST['book_id']) ? intval($_POST['book_id']) : null;
    $subject = sanitize($_POST['subject'] ?? '');
    $body = sanitize($_POST['message'] ?? '');
    $priority = in_array($_POST['priority'] ?? '', ['low', 'normal', 'high']) ? $_POST['priority'] : 'normal';

    // If receiver_id not provided, try to resolve from the visible label
    if ($receiver_id <= 0 && $recipient_label !== '') {
        $db = Database::getInstance()->getConnection();
        $resolved = false;
        $resolutionMethod = null;

        // try extracting an email inside <...>
        if (preg_match('/<([^>]+)>/', $recipient_label, $m)) {
            $email = trim($m[1]);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $st = $db->prepare('SELECT id, status FROM users WHERE email = ? LIMIT 1');
                $st->execute([$email]);
                $u = $st->fetch();
                if ($u && $u['status'] === 'active') {
                    $receiver_id = intval($u['id']);
                    $resolved = true;
                    $resolutionMethod = 'email_in_brackets';
                }
            }
        }

        // try if the entire input is an email
        if (!$resolved && filter_var($recipient_label, FILTER_VALIDATE_EMAIL)) {
            $st = $db->prepare('SELECT id, status FROM users WHERE email = ? LIMIT 1');
            $st->execute([$recipient_label]);
            $u = $st->fetch();
            if ($u && $u['status'] === 'active') {
                $receiver_id = intval($u['id']);
                $resolved = true;
                $resolutionMethod = 'email_exact';
            }
        }

        // try exact full_name match
        if (!$resolved) {
            $st = $db->prepare('SELECT id, status FROM users WHERE full_name = ? LIMIT 1');
            $st->execute([$recipient_label]);
            $u = $st->fetch();
            if ($u && $u['status'] === 'active') {
                $receiver_id = intval($u['id']);
                $resolved = true;
                $resolutionMethod = 'name_exact';
            }
        }

        // fallback to a LIKE search (first match)
        if (!$resolved) {
            $pattern = '%' . str_replace('%', '\\%', $recipient_label) . '%';
            $st = $db->prepare('SELECT id, status FROM users WHERE status = ? AND (full_name LIKE ? OR email LIKE ?) LIMIT 1');
            $st->execute(['active', $pattern, $pattern]);
            $u = $st->fetch();
            if ($u) {
                $receiver_id = intval($u['id']);
                $resolved = true;
                $resolutionMethod = 'like_fallback';
            }
        }
        
        // If we resolved via server-side logic, set a session flag
        if ($resolved) {
            $_SESSION['recipient_resolved_server'] = [
                'label' => $recipient_label,
                'resolved_id' => $receiver_id,
                'method' => $resolutionMethod
            ];
        }
    }

    // Validation
    if (empty($body)) {
        $errors[] = 'Message cannot be empty';
    }
    
    if (strlen($subject) > 200) {
        $errors[] = 'Subject cannot exceed 200 characters';
    }
    
    if (strlen($body) > 5000) {
        $errors[] = 'Message cannot exceed 5000 characters';
    }

    // Check recipient exists and not current user
    $validRecipient = false;
    foreach ($recipients as $r) {
        if ($r['id'] == $receiver_id) {
            $validRecipient = true;
            break;
        }
    }
    if (!$validRecipient) {
        $errors[] = 'Please select a valid recipient from the suggestions';
    }

    // Check file uploads
    $totalFileSize = 0;
    if (!empty($_FILES['attachments'])) {
        $maxFileSize = 10 * 1024 * 1024; // 10MB per file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'audio/mpeg', 'video/mp4', 'text/plain'];
        
        $files = $_FILES['attachments'];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    if ($files['size'][$i] > $maxFileSize) {
                        $errors[] = 'File "' . $files['name'][$i] . '" exceeds 10MB limit';
                    }
                    if (!in_array($files['type'][$i], $allowedTypes)) {
                        $errors[] = 'File "' . $files['name'][$i] . '" has invalid type. Allowed: images, PDF, audio, video, text';
                    }
                    $totalFileSize += $files['size'][$i];
                }
            }
        } else {
            if ($files['error'] === UPLOAD_ERR_OK) {
                if ($files['size'] > $maxFileSize) {
                    $errors[] = 'File "' . $files['name'] . '" exceeds 10MB limit';
                }
                if (!in_array($files['type'], $allowedTypes)) {
                    $errors[] = 'File "' . $files['name'] . '" has invalid type. Allowed: images, PDF, audio, video, text';
                }
                $totalFileSize += $files['size'];
            }
        }
        
        if ($totalFileSize > 25 * 1024 * 1024) { // 25MB total
            $errors[] = 'Total attachments size exceeds 25MB limit';
        }
    }

    if (empty($errors)) {
        // Handle file uploads
        $attachmentIds = [];
        if (!empty($_FILES['attachments'])) {
            $att = new Attachment();
            $files = $_FILES['attachments'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $fileArray = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        $aid = $att->storeUpload($fileArray, $currentUserId);
                        if ($aid) $attachmentIds[] = $aid;
                    }
                }
            } else {
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $aid = $att->storeUpload($files, $currentUserId);
                    if ($aid) $attachmentIds[] = $aid;
                }
            }
        }

        $res = $message->sendMessage($currentUserId, $receiver_id, $body, $book_id, $subject, $attachmentIds, $priority);
        if ($res['success']) {
            // Redirect to messages list with success parameter
            redirect('/views/dashboard/messages.php?sent=1');
        } else {
            $errors[] = $res['message'];
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compose Message - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --border-color: #e0e0e0;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        
        .message-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .compose-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        
        .compose-card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }
        
        .form-control, .form-select, .select2-selection {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }
        
        .btn-send {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-cancel {
            border-radius: 8px;
            padding: 0.75rem 2rem;
        }
        
        .file-upload-area {
            border: 2px dashed #ccc;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        
        .file-upload-area:hover, .file-upload-area.dragover {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.05);
        }
        
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .file-item {
            background: var(--light-bg);
            border-radius: 6px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }
        
        .file-remove {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
        }
        
        .char-counter {
            font-size: 0.85rem;
            color: #6c757d;
            text-align: right;
            margin-top: 5px;
        }
        
        .char-counter.warning {
            color: #ffc107;
        }
        
        .char-counter.danger {
            color: #dc3545;
        }
        
        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .priority-low {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .priority-high {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .recipient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--success-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .message-container {
                padding: 0 15px;
            }
            
            .btn-group-responsive {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-group-responsive .btn {
                width: 100%;
            }
            
            .card-header h2 {
                font-size: 1.5rem;
            }
        }
        
        .select2-container--default .select2-selection--single {
            height: auto;
            padding: 0.75rem 1rem;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding: 0;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container py-4 message-container">
        <div class="compose-card card mb-4">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                    </div>
                    <div>
                        <h2 class="mb-1">Compose New Message</h2>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $e): ?>
                                <li><?= htmlspecialchars($e) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" id="composeForm" enctype="multipart/form-data">
                    <!-- Recipient Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Recipient <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-control" id="recipient_select" name="receiver_id" required style="width: 100%;">
                                <option value="">Select a recipient...</option>
                                <?php foreach ($recipients as $r): 
                                    $selected = ($prefillReceiver && $prefillReceiver == $r['id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $r['id'] ?>" <?= $selected ?>>
                                        <?= htmlspecialchars($r['full_name']) ?> &lt;<?= htmlspecialchars($r['email']) ?>&gt;
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>                        
                        <!-- Show pre-filled recipient info -->
                        <?php if ($prefillRecipientInfo): ?>
                            <div class="mt-3 p-3 bg-light rounded d-flex align-items-center">
                                <div class="recipient-avatar">
                                    <?= strtoupper(substr($prefillRecipientInfo['full_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($prefillRecipientInfo['full_name']) ?></strong>
                                    <div class="text-muted small"><?= htmlspecialchars($prefillRecipientInfo['email']) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Book Reference -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Regarding Book (Optional)</label>
                        <div class="input-group">
                            <select name="book_id" class="form-select" id="book_select">
                                <option value="">-- Select a book (optional) --</option>
                                <?php foreach ($books as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= ($prefillBook && $prefillBook == $b['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['title']) ?> by <?= htmlspecialchars($b['author']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Message Body -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
                        <div class="position-relative">
                            <textarea name="message" class="form-control" id="messageInput" rows="2" 
                                      placeholder="Type your message here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            <div class="char-counter" id="messageCounter">
                                <span id="messageChars">0</span>/5000 characters
                            </div>
                        </div>
                    </div>
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center btn-group-responsive">
                        <div class="d-flex gap-3">
                            <a href="<?= site_url('views/dashboard/messages.php') ?>" class="btn btn-cancel btn-outline-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                            <button type="submit" id="sendBtn" class="btn btn-send btn-primary">
                                <i class="bi bi-send"></i> Send Message
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 for recipient selection
            $('#recipient_select').select2({
                placeholder: "Select a recipient...",
                allowClear: true,
                templateResult: formatRecipient,
                templateSelection: formatRecipientSelection
            });
            
            // Initialize Select2 for book selection
            $('#book_select').select2({
                placeholder: "Select a book (optional)...",
                allowClear: true
            });
            
            // Character counters
            const subjectInput = $('#subject_input');
            const messageInput = $('#messageInput');
            const subjectCounter = $('#subjectCounter');
            const messageCounter = $('#messageCounter');
            const subjectChars = $('#subjectChars');
            const messageChars = $('#messageChars');
            
            function updateCharCounters() {
                const subjectLength = subjectInput.val().length;
                const messageLength = messageInput.val().length;
                
                subjectChars.text(subjectLength);
                messageChars.text(messageLength);
                
                // Update color based on length
                if (subjectLength > 180) {
                    subjectCounter.removeClass('warning').addClass('danger');
                } else if (subjectLength > 150) {
                    subjectCounter.removeClass('danger').addClass('warning');
                } else {
                    subjectCounter.removeClass('danger warning');
                }
                
                if (messageLength > 4500) {
                    messageCounter.removeClass('warning').addClass('danger');
                } else if (messageLength > 4000) {
                    messageCounter.removeClass('danger').addClass('warning');
                } else {
                    messageCounter.removeClass('danger warning');
                }
            }
            
            subjectInput.on('input', updateCharCounters);
            messageInput.on('input', updateCharCounters);
            updateCharCounters(); // Initial update
            
            // Auto-suggest subject when book is selected
            $('#book_select').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                if (selectedOption.val() && !subjectInput.val()) {
                    const bookTitle = selectedOption.text().split(' by ')[0];
                    subjectInput.val('Inquiry about: ' + bookTitle);
                    updateCharCounters();
                }
            });
            
            // File upload handling
            const fileInput = $('#fileInput');
            const fileDropArea = $('#fileDropArea');
            const filePreview = $('#filePreview');
            
            fileDropArea.on('click', function() {
                fileInput.click();
            });
            
            fileInput.on('change', handleFiles);
            
            // Drag and drop
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileDropArea.on(eventName, preventDefaults);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                fileDropArea.on(eventName, highlight);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                fileDropArea.on(eventName, unhighlight);
            });
            
            function highlight() {
                fileDropArea.addClass('dragover');
            }
            
            function unhighlight() {
                fileDropArea.removeClass('dragover');
            }
            
            fileDropArea.on('drop', function(e) {
                const dt = e.originalEvent.dataTransfer;
                const files = dt.files;
                handleFiles({ target: { files } });
            });
            
            function handleFiles(e) {
                const files = e.target.files;
                filePreview.empty();
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const fileItem = $('<div class="file-item"></div>');
                    
                    // Get file icon based on type
                    let icon = 'bi-file-earmark';
                    if (file.type.startsWith('image/')) icon = 'bi-file-image';
                    else if (file.type === 'application/pdf') icon = 'bi-file-pdf';
                    else if (file.type.startsWith('audio/')) icon = 'bi-file-music';
                    else if (file.type.startsWith('video/')) icon = 'bi-file-play';
                    else if (file.type.startsWith('text/')) icon = 'bi-file-text';
                    
                    const fileSize = formatFileSize(file.size);
                    
                    fileItem.html(`
                        <i class="bi ${icon}"></i>
                        <span class="file-name">${file.name}</span>
                        <span class="text-muted ms-2">(${fileSize})</span>
                        <span class="file-remove ms-2" data-index="${i}">&times;</span>
                    `);
                    
                    filePreview.append(fileItem);
                }
                
                // Add remove functionality
                $('.file-remove').on('click', function() {
                    const index = $(this).data('index');
                    removeFileFromInput(index);
                    $(this).parent().remove();
                });
            }
            
            function removeFileFromInput(index) {
                const dt = new DataTransfer();
                const files = fileInput[0].files;
                
                for (let i = 0; i < files.length; i++) {
                    if (i !== index) {
                        dt.items.add(files[i]);
                    }
                }
                
                fileInput[0].files = dt.files;
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
           
                
                localStorage.setItem('messageDraft', JSON.stringify(draftData));
                
                // Show feedback
                const btn = $(this);
                const originalText = btn.html();
                btn.html('<i class="bi bi-check-circle"></i> Draft Saved');
                btn.removeClass('btn-outline-secondary').addClass('btn-success');
                
                setTimeout(() => {
                    btn.html(originalText);
                    btn.removeClass('btn-success').addClass('btn-outline-secondary');
                }, 2000);
            });
            
            // Form submission handling
            $('#composeForm').on('submit', function(e) {
                const sendBtn = $('#sendBtn');
                const originalText = sendBtn.html();
                
                // Disable button and show loading state
                sendBtn.prop('disabled', true);
                sendBtn.html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending...');
                
                // Clear draft on successful send
                localStorage.removeItem('messageDraft');
                
                // Allow form to submit normally
                return true;
            });
            
            // Format recipient display in Select2
            function formatRecipient(recipient) {
                if (!recipient.id) return recipient.text;
                
                const text = recipient.text;
                const matches = text.match(/^(.*?) <(.*?)>$/);
                
                if (matches) {
                    const name = matches[1];
                    const email = matches[2];
                    const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
                    
                    return $(`
                        <div class="d-flex align-items-center">
                            <div class="recipient-avatar me-3">${initials}</div>
                            <div>
                                <div class="fw-medium">${name}</div>
                                <div class="text-muted small">${email}</div>
                            </div>
                        </div>
                    `);
                }
                
                return recipient.text;
            }
            
            function formatRecipientSelection(recipient) {
                if (!recipient.id) return recipient.text;
                
                const text = recipient.text;
                const matches = text.match(/^(.*?) <(.*?)>$/);
                
                if (matches) {
                    return matches[1]; // Just show the name in the selection
                }
                
                return recipient.text;
            }
        });
    </script>
</body>
</html>
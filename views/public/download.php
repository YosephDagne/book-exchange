<?php
require_once '../../includes/init.php';

/**
 * Secure file download/view endpoint for book attachments
 * Usage:
 *   download.php?book_id=123
 *   download.php?book_id=123&action=view
 */

// ----------------------
// INPUT VALIDATION
// ----------------------
$bookId = intval($_GET['book_id'] ?? 0);
$action = $_GET['action'] ?? 'download'; // view | download

if ($bookId <= 0) {
    http_response_code(400);
    echo 'Invalid book id';
    exit;
}

// ----------------------
// FETCH BOOK
// ----------------------
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare(
    'SELECT id, title, user_id, status, file_path, exchange_type
     FROM books WHERE id = ? LIMIT 1'
);
$stmt->execute([$bookId]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book || $book['status'] !== 'approved') {
    http_response_code(404);
    echo 'Book not found or not available';
    exit;
}

if (empty($book['file_path'])) {
    http_response_code(404);
    echo 'No file attached to this book';
    exit;
}

// ----------------------
// ACCESS CONTROL
// ----------------------
$exchange = $book['exchange_type'] ?? 'donate';
$allowed = false;
$user = new User();

// DONATE → public
if ($exchange === 'donate') {
    $allowed = true;
}

// BUY / BORROW
elseif ($exchange === 'buy' || $exchange === 'borrow') {

    if (!$user->isLoggedIn()) {
        redirect('/views/auth/login.php');
    }

    $currentUserId = $user->getCurrentUserId();
    $isOwner = ($currentUserId == $book['user_id']);
    $isAdmin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');

    if ($isOwner || $isAdmin) {
        $allowed = true;
    } else {
        $request = new Request();
        if ($request->isRequestAccepted($currentUserId, $bookId)) {
            $allowed = true;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        echo '<h3>Access denied</h3>';
        if ($exchange === 'buy') {
            echo '<p>This book is for sale. Complete the payment to access the file.
                  <a href="' . site_url('views/public/book_detail.php?id=' . $bookId) . '">Book Details</a></p>';
        } else {
             echo '<p>This book is available for borrowing.
                  <a href="' . site_url('views/public/book_detail.php?id=' . $bookId) . '">Send Request</a></p>';
        }
        exit;
    }
}

// UNKNOWN TYPE
else {
    http_response_code(403);
    echo '<h3>Access denied</h3>';
    echo '<p>Access to this file is restricted.</p>';
    exit;
}

// ----------------------
// FILE PATH SECURITY
// ----------------------
$relativePath = ltrim($book['file_path'], '/\\');
$projectRoot = realpath(__DIR__ . '/../../');
$filePath = realpath($projectRoot . DIRECTORY_SEPARATOR . $relativePath);

// CHECK: uploads/book_files
$uploadsDir = realpath($projectRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'book_files');

if (!$filePath || !$uploadsDir || strpos($filePath, $uploadsDir) !== 0 || !file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found on server';
    exit;
}

// ----------------------
// LOG DOWNLOAD / VIEW
// ----------------------
try {
    $logUserId = $user->isLoggedIn() ? $user->getCurrentUserId() : null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $logStmt = $db->prepare("
        INSERT INTO downloads (user_id, book_id, action, file_path, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $logStmt->execute([
        $logUserId,
        $bookId,
        ($action === 'view' ? 'view' : 'download'),
        $relativePath,
        $ip,
        $ua
    ]);
} catch (Exception $e) {
    error_log('Download log failed: ' . $e->getMessage());
}

// ----------------------
// SERVE FILE
// ----------------------
$mime = mime_content_type($filePath) ?: 'application/octet-stream';
$filename = basename($filePath);
$filesize = filesize($filePath);

// VIEW PDF INLINE
if ($action === 'view' && strpos($mime, 'pdf') !== false) {
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . $filesize);
    header('Content-Disposition: inline; filename="' . $filename . '"');
    readfile($filePath);
    exit;
}

// FORCE DOWNLOAD
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $filesize);
header('Cache-Control: must-revalidate');
header('Pragma: public');

readfile($filePath);
exit;

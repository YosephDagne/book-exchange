<?php
require_once '../../includes/init.php';

$user = new User();
$book = new Book();
$request = new Request();

$bookId = (int) ($_GET['id'] ?? 0);
$bookData = $book->getBookById($bookId);

// Redirect if book not found or not approved
if (!$bookData || $bookData['status'] !== 'approved') {
    redirect('/index.php');
}

$errors = [];
$success = '';

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user->isLoggedIn()) {

    $message = sanitize($_POST['message'] ?? '');
    $borrowDays = null;
    $paymentProof = null;

    // Borrow validation
    if ($bookData['exchange_type'] === 'borrow') {
        $borrowDays = (int) ($_POST['borrow_days'] ?? 0);
        if ($borrowDays < 1 || $borrowDays > 30) {
            $errors[] = 'Borrow days must be between 1 and 30.';
        }
    }

    // Buy validation
    if ($bookData['exchange_type'] === 'buy') {
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Payment proof is required.';
        } else {
            $uploadDir = __DIR__ . '/../../uploads/transactions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
            $filename = uniqid('proof_') . '.' . $ext;

            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $uploadDir . $filename)) {
                $paymentProof = 'uploads/transactions/' . $filename;
            } else {
                $errors[] = 'Failed to upload payment proof.';
            }
        }
    }

    if (empty($errors)) {
        $result = $request->createRequest(
            $user->getCurrentUserId(),
            $bookId,
            $message,
            $borrowDays,
            $paymentProof
        );

        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($bookData['title']) ?> - UniConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php include '../../includes/navbar.php'; ?>

<div class="container my-5">

    <a href="<?= site_url('index.php') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        ← Back to Books
    </a>

    <div class="row">
        <!-- IMAGE -->
        <div class="col-md-4">
            <?php if (!empty($bookData['image_path'])): ?>
                <img src="<?= site_url($bookData['image_path']) ?>" class="img-fluid rounded shadow">
            <?php else: ?>
                <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded"
                     style="height:400px;">
                    <i class="bi bi-book fs-1"></i>
                </div>
            <?php endif; ?>
        </div>

        <!-- DETAILS -->
        <div class="col-md-8">
            <h2><?= htmlspecialchars($bookData['title']) ?></h2>
            <p class="text-muted">by <?= htmlspecialchars($bookData['author']) ?></p>

            <span class="badge bg-info"><?= $bookData['exchange_type'] === 'buy' ? 'For Sale' : ucfirst($bookData['exchange_type']) ?></span>
            <span class="badge bg-primary"><?= htmlspecialchars($bookData['category'] ?? 'Uncategorized') ?></span>

            <?php if ($bookData['exchange_type'] === 'buy' && !empty($bookData['price'])): ?>
                <h4 class="text-success mt-3">
                    <?= number_format((float) $bookData['price'], 2) ?> ETB
                </h4>
            <?php endif; ?>

            <!-- DESCRIPTION -->
            <?php if (!empty($bookData['description'])): ?>
                <div class="mt-4">
                    <h5>Description</h5>
                    <p class="text-muted">
                        <?= nl2br(htmlspecialchars($bookData['description'])) ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- FILE ACCESS (SECURE DOWNLOAD / VIEW) -->
            <?php 
            $canDownload = false;
            // Owner and Admin always access
            if ($user->isLoggedIn()) {
                if ($user->getCurrentUserId() == $bookData['user_id'] || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')) {
                    $canDownload = true;
                } elseif ($bookData['exchange_type'] === 'donate') {
                     // Donate is open for all logged in users (or even public? defaulting to download.php logic which is public, but let's encourage login)
                     $canDownload = true; 
                } else {
                    // Check if request accepted
                    if ($request->isRequestAccepted($user->getCurrentUserId(), $bookId)) {
                        $canDownload = true;
                    }
                }
            } elseif ($bookData['exchange_type'] === 'donate') {
                 $canDownload = true; 
            }

            if (!empty($bookData['file_path'])): ?>
                <div class="mt-3">
                    <?php if ($canDownload): ?>
                        <div class="d-flex gap-2">
                             <!-- Fixed path to views/public/download.php -->
                            <a href="<?= site_url('views/public/download.php?book_id=' . $bookId) ?>"
                               class="btn btn-success">
                                <i class="bi bi-download"></i> Download Book
                            </a>

                            <a href="<?= site_url('views/public/download.php?book_id=' . $bookId . '&action=view') ?>"
                               class="btn btn-outline-secondary">
                                <i class="bi bi-eye"></i> View Online
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-2">
                            <i class="bi bi-lock"></i> This book has a digital file available. 
                            <?php if ($bookData['exchange_type'] === 'buy'): ?>
                                Complete the purchase to access it.
                            <?php elseif ($bookData['exchange_type'] === 'borrow'): ?>
                                Request to borrow it to get access.
                            <?php else: ?>
                                Login to access it.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <hr>

            <!-- SUCCESS / ERRORS -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $e): ?>
                        <?= htmlspecialchars($e) ?><br>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- REQUEST FORM -->
            <?php if (!$user->isLoggedIn()): ?>

                <div class="alert alert-warning">
                    Please <a href="<?= site_url('views/auth/login.php') ?>">login</a> to send a request.
                </div>

            <?php elseif ($user->getCurrentUserId() === $bookData['user_id']): ?>

                <div class="alert alert-info">
                    You are the owner of this book.
                </div>

            <?php else: ?>

                <div class="alert alert-info">
                    <strong>Important:</strong>
                    <?php if ($bookData['exchange_type'] === 'borrow'): ?>
                        Specify how many days you want to borrow the book.
                    <?php elseif ($bookData['exchange_type'] === 'buy'): ?>
                        Upload your payment proof to complete the request.
                    <?php endif; ?>
                </div>

                <?php if ($bookData['exchange_type'] === 'buy'): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-light">
                            <strong><i class="bi bi-wallet2"></i> Bank Details</strong>
                        </div>
                        <ul class="list-group list-group-flush">
                            <?php 
                            $hasBank = false;
                            for ($i = 1; $i <= 3; $i++): 
                                $num = $bookData["payment_account_{$i}_number"] ?? '';
                                if (!empty($num)): 
                                    $hasBank = true;
                            ?>
                                    <li class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <strong><?= htmlspecialchars($bookData["payment_account_{$i}_type"] ?? 'Bank') ?>:</strong>
                                                <?= htmlspecialchars($num) ?>
                                            </span>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($bookData["payment_account_{$i}_holder"] ?? '') ?></span>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if (!$hasBank): ?>
                                <li class="list-group-item text-danger">No bank details listed. Please contact the seller.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="mt-3">
                    <div class="mb-3">
                        <label class="form-label">Message to Owner</label>
                        <textarea name="message" class="form-control" required></textarea>
                    </div>

                    <?php if ($bookData['exchange_type'] === 'borrow'): ?>
                        <div class="mb-3">
                            <label class="form-label">Borrow Days</label>
                            <input type="number" name="borrow_days" min="1" max="30"
                                   class="form-control" required>
                        </div>
                    <?php elseif ($bookData['exchange_type'] === 'buy'): ?>
                        <div class="mb-3">
                            <label class="form-label">Payment Proof</label>
                            <input type="file" name="payment_proof"
                                   class="form-control" required>
                        </div>
                    <?php endif; ?>

                    <button class="btn btn-primary w-100">
                        <i class="bi bi-send"></i> Send Request
                    </button>
                </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

</body>
</html>

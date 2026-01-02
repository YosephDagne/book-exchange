<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$book = new Book();

$bookId = $_GET['id'] ?? 0;
$bookData = $book->getBookById($bookId);

if (!$bookData || $bookData['user_id'] != $user->getCurrentUserId()) {
    redirect('/views/dashboard/index.php');
}

// Define categories from your upload.php
$categories = [
    'Fiction',
    'Non-Fiction',
    'Academic & Educational',
    'Technology & Computing',
    'Children & Teens',
    'Arts & Lifestyle',
    'Comics & Graphic Novels',
    'Religion & Spirituality',
    'Business & Economics',
    'Health & Wellness',
    'Travel & Geography',
    'History',
    'Politics & Social Studies',
    'Poetry & Drama',
    'Magazines & Periodicals'
];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitize($_POST['title']),
        'author' => sanitize($_POST['author']),
        'category' => sanitize($_POST['category']), // Changed from department to category
        'description' => sanitize($_POST['description']),
        'exchange_type' => $_POST['exchange_type'],
        'price' => $_POST['price'] ?? null,
        'payment_account_1_type' => $_POST['payment_account_1_type'] ?? null,
        'payment_account_1_number' => $_POST['payment_account_1_number'] ?? null,
        'payment_account_1_holder' => $_POST['payment_account_1_holder'] ?? null,
        'payment_account_2_type' => $_POST['payment_account_2_type'] ?? null,
        'payment_account_2_number' => $_POST['payment_account_2_number'] ?? null,
        'payment_account_2_holder' => $_POST['payment_account_2_holder'] ?? null,
        'payment_account_3_type' => $_POST['payment_account_3_type'] ?? null,
        'payment_account_3_number' => $_POST['payment_account_3_number'] ?? null,
        'payment_account_3_holder' => $_POST['payment_account_3_holder'] ?? null
    ];

    // Validate payment fields for 'buy' exchange type
    if ($data['exchange_type'] === 'buy') {
        $hasAtLeastOneAccount = false;
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($data["payment_account_{$i}_type"]) && !empty($data["payment_account_{$i}_number"])) {
                $hasAtLeastOneAccount = true;
                break;
            }
        }
        if (!$hasAtLeastOneAccount) {
            $errors[] = 'At least one payment account is required for books for sale';
        }
    }

    if (empty($errors)) {
        // Handle image upload (optional)
        $imagePath = $bookData['image_path']; // Keep existing image by default
        if (!empty($_FILES['image']) && isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $book->uploadImage($_FILES['image']);
            if (strpos($uploadResult, 'uploads/') === 0) {
                $imagePath = $uploadResult;
                // Delete old image if it exists and is different
                if ($bookData['image_path'] && $bookData['image_path'] !== $imagePath) {
                    $oldImagePath = __DIR__ . '/../../' . $bookData['image_path'];
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }
            } else {
                $errors[] = $uploadResult;
            }
        }

        // Handle book file upload (optional)
        $filePath = $bookData['file_path']; // Keep existing file by default
        if (!empty($_FILES['book_file']) && isset($_FILES['book_file']['error']) && $_FILES['book_file']['error'] === UPLOAD_ERR_OK) {
            $newFilePath = $book->uploadDocument($_FILES['book_file']);
            if ($newFilePath !== false) {
                $filePath = $newFilePath;
                // Delete old file if it exists and is different
                if ($bookData['file_path'] && $bookData['file_path'] !== $filePath) {
                    $oldFilePath = __DIR__ . '/../../' . $bookData['file_path'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
            } else {
                $errors[] = 'Book file upload failed';
            }
        }

        if (empty($errors)) {
            $data['image_path'] = $imagePath;
            $data['file_path'] = $filePath;

            // First, we need to add an updateListing method to Book class
            // For now, we'll use direct update
            $result = $book->updateListing($bookId, $user->getCurrentUserId(), $data);
            if ($result['success']) {
                $success = $result['message'];
                $bookData = $book->getBookById($bookId);
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Book</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                                <?php foreach ($errors as $error): ?>
                                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                                <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Book Title *</label>
                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($bookData['title'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Author *</label>
                                    <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($bookData['author'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                            <option value="<?= htmlspecialchars($category) ?>" <?= ($bookData['category'] ?? '') == $category ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category) ?>
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($bookData['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Exchange Type *</label>
                                    <select name="exchange_type" class="form-select" id="exchangeType" required>
                                        <option value="borrow" <?= ($bookData['exchange_type'] ?? '') == 'borrow' ? 'selected' : '' ?>>Borrow</option>
                                        <option value="buy" <?= ($bookData['exchange_type'] ?? '') == 'buy' ? 'selected' : '' ?>>Buy</option>
                                        <option value="donate" <?= ($bookData['exchange_type'] ?? '') == 'donate' ? 'selected' : '' ?>>Donate</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="priceField" style="display: <?= ($bookData['exchange_type'] ?? '') == 'buy' ? 'block' : 'none' ?>;">
                                    <label class="form-label">Price (ETB)</label>
                                    <input type="number" name="price" class="form-control" value="<?= htmlspecialchars($bookData['price'] ?? '') ?>" step="0.01" min="0">
                                </div>
                            </div>

                            <!-- Payment Account Fields (shown only for 'buy' exchange type) -->
                            <div id="paymentFields" style="display: <?= ($bookData['exchange_type'] ?? '') == 'buy' ? 'block' : 'none' ?>;">
                                <p class="text-muted small">Provide at least one payment account. Buyers can choose which account to use for payment.</p>

                                <!-- Account 1 -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Account 1</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank</label>
                                                <input type="text" name="payment_account_1_type" class="form-control" placeholder="Bank Name" value="<?= htmlspecialchars($bookData['payment_account_1_type'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="payment_account_1_number" class="form-control" placeholder="Enter account number" value="<?= htmlspecialchars($bookData['payment_account_1_number'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="payment_account_1_holder" class="form-control" placeholder="Account holder name" value="<?= htmlspecialchars($bookData['payment_account_1_holder'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account 2 -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Account 2 (Optional)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank</label>
                                                <input type="text" name="payment_account_2_type" class="form-control" placeholder="Bank Name" value="<?= htmlspecialchars($bookData['payment_account_2_type'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="payment_account_2_number" class="form-control" placeholder="Enter account number" value="<?= htmlspecialchars($bookData['payment_account_2_number'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="payment_account_2_holder" class="form-control" placeholder="Account holder name" value="<?= htmlspecialchars($bookData['payment_account_2_holder'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account 3 -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Account 3 (Optional)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank</label>
                                                <input type="text" name="payment_account_3_type" class="form-control" placeholder="Bank Name" value="<?= htmlspecialchars($bookData['payment_account_3_type'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="payment_account_3_number" class="form-control" placeholder="Enter account number" value="<?= htmlspecialchars($bookData['payment_account_3_number'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="payment_account_3_holder" class="form-control" placeholder="Account holder name" value="<?= htmlspecialchars($bookData['payment_account_3_holder'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <small class="text-muted">Leave account holder name blank if same as your profile name</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Book Image (optional)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Max size: 5MB. Supported formats: JPG, PNG, GIF</small>
                                <?php if (!empty($bookData['image_path'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Current image:</small><br>
                                            <img src="../../<?= htmlspecialchars($bookData['image_path']) ?>" alt="Current book image" style="max-width: 200px; max-height: 200px;" class="mt-1">
                                        </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Book File (PDF/DOC/DOCX) (optional)</label>
                                <input type="file" name="book_file" class="form-control" accept=".pdf,.doc,.docx">
                                <small class="text-muted">Max size: 10MB. Supported formats: PDF, DOC, DOCX</small>
                                <?php if (!empty($bookData['file_path'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Current file: <?= basename($bookData['file_path']) ?></small>
                                        </div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Book</button>
                                <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('exchangeType').addEventListener('change', function() {
            const priceField = document.getElementById('priceField');
            const paymentFields = document.getElementById('paymentFields');
            if (this.value === 'buy') {
                priceField.style.display = 'block';
                paymentFields.style.display = 'block';
            } else {
                priceField.style.display = 'none';
                paymentFields.style.display = 'none';
            }
        });
    </script>
</body>
</html>
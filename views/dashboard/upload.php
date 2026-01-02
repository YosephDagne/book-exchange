<?php
// ==========================
// INIT & AUTH
// ==========================
require_once __DIR__ . '/../../includes/init.php';
requireLogin();

$user = new User();
$bookService = new Book();

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
        'user_id' => $user->getCurrentUserId(),
        'title' => sanitize($_POST['title'] ?? ''),
        'author' => sanitize($_POST['author'] ?? ''),
        'category' => sanitize($_POST['category'] ?? ''),
        'description' => sanitize($_POST['description'] ?? ''),
        'exchange_type' => sanitize($_POST['exchange_type'] ?? 'borrow'),
        'price' => sanitize($_POST['price'] ?? null),
        'payment_account_1_type' => sanitize($_POST['payment_account_1_type'] ?? null),
        'payment_account_1_number' => sanitize($_POST['payment_account_1_number'] ?? null),
        'payment_account_1_holder' => sanitize($_POST['payment_account_1_holder'] ?? null),
    ];

$result = $bookService->createListing(
    $data,
    $_FILES['image'] ?? null,
    $_FILES['book_file'] ?? null
);

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $errors[] = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Book | Ethiopian UniConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Main Style -->
    <link href="../../assets/css/style.css" rel="stylesheet">

    <style>
        /* Upload Page Specific Styles */
        .upload-container {
            max-width: 900px;
            margin: 3rem auto 4rem;
            padding: 0 1.5rem;
        }

        .upload-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .form-header h1 {
            font-family: "Outfit", sans-serif;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-section {
            margin-bottom: 2.5rem;
            padding-bottom: 2.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .section-title {
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .exchange-dropdown-wrapper {
            position: relative;
        }

        .exchange-dropdown {
            appearance: none;
            padding: 1rem;
            font-weight: 600;
            border-radius: 12px;
            border: 2px solid rgba(99, 102, 241, .3);
            background: linear-gradient(135deg, rgba(99, 102, 241, .08), rgba(139, 92, 246, .05));
        }

        .dropdown-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .payment-details {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 16px;
            border-left: 4px solid #6366f1;
            margin-top: 1.5rem;
        }

        .file-upload-wrapper {
            border: 2px dashed #d1d5db;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            position: relative;
        }

        .file-upload-wrapper input {
            position: absolute;
            inset: 0;
            opacity: 0;
        }

        .submit-btn {
            width: 100%;
            padding: 1.2rem;
            border-radius: 14px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            border: none;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="upload-container">
        <div class="upload-card">
            <div class="form-header">
                <h1>Upload Your Book</h1>
                <p>Share knowledge with students across Ethiopia</p>
            </div>

            <?php foreach ($errors as $e): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
            <?php endforeach; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- BOOK INFO -->
                <div class="form-section">
                    <div class="section-title"><i class="bi bi-book"></i> Book Information</div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Title *</label>
                            <input name="title" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Author *</label>
                            <input name="author" class="form-control" required>
                        </div>
                    </div>
                    <label class="form-label">Category *</label>
                    <select name="category" class="form-select mb-3" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="4"></textarea>
                </div>

                <!-- EXCHANGE TYPE -->
                <div class="form-section">
                    <div class="section-title"><i class="bi bi-arrow-left-right"></i> Exchange Type</div>
                    <div class="exchange-dropdown-wrapper mb-3">
                        <select name="exchange_type" class="form-select exchange-dropdown"
                            onchange="handleExchange(this.value)">
                            <option value="borrow">📚 Borrow</option>
                            <option value="buy">💰 Sell</option>
                            <option value="donate">🎁 Donate</option>
                        </select>
                        <i class="bi bi-chevron-down dropdown-icon"></i>
                    </div>
                    <div id="priceField" style="display:none;">
                        <label class="form-label">Price (ETB)</label>
                        <input type="number" name="price" class="form-control">
                    </div>
                    <div id="paymentFields" style="display:none;">
                        <div class="payment-details">
                            <label class="form-label">Bank Name</label>
                            <input name="payment_account_1_type" class="form-control mb-2">
                            <label class="form-label">Account Number</label>
                            <input name="payment_account_1_number" class="form-control mb-2">
                            <label class="form-label">Account Holder</label>
                            <input name="payment_account_1_holder" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- FILE -->
                <!-- BOOK COVER & OPTIONAL FILE SECTION -->
                <div class="form-section">
                    <!-- Book Cover -->
                    <div class="section-title"><i class="bi bi-cloud-upload"></i> Book Cover</div>
                    <div class="file-upload-wrapper"
                        style="margin-bottom: 1.5rem; border: 2px dashed #8b5cf6; padding: 2rem; border-radius: 16px; text-align: center; cursor: pointer; background: linear-gradient(135deg, rgba(99,102,241,0.05), rgba(139,92,246,0.05)); transition: all 0.2s;">
                        <p style="font-size: 2rem; color: #6366f1;"><i class="bi bi-image"></i></p>
                        <p style="margin: 0; color: #4b5563;">Click to upload cover image</p>
                        <input type="file" name="image" accept="image/*"
                            style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                    </div>

                    <!-- Optional Book File -->
                    <div class="section-title"><i class="bi bi-file-earmark-text"></i> Optional Book File</div>
                    <div class="file-upload-wrapper"
                        style="border: 2px dashed #6b7280; padding: 1.5rem; border-radius: 16px; text-align: center; cursor: pointer; background: #f9fafb; transition: all 0.2s;">
                        <p style="font-size: 2rem; color: #6b7280;"><i class="bi bi-file-earmark-text"></i></p>
                        <p style="margin: 0; color: #4b5563;">Upload book</p>
                        <input type="file" name="book_file" accept=".pdf,.doc,.docx,.txt"
                            style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
                    </div>
                </div>

                <style>
                    .file-upload-wrapper:hover {
                        background: rgba(99, 102, 241, 0.1);
                        border-color: #6366f1;
                    }
                </style>


                <button class="submit-btn">
                    <i class="bi bi-upload"></i> Publish Book
                </button>
            </form>
        </div>
    </div>

    <script>
        function handleExchange(type) {
            document.getElementById('priceField').style.display =
                document.getElementById('paymentFields').style.display =
                (type === 'buy') ? 'block' : 'none';
        }
        handleExchange('borrow');
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
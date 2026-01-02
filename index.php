<?php
require_once 'includes/init.php';

$user = new User();
$book = new Book();

// Get search filters from query parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'exchange_type' => $_GET['exchange_type'] ?? ''
];

// Get all approved books
$books = $book->getBooks($filters);

// Categories list
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniConnect - University Book Exchange</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <style>
        .card-book {
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card-book:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .badge-exchange {
            text-transform: capitalize;
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 40px;
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="fw-bold">University Book Exchange</h1>
                    <p class="lead">Exchange, borrow, or share books with students across Ethiopia.</p>
                    <?php if (!$user->isLoggedIn()): ?>
                        <a href="views/auth/register.php" class="btn btn-light btn-lg me-2">Get Started</a>
                        <a href="views/auth/login.php" class="btn btn-outline-light btn-lg">Login</a>
                    <?php else: ?>
                        <a href="views/dashboard/upload.php" class="btn btn-light btn-lg">Upload a Book</a>
                    <?php endif; ?>
                </div>
                <div class="col-lg-5 text-center">
                    <i class="bi bi-book display-1"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container my-5">

        <!-- Search & Filters -->
        <div class="form-section">
            <form method="GET" action="index.php" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search books..."
                        value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category) ?>" <?= $filters['category'] === $category ? 'selected' : '' ?>><?= htmlspecialchars($category) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="exchange_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="borrow" <?= $filters['exchange_type'] === 'borrow' ? 'selected' : '' ?>>Borrow
                        </option>
                        <option value="buy" <?= $filters['exchange_type'] === 'buy' ? 'selected' : '' ?>>Buy</option>
                        <option value="donate" <?= $filters['exchange_type'] === 'donate' ? 'selected' : '' ?>>Donate
                        </option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
                </div>
            </form>
        </div>

        <!-- Books Grid -->
        <h2 class="mb-4">Available Books</h2>
        <?php if (empty($books)): ?>
            <div class="alert alert-info"><i class="bi bi-info-circle"></i> No books found.</div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($books as $bookItem): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card card-book h-100 shadow-sm">
                            <?php if (!empty($bookItem['image_path'])): ?>
                                <img src="<?= htmlspecialchars($bookItem['image_path']) ?>" class="card-img-top"
                                    style="height:250px;object-fit:cover;" alt="Book image">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center"
                                    style="height:250px;">
                                    <i class="bi bi-book text-white fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($bookItem['title']) ?></h5>
                                <p class="text-muted small mb-2">by <?= htmlspecialchars($bookItem['author']) ?></p>

                                <?php if ($bookItem['exchange_type'] === 'buy' && !empty($bookItem['price'])): ?>
                                    <p class="fw-bold text-success mb-3"><?= number_format((float) $bookItem['price'], 2) ?> ETB</p>
                                <?php endif; ?>
                                <a href="views/public/book_detail.php?id=<?= (int) $bookItem['id'] ?>"
                                    class="btn btn-primary btn-sm mt-auto w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
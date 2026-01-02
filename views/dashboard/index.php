<?php
require_once __DIR__ . '/../../includes/init.php';
requireLogin();

/* ===============================
   Initialize Services
================================ */
$userService = new User();
$bookService = new Book();
$requestService = new Request();

$userId = $userService->getCurrentUserId();

/* ===============================
   Fetch Data
================================ */
$userListings = $bookService->getUserListings($userId);
$receivedRequests = $requestService->getReceivedRequests($userId);
$sentRequests = $requestService->getSentRequests($userId);

/* ===============================
   Derived Stats
================================ */
$pendingListings = array_filter(
    $userListings,
    fn($book) => $book['status'] === 'pending'
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | UniConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">

    <style>
        body {
            background-color: #f5f5f5;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin: 0;
            font-weight: 600;
            color: #0d6efd;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #6c757d;
        }

        .status-badge {
            font-size: .75rem;
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-available {
            background: #d1ecf1;
            color: #0c5460;
        }

        .request-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .request-item:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container py-4">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Book listing deleted successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Dashboard</h2>
            <a href="upload.php" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Upload Book
            </a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card shadow-sm">
                    <h3><?= count($userListings) ?></h3>
                    <p class="mb-0 text-muted small">My Listings</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card shadow-sm">
                    <h3><?= count($receivedRequests) ?></h3>
                    <p class="mb-0 text-muted small">Received Requests</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card shadow-sm">
                    <h3><?= count($sentRequests) ?></h3>
                    <p class="mb-0 text-muted small">Sent Requests</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card shadow-sm">
                    <h3><?= count($pendingListings) ?></h3>
                    <p class="mb-0 text-muted small">Pending Listings</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white fw-bold">My Book Listings</div>
            <div class="card-body p-0">
                <?php if (!$userListings): ?>
                    <div class="empty-state">
                        <i class="bi bi-book fs-1 mb-2"></i>
                        <p>No books uploaded yet.</p>
                        <a href="upload.php" class="btn btn-primary btn-sm">Upload First Book</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userListings as $book): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($book['title'] ?? '') ?></strong></td>
                                        <td><?= htmlspecialchars($book['author'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($book['category'] ?? '') ?></td>
                                        <td><span class="badge bg-info"><?= ucfirst($book['exchange_type'] ?? '') ?></span></td>
                                        <td>
                                            <?php
                                            $status = $book['status'] ?? 'pending';
                                            $statusClass = "status-$status";
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="edit_book.php?id=<?= $book['id'] ?>"
                                                class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="confirmDelete(<?= $book['id'] ?>, '<?= htmlspecialchars(addslashes($book['title'])) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">Recent Received Requests</div>
                    <div class="card-body">
                        <?php if (!$receivedRequests): ?>
                            <p class="text-muted text-center py-3">No requests received</p>
                        <?php else: ?>
                            <?php foreach (array_slice($receivedRequests, 0, 3) as $req): ?>
                                <div class="request-item">
                                    <div class="fw-bold"><?= htmlspecialchars($req['book_title'] ?? '') ?></div>
                                    <div class="small text-muted">From:
                                        <?= htmlspecialchars($req['requester_name'] ?? 'Unknown') ?></div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="requests.php?tab=received" class="btn btn-sm btn-link text-decoration-none">View
                                    All Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-bold">Recent Sent Requests</div>
                    <div class="card-body">
                        <?php if (!$sentRequests): ?>
                            <p class="text-muted text-center py-3">No sent requests</p>
                        <?php else: ?>
                            <?php foreach (array_slice($sentRequests, 0, 3) as $req): ?>
                                <div class="request-item">
                                    <div class="fw-bold"><?= htmlspecialchars($req['book_title'] ?? '') ?></div>
                                    <div class="small text-muted">To: <?= htmlspecialchars($req['owner_name'] ?? 'Unknown') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <a href="requests.php?tab=sent" class="btn btn-sm btn-link text-decoration-none">View All
                                    Sent</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="bookTitleDisplay"></strong>?
                    <p class="text-danger small mt-2 mb-0"><i class="bi bi-exclamation-triangle"></i> This action cannot
                        be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmDeleteLink" href="#" class="btn btn-danger">Delete Listing</a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(id, title) {
            // Fill the modal data
            document.getElementById('bookTitleDisplay').textContent = title;
            document.getElementById('confirmDeleteLink').href = 'delete_book.php?id=' + id;

            // Show the modal
            var myModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            myModal.show();
        }
    </script>

</body>

</html>
<?php
require_once '../../includes/init.php';
requireLogin();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$user = new User();
$request = new Request();
$db = Database::getInstance()->getConnection();

$userId = $user->getCurrentUserId();
$statusFilter = $_GET['status'] ?? 'all';
$receivedRequests = $request->getReceivedRequests($userId, $statusFilter);
$sentRequests = $request->getSentRequests($userId, $statusFilter);
$receivedCount = $request->getReceivedRequestsCount($userId);
$sentCount = $request->getSentRequestsCount($userId);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $requestId = $_POST['request_id'];
    $status = $_POST['status'];
    $borrowDays = $_POST['borrow_days'] ?? null;
    $request->updateRequestStatus($requestId, $userId, $status, $borrowDays);
    redirect('/views/dashboard/requests.php?t=' . time());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Requests - UniConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">

    <style>
        .status-badge {
            font-weight: 600;
            padding: 0.3rem 0.6rem;
            border-radius: 0.4rem;
        }

        .status-pending {
            background: #facc15;
            color: #000;
        }

        .status-accepted {
            background: #22c55e;
            color: #fff;
        }

        .status-rejected {
            background: #ef4444;
            color: #fff;
        }

        .status-cancelled {
            background: #6b7280;
            color: #fff;
        }

        .status-completed {
            background: #3b82f6;
            color: #fff;
        }

        .card-book img {
            height: 100px;
            object-fit: cover;
        }

        .file-download-btn {
            margin-top: 0.5rem;
        }

        .request-message {
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <h2 class="mb-4">Book Requests</h2>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex gap-2">
                    <label for="status" class="form-label mt-2">Filter by Status:</label>
                    <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Requests</option>
                        <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="accepted" <?= $statusFilter === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                        <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </form>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#received">
                    Received Requests <span class="badge bg-primary"><?= $receivedCount ?></span>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sent">
                    Sent Requests <span class="badge bg-info"><?= $sentCount ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Received Requests -->
            <div class="tab-pane fade show active" id="received">
                <?php if (empty($receivedRequests)): ?>
                    <div class="alert alert-info">No requests received yet.</div>
                <?php else: ?>
                    <?php foreach ($receivedRequests as $req): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <?php if (!empty($req['image_path'])): ?>
                                            <img src="<?= site_url($req['image_path']) ?>" class="img-fluid rounded card-book">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded"
                                                style="height:100px;">
                                                <i class="bi bi-book fs-2"></i>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['file_path'])): ?>
                                            <a href="<?= site_url($req['file_path']) ?>"
                                                class="btn btn-sm btn-outline-primary w-100 file-download-btn" download>
                                                <i class="bi bi-download"></i> Download File
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-9">
                                        <h5><?= htmlspecialchars($req['book_title']) ?></h5>
                                        <p class="mb-1"><strong>From:</strong> <?= htmlspecialchars($req['requester_name']) ?>
                                            (<?= htmlspecialchars($req['requester_email']) ?>)</p>
                                        <?php if ($req['requester_phone']): ?>
                                            <p class="mb-1"><strong>Phone:</strong> <?= htmlspecialchars($req['requester_phone']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="request-message mb-1"><strong>Message:</strong>
                                            <?= nl2br(htmlspecialchars($req['message'])) ?></p>
                                        <?php if (($req['exchange_type'] ?? '') === 'borrow' && !empty($req['requested_borrow_days'])): ?>
                                            <p class="mb-1"><strong>Requested Borrow Period:</strong>
                                                <?= $req['requested_borrow_days'] ?> days</p>
                                        <?php endif; ?>
                                        <p class="text-muted small"><i class="bi bi-clock"></i>
                                            <?= timeAgo($req['created_at']) ?></p>

                                        <span
                                            class="status-badge status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span>

                                        <!-- Actions -->
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <div class="mt-2">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <?php if ($req['exchange_type'] === 'borrow'): ?>
                                                        <input type="number" name="borrow_days" min="1" max="30"
                                                            class="form-control form-control-sm d-inline w-auto" placeholder="Days"
                                                            required>
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                                                </form>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Sent Requests -->
            <div class="tab-pane fade" id="sent">
                <?php if (empty($sentRequests)): ?>
                    <div class="alert alert-info">You haven't sent any requests yet.</div>
                <?php else: ?>
                    <?php foreach ($sentRequests as $req): ?>
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-3">
                                        <?php if (!empty($req['image_path'])): ?>
                                            <img src="<?= site_url($req['image_path']) ?>" class="img-fluid rounded card-book">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded"
                                                style="height:100px;">
                                                <i class="bi bi-book fs-2"></i>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($req['file_path']) && $req['status'] === 'completed'): ?>
                                            <a href="<?= site_url($req['file_path']) ?>"
                                                class="btn btn-sm btn-outline-primary w-100 file-download-btn" download>
                                                <i class="bi bi-download"></i> Download File
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-9">
                                        <h5><?= htmlspecialchars($req['book_title']) ?></h5>
                                        <p class="mb-1"><strong>To:</strong> <?= htmlspecialchars($req['owner_name']) ?>
                                            (<?= htmlspecialchars($req['owner_email']) ?>)</p>
                                        <p class="request-message mb-1"><strong>Your Message:</strong>
                                            <?= nl2br(htmlspecialchars($req['message'])) ?></p>
                                        <p class="text-muted small"><i class="bi bi-clock"></i>
                                            <?= timeAgo($req['created_at']) ?></p>

                                        <span
                                            class="status-badge status-<?= $req['status'] ?>"><?= ucfirst($req['status']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
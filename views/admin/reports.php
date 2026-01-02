<?php
require_once '../../includes/init.php';

requireLogin();
requireAdmin();

$admin = new Admin();
$stats = $admin->getStatistics();

// Safety defaults (prevents warnings)
$stats['by_exchange_type'] = $stats['by_exchange_type'] ?? [];
$stats['top_categories'] = $stats['top_categories'] ?? [];
$stats['total_users'] = $stats['total_users'] ?? 0;
$stats['total_books'] = $stats['total_books'] ?? 0;
$stats['total_requests'] = $stats['total_requests'] ?? 0;
$stats['completed_exchanges'] = $stats['completed_exchanges'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - UniConnect</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">

        <h2 class="mb-4">
            <i class="bi bi-graph-up"></i> System Reports
        </h2>

        <!-- REPORT TABLES -->
        <div class="row g-4 mb-4">

            <!-- Books by Exchange Type -->
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Books by Exchange Type</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['by_exchange_type'])): ?>
                                    <?php foreach ($stats['by_exchange_type'] as $type): ?>
                                        <tr>
                                            <td><?= ucfirst(htmlspecialchars($type['exchange_type'])) ?></td>
                                            <td><strong><?= (int) $type['count'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">
                                            No data available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Book Categories -->
            <div class="col-md-6">
                <div class="card shadow reports-table">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Top Book Categories</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Books</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($stats['top_categories'])): ?>
                                    <?php foreach ($stats['top_categories'] as $cat): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($cat['category']) ?></td>
                                            <td><strong><?= (int) $cat['count'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">
                                            No data available
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- OVERALL STATS -->
        <div class="card shadow mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Overall Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">

                    <div class="col-md-3 mb-3">
                        <div class="stat-card-admin stat-card-users">
                            <h2><?= (int) $stats['total_users'] ?></h2>
                            <p>Total Users</p>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stat-card-admin stat-card-books">
                            <h2><?= (int) $stats['total_books'] ?></h2>
                            <p>Total Books</p>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stat-card-admin stat-card-requests">
                            <h2><?= (int) $stats['total_requests'] ?></h2>
                            <p>Total Requests</p>
                        </div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <div class="stat-card-admin stat-card-exchanges">
                            <h2><?= (int) $stats['completed_exchanges'] ?></h2>
                            <p>Completed Exchanges</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <a href="index.php" class="btn btn-secondary">
            ← Back to Admin Dashboard
        </a>

    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
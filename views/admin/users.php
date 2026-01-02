<?php
require_once '../../includes/init.php';

requireLogin();
requireAdmin();

$admin = new Admin();
$users = $admin->getAllUsers();

// Handle user activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($userId && $action === 'activate') {
        $admin->activateUser($userId);
    } elseif ($userId && $action === 'deactivate') {
        $admin->deactivateUser($userId);
    }

    redirect('/views/admin/users.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - UniConnect</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">

        <h2 class="mb-4">
            <i class="bi bi-people"></i> User Management
        </h2>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">

                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $userItem): ?>
                                    <tr>
                                        <td><?= (int) $userItem['id'] ?></td>

                                        <td><?= htmlspecialchars($userItem['full_name']) ?></td>

                                        <td><?= htmlspecialchars($userItem['email']) ?></td>
                                        <td>
                                            <span class=" <?= $userItem['role'] === 'admin' ? 'danger' : 'primary' ?>">
                                                <?= ucfirst($userItem['role']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span
                                                class="<?= $userItem['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($userItem['status']) ?>
                                            </span>
                                        </td>

                                        <td><?= formatDate($userItem['created_at']) ?></td>

                                        <td>
                                            <?= $userItem['last_login']
                                                ? timeAgo($userItem['last_login'])
                                                : 'Never' ?>
                                        </td>

                                        <td>
                                            <?php if ($userItem['role'] !== 'admin'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= (int) $userItem['id'] ?>">

                                                    <?php if ($userItem['status'] === 'active'): ?>
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <button type="submit" class="btn btn-sm btn-warning">
                                                            Deactivate
                                                        </button>
                                                    <?php else: ?>
                                                        <input type="hidden" name="action" value="activate">
                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            Activate
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">No actions</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No users found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="index.php" class="btn btn-secondary">
                ← Back to Admin Dashboard
            </a>
        </div>

    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
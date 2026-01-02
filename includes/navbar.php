<?php
if (!isset($user)) {
    $user = new User();
}
?>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= site_url('index.php') ?>">
            <i class="bi bi-book-half me-1"></i> Book Exchange
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">

               

                <?php if ($user->isLoggedIn()): ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/index.php') ?>">Dashboard</a>
                    </li>
                     <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('index.php') ?>">Browse Books</a>
                </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/upload.php') ?>">Upload</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/requests.php') ?>">Requests</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/messages.php') ?>">
                            Messages
                            <?php
                            $messageService = new Message();
                            $unread = $messageService->getUnreadCount($user->getCurrentUserId());
                            if ($unread > 0):
                            ?>
                                <span class="badge bg-danger ms-1"><?= $unread ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if ($user->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('views/admin/index.php') ?>">Admin</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center"
                           href="#" role="button" data-bs-toggle="dropdown">
                            <span class="avatar-circle me-2">
                                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= site_url('views/dashboard/profile.php') ?>">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= site_url('views/auth/logout.php') ?>">Logout</a></li>
                        </ul>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/auth/login.php') ?>">Login</a>
                    </li>

                    <li class="nav-item">
                        <a class="btn btn-light ms-lg-2" href="<?= site_url('views/auth/register.php') ?>">
                            Register
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<?php
require_once '../../includes/init.php';

$user = new User();

// Redirect if already logged in
if ($user->isLoggedIn()) {
    redirect('/views/dashboard/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $errors[] = 'Email and password are required';
    } else {
        $result = $user->login($email, $password, $remember);
        if ($result['success']) {
            redirect('/views/dashboard/index.php');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 0;
            background: linear-gradient(135deg, #f6f5ff 0%, #f0f9ff 100%);
        }
        .auth-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .auth-header {
            background: var(--gradient-primary);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .auth-body {
            padding: 2.5rem;
        }
    </style>
</head>

<body>
    <div class="auth-card animated fade-in-up">
        <div class="auth-header">
            <h2 class="fw-bold mb-0"><i class="bi bi-book-half"></i> UniConnect</h2>
            <p class="mb-0 opacity-75">Welcome back!</p>
        </div>
        
        <div class="auth-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fade show">
                    <i class="bi bi-exclamation-circle-fill alert-icon"></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <div><?= htmlspecialchars($error) ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label text-secondary">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="name@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label text-secondary mb-0">Password</label>
                        <a href="forgot_password.php" class="text-decoration-none small">Forgot Password?</a>
                    </div>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary py-2 rounded-pill shadow-sm">
                        Login <i class="bi bi-box-arrow-in-right ms-1"></i>
                    </button>
                </div>

                <div class="text-center">
                    <p class="text-muted">Don't have an account? <a href="register.php" class="fw-bold">Register</a></p>
                    <a href="<?= site_url('index.php') ?>" class="text-muted small text-decoration-none">
                        <i class="bi bi-arrow-left"></i> Back to Home
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
require_once '../../includes/init.php';

$user = new User();

// Redirect if already logged in
if ($user->isLoggedIn()) {
    redirect('/views/dashboard/index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'phone' => sanitize($_POST['phone'] ?? '')
    ];

    /* =======================
       VALIDATION
    ======================= */

    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }

    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    }

    // Password validation
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    } else {
        if (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $data['password'])) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $data['password'])) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $data['password'])) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[\W_]/', $data['password'])) {
            $errors[] = 'Password must contain at least one special character';
        }
    }

    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }

    /* =======================
       REGISTER USER
    ======================= */

    if (empty($errors)) {
        $result = $user->register($data);

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UniConnect</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 0; /* Extra padding for scrolling if needed */
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
            max-width: 500px;
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
            <h2 class="fw-bold mb-0">
                <i class="bi bi-book-half"></i> UniConnect
            </h2>
            <p class="mb-0 opacity-75">Create your account</p>
        </div>

        <div class="auth-body">

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fade show">
                    <i class="bi bi-exclamation-circle-fill alert-icon"></i>
                    <div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success fade show">
                    <i class="bi bi-check-circle-fill alert-icon"></i>
                    <div>
                        <?= htmlspecialchars($success) ?>
                        <div class="mt-2">
                             <a href="login.php" class="btn btn-sm btn-success text-white">Login now</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label text-secondary">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="John Doe"
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary">University Email</label>
                    <input type="email" name="email" class="form-control" placeholder="student@university.edu.et"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary">Phone Number</label>
                    <input type="tel" name="phone" class="form-control"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="0911234567">
                </div>

                <div class="mb-3">
                    <label class="form-label text-secondary">Password</label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="form-text small">
                        Min 8 chars, uppercase, lowercase, number & special char
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-secondary">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>

                <div class="d-grid mb-3">
                    <button type="submit" class="btn btn-primary py-2 rounded-pill shadow-sm">
                        Create Account <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                </div>
            </form>

            <div class="text-center mt-3">
                <p class="text-muted">
                    Already have an account?
                    <a href="login.php" class="fw-bold">Login</a>
                </p>
                <a href="<?= site_url('index.php') ?>" class="text-muted small text-decoration-none">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
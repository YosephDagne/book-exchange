<?php
require_once '../../includes/init.php';
require_once '../../classes/PasswordReset.php';

$errors = [];
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);

    if (empty($email)) {
        $errors[] = 'Please enter your email address.';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $passwordReset = new PasswordReset();
        $result = $passwordReset->requestReset($email);
        if ($result['success']) {
            // Redirect to reset password page with email
            $redirect_url = site_url('views/auth/reset_password.php?email=' . urlencode($email));
            header("Location: " . $redirect_url);
            exit();
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
    <title>Forgot Password - UniConnect</title>
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
            <h2 class="fw-bold mb-0"><i class="bi bi-shield-lock"></i> Recovery</h2>
            <p class="mb-0 opacity-75">Restore access to your account</p>
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

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success fade show">
                    <i class="bi bi-check-circle-fill alert-icon"></i>
                    <div><?= htmlspecialchars($success_message) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label text-secondary">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="name@university.edu.et" required autofocus>
                    <div class="form-text">We'll send a recovery link to this email.</div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary py-2 rounded-pill shadow-sm">
                        Send OTP <i class="bi bi-arrow-right ms-1"></i>
                    </button>
                    <a href="login.php" class="btn btn-light py-2 rounded-pill mt-2">
                        <i class="bi bi-arrow-left me-1"></i> Back to Login
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
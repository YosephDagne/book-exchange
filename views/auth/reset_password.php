<?php
require_once '../../includes/init.php';
require_once '../../classes/PasswordReset.php';

$errors = [];
$success_message = '';

$email = $_GET['email'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($email) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If accessed directly without email, redirect to forgot password
   redirect('forgot_password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $otp = $_POST['otp'] ?? '';

    if (empty($email) || empty($otp) || empty($password) || empty($password_confirm)) {
        $errors[] = 'All fields are required.';
    } else if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    } else if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } else {
        $passwordReset = new PasswordReset();
        $result = $passwordReset->resetPassword($email, $otp, $password);
        
        if ($result['success']) {
            $success_message = $result['message'];
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
    <title>Reset Password - UniConnect</title>
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
            <h2 class="fw-bold mb-0"><i class="bi bi-key"></i> New Password</h2>
            <p class="mb-0 opacity-75">Enter OTP and new password</p>
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
                <!-- Success State -->
                <div class="d-grid mt-4">
                    <a href="login.php" class="btn btn-primary py-2 rounded-pill shadow-sm">
                        Go to Login <i class="bi bi-box-arrow-in-right ms-1"></i>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Reset Form (if not successful) -->
            <?php if (empty($success_message)): ?>
                <form method="POST" action="">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    
                    <div class="mb-3">
                         <label class="form-label text-secondary">Email</label>
                         <input type="text" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">OTP Code</label>
                        <input type="text" name="otp" class="form-control" placeholder="6-digit code from email" required autofocus maxlength="6">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-secondary">New Password</label>
                        <input type="password" name="password" class="form-control" placeholder="At least 6 chars" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-secondary">Confirm Password</label>
                        <input type="password" name="password_confirm" class="form-control" placeholder="Repeat password" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary py-2 rounded-pill shadow-sm">
                            Reset Password <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                         <a href="forgot_password.php" class="text-decoration-none small text-muted">Resend OTP?</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
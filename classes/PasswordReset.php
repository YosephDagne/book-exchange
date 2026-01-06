<?php

class PasswordReset {
    private $db;
    private $user;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->user = new User();
    }

    public function requestReset($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user_data = $stmt->fetch();

        if ($user_data) {
            $user_id = $user_data['id'];
            
            // Generate 6 digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP expires in 15 minutes

            // Delete any existing tokens for this user to keep it clean
            $sql = "DELETE FROM password_resets WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);

            // Store the new OTP
            $sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $otp, $expires]);

            $subject = 'Your Password Reset OTP - UniConnect';
            $body = '<div style="font-family: Arial, sans-serif; padding: 20px;">';
            $body .= '<h2 style="color: #6366f1;">Password Reset Code</h2>';
            $body .= '<p>Hello,</p>';
            $body .= '<p>You requested to reset your password. Use the following OTP code to verify your identity:</p>';
            $body .= '<div style="background: #f3f4f6; padding: 15px; font-size: 24px; font-weight: bold; text-align: center; border-radius: 8px; margin: 20px 0; letter-spacing: 5px; color: #1e1b4b;">' . $otp . '</div>';
            $body .= '<p>This code will expire in 15 minutes.</p>';
            $body .= '<p>If you did not request this, please ignore this email.</p>';
            $body .= '<p>Regards,<br>UniConnect Team</p>';
            $body .= '</div>';

            send_email($email, $subject, $body);

            return ['success' => true, 'message' => 'OTP sent to your email.'];
        }

        // Return success even if email not found to prevent enumeration, but for UX here we might want to be explicit or generic.
        // The previous code returned a generic success message.
        return ['success' => true, 'message' => 'If an account exists, an OTP has been sent.'];
    }

    public function resetPassword($email, $otp, $new_password) {
        // 1. Get user by email
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid Request.'];
        }
        $user_id = $user['id'];

        // 2. Verify OTP
        $sql = "SELECT id, expires_at FROM password_resets WHERE user_id = ? AND token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$user_id, $otp]);
        $reset_data = $stmt->fetch();

        if ($reset_data && strtotime($reset_data['expires_at']) > time()) {
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update user's password
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$hashed_password, $user_id]);

            // Invalidate the token
            $sql = "DELETE FROM password_resets WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$reset_data['id']]);

            return ['success' => true, 'message' => 'Your password has been reset successfully.'];
        }

        return ['success' => false, 'message' => 'Invalid or expired OTP.'];
    }

    // Deprecated/Modified for direct validation if needed
    public function validateToken($token) {
        // Not used in OTP flow usually
        return false;
    }
}

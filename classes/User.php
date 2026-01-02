<?php

class User
{
    private $db;
    private $userId;
    private $userData;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Register new user (NO category)
     */
    public function register($data)
    {
        if (!$this->isUniversityEmail($data['email'])) {
            return ['success' => false, 'message' => 'Please use a valid university email (.edu.et)'];
        }

        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (full_name, email, password, phone, created_at)
                VALUES (?, ?, ?, ?, NOW())";

        $params = [
            $data['full_name'],
            $data['email'],
            $hashedPassword,
            $data['phone']
        ];

        $stmt = $this->db->prepare($sql);

        if ($stmt->execute($params)) {
            return ['success' => true, 'message' => 'Registration successful'];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    /**
     * Login user
     */
    public function login($email, $password, $remember = false)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'inactive') {
                return ['success' => false, 'message' => 'This user is deactivated'];
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $hashedToken = password_hash($token, PASSWORD_BCRYPT);

                $stmt = $this->db->prepare(
                    "UPDATE users SET remember_token = ? WHERE id = ?"
                );
                $stmt->execute([$hashedToken, $user['id']]);

                setcookie('remember_token', $token, time() + COOKIE_LIFETIME, '/');
                setcookie('user_id', $user['id'], time() + COOKIE_LIFETIME, '/');
            }

            $this->db->prepare(
                "UPDATE users SET last_login = NOW() WHERE id = ?"
            )->execute([$user['id']]);

            return ['success' => true, 'message' => 'Login successful'];
        }

        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    public function isLoggedIn()
    {
        if (!empty($_SESSION['logged_in'])) {
            return true;
        }

        if (isset($_COOKIE['remember_token'], $_COOKIE['user_id'])) {
            return $this->loginWithCookie();
        }

        return false;
    }

    private function loginWithCookie()
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE id = ? AND status = 'active'"
        );
        $stmt->execute([$_COOKIE['user_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($_COOKIE['remember_token'], $user['remember_token'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            return true;
        }

        return false;
    }

    public function logout()
    {
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
    }

    private function isUniversityEmail($email)
    {
        return preg_match('/@.*\.edu\.et$/i', $email);
    }

    private function emailExists($email)
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get user by ID (NO category)
     */
    public function getUserById($userId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, full_name, email, phone, profile_image, created_at
             FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Update profile (NO category)
     */
    public function updateProfile($userId, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET full_name = ?, phone = ? WHERE id = ?"
        );

        return $stmt->execute([
            $data['full_name'],
            $data['phone'],
            $userId
        ]);
    }

    public function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    public function getCurrentUser()
    {
        return $this->getCurrentUserId()
            ? $this->getUserById($this->getCurrentUserId())
            : null;
    }

    public function isAdmin()
    {
        return ($_SESSION['user_role'] ?? '') === 'admin';
    }
}

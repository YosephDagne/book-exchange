<?php

class Admin
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* =========================
       BOOK MANAGEMENT
       ========================= */

    public function approveBook($bookId)
    {
        $stmt = $this->db->prepare(
            "UPDATE books SET status = 'approved' WHERE id = ?"
        );

        if ($stmt->execute([$bookId])) {
            $this->logAction('approve_book', $bookId);
            return ['success' => true, 'message' => 'Book approved'];
        }

        return ['success' => false, 'message' => 'Approval failed'];
    }

    public function blockBook($bookId, $reason = '')
    {
        $stmt = $this->db->prepare(
            "UPDATE books SET status = 'blocked' WHERE id = ?"
        );

        if ($stmt->execute([$bookId])) {
            $this->logAction('block_book', $bookId, $reason);
            return ['success' => true, 'message' => 'Book blocked'];
        }

        return ['success' => false, 'message' => 'Block failed'];
    }

    public function deleteBook($bookId)
    {
        // fetch image
        $stmt = $this->db->prepare(
            "SELECT image_path FROM books WHERE id = ?"
        );
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if ($book && !empty($book['image_path'])) {
            $path = __DIR__ . '/../' . $book['image_path'];
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $stmt = $this->db->prepare(
            "DELETE FROM books WHERE id = ?"
        );

        if ($stmt->execute([$bookId])) {
            $this->logAction('delete_book', $bookId);
            return ['success' => true, 'message' => 'Book deleted'];
        }

        return ['success' => false, 'message' => 'Delete failed'];
    }

    /* =========================
       USER MANAGEMENT
       ========================= */

    public function getAllUsers()
    {
        $stmt = $this->db->prepare("
            SELECT id, full_name, email, phone, role, status, created_at, last_login
            FROM users
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deactivateUser($userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = 'inactive' WHERE id = ?"
        );

        if ($stmt->execute([$userId])) {
            $this->logAction('deactivate_user', $userId);
            return ['success' => true, 'message' => 'User deactivated'];
        }

        return ['success' => false, 'message' => 'Deactivation failed'];
    }

    public function activateUser($userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET status = 'active' WHERE id = ?"
        );

        if ($stmt->execute([$userId])) {
            $this->logAction('activate_user', $userId);
            return ['success' => true, 'message' => 'User activated'];
        }

        return ['success' => false, 'message' => 'Activation failed'];
    }

    /* =========================
       DASHBOARD STATISTICS
       ========================= */

    public function getStatistics()
    {
        $stats = [];

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM users WHERE role = 'user'"
        );
        $stmt->execute();
        $stats['total_users'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM books"
        );
        $stmt->execute();
        $stats['total_books'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM books WHERE status = 'pending'"
        );
        $stmt->execute();
        $stats['pending_approvals'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM requests"
        );
        $stmt->execute();
        $stats['total_requests'] = $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM requests WHERE status = 'accepted'"
        );
        $stmt->execute();
        $stats['completed_exchanges'] = $stmt->fetchColumn();

        // books by exchange type
        $stmt = $this->db->prepare("
            SELECT exchange_type, COUNT(*) as count
            FROM books
            WHERE status = 'approved'
            GROUP BY exchange_type
        ");
        $stmt->execute();
        $stats['by_exchange_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // top book categories (correct usage)
        $stmt = $this->db->prepare("
            SELECT category, COUNT(*) as count
            FROM books
            GROUP BY category
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['top_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    }

    /* =========================
       ADMIN LOGS
       ========================= */

    private function logAction($action, $targetId, $details = '')
    {
        $adminId = $_SESSION['user_id'] ?? null;

        $stmt = $this->db->prepare("
            INSERT INTO admin_logs (admin_id, action, target_id, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$adminId, $action, $targetId, $details]);
    }

    public function getAdminLogs($limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.full_name AS admin_name
            FROM admin_logs l
            LEFT JOIN users u ON u.id = l.admin_id
            ORDER BY l.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

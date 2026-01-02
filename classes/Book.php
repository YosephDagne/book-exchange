<?php

class Book
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /* =========================
       ADMIN METHODS
    ========================== */

    public function getPendingBooks()
    {
        $sql = "SELECT b.*, u.full_name AS owner_name
                FROM books b
                LEFT JOIN users u ON b.user_id = u.id
                WHERE b.status = 'pending'
                ORDER BY b.created_at DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function updateBookStatus($bookId, $status)
    {
        $stmt = $this->db->prepare("UPDATE books SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $bookId]);
    }

    public function getAllBooksForAdmin()
    {
        $sql = "SELECT b.*, u.full_name AS owner_name
                FROM books b
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY b.created_at DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function getBookStats()
    {
        $sql = "SELECT
                COUNT(*) total,
                SUM(status='pending') pending,
                SUM(status='approved') approved,
                SUM(status='rejected') rejected
                FROM books";

        return $this->db->query($sql)->fetch();
    }

    /* =========================
       CREATE LISTING (FIXED)
    ========================== */

    public function createListing($data, $imageFile = null, $bookFile = null)
    {
        $imagePath = null;
        $filePath  = null;

        // Upload cover image
        if ($imageFile && $imageFile['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->uploadImage($imageFile);
            if (!$imagePath) {
                return ['success' => false, 'message' => 'Invalid book cover image'];
            }
        }

        // Upload book file (PDF/DOC/TXT)
        if ($bookFile && $bookFile['error'] === UPLOAD_ERR_OK) {
            $filePath = $this->uploadDocument($bookFile);
            if (!$filePath) {
                return ['success' => false, 'message' => 'Invalid book file'];
            }
        }

        $sql = "INSERT INTO books (
            user_id, title, author, category, description,
            exchange_type, price,
            image_path, file_path,
            payment_account_1_type, payment_account_1_number, payment_account_1_holder,
            status, created_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?, 'pending', NOW())";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['user_id'],
            $data['title'],
            $data['author'],
            $data['category'],
            $data['description'],
            $data['exchange_type'],
            $data['price'],
            $imagePath,
            $filePath,
            $data['payment_account_1_type'],
            $data['payment_account_1_number'],
            $data['payment_account_1_holder'],
        ]);

        return ['success' => true, 'message' => 'Book uploaded successfully and awaiting approval'];
    }

    /* =========================
       FILE UPLOAD HELPERS
    ========================== */

    public function uploadImage($file)
    {
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) return false;

        $dir = __DIR__ . '/../uploads/book_covers/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name = uniqid('cover_') . '.' . $ext;
        $path = $dir . $name;

        return move_uploaded_file($file['tmp_name'], $path)
            ? 'uploads/book_covers/' . $name
            : false;
    }

    public function uploadDocument($file)
    {
        $allowedExt = ['pdf', 'doc', 'docx', 'txt'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) return false;
        if ($file['size'] > 10 * 1024 * 1024) return false;

        $dir = __DIR__ . '/../uploads/book_files/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $name = uniqid('book_') . '.' . $ext;
        $path = $dir . $name;

        return move_uploaded_file($file['tmp_name'], $path)
            ? 'uploads/book_files/' . $name
            : false;
    }

    /* =========================
       USER & PUBLIC METHODS
    ========================== */

    public function getUserListings($userId)
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getBookById($bookId)
    {
        $stmt = $this->db->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        return $stmt->fetch();
    }

    public function deleteListing($bookId, $userId)
    {
        $book = $this->getBookById($bookId);
        if (!$book || $book['user_id'] != $userId) return false;

        if ($book['image_path']) @unlink(__DIR__ . '/../' . $book['image_path']);
        if ($book['file_path'])  @unlink(__DIR__ . '/../' . $book['file_path']);

        $stmt = $this->db->prepare("DELETE FROM books WHERE id = ?");
        return $stmt->execute([$bookId]);
    }

    public function updateListing($bookId, $userId, $data)
    {
        // Security check
        $book = $this->getBookById($bookId);
        if (!$book || $book['user_id'] != $userId) {
             return ['success' => false, 'message' => 'Unauthorized'];
        }

        $sql = "UPDATE books SET
                title = ?,
                author = ?,
                category = ?,
                description = ?,
                exchange_type = ?,
                price = ?,
                payment_account_1_type = ?,
                payment_account_1_number = ?,
                payment_account_1_holder = ?,
                payment_account_2_type = ?,
                payment_account_2_number = ?,
                payment_account_2_holder = ?,
                payment_account_3_type = ?,
                payment_account_3_number = ?,
                payment_account_3_holder = ?,
                image_path = ?,
                file_path = ?
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        
        $params = [
            $data['title'],
            $data['author'],
            $data['category'],
            $data['description'],
            $data['exchange_type'],
            $data['price'],
            $data['payment_account_1_type'] ?? null,
            $data['payment_account_1_number'] ?? null,
            $data['payment_account_1_holder'] ?? null,
            $data['payment_account_2_type'] ?? null,
            $data['payment_account_2_number'] ?? null,
            $data['payment_account_2_holder'] ?? null,
            $data['payment_account_3_type'] ?? null,
            $data['payment_account_3_number'] ?? null,
            $data['payment_account_3_holder'] ?? null,
            $data['image_path'],
            $data['file_path'],
            $bookId
        ];

        try {
            if ($stmt->execute($params)) {
                return ['success' => true, 'message' => 'Book updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Database update failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function getBooks($filters = [])
    {
        $sql = "SELECT * FROM books WHERE status='approved'";
        $params = [];

        if (!empty($filters['category'])) {
            $sql .= " AND category=?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR author LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFeaturedBooks($limit = 8)
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM books WHERE status='approved' ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}

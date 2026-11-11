<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'sql106.infinityfree.com');
define('DB_NAME', 'if0_43413132_busbooking');
define('DB_USER', 'if0_43413132');
define('DB_PASS', 'P9A6UyrA6GZQh');

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "خطأ في الاتصال بقاعدة البيانات: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// دالة للحصول على اتصال قاعدة البيانات
function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>


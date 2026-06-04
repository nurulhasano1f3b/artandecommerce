<?php
/**
 * Database connection.
 *
 * Credentials are read from environment variables so they never need to be
 * hardcoded in source code. (H-01)
 *
 * Set these in your Apache VirtualHost or server environment:
 *   SetEnv DB_HOST  localhost
 *   SetEnv DB_NAME  ecommerce_db
 *   SetEnv DB_USER  artapp
 *   SetEnv DB_PASS  your_password
 *
 * The fallback values below are for local development only.
 * Never use root with an empty password in production.
 */
class Database {

    public $conn;

    public function connect() {
        $host    = getenv('DB_HOST') ?: 'localhost';
        $db_name = getenv('DB_NAME') ?: 'ecommerce_db';
        $user    = getenv('DB_USER') ?: 'root';
        $pass    = getenv('DB_PASS') ?: '';

        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
                $user,
                $pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Log the real error server-side; never expose it to the browser. (H-02)
            error_log("Database connection failed: " . $e->getMessage());
            http_response_code(503);
            exit("Service temporarily unavailable. Please try again later.");
        }

        return $this->conn;
    }
}

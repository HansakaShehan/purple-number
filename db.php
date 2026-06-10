<?php
// MySQL database initialization and connection
class Database {
    private static $instance = null;
    private $pdo;
    
    // MySQL Configuration - Set via environment variables or update here
    private $host = 'localhost';
    private $port = '3306';
    private $database = 'purple_game';
    private $username = 'root';
    private $password = '';

    private function __construct() {
        try {
            // Read from environment if available
            $this->host = getenv('DB_HOST') ?: $this->host;
            $this->port = getenv('DB_PORT') ?: $this->port;
            $this->database = getenv('DB_NAME') ?: $this->database;
            $this->username = getenv('DB_USER') ?: $this->username;
            $this->password = getenv('DB_PASS') ?: $this->password;

            // Connect to MySQL server (without database selected)
            $dsn = "mysql:host={$this->host};port={$this->port};charset=utf8mb4";
            $tempPdo = new PDO($dsn, $this->username, $this->password);
            $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if it doesn't exist
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Now connect to the actual database
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->initSchema();
        } catch (PDOException $e) {
            die('Database error: ' . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO() {
        return $this->pdo;
    }

    private function initSchema() {
        // Check if tables exist
        $result = $this->pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = '{$this->database}' AND table_name = 'users' LIMIT 1");
        
        if ($result && $result->fetch()) {
            return; // Tables already exist
        }

        // Create tables
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_admin TINYINT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS admin_config (
                id INT PRIMARY KEY,
                rounds_count INT DEFAULT 20,
                turn_duration_seconds INT DEFAULT 10,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS game_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                room_code VARCHAR(10) UNIQUE NOT NULL,
                player1_id INT NOT NULL,
                player2_id INT NULL,
                total_rounds INT DEFAULT 20,
                start_time TIMESTAMP NULL,
                end_time TIMESTAMP NULL,
                status VARCHAR(50) DEFAULT 'waiting',
                winner_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (player1_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (player2_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL,
                INDEX idx_room_code (room_code),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS guesses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                session_id INT NOT NULL,
                player_id INT NOT NULL,
                secret_number INT NOT NULL,
                guessed_number INT NOT NULL,
                is_correct TINYINT DEFAULT 0,
                turn_order INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
                FOREIGN KEY (player_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_session_player (session_id, player_id),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Insert default admin config
        $this->pdo->exec("INSERT INTO admin_config (id, rounds_count, turn_duration_seconds) VALUES (1, 20, 10) ON DUPLICATE KEY UPDATE id=id");
    }
}

// Get database connection
$db = Database::getInstance()->getPDO();

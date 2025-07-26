<?php
// backend/config/database.php - FIXED VERSION
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $charset = DB_CHARSET;
    private $pdo;
    private static $instance = null;
    
    // Singleton pattern for better connection management
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
                PDO::ATTR_PERSISTENT => false, // Connection pooling için
                PDO::ATTR_TIMEOUT => 30, // Connection timeout
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];
            
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            
            // Additional security settings
            $this->pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            
        } catch(PDOException $e) {
            logActivity('CRITICAL', 'Database connection failed: ' . $e->getMessage());
            throw new Exception("Database connection failed. Please check configuration.");
        }
    }
    
    public function getConnection() {
        // Check if connection is still alive
        try {
            $this->pdo->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconnect if connection is lost
            $this->connect();
        }
        return $this->pdo;
    }
    
    // Prepared statement ile sorgu çalıştırma - Enhanced
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            
            // Validate parameters
            foreach ($params as $key => $value) {
                if (is_string($value) && strlen($value) > 65535) {
                    throw new Exception("Parameter too long: {$key}");
                }
            }
            
            $stmt->execute($params);
            return $stmt;
            
        } catch(PDOException $e) {
            logActivity('ERROR', 'Query failed: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Database query failed");
        }
    }
    
    // Tek satır getirme - Enhanced
    public function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();
        return $result ?: null; // Return null instead of false
    }
    
    // Çoklu satır getirme - Enhanced
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Insert işlemi - Enhanced with validation
    public function insert($table, $data) {
        // Validate table name
        if (!$this->isValidTableName($table)) {
            throw new Exception("Invalid table name");
        }
        
        // Validate data
        if (empty($data) || !is_array($data)) {
            throw new Exception("Invalid data for insert");
        }
        
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        
        // Add created_at if table has this column
        if ($this->hasColumn($table, 'created_at') && !isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
        }
        
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $fields) . "`) VALUES ({$placeholders})";
        
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
    
    // Update işlemi - Enhanced with validation
    public function update($table, $data, $where, $whereParams = []) {
        // Validate table name
        if (!$this->isValidTableName($table)) {
            throw new Exception("Invalid table name");
        }
        
        // Validate data
        if (empty($data) || !is_array($data)) {
            throw new Exception("Invalid data for update");
        }
        
        // Add updated_at if table has this column
        if ($this->hasColumn($table, 'updated_at')) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
        $fields = [];
        foreach($data as $key => $value) {
            $fields[] = "`{$key}` = :{$key}";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE {$where}";
        $params = array_merge($data, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Delete işlemi - Enhanced with validation
    public function delete($table, $where, $params = []) {
        // Validate table name
        if (!$this->isValidTableName($table)) {
            throw new Exception("Invalid table name");
        }
        
        if (empty($where)) {
            throw new Exception("DELETE requires WHERE clause");
        }
        
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Check if table exists and is valid
    private function isValidTableName($table) {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $table)) {
            return false;
        }
        
        // Check if table exists
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Check if column exists in table
    private function hasColumn($table, $column) {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
            $stmt->execute([$column]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Transaction başlatma
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    // Transaction commit
    public function commit() {
        return $this->pdo->commit();
    }
    
    // Transaction rollback
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    // Enhanced HTML escaping
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        if ($data === null) {
            return null;
        }
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    // Enhanced input sanitization
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        if ($input === null) {
            return null;
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove magic quotes if enabled
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        
        return $input;
    }
    
    // CSRF token oluşturma - Enhanced
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    // CSRF token doğrulama - Enhanced
    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Token süresi kontrolü
        if (time() - $_SESSION['csrf_token_time'] > CSRF_TOKEN_EXPIRE) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        // Timing attack koruması
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Health check method
    public function isHealthy() {
        try {
            $stmt = $this->pdo->query('SELECT 1');
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Close connection
    public function close() {
        $this->pdo = null;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
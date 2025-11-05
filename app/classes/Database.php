<?php
/**
 * Database Class
 * Handles all database operations using PDO
 * Provides secure prepared statements and transaction support
 */

require_once __DIR__ . '/../../config.php';

class Database {
    private $host;
    private $user;
    private $pass;
    private $dbname;
    private $conn;
    private $error;
    private $stmt;

    public function __construct() {
        $this->host = DB_HOST;
        $this->user = DB_USER;
        $this->pass = DB_PASS;
        $this->dbname = DB_NAME;

        $this->connect();
    }

    /**
     * Create PDO connection
     */
    private function connect() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';

        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        );

        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            die('Database Connection Error: ' . $this->error);
        }
    }

    /**
     * Prepare statement with query
     *
     * @param string $query SQL query string
     */
    public function query($query) {
        $this->stmt = $this->conn->prepare($query);
    }

    /**
     * Bind values to prepared statement
     *
     * @param string $param Parameter placeholder
     * @param mixed $value Value to bind
     * @param int $type PDO data type
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Execute the prepared statement
     *
     * @return bool
     */
    public function execute() {
        return $this->stmt->execute();
    }

    /**
     * Get result set as array of objects
     *
     * @return array
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Get single record as object
     *
     * @return object
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * Get row count
     *
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Get last inserted ID
     *
     * @return string
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function endTransaction() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function cancelTransaction() {
        return $this->conn->rollBack();
    }

    /**
     * Get last error information
     *
     * @return array
     */
    public function getLastError() {
        return $this->conn->errorInfo();
    }

    /**
     * Get PDO connection object
     *
     * @return PDO
     */
    public function getConnection() {
        return $this->conn;
    }
}
?>

<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'vintage_dial');
define('DB_USER', 'root');
define('DB_PASS', '');

define('DB_FETCH_ASSOC', 2);
define('DB_FETCH_COLUMN', 7);

if (!class_exists('DatabaseException')) {
    class DatabaseException extends Exception {}
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class MySQLiStatement
{
    private $stmt = null;
    private $result = null;
    private $conn;

    public function __construct($conn, $query, $result = null)
    {
        $this->conn = $conn;

        if ($result !== null) {
            $this->result = $result;
            return;
        }

        $this->stmt = $this->conn->prepare($query);
        if ($this->stmt === false) {
            throw new DatabaseException($this->conn->error);
        }
    }

    public function execute(array $params = []): bool
    {
        if (!empty($params) && $this->stmt !== null) {
            $typeString = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $typeString .= 'i';
                } elseif (is_double($param) || is_float($param)) {
                    $typeString .= 'd';
                } else {
                    $typeString .= 's';
                }
            }

            $bindParams = [];
            $bindParams[] = $typeString;
            foreach ($params as $index => $value) {
                $bindParams[] = &$params[$index];
            }

            if (!call_user_func_array([$this->stmt, 'bind_param'], $bindParams)) {
                throw new DatabaseException($this->stmt->error);
            }
        }

        if ($this->stmt === null) {
            throw new DatabaseException('Invalid prepared statement.');
        }

        $success = $this->stmt->execute();
        if ($success === false) {
            throw new DatabaseException($this->stmt->error);
        }

        $result = $this->stmt->get_result();
        $this->result = ($result !== false) ? $result : null;

        return true;
    }

    public function fetch(int $mode = DB_FETCH_ASSOC)
    {
        $this->ensureResult();
        if ($this->result === null) {
            return false;
        }

        if ($mode === DB_FETCH_COLUMN) {
            return $this->fetchColumn();
        }

        return $this->result->fetch_assoc();
    }

    public function fetchAll(int $mode = DB_FETCH_ASSOC): array
    {
        $this->ensureResult();
        if ($this->result === null) {
            return [];
        }

        if ($mode === DB_FETCH_COLUMN) {
            $rows = [];
            while ($row = $this->result->fetch_row()) {
                $rows[] = $row[0] ?? null;
            }
            return $rows;
        }

        if ($mode === DB_FETCH_ASSOC) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }

        return $this->result->fetch_all();
    }

    public function fetchColumn(int $column = 0)
    {
        $this->ensureResult();
        if ($this->result === null) {
            return false;
        }

        $row = $this->result->fetch_row();
        return $row[$column] ?? false;
    }

    public function closeCursor(): bool
    {
        if ($this->result !== null) {
            $this->result->free();
            $this->result = null;
        }
        return true;
    }

    private function ensureResult(): void
    {
        if ($this->result === null && $this->stmt !== null) {
            $result = $this->stmt->get_result();
            $this->result = ($result !== false) ? $result : null;
        }
    }
}

class MySQLiDatabase
{
    private $conn;

    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_errno) {
            throw new DatabaseException('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function prepare(string $query): MySQLiStatement
    {
        return new MySQLiStatement($this->conn, $query);
    }

    public function query(string $query): MySQLiStatement
    {
        $result = $this->conn->query($query);
        if ($result === false) {
            throw new DatabaseException($this->conn->error);
        }

        if ($result instanceof mysqli_result) {
            return new MySQLiStatement($this->conn, $query, $result);
        }

        $statement = new MySQLiStatement($this->conn, $query);
        $statement->execute();
        return $statement;
    }

    public function exec(string $query): int
    {
        $result = $this->conn->query($query);
        if ($result === false) {
            throw new DatabaseException($this->conn->error);
        }

        return $this->conn->affected_rows;
    }

    public function beginTransaction(): bool
    {
        return $this->conn->begin_transaction();
    }

    public function commit(): bool
    {
        return $this->conn->commit();
    }

    public function rollBack(): bool
    {
        return $this->conn->rollback();
    }

    public function lastInsertId()
    {
        return $this->conn->insert_id;
    }

}

try {
    $db = new MySQLiDatabase();
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
<?php

namespace Database;

use PDO;
use PDOStatement;

class MySQLWrapper {
    private PDO $pdo;

    public function __construct() {
        //SQLホストの作成
        $dsn = sprintf(
            "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
            $_ENV["DB_HOST"],
            $_ENV["DB_PORT"],
            $_ENV["DB_NAME"]
        );

        $this->pdo = new PDO(
            $dsn,
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    public function execute (string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);

      foreach (array_values($params) as $index => $value) {
            $type = match (true) {
                is_int($value) => PDO::PARAM_INT,
                is_null($value) => PDO::PARAM_NULL,
                is_bool($value) => PDO::PARAM_BOOL,
                default => PDO::PARAM_STR,
            };

            $stmt->bindValue($index + 1, $value, $type);
      }

      $stmt->execute();

      return $stmt;
    }

    public function lastInsertId(): int {
        return (int) $this->pdo->lastInsertId();
    }
}
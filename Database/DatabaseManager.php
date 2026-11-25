<?php

namespace Database;

class DatabaseManager {
    private MySQLWrapper $db;

    public function __construct(MySQLWrapper $db) {
        $this->db = $db;
    }

    public function createMigrationsTable(): void {
        $sql = " CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_name VARCHAR(255) NOT NULL UNIQUE,
                executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        $this->db->execute($sql);
    }

    public function runMigrations(): void {
        // マイグレーション管理テーブルを準備
        $this->createMigrationsTable();

        // 実行済みのファイル名を取得
        $stmt = $this->db->execute (
          "SELECT migration_name FROM migrations"
        );

        $executed = $stmt->fetchAll (\PDO::FETCH_COLUMN);

        // マイグレーションファイルを取得
        $files = glob(__DIR__ . '/Migrations/*.php');

        if ($files === false) {
            throw new \RuntimeException (
                'マイグレーションファイルの取得に失敗しました。'
            );
        }

        sort($files);

        foreach ($files as $file) {
            $migrationName = basename($file);

            // 実行済みならスキップ
            if (in_array($migrationName, $executed, true)) {
                echo "スキップ: {$migrationName}\n";
                continue;
            }

            // マイグレーションを読み込む
            $migration = require $file;

            // SQLを実行
            $migration($this->db);

            // 実行履歴を保存
            $this->db->execute(
                "INSERT INTO migrations (migration_name) VALUES (?)",
                [$migrationName]
            );

            echo "実行完了: {$migrationName}\n";
        }
    }
}
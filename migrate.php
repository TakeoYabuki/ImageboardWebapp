<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Database/MySQLWrapper.php';
require_once __DIR__ . '/Database/DatabaseManager.php';

use Database\MySQLWrapper;
use Database\DatabaseManager;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $db = new MySQLWrapper();

    $manager = new DatabaseManager($db);

    $manager->runMigrations();

    echo "マイグレーション管理テーブルを作成しました。\n";

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo "データベース処理に失敗しました。\n";
    exit(1);
}
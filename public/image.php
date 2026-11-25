<?php

// GET以外は許可しない
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit;
}

// クエリパラメータを取得
$fileName = $_GET['file'] ?? '';
$type = $_GET['type'] ?? 'thumbnail';

// ファイル名を検証
if ( !is_string($fileName) || !preg_match('/\A(?:[a-f0-9]{32}|[a-f0-9]{64})\.(jpg|png|gif)\z/', $fileName)) {
    http_response_code(400);
    exit('ファイル名が正しくありません。');
}

// 画像の種類に応じてディレクトリを選択
$directory = match ($type) {
    'original' => 'uploads',
    'thumbnail' => 'thumbnails',
    default => null,
};

if ($directory === null) {
    http_response_code(400);
    exit('画像の種類が正しくありません。');
}

// ファイルパスを作成
$filePath = dirname(__DIR__) . '/storage/' . $directory . '/' . $fileName;

// ファイルの存在確認
if (!is_file($filePath) || !is_readable($filePath)) {
    http_response_code(404);
    exit('画像が見つかりません。');
}

// MIMEタイプを取得
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);

$allowedTypes = ['image/jpeg', 'image/png','image/gif'];

if (!in_array($mimeType, $allowedTypes, true)) {
    http_response_code(415);
    exit('対応していない画像形式です。');
}

// 画像をブラウザに返す
header('Content-Type: ' . $mimeType);
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;

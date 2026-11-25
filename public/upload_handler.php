<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/post.php';

use Database\MySQLWrapper;
use Database\DataAccess\MySQLPostDAO;
use Models\Post;
use Services\ImageUploadService;

// POST以外は許可しない
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('POSTメソッドのみ許可されています。');
}

// 件名と本文の取得
$subject = $_POST['subject'] ?? '';
$content = $_POST['content'] ?? '';

// 入力値の検証
if (!is_string($subject) || !is_string($content) || trim($content) === '') {
    http_response_code(400);
    exit('投稿内容が正しくありません。');
}

$subject = trim($subject);
$content = trim($content);

if (mb_strlen($content, 'UTF-8') > MAX_CONTENT_LENGTH) {
    http_response_code(400);
    exit('本文は5000文字以内にしてください。');
}

// 件名の文字数制限
if (mb_strlen($subject) > 255) {
    http_response_code(400);
    exit('件名は255文字以内にしてください。');
}

// 件名が空の場合はNULLにする
if ($subject === '') {
    $subject = null;
}

// 画像データの構造を検証
if (isset($_FILES['image']) && !is_int($_FILES['image']['error'] ?? null)) {
    http_response_code(400);
    exit('画像データが正しくありません。');
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    exit('画像を選択してください。');
}

// 画像なしを初期状態とする
$fileName = null;

try {
    // 環境変数の読み込み
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();

    // DB接続
    $db = new MySQLWrapper();
    $dao = new MySQLPostDAO($db);

    // 画像を保存する
    $imageService = new ImageUploadService();
    $fileName = $imageService->upload($_FILES['image']);

    // 親投稿用のPostオブジェクトを生成
    $post = new Post(
        null,
        null,
        $subject,
        $content,
        null,
        null,
        $fileName
    );

    // DBに登録
    if (!$dao->create($post)) {
        throw new RuntimeException('投稿の保存に失敗しました。');
    }
    //IDをNULLから変更
    if ($fileName !== null) {
    $savedPost = $dao->getById($post->getId());

    if ($savedPost === null) {
        throw new RuntimeException('投稿を取得できません。');
    }
    //ファイルネームをハッシュ化
    $fileName = $imageService->renameImage($fileName, $savedPost->getId(),$savedPost->getCreatedAt());

    $savedPost->setImageFilename($fileName);

    if (!$dao->update($savedPost)) {
        throw new RuntimeException('画像情報の更新に失敗しました。');
    }
    //サムネイル作成
    $imageService->createThumbnail($fileName);
}

} catch (Throwable $e) {

    // 保存済みの画像を削除する
    if ($fileName !== null) {
        $uploadPath = dirname(__DIR__) . '/storage/uploads/' . $fileName;

        $thumbnailPath = dirname(__DIR__) . '/storage/thumbnails/' . $fileName;

        if (is_file($uploadPath)) {
            unlink($uploadPath);
        }

        if (is_file($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }
    //保存済みのDBのレコードを消去
    if (isset($post) && $post->getId() !== null) {
        try {
            $dao->delete($post->getId());
        } catch (Throwable $cleanupError) {
            error_log($cleanupError->getMessage());
        }
    }
    //例外の種類に応じたエラー処理
    if ($e instanceof InvalidArgumentException) {
        http_response_code(400);
        exit(htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        ));
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit('投稿処理中にエラーが発生しました。');

        error_log($e->getMessage());

        http_response_code(500);
        exit('投稿処理中にエラーが発生しました。');
}

// 投稿完了後、投稿一覧に戻る
header('Location: index.php', true, 303);
exit;

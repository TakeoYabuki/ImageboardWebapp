
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/pagination.php';
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

// 親投稿IDの取得と検証
$replyToId = filter_input(INPUT_POST, 'reply_to_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($replyToId === false || $replyToId === null) {
    http_response_code(400);
    exit('親投稿IDが正しくありません。');
}

// 本文の取得と検証
$content = $_POST['content'] ?? '';

if (!is_string($content) || trim($content) === '') {
    http_response_code(400);
    exit('本文を入力してください。');
}

$content = trim($content);

if (mb_strlen($content, 'UTF-8') > MAX_CONTENT_LENGTH) {
    http_response_code(400);
    exit('本文は5000文字以内にしてください。');
}
// 画像データの構造を検証
if (isset($_FILES['image']) && !is_int($_FILES['image']['error'] ?? null)) {
    http_response_code(400);
    exit('画像データが正しくありません。');
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

    // 親投稿の存在確認
    $thread = $dao->getById($replyToId);

    if ($thread === null || $thread->getReplyToId() !== null) {
        http_response_code(404);
        exit('返信先のスレッドが見つかりません。');
    }

    // 画像が選択されている場合のみ保存する
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $imageService = new ImageUploadService();

        $fileName = $imageService->upload($_FILES['image']);

    }

    // 返信用のPostオブジェクトを生成
    $reply = new Post(
        null,
        $replyToId,
        null,
        $content,
        null,
        null,
        $fileName
    );

    // DBに登録
    if (!$dao->create($reply)) {
        throw new RuntimeException('返信の保存に失敗しました。');
    }

    if ($fileName !== null) {
      $savedPost = $dao->getById($reply->getId());

      if ($savedPost === null) {
          throw new RuntimeException('投稿を取得できません。');
      }

      $fileName = $imageService->renameImage($fileName, $savedPost->getId(), $savedPost->getCreatedAt());

      $savedPost->setImageFilename($fileName);

      if (!$dao->update($savedPost)) {
          throw new RuntimeException('画像情報の更新に失敗しました。');
      }

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
    if (isset($reply) && $reply->getId() !== null) {
        try {
            $dao->delete($reply->getId());
        } catch (Throwable $cleanupError) {
            error_log($cleanupError->getMessage());
        }
    }
    //例外の種類に応じたエラー処理
    if ($e instanceof InvalidArgumentException) {
        http_response_code(400);
        exit(htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }

    error_log($e->getMessage());

    http_response_code(500);
    exit('返信処理中にエラーが発生しました。');
}

// 返信投稿後の最終ページを取得
try {
    $totalReplies = $dao->countReplies($thread);

    $lastPage = max(1, (int) ceil($totalReplies / REPLIES_PER_PAGE));
} catch (Throwable $e) {
    error_log($e->getMessage());

    $lastPage = 1;
}

// 最終ページへリダイレクト
header('Location: thread.php?id=' . $replyToId . '&page=' . $lastPage, true, 303);

exit;
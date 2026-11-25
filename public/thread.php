<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/pagination.php';

use Database\MySQLWrapper;
use Database\DataAccess\MySQLPostDAO;

// スレッドIDの取得と検証
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false || $id === null) {
    http_response_code(400);
    exit('スレッドIDが正しくありません。');
}

// 環境変数の読み込み
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// DAOの生成
$db = new MySQLWrapper();
$dao = new MySQLPostDAO($db);

// 親投稿の取得
$thread = $dao->getById($id);

// スレッドの存在確認
if ($thread === null || $thread->getReplyToId() !== null) {
    http_response_code(404);
    exit('スレッドが見つかりません。');
}


// 1ページあたりの返信表示件数
$limit = REPLIES_PER_PAGE;

// ページ番号の取得と検証
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

// ページ番号が指定されていない場合
if ($page === null) {
    $page = 1;
}

// 不正なページ番号の場合
if ($page === false) {
    http_response_code(400);
    exit('ページ番号が正しくありません。');
}

// 返信の総件数を取得
$total = $dao->countReplies($thread);

// 総ページ数を計算
$totalPages = max(1, (int) ceil($total / $limit));

// 存在しないページの場合
if ($page > $totalPages) {
    http_response_code(404);
    exit('指定されたページが存在しません。');
}

// OFFSETを計算
$offset = ($page - 1) * $limit;

// 指定されたページの返信を取得
$replies = $dao->getReplies($thread, $offset, $limit);
?>

<!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>スレッド詳細</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
        <script src="js/main.js" defer></script>
    </head>
    
    <body>
        <h1>スレッド詳細</h1>

        <p>
            <a href="index.php">投稿一覧に戻る</a>
        </p>

        <!-- 親投稿 -->
        <article>
            <h2>
                <?= htmlspecialchars($thread->getSubject() ?? '無題', ENT_QUOTES, 'UTF-8') ?>
            </h2>

            <p>
                投稿番号：<?= $thread->getId() ?>
            </p>

            <p>
                <?= nl2br(htmlspecialchars($thread->getContent(), ENT_QUOTES, 'UTF-8')) ?>
            </p>

            <p>
                投稿日時：
                <?= htmlspecialchars($thread->getCreatedAt() ?? '', ENT_QUOTES, 'UTF-8') ?>
            </p>

            <?php $imageFilename = $thread->getImageFilename(); ?>

            <?php if ($imageFilename !== null): ?>
                <a href="image.php?type=original&amp;file=<?= rawurlencode($imageFilename) ?>" target="_blank" rel="noopener">
                    <img src="image.php?type=thumbnail&amp;file=<?= rawurlencode($imageFilename) ?>" alt="投稿画像" loading="lazy">
                </a>
            <?php endif; ?>
        </article>

        <hr>
        
        <div id="replies">
            <!-- 返信一覧 -->
            <h2>返信一覧</h2>
            <p>全<?= $total ?>件の返信 / <?= $page ?>ページ目 （全<?= $totalPages ?>ページ）</p>

            <?php if (empty($replies)): ?>
                <p>まだ返信はありません。</p>
            <?php endif; ?>

            <?php foreach ($replies as $reply): ?>
                <article>
                    <p>
                        投稿番号：<?= $reply->getId() ?>
                    </p>

                    <p>
                        <?= nl2br(htmlspecialchars($reply->getContent(), ENT_QUOTES, 'UTF-8')) ?>
                    </p>

                    <p>
                        投稿日時：
                        <?= htmlspecialchars($reply->getCreatedAt() ?? '', ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <?php $imageFilename = $reply->getImageFilename(); ?>

                    <?php if ($imageFilename !== null): ?>
                        <a href="image.php?type=original&amp;file=<?= rawurlencode($imageFilename) ?>" target="_blank" rel="noopener">
                            <img src="image.php?type=thumbnail&amp;file=<?= rawurlencode($imageFilename) ?>" alt="返信画像" loading="lazy">
                        </a>
                    <?php endif; ?>

                    <hr> </article> <?php endforeach; ?> 
                    <nav aria-label="返信ページ切り替え">
                    <?php if ($page > 1): ?>
                        <a href="thread.php?id=<?= $id ?>&amp;page=<?= $page - 1 ?>">前へ</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === $page): ?>
                            <strong><?= $i ?></strong>
                        <?php else: ?>
                            <a href="thread.php?id=<?= $id ?>&amp;page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="thread.php?id=<?= $id ?>&amp;page=<?= $page + 1 ?>">次へ</a>
                    <?php endif; ?>
                </nav>
        </div>  
                    <hr>

            <h2>返信する</h2>
        

        <form id="reply-form" action="reply_handler.php" method="POST" enctype="multipart/form-data">

            <input type="hidden" name="reply_to_id" value="<?= $thread->getId() ?>">

            <p>
                <label for="content">本文（必須）</label>
                <br>
                <textarea id="content" name="content" rows="5" cols="50" maxlength="5000" required></textarea>
            </p>

            <p>
                <label for="image">画像（任意）</label>
                <br>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif">
            </p>

            <button type="submit">返信する</button>

        </form>

        <p id="message" role="status"></p>

    </body>
</html>

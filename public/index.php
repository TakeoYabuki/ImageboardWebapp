
<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/pagination.php';


use Database\MySQLWrapper;
use Database\DataAccess\MySQLPostDAO;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$db = new MySQLWrapper();
$dao = new MySQLPostDAO($db);

// 1ページあたりの表示件数
$limit = THREADS_PER_PAGE;

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

// 親投稿の総件数を取得
$total = $dao->countThreads();

// 総ページ数を計算
$totalPages = max(1, (int) ceil($total / $limit));

// 存在しないページの場合
if ($page > $totalPages) {
    http_response_code(404);
    exit('指定されたページが存在しません。');
}

// OFFSETを計算
$offset = ($page - 1) * $limit;

// 指定されたページの投稿を取得
$threads = $dao->getAllThreads($offset, $limit);
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>画像掲示板</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <h1>画像掲示板</h1>

        <p>
            <a href="create.php">新規投稿</a>
        </p>

        <p>
            全<?= $total ?>件の投稿 / <?= $page ?>ページ目（全<?= $totalPages ?>ページ）
        </p>

        <?php if (empty($threads)): ?>
            <p>まだ投稿はありません。</p>
        <?php endif; ?>

        <?php foreach ($threads as $post): ?>
            <article>
                <h2>
                    <a href="thread.php?id=<?= $post->getId() ?>"> <?= htmlspecialchars($post->getSubject() ?? '無題', ENT_QUOTES, 'UTF-8') ?> </a>
                </h2>

                <p>
                    <?= nl2br(htmlspecialchars($post->getContent(), ENT_QUOTES, 'UTF-8')) ?>
                </p>

                <p>
                    投稿日時：
                    <?= htmlspecialchars($post->getCreatedAt() ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>

                <?php $imageFilename = $post->getImageFilename(); ?>

                <?php if ($imageFilename !== null): ?>

                    <a href="image.php?type=original&amp;file=<?= rawurlencode($imageFilename) ?>" target="_blank" rel="noopener">
                        <img src="image.php?type=thumbnail&amp;file=<?= rawurlencode($imageFilename) ?>" alt="投稿画像" loading="lazy">
                    </a>

                <?php endif; ?>

                <hr>

                <?php $latestReplies = $dao->getLatestReplies($post, 5); ?>

                <?php if (!empty($latestReplies)): ?>
                    <section class="latest-replies">
                        <h3>最新の返信</h3>

                        <?php foreach ($latestReplies as $reply): ?>
                            <div class="reply">
                                <p>投稿番号：<?= $reply->getId() ?></p>

                                <p>
                                    <?= nl2br(htmlspecialchars($reply->getContent(), ENT_QUOTES, 'UTF-8')) ?>
                                </p>

                                <?php $imageFilename = $reply->getImageFilename(); ?>

                                <?php if ($imageFilename !== null): ?>
                                    <a href="image.php?type=original&amp;file=<?= rawurlencode($imageFilename) ?>" target="_blank" rel="noopener">
                                        <img src="image.php?type=thumbnail&amp;file=<?= rawurlencode($imageFilename) ?>" alt="返信画像" loading="lazy">
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <a href="thread.php?id=<?= $post->getId() ?>">
                            すべての返信を見る
                        </a>
                    </section>
                <?php endif; ?>

            </article>
        <?php endforeach; ?>

        <nav aria-label="ページ切り替え">

            <?php if ($page > 1): ?>
                <a href="index.php?page=<?= $page - 1 ?>">
                    前へ
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                <?php if ($i === $page): ?>
                    <strong><?= $i ?></strong>
                <?php else: ?>
                    <a href="index.php?page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                <?php endif; ?>

            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="index.php?page=<?= $page + 1 ?>">
                    次へ
                </a>
            <?php endif; ?>

        </nav>

    </body>
</html>

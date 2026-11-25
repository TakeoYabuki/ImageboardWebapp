
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="UTF-8">
        <title>新規スレッド作成</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
        <script src="js/main.js" defer></script>
    </head>

    <body>
        <h1>新規スレッド作成</h1>

        <p>
            <a href="index.php">投稿一覧に戻る</a>
        </p>

        <form id="thread-form" action="upload_handler.php" method="POST" enctype="multipart/form-data">

            <p>
                <label for="subject">件名（任意）</label>
                <br>
                <input type="text" id="subject" name="subject" maxlength="255">
            </p>

            <p>
                <label for="content">本文（必須）</label>
                <br>
                <textarea id="content" name="content" rows="5" cols="50" maxlength="5000" required></textarea>
            </p>

            <p>
                <label for="image">画像（必須）</label>
                <br>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" required>
            </p>

            <p>対応画像：JPEG・PNG・GIF（最大5MB）</p>

            <button type="submit">投稿する</button>

        </form>

        <p id="message" role="status"></p>

    </body>
</html>
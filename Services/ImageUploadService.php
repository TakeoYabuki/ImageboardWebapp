<?php

namespace Services;

class ImageUploadService {
    public function upload(array $file): string {
        // アップロードエラーの確認
        if ($file['error'] !== UPLOAD_ERR_OK) {
            if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE, UPLOAD_ERR_PARTIAL, UPLOAD_ERR_NO_FILE], true)) {
                    throw new \InvalidArgumentException('画像のアップロード内容が正しくありません。');
            }

            throw new \RuntimeException('アップロードに失敗しました。');
        }


        // 最大5MB
        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            throw new \InvalidArgumentException('画像サイズは5MB以下にしてください。');;
        }

        // MIMEタイプの検証
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png'  => 'png', 'image/gif'  => 'gif',];

        if (!isset($allowedTypes[$mimeType])) {
            throw new \InvalidArgumentException('JPEG・PNG・GIF画像のみアップロードできます。');
        }

        // 実際の画像情報を取得
        $imageInfo = @getimagesize($file['tmp_name']);

        if ($imageInfo === false) {
            throw new \InvalidArgumentException('有効な画像ファイルではありません。');
        }

        // 画像の幅と高さ
        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // MIMEタイプと画像形式が一致するか確認
        if ($imageInfo['mime'] !== $mimeType) {
            throw new \InvalidArgumentException('画像形式が一致しません。');
        }

        // 画像サイズ（縦横）の上限
        $maxDimension = 5000;

        if ($width <= 0 || $height <= 0 || $width > $maxDimension || $height > $maxDimension) {
            throw new \InvalidArgumentException('画像の縦横サイズが許容範囲を超えています。');
        }

        // ランダムなファイル名を生成
        $extension = $allowedTypes[$mimeType];
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        // 保存先
        $uploadDir = dirname(__DIR__) . '/storage/uploads/';
        $destination = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('画像の保存に失敗しました。');
        }

        return $fileName;
    }

    public function createThumbnail(string $fileName): void {

        $uploadDir = dirname(__DIR__) . '/storage/uploads/';
        $thumbnailDir = dirname(__DIR__) . '/storage/thumbnails/';

        $sourcePath = $uploadDir . $fileName;
        $destinationPath = $thumbnailDir . $fileName;

        // 画像情報を取得
        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false) {
            throw new \RuntimeException('画像情報を取得できません。');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];
        $imageType = $imageInfo[2];

        // 最大幅・最大高さ
        $maxWidth = 300;
        $maxHeight = 300;

        // 縦横比を維持して縮小
        $ratio = min($maxWidth / $width, $maxHeight / $height, 1);

        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        // 画像形式に応じて読み込む
        $sourceImage = match ($imageType) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            default => false,
        };

        if ($sourceImage === false) {
            throw new \RuntimeException('画像を読み込めません。');
        }

        // サムネイル用画像を作成
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        if ($thumbnail === false) {
            throw new \RuntimeException('サムネイルを作成できません。');
        }

        try {
            // PNG・GIFの透明部分を保持
            if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);

                $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);

                imagefill($thumbnail, 0, 0, $transparent);
            }

            // 元画像を縮小してコピー
            if (!imagecopyresampled(
                $thumbnail,
                $sourceImage,
                0, 0, 0, 0,
                $newWidth,
                $newHeight,
                $width,
                $height
            )) {
                throw new \RuntimeException('画像の縮小に失敗しました。');
            }

            // 画像形式に応じて保存
            $saved = match ($imageType) {
                IMAGETYPE_JPEG => imagejpeg($thumbnail, $destinationPath, 85),
                IMAGETYPE_PNG => imagepng($thumbnail, $destinationPath),
                IMAGETYPE_GIF => imagegif($thumbnail, $destinationPath),
                default => false,
            };

            if (!$saved) {
                throw new \RuntimeException('サムネイルの保存に失敗しました。');
            }
        } finally {
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);
        }
    }

    public function renameImage(string $fileName, int $postId, string $createdAt): string {
        ///拡張子の取得（例　"abc123.jpg"	なら　"jpg"）
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        //ハッシュ化して新しいファイル名取得
        $newFileName = hash('sha256', $postId . '_' . $createdAt) . '.' . $extension;

        $directory = dirname(__DIR__) . '/storage/uploads/';
        //ファイルネームを上書き
        if (!rename($directory . $fileName, $directory . $newFileName)) {
            throw new \RuntimeException('画像名の変更に失敗しました。');
        }

        return $newFileName;
    }
}
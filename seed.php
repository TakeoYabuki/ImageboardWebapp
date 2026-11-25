<?php

require_once __DIR__ . '/vendor/autoload.php';

use Database\MySQLWrapper;
use Database\DataAccess\MySQLPostDAO;
use Models\Post;
use Services\ImageUploadService;

// 環境変数の読み込み
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// DAOの生成
$db = new MySQLWrapper();
$dao = new MySQLPostDAO($db);

// Fakerの生成
$faker = Faker\Factory::create('ja_JP');

$imageService = new ImageUploadService();

// スレッドを10件生成
for ($i = 0; $i < 10; $i++) {

    $thread = new Post(
        null,
        null,
        $faker->realText(20),
        $faker->realText(100),
        null,
        null,
        null
    );

    if (!$dao->create($thread)) {
        throw new RuntimeException('スレッドの生成に失敗しました。');
    }

    // DBから登録済み投稿を取得
    $savedThread = $dao->getById($thread->getId());

    if ($savedThread === null) {
        throw new RuntimeException('スレッドを取得できません。');
    }

    // 画像ファイル名の生成
    $fileName = hash(
        'sha256',
        $savedThread->getId() . '_' . $savedThread->getCreatedAt()
    ) . '.png';

    // GDでテスト画像を生成
    $image = imagecreatetruecolor(400, 300);

    $background = imagecolorallocate(
        $image,
        $faker->numberBetween(0, 255),
        $faker->numberBetween(0, 255),
        $faker->numberBetween(0, 255)
    );

    imagefill($image, 0, 0, $background);

    $white = imagecolorallocate($image, 255, 255, 255);

    imagestring(
        $image,
        5,
        130,
        140,
        'Thread No.' . $savedThread->getId(),
        $white
    );

    $saved = imagepng(
        $image,
        __DIR__ . '/storage/uploads/' . $fileName
    );

    imagedestroy($image);

    if (!$saved) {
        throw new RuntimeException('画像の保存に失敗しました。');
    }

    // サムネイルの生成
    $imageService->createThumbnail($fileName);

    // DBの画像情報を更新
    $savedThread->setImageFilename($fileName);

    if (!$dao->update($savedThread)) {
        throw new RuntimeException('画像情報の更新に失敗しました。');
    }

    // 各スレッドに返信を10件生成
    for ($j = 0; $j < 10; $j++) {

        $reply = new Post(
            null,
            $savedThread->getId(),
            null,
            $faker->realText(80),
            null,
            null,
            null
        );

        if (!$dao->create($reply)) {
            throw new RuntimeException('返信の生成に失敗しました。');
        }
    }

    echo 'Thread ' . $savedThread->getId() . " created.\n";
}

echo "Seeding completed.\n";
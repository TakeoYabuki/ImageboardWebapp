# ImageBoardWebapp

## 概要

PHPとMySQLを使用した匿名画像掲示板アプリケーションです。

ユーザー登録やログインを必要とせず、画像付きのスレッドを作成し、他の投稿に返信することができます。

## 主な機能

- 画像付きスレッドの作成
- 画像付き・画像なしの返信
- スレッド一覧と最新5件の返信の表示
- スレッド詳細と全返信の閲覧
- ページネーション
- 画像のアップロードとサムネイルの自動生成
- サムネイルから元画像へのアクセス
- SHA256による画像ファイル名のハッシュ化
- JavaScriptによる非同期投稿・返信一覧の更新
- 入力値のバリデーション
- Fakerによるテストデータの生成

## 使用技術

| 技術 | 用途 |
|---|---|
| PHP 8.3 | バックエンド |
| MySQL 8.0 | データベース |
| PDO | データベース接続・操作 |
| HTML / CSS | 画面表示 |
| JavaScript | 非同期通信・画面更新 |
| GD | 画像処理・サムネイル生成 |
| Composer | パッケージ管理 |
| phpdotenv | 環境変数の管理 |
| Faker | テストデータの生成 |

## ディレクトリ構成

```text
ImageBoardWebapp/
├── Database/
│   ├── DataAccess/
│   │   ├── Interfaces/
│   │   │   └── PostDAO.php
│   │   └── MySQLPostDAO.php
│   ├── Migrations/
│   │   ├── 001_create_posts.php
│   │   └── 002_add_image_filename_to_posts.php
│   ├── DatabaseManager.php
│   └── MySQLWrapper.php
├── Models/
│   └── Post.php
├── Services/
│   └── ImageUploadService.php
├── config/
│   ├── pagination.php
│   └── post.php
├── public/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   ├── index.php
│   ├── create.php
│   ├── thread.php
│   ├── image.php
│   ├── upload_handler.php
│   └── reply_handler.php
├── storage/
│   ├── uploads/
│   └── thumbnails/
├── .env
├── .gitignore
├── composer.json
├── composer.lock
├── migrate.php
├── seed.php
└── README.md
```

## セットアップ

### 1. 必要な実行環境

- PHP 8.2以上
- MySQL
- Composer
- PHP拡張モジュール（pdo_mysql、gd、mbstring、fileinfo）

### 2. 依存ライブラリのインストール

```bash
composer install
```

### 3. データベースの作成

MySQLで以下のSQLを実行します。

```sql
CREATE DATABASE imageboard_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 4. 環境変数の設定

プロジェクトのルートディレクトリに `.env` ファイルを作成します。

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=imageboard_db
DB_USER=your_username
DB_PASSWORD=your_password
```

DB_USERとDB_PASSWORDは自身のMySQL接続情報に変更してください。

### 5. 画像保存用ディレクトリの作成

```bash
mkdir -p storage/uploads storage/thumbnails
```

### 6. マイグレーションの実行

```bash
php migrate.php
```

必要なデータベーステーブルが作成されます。

### 7. テストデータの生成（任意）

```bash
php seed.php
```

画像付きスレッド10件と、各スレッドに対する返信10件が生成されます。

実行するたびに新しいテストデータが追加されます。

### 8. ローカルサーバーの起動

```bash
php -S localhost:8000 -t public
```

ブラウザで以下のURLにアクセスしてください。

http://localhost:8000

## アプリケーションの仕様

| 項目 | 仕様 |
|---|---|
| 新規スレッドの画像 | 必須 |
| 返信の画像 | 任意 |
| 件名 | 任意・最大255文字 |
| 本文 | 必須・最大5000文字 |
| 対応画像形式 | JPEG、PNG、GIF |
| 最大画像容量 | 5MB |
| スレッド表示件数 | 20件 / ページ |
| 返信表示件数 | 50件 / ページ |
| 一覧での最新返信表示 | 5件 / スレッド |

## データベース設計

postsテーブルでスレッドと返信を管理します。

| カラム | 説明 |
|---|---|
| post_id | 投稿ID・主キー |
| reply_to_id | 返信先の投稿ID |
| subject | 件名 |
| content | 投稿本文 |
| image_filename | 画像ファイル名 |
| created_at | 作成日時 |
| updated_at | 更新日時 |

reply_to_idがNULLの場合は親投稿、値が設定されている場合は返信として扱います。

データベースへのアクセスにはDAOパターンを採用しています。

## セキュリティ対策

- PDOのプリペアドステートメントによるSQLインジェクション対策
- htmlspecialcharsによるXSS対策
- 画像のMIMEタイプ・サイズ・形式の検証
- 画像ファイル名のハッシュ化
- publicディレクトリと画像保存領域の分離
- 環境変数によるデータベース接続情報の管理
- .gitignoreによる機密情報・アップロード画像の除外

## 開発目的

PHPによるWebアプリケーション開発、DAOパターン、データベース設計、マイグレーション、画像処理、JavaScriptによる非同期通信を学習するために作成しました。

本アプリケーションはローカル環境での動作を想定しています。
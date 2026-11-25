<?php

namespace Database\DataAccess;

use Database\MySQLWrapper;
use Database\DataAccess\Interfaces\PostDAO;
use Models\Post;

class MySQLPostDAO implements PostDAO {
    private MySQLWrapper $db;

    public function __construct(MySQLWrapper $db) {
        $this->db = $db;
    }

  //共通処理(MYSQLから取得した連想配列をPOSTオブジェクトにする)
  private function createPostFromRow(array $row): Post {
      return new Post(
          (int) $row['post_id'],
          $row['reply_to_id'] !== null ? (int) $row['reply_to_id'] : null,
          $row['subject'],
          $row['content'],
          $row['created_at'],
          $row['updated_at'],
          $row['image_filename']
      );
  }

//CREATE
    public function create(Post $postData): bool {
        $sql = "
            INSERT INTO posts (reply_to_id, subject, content, image_filename)
            VALUES (?, ?, ?, ?)
        ";

        $this->db->execute($sql, [
            $postData->getReplyToId(),
            $postData->getSubject(),
            $postData->getContent(),
            $postData->getImageFileName()
        ]);

        $postData->setId($this->db->lastInsertId());

        return true;
    }

    //GET
    public function getById(int $id): ?Post {
        $stmt = $this->db->execute(
            "SELECT * FROM posts WHERE post_id = ?",
            [$id]
        );

        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return $this->createPostFromRow($row);
    }

    //UPDATE
    public function update(Post $postData): bool {
        $id = $postData->getId();

        if ($id === null) {
            return false;
        }

        $sql = "
            UPDATE posts SET reply_to_id=?, subject = ?, content = ?, image_filename = ?
            WHERE post_id = ?
        ";

        $stmt = $this->db->execute($sql, [
            $postData->getReplyToId(),
            $postData->getSubject(),
            $postData->getContent(),
            $postData->getImageFilename(),
            $postData->getId()
        ]);

        return $stmt->rowCount() > 0;
    }

    //DELETE
    public function delete(int $id): bool {
        $sql = "DELETE FROM posts WHERE post_id = ?";

        $stmt = $this->db->execute($sql, [$id]);

        return $stmt->rowCount() > 0;
    }

    //既存の投稿があるかを確認
    public function createOrUpdate(Post $postData): bool {
        if ($postData->getId() === null) {
            return $this->create($postData);
        }

        return $this->update($postData);
    }

    //親スレッドのみを取得。
    public function getAllThreads(int $offset, int $limit): array {
        $sql = "
            SELECT *
            FROM posts
            WHERE reply_to_id IS NULL
            ORDER BY created_at DESC, post_id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->execute($sql, [
            $limit,
            $offset
        ]);

        $rows = $stmt->fetchAll();

        $posts = [];

        foreach ($rows as $row) {
            $posts[] = $this->createPostFromRow($row);
        }

        return $posts;
    }

    public function getReplies(Post $postData, int $offset, int $limit): array {
        $postId = $postData->getId();

        if ($postId === null) {
          return [];
        }

        $sql = "
            SELECT *
            FROM posts
            WHERE reply_to_id = ?
            ORDER BY created_at ASC, post_id ASC
            LIMIT ? OFFSET ?
        ";

        $stmt = $this->db->execute($sql, [$postId, $limit, $offset]);

        $rows = $stmt->fetchAll();

        $replies = [];

        foreach ($rows as $row) {
            $replies[] = $this->createPostFromRow($row);
        }

        return $replies;
    }

    public function countThreads(): int {
        $stmt = $this->db->execute(
            "SELECT COUNT(*) FROM posts
            WHERE reply_to_id IS NULL"
        );

        return (int) $stmt->fetchColumn();
    }

    public function countReplies(Post $postData): int {
        $id = $postData->getId();

        if ($id === null) {
            return 0;
        }

        $stmt = $this->db->execute(
            "SELECT COUNT(*) FROM posts
            WHERE reply_to_id = ?",
            [$id]
        );

        return (int) $stmt->fetchColumn();
    }

    public function getLatestReplies(Post $postData, int $limit = 5): array {
        $id = $postData->getId();

        if ($id === null) {
            return [];
        }

        $stmt = $this->db->execute(
            "SELECT * FROM posts
            WHERE reply_to_id = ?
            ORDER BY created_at DESC, post_id DESC
            LIMIT ?",
            [$id, $limit]
        );

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $replies = [];

        foreach ($rows as $row) {
            $replies[] = $this->createPostFromRow($row);
        }

        return array_reverse($replies);
    }
}
<?php

namespace Database\DataAccess\Interfaces;

use Models\Post;

interface PostDAO {
    public function create(Post $postData): bool;

    public function getById(int $id): ?Post;

    public function update(Post $postData): bool;

    public function delete(int $id): bool;

    public function createOrUpdate(Post $postData): bool;

    /**
     * @return Post[]
     */
    public function getAllThreads(int $offset, int $limit): array;

    /**
     * @return Post[]
     */
    public function getReplies (Post $postData, int $offset, int $limit): array;

    public function countThreads(): int;

    public function countReplies(Post $postData): int;
}
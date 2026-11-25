<?php

namespace Models;

class Post {
    private ?int $id;
    private ?int $replyToId;
    private ?string $subject;
    private string $content;
    private ?string $createdAt;
    private ?string $updatedAt;
    private ?string $imageFilename;

    public function __construct (
        ?int $id,
        ?int $replyToId,
        ?string $subject,
        string $content,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?string $imageFilename = null
    ) {
        $this->id = $id;
        $this->replyToId = $replyToId;
        $this->subject = $subject;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->imageFilename = $imageFilename;
    }

    public function getId (): ?int {
        return $this->id;
    }

    public function getReplyToId(): ?int {
        return $this->replyToId;
    }

    public function getSubject(): ?string {
        return $this->subject;
    }

    public function getContent(): string {
        return $this->content;
    }

    public function getCreatedAt(): ?string {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string {
        return $this->updatedAt;
    }

    public function getImageFilename(): ?string {
        return $this->imageFilename;
    }

    public function setId(int $id): void {
        $this->id = $id;
    }

    public function setContent(string $content): void {
        $this->content = $content;
    }

    public function setSubject(?string $subject): void {
        $this->subject = $subject;
    }

    public function setImageFilename(?string $imageFilename): void {
        $this->imageFilename = $imageFilename;
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
}
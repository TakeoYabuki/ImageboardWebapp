<?php

use Database\MySQLWrapper;

return function (MySQLWrapper $db): void {
    $sql = " CREATE TABLE IF NOT EXISTS posts (
            post_id INT AUTO_INCREMENT PRIMARY KEY,
            reply_to_id INT NULL,
            subject VARCHAR(255) NULL,
            content TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP,

            CONSTRAINT fk_posts_reply
                FOREIGN KEY (reply_to_id)
                REFERENCES posts(post_id)
                ON DELETE CASCADE
        )
    ";

    $db->execute($sql);
};
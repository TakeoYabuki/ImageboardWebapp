<?php

use Database\MySQLWrapper;

return function (MySQLWrapper $db): void {
    $db->execute(
        "ALTER TABLE posts
         ADD COLUMN image_filename VARCHAR(255) NULL"
    );
};
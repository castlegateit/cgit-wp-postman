<?php

/**
 * Create a database table to store logs
 */
register_activation_hook(__FILE__, function() {
    global $wpdb;

    $table = $wpdb->prefix . 'cgit_postman_log';
    $sql = 'CREATE TABLE IF NOT EXISTS ' . $table . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATETIME,
        ip VARCHAR(16),
        user_agent VARCHAR(512),
        user_id INT,
        to VARCHAR(128),
        from VARCHAR(128),
        subject VARCHAR(256),
        body LONGTEXT,
        headers VARCHAR(512)
    )';

    $wpdb->query($sql);
});

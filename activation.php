<?php

/**
 * Create a database table to store logs
 */
register_activation_hook(CGIT_POSTMAN_PLUGIN_FILE, function() {
    global $wpdb;

    $table = $wpdb->prefix . 'cgit_postman_log';
    $sql = 'CREATE TABLE IF NOT EXISTS ' . $table . ' (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATETIME,
        ip VARCHAR(16),
        user_agent VARCHAR(512),
        user_id INT,
        mail_to VARCHAR(128),
        mail_from VARCHAR(128),
        mail_subject VARCHAR(256),
        mail_body LONGTEXT,
        mail_headers VARCHAR(512)
    )';

    $wpdb->query($sql);
});

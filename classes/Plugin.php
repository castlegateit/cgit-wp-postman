<?php

namespace Castlegate\Postman;

class Plugin
{
    /**
     * Database connection
     *
     * @var wpdb
     */
    private $database;

    /**
     * Database table name
     *
     * @var string
     */
    private $table;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        global $wpdb;

        $this->database = $wpdb;
        $this->table = $wpdb->base_prefix . 'cgit_postman_log';

        // Create database table for form submission logs
        register_activation_hook(
            CGIT_WP_POSTMAN_PLUGIN_FILE,
            [$this, 'createLogTable']
        );

        // Initialize log spooler
        new LogManager();
    }

    /**
     * Initialization
     *
     * @return void
     */
    public static function init(): void
    {
        $plugin = new self();

        do_action('cgit_postman_loaded');
    }

    /**
     * Create submission log table
     *
     * @return void
     */
    public function createLogTable()
    {
        $this->database->query('CREATE TABLE IF NOT EXISTS ' . $this->table . ' (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATETIME,
            form_id VARCHAR(256),
            blog_id INT,
            post_id INT,
            ip VARCHAR(16),
            user_agent VARCHAR(512),
            user_id INT,
            mail_to VARCHAR(128),
            mail_from VARCHAR(128),
            mail_subject VARCHAR(256),
            mail_body LONGTEXT,
            mail_headers VARCHAR(512),
            field_data LONGTEXT
        )');
    }
}

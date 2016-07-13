<?php

namespace Cgit\Postman;

class Plugin
{
    /**
     * Singleton class instance
     *
     * @var Plugin
     */
    private static $instance;

    /**
     * Private constructor
     *
     * @return void
     */
    private function __construct()
    {
        // Create database table for form submission logs
        register_activation_hook(
            CGIT_POSTMAN_PLUGIN_FILE,
            [$this, 'createLogTable']
        );

        // Initialize log spooler
        Lumberjack::getInstance();
    }

    /**
     * Return the singleton class instance
     *
     * @return Plugin
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Create submission log table
     *
     * @return void
     */
    public function createLogTable()
    {
        global $wpdb;

        $name = $wpdb->prefix . 'cgit_postman_log';
        $sql = 'CREATE TABLE IF NOT EXISTS ' . $name . ' (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATETIME,
            form_id VARCHAR(256),
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
        )';

        $wpdb->query($sql);
    }
}

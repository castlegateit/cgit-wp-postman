<?php

namespace Cgit\Postman;

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
            CGIT_POSTMAN_PLUGIN,
            [$this, 'createLogTable']
        );

        // Check for database compatible issues
        $this->checkNetworkCompatibility();

        // Initialize log spooler
        new Lumberjack;
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

        $this->updateLegacyTables();
    }

    /**
     * Is the database table network compatible?
     *
     * @return boolean
     */
    public function isNetworkCompatible()
    {
        global $wpdb;

        $table = $this->table;
        $results = $wpdb->get_results("SHOW COLUMNS FROM $table
            LIKE 'blog_id'");

        if ($results) {
            return true;
        }

        return false;
    }

    /**
     * Update legacy tables for multisite network compatibility
     *
     * @return void
     */
    private function updateLegacyTables()
    {
        if ($this->isNetworkCompatible()) {
            return;
        }

        $this->database->query('ALTER TABLE ' . $this->table
            . ' ADD blog_id INT AFTER form_id');
    }

    /**
     * Check for and notify of network compatibility issues
     *
     * @return void
     */
    private function checkNetworkCompatibility()
    {
        if ($this->isNetworkCompatible()) {
            return;
        }

        add_action('admin_notices', function () {
            ?>
            <div class="error">
                <p><strong>Warning:</strong> Please reactivate the Postman
                plugin to update the database for compatibility with the latest
                version of the plugin.</p>
            </div>
            <?php
        });
    }
}

<?php

/**
 * Plugin Name:  Castlegate IT WP Postman
 * Plugin URI:   https://github.com/castlegateit/cgit-wp-postman
 * Description:  Flexible contact form plugin for WordPress.
 * Version:      3.4.0
 * Requires PHP: 8.2
 * Author:       Castlegate IT
 * Author URI:   https://www.castlegateit.co.uk/
 * License:      MIT
 * Update URI:   https://github.com/castlegateit/cgit-wp-postman
 */

use Castlegate\Postman\Plugin;

if (!defined('ABSPATH')) {
    wp_die('Access denied');
}

define('CGIT_WP_POSTMAN_VERSION', '3.4.0');
define('CGIT_WP_POSTMAN_PLUGIN_FILE', __FILE__);
define('CGIT_WP_POSTMAN_PLUGIN_DIR', __DIR__);

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init();

<?php

/*

Plugin Name: Castlegate IT WP Postman
Plugin URI: http://github.com/castlegateit/cgit-wp-postman
Description: Flexible contact form plugin for WordPress.
Version: 3.2.0
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

use Castlegate\Postman\Plugin;

if (!defined('ABSPATH')) {
    wp_die('Access denied');
}

define('CGIT_POSTMAN_PLUGIN', __FILE__);

define('CGIT_WP_POSTMAN_PLUGIN_FILE', __FILE__);
define('CGIT_WP_POSTMAN_PLUGIN_DIR', __DIR__);

require_once __DIR__ . '/vendor/autoload.php';

Plugin::init();

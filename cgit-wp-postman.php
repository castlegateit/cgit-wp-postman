<?php

/*

Plugin Name: Castlegate IT WP Postman
Plugin URI: http://github.com/castlegateit/cgit-wp-postman
Description: Flexible contact form plugin for WordPress.
Version: 2.8.2
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

if (!defined('ABSPATH')) {
    wp_die('Access denied');
}

define('CGIT_POSTMAN_PLUGIN', __FILE__);

require_once __DIR__ . '/classes/autoload.php';

$plugin = new \Cgit\Postman\Plugin();

do_action('cgit_postman_loaded');

<?php

/*

Plugin Name: Castlegate IT WP Postman
Plugin URI: http://github.com/castlegateit/cgit-wp-postman
Description: Flexible contact form plugin for WordPress.
Version: 1.0
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

/**
 * Load plugin
 */
add_action('plugins_loaded', function() {
    require __DIR__ . '/src/autoload.php';
    require __DIR__ . '/activation.php';
}, 10);

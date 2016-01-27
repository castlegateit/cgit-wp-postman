<?php

/*

Plugin Name: Castlegate IT Postman
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
    include dirname(__FILE__) . '/notices.php';
    include dirname(__FILE__) . '/validator.php';
    include dirname(__FILE__) . '/mailer.php';
    include dirname(__FILE__) . '/postman.php';
}, 10);

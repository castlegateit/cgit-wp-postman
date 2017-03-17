<?php

/*

Plugin Name: Castlegate IT WP Postman
Plugin URI: http://github.com/castlegateit/cgit-wp-postman
Description: Flexible contact form plugin for WordPress.
Version: 2.4
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

use Cgit\Postman\Plugin;

// Constants
define('CGIT_POSTMAN_PLUGIN_FILE', __FILE__);

// Load plugin
require __DIR__ . '/src/autoload.php';

// Initialization
Plugin::getInstance();

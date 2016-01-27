<?php

/**
 * Check log directory has been defined and that it exists
 */
if (!defined('CGIT_CONTACT_FORM_LOG')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>Warning:</strong> '
            . 'the contact form log directory has not been defined. '
            . 'Please define <code>CGIT_CONTACT_FORM_LOG</code> in '
            . '<code>wp-config.php</code>.</div>';
    });
} elseif (!file_exists(CGIT_CONTACT_FORM_LOG)) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>Warning:</strong> '
            . 'the contact form log directory does not exist. '
            . 'Please check <code>CGIT_CONTACT_FORM_LOG</code> in '
            . '<code>wp-config.php</code>.</div>';
    });
}

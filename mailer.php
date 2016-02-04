<?php

namespace Cgit\PostmanPlugin;

/**
 * Send mail
 */
class Mailer
{
    /**
     * Email recipients
     */
    public $to;
    public $cc;
    public $bcc;

    /**
     * Email headers
     */
    public $headers = [];
    public $from;

    /**
     * Email content
     */
    public $subject;
    public $content;

    /**
     * Constructor
     *
     * Accepts an associated array of properties that override the initial
     * property values for this object.
     */
    public function __construct($args = [])
    {
        // Update properties
        foreach ($args as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Send message
     *
     * Attempts to sent the message using the wp_mail() function and attempts to
     * write an entry in the log file.
     */
    public function send()
    {
        $to = apply_filters('cgit_postman_mail_to', $this->to);
        $subject = apply_filters('cgit_postman_mail_subject', $this->subject);
        $content = apply_filters('cgit_postman_mail_content', $this->content);
        $headers = $this->getHeaders();

        // Print email data instead of sending
        if (defined('CGIT_POSTMAN_MAIL_DUMP') && CGIT_POSTMAN_MAIL_DUMP) {
            return $this->dump($to, $subject, $content, $headers);
        }

        // Send message
        $result = wp_mail($to, $subject, $content, $headers);

        // Log message contents
        $this->log();

        return $result;
    }

    /**
     * Return headers
     *
     * Assemble headers from associative array and $from property and return
     * correctly formatted value.
     */
    private function getHeaders()
    {
        $headers = $this->headers;
        $pairs = [];

        // Set From, Cc, and Bcc headers
        $headers['From'] = apply_filters('cgit_postman_mail_from', $this->from);

        if ($this->cc) {
            $headers['Cc'] = apply_filters('cgit_postman_mail_cc', $this->cc);
        }

        if ($this->bcc) {
            $headers['Bcc'] = apply_filters('cgit_postman_mail_bcc', $this->bcc);
        }

        // Filter headers
        $headers = apply_filters('cgit_postman_mail_headers', $headers);

        // Convert associative array to formatted headers
        foreach ($headers as $key => $value) {
            $pairs[] = $key . ': ' . $value;
        }

        return implode(PHP_EOL, $pairs);
    }

    /**
     * Dump data
     *
     * Print email data instead of sending the message for debugging. Useful if
     * you can't send email or don't want a very full inbox :)
     */
    private function dump($to, $subject, $content, $headers)
    {
        $text = '<pre>To: ' . $to . PHP_EOL;
        $text .= 'Subject: ' . $subject . PHP_EOL;
        $text .= 'Headers: ' . $headers . PHP_EOL;
        $text .= 'Content: ' . str_repeat(PHP_EOL, 2) . $content . '</pre>';

        echo $text;

        return true;
    }

    /**
     * Write to log file
     *
     * Attempts to write to the log file defined with CGIT_CONTACT_FORM_LOG. If
     * the constant is not defined, no log file will be written. If the constant
     * is defined but the directory does not exist, it will be created.
     */
    private function log()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cgit_postman_log';
        $user_id = get_current_user_id();

        $wpdb->insert($table, [
            'date' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'user_id' => $user_id,
            'to' => $this->to,
            'from' => $this->from,
            'subject' => $this->subject,
            'body' => $this->content,
            'headers' => $this->getHeaders(),
        ]);
    }
}

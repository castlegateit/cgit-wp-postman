<?php

namespace Cgit\Postman;

/**
 * Generic mailer class
 */
class Norman
{
    /**
     * Email recipient(s)
     *
     * @var string
     */
    public $to;

    /**
     * Email carbon copy recipient(s)
     *
     * @var string
     */
    public $cc;

    /**
     * Email blind carbon copy recipient(s)
     *
     * @var string
     */
    public $bcc;

    /**
     * Email "From" header value
     *
     * @var string
     */
    public $from;

    /**
     * Associative array of additional email headers
     *
     * @var array
     */
    public $headers = [];

    /**
     * Email subject
     *
     * @var string
     */
    public $subject;

    /**
     * Email content
     *
     * @var string
     */
    public $content;

    /**
     * Constructor
     *
     * Accepts an associated array of properties that override the initial
     * property values for this object.
     *
     * @param array $args
     * @return void
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
     * Attempts to sent the message using the wp_mail() function. Applies
     * filters to the various properties. If the CGIT_POSTMAN_MAIL_DUMP constant
     * is defined, the email content will be returned instead of sent (for
     * debugging).
     *
     * @return mixed
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
        return wp_mail($to, $subject, $content, $headers);
    }

    /**
     * Return headers
     *
     * Assemble headers from associative array and $from property and return
     * correctly formatted value.
     *
     * @return void
     */
    protected function getHeaders()
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
     *
     * @return void
     */
    private function dump($to, $subject, $content, $headers)
    {
        echo '<pre>To: ' . $to . PHP_EOL
            . 'Subject: ' . $subject . PHP_EOL
            . $headers . PHP_EOL . PHP_EOL
            . $content . '</pre>';

        return true;
    }
}

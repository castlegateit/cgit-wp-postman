<?php

namespace Cgit;

use Cgit\Postman\Norman;
use Cgit\Postman\Validator;
use Cgit\Postman\Turing;

/**
 * Post request manager
 */
class Postman
{
    /**
     * Form ID
     *
     * A unique value that will be used to identify the form on submission and
     * in the log table in the database.
     *
     * @var string
     */
    public $id;

    /**
     * Validator class
     *
     * The fully qualified name of the validator class. This can be overridden
     * to use a different class to validate each field.
     *
     * @var string
     */
    public $validator = '\Cgit\Postman\Validator';

    /**
     * Mailer class
     *
     * The fully qualified name of the mailer class. This can be overridden to
     * use a different class to send the email message.
     *
     * @var string
     */
    public $mailer = '\Cgit\Postman\Norman';

    /**
     * Captcha class
     *
     * The fully qualified name of the captcha class. This can be overridden to
     * use a different class to send the email message.
     *
     * @var string
     */
    public $captcha = '\Cgit\Postman\Turing';

    /**
     * Form method
     *
     * By default, the object will look for the form fields in $_POST. Set this
     * to "get" (case-insensitive) to check for $_GET requests.
     *
     * @var string
     */
    public $method = 'post';

    /**
     * Default error message
     *
     * This can be overridden for each field using the "error" item in the array
     * of field options.
     *
     * @var string
     */
    public $errorMessage = 'Invalid input';

    /**
     * Error template
     *
     * If this is set and contains a "%s" placeholder for sprintf(), it will be
     * applied to each error message. This allows you to print error messages
     * wrapped in custom HTML without using conditional statements in your
     * template.
     *
     * @var string
     */
    public $errorTemplate = false;

    /**
     * Default mailer settings
     *
     * An associative array of options that can be passed to the constructor of
     * the Norman mailer class.
     *
     * @var array
     */
    public $mailerSettings = [];

    /**
     * Whether the contact form has been sent out or not.
     *
     * @var bool
     */
    private $sent = false;

    /**
     * Fields
     *
     * An array of fields that will be processed as part of this form. Fields
     * are added using the field() method.
     *
     * @var array
     */
    private $fields = [];

    /**
     * Errors
     *
     * An associative array of errors, where the array keys are the names of the
     * fields that have errors in their values.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Submitted form data
     *
     * The values of the submitted fields are taken from the $_POST or $_GET
     * data and added to this array. Unlike the raw request data, this array
     * will only contain the values of registered fields.
     *
     * @var array
     */
    private $data = [];

    /**
     * Constructor
     *
     * Set default values for the email recipient, from address, and subject
     * line. Make an educated guess for the current domain based on the
     * SERVER_NAME environment variable.
     *
     * @param string $id
     * @return void
     */

    /**
     * Whether the contact form has a captcha or not.
     *
     * @var bool
     */
    public $hasCaptcha = false;

    /*
     * Constructor
     */

    public function __construct($id)
    {
        // Set form ID
        $this->id = $id;

        // Set default mailer options
        $domain = strtolower($_SERVER['SERVER_NAME']);

        if (substr($domain, 0, 4) == 'www.') {
            $domain = substr($domain, 4);
        }

        $this->mailerSettings = [
            'to' => get_option('admin_email'),
            'from' => 'wordpress@' . $domain,
            'subject' => '[' . get_bloginfo('name') . '] Website Enquiry',
            'headers' => [],
        ];
    }

    /**
     * Add field
     *
     * @param string $name
     * @param array $options
     * @return void
     */
    public function field($name, $options = [])
    {
        $this->fields[$name] = $options;
    }

    /**
     * Add fields
     *
     * @param array $fields
     * @return void
     */
    public function fields($fields)
    {
        foreach ($fields as $name => $options) {
            $this->field($name, $options);
        }
    }

    /**
     * Return field value
     *
     * If the field has a value in the submitted data, use that value. If not,
     * check for a default value in the field definition. Finally, apply a named
     * filter to the value.
     *
     * @param string $name
     * @return mixed
     */
    public function value($name)
    {
        $value = false;

        if (isset($this->data[$name])) {
            $value = $this->data[$name];
        } elseif (isset($this->fields[$name]['value'])) {
            $value = $this->fields[$name]['value'];
        }

        // Escape value
        $value = self::escape($value);

        return apply_filters('cgit_postman_value_' . $name, $value, $this->id);
    }

    /**
     * Return field error message
     *
     * If the errorTemplate property is set and contains a placeholder "%s", it
     * will be applied to the error message.
     *
     * @param string $name
     * @return string
     */
    public function error($name)
    {
        $error = isset($this->errors[$name]) ? $this->errors[$name] : false;
        $template = $this->errorTemplate;

        if (!$error) {
            return false;
        }

        if ($template && strpos($template, '%s') !== false) {
            $error = sprintf($template, $error);
        }

        return apply_filters('cgit_postman_error_' . $name, $error, $this->id);
    }

    /**
     * Attempt to submit form
     *
     * If data has been submitted from the right format, validate the data and
     * send the email.
     *
     * @return boolean
     */
    public function submit()
    {
        if (!$this->submitted()) {
            return false;
        }

        $this->updateData();

        // Filter data before validation
        $this->data = apply_filters('cgit_postman_data_pre_validate', $this->data, $this->id);

        $this->validateForm();

        // Filter data after validation but before sending
        $this->data = apply_filters('cgit_postman_data_post_validate', $this->data, $this->id);

        if ($this->errors) {
            return false;
        }

        // Filter data and fields before sending
        $this->data = apply_filters('cgit_postman_data', $this->data, $this->id);
        $this->fields = apply_filters('cgit_postman_fields', $this->fields, $this->id);

        return $this->send();
    }

    /**
    * Whether the contact form has been sent or not.
    *
    * @return boolean
    */
    public function sent()
    {
        return $this->sent;
    }

    /**
    * Whether the contact form has generated any errors or not.
    *
    * @return boolean
    */
    public function errors()
    {
        return (bool) $this->errors;
    }

    /**
     * Has the form been submitted?
     *
     * If the form has been submitted, the request data (GET or POST) should
     * contain the correct form ID.
     *
     * @return boolean
     */
    private function submitted()
    {
        $request = $this->request();

        if (isset($request['postman_form_id']) && $request['postman_form_id'] == $this->id) {
            return true;
        }

        return false;
    }

    /**
     * Return request data
     *
     * Returns the full contents of $_POST or $_GET, depending on the value of
     * the $method property.
     *
     * @return array
     */
    private function request()
    {
        $request = $_POST;

        if (strtolower($this->method) == 'get') {
            $request = $_GET;
        }

        return $request;
    }

    /**
     * Assign request data to data property
     *
     * In contrast to the raw request data, the $data property should only
     * contain the values from the registered fields. This also adds the value
     * of each field to the $fields array.
     *
     * @return void
     */
    private function updateData()
    {
        $request = $this->request();

        foreach (array_keys($this->fields) as $name) {
            $value = isset($request[$name]) ? $request[$name] : false;
            $this->data[$name] = $value;
            $this->fields[$name]['value'] = $value;
        }
    }

    /**
     * Validate all fields
     *
     * @return void
     */
    private function validateForm()
    {
        foreach (array_keys($this->fields) as $field) {
            $this->validate($field);
        }
    }

    /**
     * Validate field
     *
     * @param string $name
     * @return void
     */
    private function validate($name)
    {
        $opts = $this->getFieldOptions($this->fields[$name]);
        $messages = $this->getErrorMessages($opts);

        // Get field value
        $value = $this->value($name);

        // Check required fields have values
        if ($opts['required'] && !$value) {
            $this->errors[$name] = $messages['required'];
        }

        // Validate field values
        if ($value && $opts['validate']) {
            $class = $this->validator;
            $validator = new $class($value, $opts['validate'], $this->data);
            $errors = $validator->error();

            if ($errors) {
                $key = end($errors);
                $message = $messages['required'];

                // Is there a specific validation error message for this
                // particular type of error?
                if (array_key_exists($key, $messages)) {
                    $message = $messages[$key];
                }

                $this->errors[$name] = $message;
            }
        }
    }

    /**
     * Generate sanitized field options
     *
     * @param array $options
     * @return array
     */
    private function getFieldOptions($options)
    {
        return array_merge([
            'error' => $this->errorMessage,
            'required' => false,
            'validate' => false
        ], $options);
    }

    /**
     * Generate sanitized error messages
     *
     * @param array $options
     * @return array
     */
    private function getErrorMessages($options)
    {
        $messages = $options['error'];

        if (!is_array($options['error'])) {
            $messages = ['required' => $messages];
        }

        return array_merge([
            'required' => $messages['required'],
            'type' => $messages['required'],
            'maxlength' => $messages['required'],
            'minlength' => $messages['required'],
            'max' => $messages['required'],
            'min' => $messages['required'],
            'pattern' => $messages['required'],
            'match' => $messages['required'],
            'function' => $messages['required'],
        ], $messages);
    }

    /**
     * Send message
     *
     * Attempt to send the message using the Normal mailer class and save a copy
     * of the data in the log table in the database.
     *
     * @return boolean
     */
    private function send()
    {
        // Create a new mailer
        $mailer_class = $this->mailer;
        $mailer = new $mailer_class($this->mailerSettings);
        $mailer->content = $this->messageContent();

        // Save entry in the log table in the database
        $this->log();

        // Attempt to send the message
        return $this->sent = $mailer->send();
    }

    /**
     * Assemble message content
     *
     * Construct the content of the message using all the submitted fields. If
     * the fields were defined with the 'label' property, this will be used as
     * the field label in the message. If not, the field name will be used. Each
     * field value is sanitized before being added to the message.
     *
     * Fields with the 'exclude' property set to true will not appear in the
     * message body. This might be useful for hidden fields or buttons.
     *
     * @return string
     */
    private function messageContent()
    {
        $sections = [];

        foreach ($this->data as $key => $value) {
            $field = $this->fields[$key];

            // Allow certain fields to be excluded from the message body
            if (isset($field['exclude']) && $field['exclude']) {
                continue;
            }

            $label = isset($field['label']) ? $field['label'] : $key;

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $sections[] = $label . ': ' . self::sanitize($value);
        }

        return implode(str_repeat(PHP_EOL, 2), $sections);
    }

    /**
     * Escape data for HTML output
     *
     * @param mixed $data
     * @return mixed
     */
    private static function escape($data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                $item = self::escapeString($item);
            }

            return $data;
        }

        return self::escapeString($data);
    }

    /**
     * Escape string
     *
     * @param string $str
     * @return string
     */
    private static function escapeString($str)
    {
        if (!is_string($str)) {
            return $str;
        }

        return htmlspecialchars($str);
    }

    /**
     * Sanitize data for form submission
     *
     * @param string $str
     * @return string
     */
    private static function sanitize($str)
    {
        $str = strip_tags($str);
        $str = htmlspecialchars($str);

        return $str;
    }

    /**
     * Determines whether to perform Captcha logic
     *
     * If we're using Captcha logic, then begin that process.
     */
    public function enableCaptcha($id = 'g-recaptcha-response')
    {
        $this->hasCaptcha = true;
        $captcha = new $this->captcha;
        $captcha->registerCaptcha($id);
        $this->field($id, [
        'label' => $id,
        'required' => true,
        'exclude' => true,
        'validate' => [
            'function' => array($captcha, 'submitRecaptcha')
        ],
        'error' => 'Please complete the recaptcha.'
        ]);

        if ($captcha->explainFailure) {
            $this->error[$id] = $captcha->explainFailure;
        }
    }

    public function renderCaptcha()
    {
        echo '<div class="g-recaptcha" data-sitekey="'.RECAPTCHA_SITE_KEY.'"></div>';
    }

    /**
     * Log form submission
     *
     * Save a the submitted form data in the database, including the form ID,
     * the current post/page and user ID, and the assembled content of the email
     * message. Because each form can consist of any number of arbitrary fields,
     * the field data is saved as JSON instead of using separate table columns
     * for each field.
     *
     * @return void
     */
    private function log()
    {
        global $post;
        global $wpdb;

        $table = $wpdb->base_prefix . 'cgit_postman_log';
        $opts = $this->mailerSettings;
        $post_id = isset($post->ID) ? $post->ID : 0;
        $user_id = get_current_user_id();

        // Convert headers to a string
        $headers = isset($opts['headers']) ? $opts['headers'] : [];
        $pairs = [];

        foreach ($headers as $key => $value) {
            $pairs[] = $key . ': ' . $value;
        }

        // Add row to database
        $wpdb->insert($table, [
            'date' => date('Y-m-d H:i:s'),
            'form_id' => $this->id,
            'blog_id' => $wpdb->blogid,
            'post_id' => $post_id,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'user_id' => $user_id,
            'mail_to' => $opts['to'],
            'mail_from' => $opts['from'],
            'mail_subject' => $opts['subject'],
            'mail_body' => $this->messageContent(),
            'mail_headers' => implode(PHP_EOL, $pairs),
            'field_data' => json_encode($this->fields),
        ]);
    }
}

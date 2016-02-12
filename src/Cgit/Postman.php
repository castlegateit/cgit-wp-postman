<?php

namespace Cgit;

use Cgit\Postman\Validator;
use Cgit\Postman\Mailer;

/**
 * Post (and get) request manager
 */
class Postman
{
    /**
     * Form method
     *
     * By default, the object will look for the form fields in $_POST. Set this
     * to "get" (case-insensitive) to check for $_GET requests.
     */
    public $method = 'post';

    /**
     * Default error message
     *
     * This can be overridden for each field using the "error" item in the array
     * of field options.
     */
    public $errorMessage = 'Invalid input';

    /**
     * Error template
     *
     * If this is set and contains a "%s" placeholder for sprintf(), it will be
     * applied to each error message. This allows you to print error messages
     * wrapped in custom HTML without using conditional statements in your
     * template.
     */
    public $errorTemplate = false;

    /**
     * Default field options
     */
    private $defaultOptions = [
        'required' => false,
        'validate' => false,
        'error' => false,
    ];

    /**
     * Default email settings
     */
    public $mailTo;
    public $mailFrom;
    public $mailSubject;
    public $mailHeaders = [];

    /**
     * Conditions that must be met to submit the form
     *
     * The form should only submit if these field(s) exist and/or have
     * particular values. This allows you to distinguish between multiple forms
     * on the same page. The value of this property is set with detect() method.s
     */
    private $conditions;

    /**
     * Fields
     *
     * An array of fields that will be processed as part of this form. Fields
     * are added using the field() method.
     */
    private $fields = [];

    /**
     * Errors
     *
     * An associative array of errors, where the array keys are the names of the
     * fields that have errors in their values.
     */
    private $errors = [];

    /**
     * Submitted form data
     *
     * The values of the submitted fields are taken from the $_POST or $_GET
     * data and added to this array. Unlike the raw request data, this array
     * will only contain the values of registered fields.
     */
    private $data = [];

    /**
     * Constructor
     *
     * Set default values for the email recipient, from address, and subject
     * line.
     */
    public function __construct()
    {
        $domain = strtolower($_SERVER['SERVER_NAME']);

        if (substr($domain, 0, 4) == 'www.') {
            $domain = substr($domain, 4);
        }

        $this->mailTo = get_option('admin_email');
        $this->mailFrom = 'wordpress@' . $domain;
        $this->mailSubject = '[' . get_bloginfo('name') . '] Website Enquiry';
    }

    /**
     * Add field
     */
    public function field($name, $options = [])
    {
        $this->fields[$name] = $options;
    }

    /**
     * Return field value
     *
     * If the field has a value in the submitted data, use that value. If not,
     * check for a default value in the field definition. Finally, apply a named
     * filter to the value.
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
        $value = $this->escape($value);

        return apply_filters('cgit_postman_value_' . $name, $value);
    }

    /**
     * Return field error message
     *
     * If the errorTemplate property is set and contains a placeholder "%s", it
     * will be applied to the error message.
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

        return apply_filters('cgit_postman_error_' . $name, $error);
    }

    /**
     * Attempt to submit form
     *
     * If data has been submitted from the right format, validate the data and
     * send the email.
     */
    public function submit()
    {
        if (!$this->submitted()) {
            return false;
        }

        $this->getData();

        // Filter data before validation
        $this->data = apply_filters(
            'cgit_postman_data_pre_validate',
            $this->data
        );

        $this->validateForm();

        // Filter data after validation but before sending
        $this->data = apply_filters(
            'cgit_postman_data_post_validate',
            $this->data
        );

        if ($this->errors) {
            return false;
        }

        // Filter data to be submitted
        $this->data = apply_filters('cgit_postman_data', $this->data);

        return $this->send();
    }

    /**
     * Has the form been submitted?
     *
     * If the form has been submitted, the request ($_GET or $_POST) will
     * contain data and conditions set with the detect() method will be true. If
     * this is not the case, the form has not been submitted and nothing should
     * happen (e.g. no validation should take place).
     */
    private function submitted()
    {
        $request = $this->request();
        $conditions = $this->conditions;

        // If there is no request data, the form has not been submitted
        if (!$request) {
            return false;
        }

        // If there is request data and there are no conditions, assume the
        // form has been submitted.
        if (!$conditions) {
            return true;
        }

        // Convert single string conditions into an array of conditions
        if (is_string($conditions)) {
            $conditions = [$conditions];
        }

        // Check each condition has been met. If the conditions are an
        // associative array, check names and values; otherwise check names
        // only.
        if ($this->isAssoc($conditions)) {
            foreach ($conditions as $key => $value) {
                if (!isset($request[$key]) || $request[$key] != $value) {
                    return false;
                }
            }
        } else {
            foreach ($conditions as $condition) {
                if (!array_key_exists($condition, $request)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Return request data
     *
     * Returns the full contents of $_POST or $_GET, depending on the value of
     * the $method property.
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
     * Add conditions
     */
    public function detect($conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * Assign request data to data property
     *
     * In contrast to the raw request data, the $data property should only
     * contain the values from the registered fields.
     */
    private function getData()
    {
        $request = $this->request();

        foreach (array_keys($this->fields) as $name) {
            $value = isset($request[$name]) ? $request[$name] : false;
            $this->data[$name] = $value;
        }
    }

    /**
     * Validate all fields
     */
    private function validateForm()
    {
        foreach (array_keys($this->fields) as $field) {
            $this->validate($field);
        }
    }

    /**
     * Validate field
     */
    private function validate($name)
    {
        // Make sure no array keys are missing
        $options = array_merge($this->defaultOptions, $this->fields[$name]);

        // Assign values to variables
        $required = $options['required'];
        $rules = $options['validate'];
        $message = $options['error'] ?: $this->errorMessage;
        $value = $this->value($name);
        $data = $this->data;

        // Check required fields have values
        if ($required && !$value) {
            $this->errors[$name] = $message;
        }

        // Validate field values
        if ($value && $rules) {
            $validator = new Validator($value, $rules, $data);

            if ($validator->error()) {
                $this->errors[$name] = $message;
            }
        }
    }

    /**
     * Send message
     */
    private function send()
    {
        $mailer = new Mailer();

        $mailer->to = $this->mailTo;
        $mailer->from = $this->mailFrom;
        $mailer->subject = $this->mailSubject;
        $mailer->content = $this->messageContent();
        $mailer->headers = $this->mailHeaders;

        return $mailer->send();
    }

    /**
     * Assemble message content
     *
     * Construct the content of the message using all the submitted fields. If
     * the fields were defined with the 'label' property, this will be used as
     * the field label in the message. If not, the field name will be used. Each
     * field value is sanitized before being added to the message.
     */
    private function messageContent()
    {
        $sections = [];

        foreach ($this->data as $key => $value) {
            $field = $this->fields[$key];
            $label = isset($field['label']) ? $field['label'] : $key;
            $sections[] = $label . ': ' . $this->sanitize($value);
        }

        return implode(str_repeat(PHP_EOL, 2), $sections);
    }

    /**
     * Escape data for HTML output
     */
    private function escape($data)
    {
        if (is_array($data)) {
            foreach ($data as &$item) {
                $item = $this->escapeString($item);
            }

            return $data;
        }

        return $this->escapeString($data);
    }

    /**
     * Escape string
     */
    private function escapeString($str)
    {
        if (!is_string($str)) {
            return $str;
        }

        return htmlspecialchars($str);
    }

    /**
     * Sanitize data for form submission
     */
    private function sanitize($str)
    {
        $str = strip_tags($str);
        $str = htmlspecialchars($str);

        return $str;
    }

    /**
     * Distinguish between arrays and associative arrays
     */
    private function isAssoc($arr)
    {
        if (!is_array($arr)) {
            return false;
        }

        return array_keys($arr) !== array_keys(array_keys($arr));
    }
}

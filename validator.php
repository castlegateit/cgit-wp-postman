<?php

namespace Cgit\PostmanPlugin;

/**
 * Form value validation
 *
 * Given a value and an array of validation rules, this class can check the
 * validity of form submissions. Rules take the form of an associative array,
 * for example:
 *
 *  $rules = [
 *      'type' => 'email', // email, number, tel, url
 *      'maxlength' => 16, // maximum number of characters
 *      'minlength' => 4, // minimum number of characters
 *      'max' => 16, // maximum numeric value
 *      'min' => 4, // minimum numeric value
 *      'pattern' => '/foo/', // any regular expression
 *      'match' => 'bar', // value matches another named field
 *      'function' => 'foo', // any named function
 *  ];
 *
 * The valid() method returns true if the value matches all the rules. The
 * error() method returns a list of rule names that the value did not match.
 */
class Validator
{
    /**
     * Value to be scrutinized
     */
    public $value;

    /**
     * Validation rules
     */
    public $rules;

    /**
     * Validation errors
     */
    public $errors = [];

    /**
     * Form data
     *
     * An associative array of all the field names and values submitted to the
     * current form. This is required for "match" validation and can also be
     * used in custom validation functions.
     */
    public $formData = [];

    /**
     * Constructor
     */
    public function __construct($value, $rules = [], $data = [])
    {
        $this->value = $value;
        $this->rules = $rules;
        $this->formData = $data;
    }

    /**
     * Is value valid?
     */
    public function valid()
    {
        foreach ($this->rules as $name => $rule) {
            $method = $this->methodName($name);

            if (!$this->$method($this->value, $rule)) {
                $this->errors[] = $name;
            }
        }

        if ($this->errors) {
            return false;
        }

        return true;
    }

    /**
     * Is value invalid?
     */
    public function error()
    {
        $this->valid();

        return $this->errors;
    }

    /**
     * Convert rule name into method name
     */
    private function methodName($str)
    {
        return $this->camelize('is_' . $str);
    }

    /**
     * Convert snake case to camel case
     */
    private function camelize($str)
    {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }

    /**
     * Check value matches type
     *
     * Uses other methods to check that the value is a valid email address,
     * number, telephone number, or URL.
     */
    private function isType($value, $type)
    {
        $method = $this->methodName($type);

        return $this->$method($value);
    }

    /**
     * Check valid email address
     *
     * Checks the format of the email address and performs an MX record check to
     * ensure the email address uses a valid domain.
     */
    private function isEmail($value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        list($address, $domain) = explode('@', $value);
        $domain .= '.';

        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            return false;
        }

        return true;
    }

    /**
     * Check valid number
     */
    private function isNumber($value)
    {
        return is_numeric($value);
    }

    /**
     * Check valid telephone number
     */
    private function isTel($value)
    {
        return preg_match('/^[0-9,\.]+$/', $value) == 1;
    }

    /**
     * Check valid URL
     */
    private function isUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Check value length against maximum length
     */
    private function isMaxlength($value, $length)
    {
        return strlen($value) <= $length;
    }

    /**
     * Check value length against minimum length
     */
    private function isMinlength($value, $length)
    {
        return strlen($value) >= $length;
    }

    /**
     * Check value against maximum value
     */
    private function isMax($value, $max)
    {
        return $value <= $max;
    }

    /**
     * Check value against minimum value
     */
    private function isMin($value, $min)
    {
        return $value >= $min;
    }

    /**
     * Check value matches regular expression
     */
    private function isPattern($value, $pattern)
    {
        return preg_match($pattern, $value) == 1;
    }

    /**
     * Check value matches the value of another field
     */
    private function isMatch($value, $name)
    {
        if (!isset($this->formData[$name])) {
            return false;
        }

        return $value == $this->formData[$name];
    }

    /**
     * Check value using custom function
     *
     * If available, the form data are available as the second argument in the
     * named function.
     */
    private function isFunction($value, $function)
    {
        if (!function_exists($function)) {
            trigger_error('Function not defined: ' . $function);
        }

        return $function($value, $this->formData);
    }
}

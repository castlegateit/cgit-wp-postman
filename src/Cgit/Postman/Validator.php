<?php

namespace Cgit\Postman;

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
     *
     * @var mixed
     */
    public $value;

    /**
     * Validation rules
     *
     * @var array
     */
    public $rules;

    /**
     * Validation errors
     *
     * @var array
     */
    public $errors = [];

    /**
     * Form data
     *
     * An associative array of all the field names and values submitted to the
     * current form. This is required for "match" validation and can also be
     * used in custom validation functions.
     *
     * @var array
     */
    public $formData = [];

    /**
     * Constructor
     *
     * @param mixed $value
     * @param array $rules
     * @param array $data
     * @return boolean
     */
    public function __construct($value, $rules = [], $data = [])
    {
        $this->value = $value;
        $this->rules = $rules;
        $this->formData = $data;
    }

    /**
     * Is value valid?
     *
     * @return boolean
     */
    public function valid()
    {
        foreach ($this->rules as $name => $rule) {
            $method = self::methodName($name);

            // Check validation method exists
            if (!method_exists($this, $method)) {
                return trigger_error('Unknown validation method: ' . $method);
            }

            // If invalid, add validation type to array of errors
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
     *
     * This will return an array of error messages. If there all the fields are
     * valid and there are no error messages, this will return an empty array.
     *
     * @return array
     */
    public function error()
    {
        $this->valid();

        return $this->errors;
    }

    /**
     * Convert rule name into method name
     *
     * @param string $str
     * @return string
     */
    protected static function methodName($str)
    {
        return self::camelize('is_' . $str);
    }

    /**
     * Convert snake case to camel case
     *
     * @param string
     * @return string
     */
    protected static function camelize($str)
    {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }

    /**
     * Check value matches type
     *
     * Uses other methods to check that the value is a valid email address,
     * number, telephone number, or URL.
     *
     * @param mixed $value
     * @param string $type
     * @return boolean
     */
    protected function isType($value, $type)
    {
        $method = self::methodName($type);

        // Make sure that a validation method exists for the specified input
        // type. Note that type validation does not make sense for text or
        // textarea fields because values will always be returned as strings.
        if (!method_exists($this, $method)) {
            return trigger_error('Type validation not available for "' . $type
                . '" input type');
        }

        return $this->$method($value);
    }

    /**
     * Check valid email address
     *
     * Checks the format of the email address and performs an MX record check to
     * ensure the email address uses a valid domain.
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isEmail($value)
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
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isNumber($value)
    {
        return is_numeric($value);
    }

    /**
     * Check valid telephone number
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isTel($value)
    {
        return preg_match('/^[0-9,\.]+$/', $value) == 1;
    }

    /**
     * Check valid URL
     *
     * @param mixed $value
     * @return boolean
     */
    protected function isUrl($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * Check value length against maximum length
     *
     * @param mixed $value
     * @param integer $length
     * @return boolean
     */
    protected function isMaxlength($value, $length)
    {
        return strlen($value) <= $length;
    }

    /**
     * Check value length against minimum length
     *
     * @param mixed $value
     * @param integer $length
     * @return boolean
     */
    protected function isMinlength($value, $length)
    {
        return strlen($value) >= $length;
    }

    /**
     * Check value against maximum value
     *
     * @param mixed $value
     * @param integer $max
     * @return boolean
     */
    protected function isMax($value, $max)
    {
        return $value <= $max;
    }

    /**
     * Check value against minimum value
     *
     * @param mixed $value
     * @param integer $min
     * @return boolean
     */
    protected function isMin($value, $min)
    {
        return $value >= $min;
    }

    /**
     * Check value matches regular expression
     *
     * @param mixed $value
     * @param string $pattern
     * @return boolean
     */
    protected function isPattern($value, $pattern)
    {
        return preg_match($pattern, $value) == 1;
    }

    /**
     * Check value matches the value of another field
     *
     * @param mixed $value
     * @param string $name
     * @return boolean
     */
    protected function isMatch($value, $name)
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
     *
     * @param mixed $value
     * @param callback $function
     * @return boolean
     */
    protected function isFunction($value, $function)
    {
        if (!function_exists($function)) {
            trigger_error('Function not defined: ' . $function);
        }

        return $function($value, $this->formData);
    }
}

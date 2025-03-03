<?php

declare(strict_types=1);

namespace Castlegate\Postman;

trait PostmanAkismet
{
    /**
     * Akismet class instance
     *
     * @var Akismet|null
     */
    private ?Akismet $akismet = null;

    /**
     * Akismet error message
     *
     * @var string
     */
    private string $akismetErrorMessage = 'Your message appears to be spam. Please check it and try again.';

    /**
     * Akismet validation type
     *
     * Must be one of comment, forum-post, reply, blog-post, contact-form,
     * signup, or message.
     *
     * @var string|null
     */
    private $akismetType = null;

    /**
     * Akismet fields
     *
     * Akistmet fields (keys) and their corresponding Postman field names
     * (values). Multiple Postman fields can be specified as an array of field
     * names. In that case, the field values will be concatenated.
     *
     * @var array
     */
    private $akismetFields = [];

    /**
     * Validate Akismet configuration
     *
     * @return void
     */
    private function validateAkismetConf(): void
    {
        if (!($this->akismet instanceof Akismet)) {
            return;
        }

        if (!$this->akismet->active()) {
            trigger_error('Akismet enabled but API key missing.', E_USER_ERROR);
        }

        if (!Akismet::verify()) {
            trigger_error('Akismet enabled but API key invalid.', E_USER_ERROR);
        }
    }

    /**
     * Enable Akismet validation
     *
     * Enable Akismet validation for this form. The first parameter sets the
     * Akismet comment type and must be one of: forum-post, reply, blog-post,
     * contact-form, signup, or message.
     *
     * The second parameter maps the Postman fields to valid Akismet fields:
     * comment_author, comment_author_email, comment_author_url,
     * comment_content. Multiple Postman field names can be specified as an
     * array. If no map is provided, all Postman fields will be combined and
     * submitted as comment_content. But you probably don't really want that to
     * happen.
     *
     * See <https://akismet.com/development/api/>.
     *
     * @param string $type Akismet comment type.
     * @param array $map Array that maps Postman fields to Akismet fields.
     * @return void
     */
    public function enableAkismet(string $type, array $fields = []): void
    {
        $this->akismetType = $type;
        $this->akismetFields = $fields;

        $this->akismet = new Akismet();
    }

    /**
     * Is Akismet enabled?
     *
     * @return bool
     */
    public function hasAkismet(): bool
    {
        return $this->akismet instanceof Akismet;
    }

    /**
     * Validate form submission with Akismet
     *
     * @return void
     */
    private function validateAkismet(): void
    {
        // Akismet is not enabled? Skip validation.
        if (!$this->hasAkismet()) {
            return;
        }

        // Set Akismet validation parameters.
        $this->akismet->setAkismetArgs($this->getAkismetFields());

        // Akismet validation returned true. Form submission is not spam.
        if ($this->akismet->validate()) {
            return;
        }

        // Akismet validation returned false. Looks like spam :(
        $this->errors['akismet'] = $this->akismetErrorMessage;
    }

    /**
     * Return Akismet field values based on Postman field values
     *
     * Note that this simply maps named Postman fields to the keys set in the
     * array of Akismet fields. It does not check that the Akismet field keys
     * themselves are valid.
     *
     * @return array
     */
    private function getAkismetFields(): array
    {
        $values = [];

        foreach ($this->akismetFields as $akismet_key => $postman_key) {
            $values[$akismet_key] = $this->getAkismetFlatValue($postman_key, true);
        }

        return array_merge($values, [
            'comment_type' => $this->akismetType,
        ]);
    }

    /**
     * Return string value from field name(s)
     *
     * Multiple fields are combined into a single string.
     *
     * @param mixed $field
     * @param bool $strict
     * @return string|null
     */
    private function getAkismetFlatValue($field, bool $strict = false): ?string
    {
        // Single field name? Return field value.
        if (is_string($field)) {
            // Strict mode (field must exist)?
            if ($strict && !array_key_exists($field, $this->fields)) {
                trigger_error("Unknown Postman field \"$field\"", E_USER_NOTICE);
                return null;
            }

            return (string) $this->value($field);
        }

        // Array of field names? Return concated values of all fields.
        if (is_array($field)) {
            $values = [];

            foreach ($field as $sub_field) {
                $values[] = $this->getAkismetFlatValue($sub_field, $strict);
            }

            return implode(' ', $values);
        }

        // Not a string or an array of strings? Cannot return value.
        return null;
    }
}

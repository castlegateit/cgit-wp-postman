<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class ReCaptcha3 extends ReCaptcha
{
    /**
     * Script handle
     *
     * @var string
     */
    public const SCRIPT_HANDLE = 'postman-recaptcha-v3';

    /**
     * API script handle
     *
     * @var string
     */
    public const API_SCRIPT_HANDLE = 'postman-recaptcha-api-v3';

    /**
     * List of form IDs by site
     *
     * @var array
     */
    private static $forms = [];

    /**
     * Initialization
     *
     * @return void
     */
    protected function init(): void
    {
        $script_url = path_join(plugin_dir_url(CGIT_WP_POSTMAN_PLUGIN_FILE), 'assets/js/recaptcha-v3.js');
        $script_version = get_plugin_data(CGIT_WP_POSTMAN_PLUGIN_FILE)['Version'] ?? null;

        $api_script_url = 'https://www.google.com/recaptcha/api.js';
        $api_script_version = null;

        if ($this->siteKey) {
            $api_script_url = add_query_arg('render', $this->siteKey, $api_script_url);
        }

        wp_register_script(self::API_SCRIPT_HANDLE, $api_script_url, [], $api_script_version);
        wp_register_script(self::SCRIPT_HANDLE, $script_url, [self::API_SCRIPT_HANDLE], $script_version);
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueueScripts(): void
    {
        wp_enqueue_script(self::SCRIPT_HANDLE);
    }

    /**
     * Sanitize site key
     *
     * @param string|null $key
     * @return string|null
     */
    protected static function sanitizeSiteKey(string $key = null): ?string
    {
        if ($key) {
            return $key;
        }

        if (defined('RECAPTCHA_3_SITE_KEY') && RECAPTCHA_3_SITE_KEY) {
            return RECAPTCHA_3_SITE_KEY;
        }

        return null;
    }

    /**
     * Sanitize secret key
     *
     * @param string|null $key
     * @return string|null
     */
    protected static function sanitizeSecretKey(string $key = null): ?string
    {
        if ($key) {
            return $key;
        }

        if (defined('RECAPTCHA_3_SECRET_KEY') && RECAPTCHA_3_SECRET_KEY) {
            return RECAPTCHA_3_SECRET_KEY;
        }

        return null;
    }

    /**
     * Add form to list of forms to validate with ReCaptcha 3
     *
     * ReCaptcha 3 validation happens in the background while the user interacts
     * with the page. This method is used to assemble a list of forms and their
     * associated site keys that should be validated against the ReCaptcha API
     * response.
     *
     * @param string $form_id
     * @return void
     */
    public function addForm(string $form_id): void
    {
        if (!isset(self::$forms[$this->siteKey]) || !is_array(self::$forms[$this->siteKey])) {
            self::$forms[$this->siteKey] = [];
        }

        self::$forms[$this->siteKey][] = $form_id;
        self::$forms[$this->siteKey] = array_unique(self::$forms[$this->siteKey]);

        sort(self::$forms[$this->siteKey]);
        ksort(self::$forms);
    }

    /**
     * Embed list of forms
     *
     * This method adds the list of forms and their associated site keys to the
     * page as embedded JavaScript, which can be read by the plugin's ReCaptcha
     * 3 JavaScript implementation.
     *
     * @return void
     */
    public function embedForms(): void
    {
        wp_localize_script(self::SCRIPT_HANDLE, 'postman_recaptcha_api_v3', [
            'forms' => self::$forms,
        ]);
    }
}

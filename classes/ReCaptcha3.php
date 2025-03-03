<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class ReCaptcha3 extends AbstractCaptcha
{
    public const FIELD_NAME = 'g-recaptcha-response';
    public const API_ENDPOINT_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public const API_SCRIPT_URL = 'https://www.google.com/recaptcha/api.js';
    public const API_SCRIPT_HANDLE = 'postman-recaptcha-api-v3';
    public const PLUGIN_SCRIPT_HANDLE = 'postman-recaptcha-v3';

    public const SITE_KEY_CONSTANTS = [
        'RECAPTCHA_3_SITE_KEY',
        'RECAPTCHA_SITE_KEY',
    ];

    public const SECRET_KEY_CONSTANTS = [
        'RECAPTCHA_3_SECRET_KEY',
        'RECAPTCHA_SECRET_KEY',
    ];

    /**
     * List of form IDs by site key
     *
     * @var array
     */
    private static $forms = [];

    /**
     * Construct
     *
     * @param string|null $siteKey
     * @param string|null $secretKey
     * @return void
     */
    public function __construct(
        public ?string $siteKey = null,
        public ?string $secretKey = null
    ) {
        $this->validateConstants();
        $this->validateKeys();

        // API script URL with site key
        $api_script_url = static::API_SCRIPT_URL;

        if ($this->siteKey) {
            $api_script_url = add_query_arg([
                'render' => $this->siteKey,
            ], $api_script_url);
        }

        // Register API script
        wp_register_script(
            handle: static::API_SCRIPT_HANDLE,
            src: $api_script_url,
            ver: null
        );

        // Register plugin script
        wp_register_script(
            handle: static::PLUGIN_SCRIPT_HANDLE,
            src: rtrim(plugin_dir_url(CGIT_WP_POSTMAN_PLUGIN_FILE), '/') . '/assets/js/recaptcha-v3.js',
            deps: [
                static::API_SCRIPT_HANDLE,
            ],
            ver: CGIT_WP_POSTMAN_VERSION
        );

        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueueScripts(): void
    {
        wp_enqueue_script(static::API_SCRIPT_HANDLE);
        wp_enqueue_script(static::PLUGIN_SCRIPT_HANDLE);
    }

    /**
     * Render field
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '';
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
        $forms = static::$forms;
        $key = $this->siteKey;

        if (!isset($forms[$key]) || !is_array($forms[$key])) {
            $forms[$key] = [];
        }

        $forms[$key][] = $form_id;
        $forms[$key] = array_unique($forms[$key]);

        sort($forms[$key]);
        ksort($forms);

        static::$forms = $forms;
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
        wp_localize_script(static::PLUGIN_SCRIPT_HANDLE, 'postman_recaptcha_api_v3', [
            'forms' => static::$forms,
        ]);
    }
}

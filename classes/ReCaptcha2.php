<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class ReCaptcha2 extends ReCaptcha
{
    /**
     * API script handle
     *
     * @var string
     */
    public const API_SCRIPT_HANDLE = 'postman-recaptcha-api-v2';

    /**
     * Initialization
     *
     * @return void
     */
    protected function init(): void
    {
        wp_register_script(self::API_SCRIPT_HANDLE, 'https://www.google.com/recaptcha/api.js', [], null);
        add_filter('script_loader_tag', [$this, 'setScriptAttributes'], 10, 2);
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueueScripts(): void
    {
        wp_enqueue_script(self::API_SCRIPT_HANDLE);
    }

    /**
     * Set API script attributes
     *
     * Set the async and defer attributes on the API script element when using
     * ReCaptcha version 2.
     *
     * @param string $tag
     * @param string $handle
     * @return string
     */
    public function setScriptAttributes(string $tag, string $handle): string
    {
        if ($handle !== self::API_SCRIPT_HANDLE) {
            return $tag;
        }

        return str_replace('<script ', '<script async defer ', $tag);
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

        // Fall back to constant
        if (defined('RECAPTCHA_2_SITE_KEY') && RECAPTCHA_2_SITE_KEY) {
            return RECAPTCHA_2_SITE_KEY;
        }

        // Fall back to legacy constant
        if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY) {
            return RECAPTCHA_SITE_KEY;
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

        // Fall back to constant
        if (defined('RECAPTCHA_2_SECRET_KEY') && RECAPTCHA_2_SECRET_KEY) {
            return RECAPTCHA_2_SECRET_KEY;
        }

        // Fall back to legacy constant
        if (defined('RECAPTCHA_SECRET_KEY') && RECAPTCHA_SECRET_KEY) {
            return RECAPTCHA_SECRET_KEY;
        }

        return null;
    }

    /**
     * Render ReCaptcha field
     *
     * The ReCaptcha field must be rendered within the form.
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->active()) {
            return null;
        }

        ob_start();
        include CGIT_WP_POSTMAN_PLUGIN_DIR . '/views/recaptcha-v2.php';
        return ob_get_clean();
    }
}

<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class Turnstile
{
    /**
     * Cloudflare Turnstile field name
     *
     * @var string
     */
    public const FIELD_NAME = 'cf-turnstile-response';

    /**
     * Cloudflare Turnstile Endpoint URL
     *
     * @var string
     */
    public const URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Site key
     *
     * @var string|null
     */
    protected ?string $siteKey = null;

    /**
     * Secret key
     *
     * @var string|null
     */
    protected ?string $secretKey = null;

    /**
     * API script handle
     *
     * @var string
     */
    public const API_SCRIPT_HANDLE = 'postman-turnstile';

    /**
     * Constructor
     *
     * @param string $site_key
     * @param string $secret_key
     */
    public function __construct(?string $site_key = null, ?string $secret_key = null)
    {
        $this->siteKey = self::sanitizeSiteKey($site_key);
        $this->secretKey = self::sanitizeSecretKey($secret_key);

        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        $this->init();
    }

    /**
     * Initialization
     *
     * @return void
     */
    protected function init(): void
    {
        wp_register_script(
            self::API_SCRIPT_HANDLE,
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            [],
            null
        );

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
     * Set the async and defer attributes
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
    protected static function sanitizeSiteKey(?string $key = null): ?string
    {
        if (!is_null($key)) {
            return $key;
        }

        // Fall back to constant
        if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY) {
            return TURNSTILE_SITE_KEY;
        }

        return null;
    }

    /**
     * Sanitize secret key
     *
     * @param string|null $key
     * @return string|null
     */
    protected static function sanitizeSecretKey(?string $key = null): ?string
    {
        if (!is_null($key)) {
            return $key;
        }

        // Fall back to constant
        if (defined('TURNSTILE_SECRET_KEY') && TURNSTILE_SECRET_KEY) {
            return TURNSTILE_SECRET_KEY;
        }


        return null;
    }

    /**
     * Is Cloudflare Turnstile active?
     *
     * @return bool
     */
    final public function active(): bool
    {
        return $this->siteKey && $this->secretKey;
    }

    /**
     * Render Cloudflare Turnstile field
     *
     * The Cloudflare Turnstile field must be rendered within the form.
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->active()) {
            return null;
        }

        ob_start();
        include CGIT_WP_POSTMAN_PLUGIN_DIR . '/views/turnstile.php';
        return ob_get_clean();
    }

    /**
     * Response is valid?
     *
     * @param string $response Turnstile field response to validate.
     * @return bool|null
     */
    final public function validate(string $response): ?bool
    {
        if (!$this->active()) {
            trigger_error('Cloudflare Turnstile not active', E_USER_NOTICE);
            return null;
        }

        $secretKey = $this->secretKey ?? '';

        $url = self::URL;
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => self::getIp(),
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $raw_response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($raw_response, true);

        return isset($result['success']) && $result['success'];
    }

    /**
     * Get IP address
     *
     * @return mixed
     */
    public static function getIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // IP from shared internet
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // IP passed from proxy
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        // Regular IP address
        return $_SERVER['REMOTE_ADDR'];
    }
}

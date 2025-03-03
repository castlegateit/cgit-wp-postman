<?php

declare(strict_types=1);

namespace Castlegate\Postman;

abstract class AbstractCaptcha
{
    public const FIELD_NAME = null;
    public const API_ENDPOINT_URL = null;
    public const API_SCRIPT_URL = null;
    public const API_SCRIPT_HANDLE = null;

    public const SITE_KEY_CONSTANTS = [];
    public const SECRET_KEY_CONSTANTS = [];

    public const VIEW = null;

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

        wp_register_script(handle: static::API_SCRIPT_HANDLE, src: static::API_SCRIPT_URL, ver: null);

        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_filter('script_loader_tag', [$this, 'setScriptAttributes'], accepted_args: 2);
    }

    /**
     * Validate constants
     *
     * @return void
     */
    protected function validateConstants(): void
    {
        $constants = [
            'FIELD_NAME',
            'API_ENDPOINT_URL',
            'API_SCRIPT_URL',
            'API_SCRIPT_HANDLE',
        ];

        foreach ($constants as $constant) {
            $value = constant('static::' . $constant);

            if (is_string($value) && $value !== '') {
                continue;
            }

            trigger_error("Invalid constant $constant");
        }
    }

    /**
     * Validate keys
     *
     * @return void
     */
    protected function validateKeys(): void
    {
        foreach (['site', 'secret'] as $name) {
            $property = $name . 'Key';
            $fallback = strtoupper($name) . '_KEY_CONSTANTS';

            if (!is_null($this->$property)) {
                continue;
            }

            foreach (constant('static::' . $fallback) as $constant) {
                if (defined($constant)) {
                    $this->$property = constant($constant);
                    break 2;
                }
            }

            trigger_error("Missing $name key");
        }
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    public function enqueueScripts(): void
    {
        wp_enqueue_script(static::API_SCRIPT_HANDLE);
    }

    /**
     * Set script attributes
     *
     * @return void
     */
    public function setScriptAttributes(string $tag, string $handle): string
    {
        if ($handle !== static::API_SCRIPT_HANDLE) {
            return $tag;
        }

        return str_replace('<script ', '<script async defer ', $tag);
    }

    /**
     * Captcha active?
     *
     * @return bool
     */
    public function active(): bool
    {
        return $this->siteKey && $this->secretKey;
    }

    /**
     * Render field
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->active()) {
            return null;
        }

        if (is_string(static::VIEW) && static::VIEW) {
            $path = rtrim(CGIT_WP_POSTMAN_PLUGIN_DIR, '/') . '/' . ltrim(static::VIEW, '/');

            if (is_file($path)) {
                ob_start();
                include $path;
                return ob_get_clean();
            }
        }

        return '';
    }

    /**
     * Response is valid?
     *
     * @param string $response
     * @return bool|null;
     */
    public function validate(string $response): ?bool
    {
        if (!$this->active()) {
            return null;
        }

        $request = new RemoteRequest(static::API_ENDPOINT_URL, [
            'secret' => $this->secretKey,
            'response' => $response,
            'remoteip' => RemoteRequest::getRemoteIp(),
        ]);

        $result = $request->getResult();

        if (is_string($result)) {
            $data = json_decode($result);

            return is_object($data) &&
                property_exists($data, 'success') &&
                $data->success;
        }

        return false;
    }
}

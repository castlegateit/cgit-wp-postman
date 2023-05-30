<?php

declare(strict_types=1);

namespace Castlegate\Postman;

abstract class ReCaptcha
{
    /**
     * ReCaptcha field name
     *
     * @var string
     */
    public const FIELD_NAME = 'g-recaptcha-response';

    /**
     * Site key
     *
     * @var string|null
     */
    protected $siteKey = null;

    /**
     * Secret key
     *
     * @var string|null
     */
    protected $secretKey = null;

    /**
     * Construct
     *
     * @param string|null $site_key
     * @param string|null $secret_key
     * @return void
     */
    final public function __construct(string $site_key = null, string $secret_key = null)
    {
        $this->siteKey = static::sanitizeSiteKey($site_key);
        $this->secretKey = static::sanitizeSecretKey($secret_key);

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
    }

    /**
     * Enqueue scripts
     *
     * @return void
     */
    abstract public function enqueueScripts(): void;

    /**
     * Sanitize site key
     *
     * @param string|null $key
     * @return string|null
     */
    protected static function sanitizeSiteKey(string $key = null): ?string
    {
        return $key;
    }

    /**
     * Sanitize secret key
     *
     * @param string|null $key
     * @return string|null
     */
    protected static function sanitizeSecretKey(string $key = null): ?string
    {
        return $key;
    }

    /**
     * ReCaptcha active?
     *
     * @return bool
     */
    final public function active(): bool
    {
        return $this->siteKey && $this->secretKey;
    }

    /**
     * Response is valid?
     *
     * @param string $response ReCaptcha field response to validate.
     * @return bool|null
     */
    final public function validate(string $response): ?bool
    {
        if (!$this->active()) {
            trigger_error('ReCaptcha not active', E_USER_NOTICE);
            return null;
        }

        $remote_ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $recaptcha = new \ReCaptcha\ReCaptcha($this->secretKey);
        $result = $recaptcha->verify($response, $remote_ip);

        return (bool) $result->isSuccess();
    }
}

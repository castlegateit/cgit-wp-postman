<?php

declare(strict_types=1);

namespace Cgit\Postman;

class ReCaptcha
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
    private $siteKey = null;

    /**
     * Secret key
     *
     * @var string|null
     */
    private $secretKey = null;

    /**
     * Construct
     *
     * @param string|null $site_key Site key.
     * @param string|null $secret_key Secret key.
     * @return void
     */
    public function __construct(
        string $site_key = null,
        string $secret_key = null
    ) {
        if (!is_null($site_key)) {
            $this->setSiteKey($site_key);
        }

        if (!is_null($secret_key)) {
            $this->setSecretKey($secret_key);
        }
    }

    /**
     * Set site key
     *
     * @param string $site_key Site key.
     * @return void
     */
    public function setSiteKey(string $site_key): void
    {
        $this->siteKey = $site_key;
    }

    /**
     * Set secret key
     *
     * @param string $site_key Secret key.
     * @return void
     */
    public function setSecretKey(string $secret_key): void
    {
        $this->secretKey = $secret_key;
    }

    /**
     * ReCaptcha active?
     *
     * @return bool
     */
    public function active(): bool
    {
        return $this->siteKey && $this->secretKey;
    }

    /**
     * Render ReCaptcha
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->active()) {
            return null;
        }

        ob_start();

        ?>

        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        <div class="g-recaptcha" data-sitekey="<?= $this->siteKey ?>"></div>

        <?php

        return ob_get_clean();
    }

    /**
     * Response is valid?
     *
     * @param string $response ReCaptcha field response to validate.
     * @return bool|null
     */
    public function validate(string $response): ?bool
    {
        if (!$this->active()) {
            trigger_error('ReCaptcha not active', E_USER_NOTICE);

            return null;
        }

        $result = $this->request($response);

        return is_object($result) &&
            property_exists($result, 'success') &&
            $result->success;
    }

    /**
     * Return result of API request
     *
     * @param string $response ReCaptcha field response to validate.
     * @return object|null
     */
    private function request(string $response): ?object
    {
        if (!$this->active()) {
            trigger_error('ReCaptcha not active', E_USER_NOTICE);

            return null;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';

        $data = [
            'secret' => $this->secretKey,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, count($data));
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);

        curl_close($curl);

        return json_decode($result);
    }
}

<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class RemoteRequest
{
    /**
     * Remote URL
     *
     * @var string
     */
    private string $url;

    /**
     * POST data to submit to the remote URL
     *
     * @var array
     */
    private array $data = [];

    /**
     * Construct
     *
     * @param string|null $url
     * @param array $data
     * @return void
     */
    public function __construct(?string $url = null, array $data = [])
    {
        if (!is_null($url)) {
            $this->setUrl($url);
        }

        $this->setData($data);
    }

    /**
     * Set remote URL
     *
     * @param string
     * @return void
     */
    public function setUrl(string $url): void
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            trigger_error('Invalid URL', E_USER_ERROR);
        }

        $this->url = $url;
    }

    /**
     * Set POST data to submit to remote URL
     *
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Return the result from the remote URL
     *
     * @return string|null
     */
    public function getResult(): ?string
    {
        $ch = curl_init($this->url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($this->data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->data));
        }

        $result = curl_exec($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if (is_string($result)) {
            return $result;
        }

        error_log($error);

        return null;
    }

    /**
     * Return the current remote IP address
     *
     * @return string|null
     */
    public static function getRemoteIp(): ?string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (isset($_SERVER[$key]) && $_SERVER[$key]) {
                return $_SERVER[$key];
            }
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace Cgit\Postman;

class Akismet
{
    /**
     * Akismet parameters
     *
     * Excludes parameters that are loaded or generated at the time the
     * submission is validated.
     *
     * @var array
     */
    private $args = [];

    /**
     * Default Akismet parameters
     *
     * Excludes parameters that are loaded or generated at the time the
     * submission is validated.
     *
     * @var array
     */
    private $defaultArgs = [
        'comment_type' => null,
        'comment_author' => null,
        'comment_author_email' => null,
        'comment_author_url' => null,
        'comment_content' => null,
    ];

    /**
     * Construct
     *
     * @param array $args Akismet validation parameters.
     * @return void
     */
    public function __construct(array $args = [])
    {
        $this->setAkismetArgs($args);

        if (!static::active()) {
            trigger_error('Akismet not available', E_USER_NOTICE);
        }

        if (!static::verify()) {
            trigger_error('Akismet key invalid', E_USER_NOTICE);
        }
    }

    /**
     * Set Akismet validation parameters
     *
     * Invalid parameters and parameters that are loaded or generated at the
     * time the submission is validated are disregarded.
     *
     * @param array $args Akismet validation parameters.
     * @return void
     */
    public function setAkismetArgs(array $args): void
    {
        // Check for invalid Akismet parameters.
        $bad_args = array_diff_key($args, $this->defaultArgs);

        if ($bad_args) {
            $bad_args_text = implode(', ', array_keys($bad_args));

            trigger_error(
                "Unknown Akismet parameter(s) $bad_args_text",
                E_USER_NOTICE
            );
        }

        // Sanitize Akismet parameters for submission.
        $args = array_filter($args);
        $args = array_intersect_key($args, $this->defaultArgs);
        $args = array_merge($this->args, $args);

        $this->args = array_merge($this->defaultArgs, $args);
    }

    /**
     * Get Akismet validation parameters
     *
     * @param bool $saved Restrict to manual, saved values.
     * @return array
     */
    public function getAkismetArgs(bool $saved = false): array
    {
        if ($saved) {
            return $this->args;
        }

        $args = array_merge($this->args, [
            'blog' => get_option('home'),
            'user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'blog_lang' => get_option('WPLANG'),
            'blog_charset' => get_option('blog_charset'),
        ]);

        if (is_page() || is_single()) {
            $args['permalink'] = get_permalink();
        }

        return array_filter($args);
    }

    /**
     * Validate form data with Akismet
     *
     * Return true if the form submission looks valid; return false if the form
     * submission looks like spam. (Aways returns true if the Akismet plugin is
     * disabled or unregistered.)
     *
     * See <https://akismet.com/development/api/>.
     *
     * @return bool
     */
    public function validate(): bool
    {
        // Akismet plugin disabled? Skip spam check.
        if (!static::active()) {
            return true;
        }

        $host = static::getApiKey() . '.rest.akismet.com';
        $path = '/1.1/comment-check';
        $args = $this->getAkismetArgs();

        $response = static::request($host, $path, $args);

        // Request failed? Skip spam check.
        if (is_null($response)) {
            return true;
        }

        return $response !== 'true';
    }

    /**
     * Akismet plugin active and registered?
     *
     * @return bool
     */
    public static function active(): bool
    {
        return class_exists('\\Akismet') && \Akismet::get_api_key();
    }

    /**
     * Return Akismet API key
     *
     * @return string|null
     */
    public static function getApiKey(): ?string
    {
        if (static::active()) {
            return \Akismet::get_api_key();
        }

        return null;
    }

    /**
     * Verify Akismet key
     *
     * Return true if the the Akismet plugin is active, the Akismet API key is
     * set, and the Akismet key has been verified on the remote server.
     *
     * See <https://akismet.com/development/api/>.
     *
     * @return bool
     */
    public static function verify(): bool
    {
        $response = static::request('rest.akismet.com', '/1.1/verify-key', [
            'key' => static::getApiKey(),
            'blog' => get_option('home'),
        ]);

        return $response === 'valid';
    }

    /**
     * Perform API request and return response
     *
     * @param string $host HTTP host.
     * @param string $path Request path.
     * @param array $data Request data.
     * @return string|null Request response.
     */
    public static function request(
        string $host,
        string $path,
        array $data = []
    ): ?string {
        if (!static::active()) {
            return null;
        }

        $request = http_build_query($data);
        $port = 443;
        $content_type = 'application/x-www-form-urlencoded';
        $content_length = strlen($request);
        $user_agent = static::getUserAgent();

        $http_request_lines = [
            "POST $path HTTP/1.0",
            "Host: $host",
            "Content-Type: $content_type",
            "Content-Length: $content_length",
            "User-Agent: $user_agent",
            '',
            $request,
        ];

        $eol = "\r\n";
        $response = '';
        $socket = fsockopen("ssl://$host", $port, $error_code, $error_message, 10);

        if (!$socket) {
            trigger_error('Akismet API connection failed', E_USER_NOTICE);

            return null;
        }

        fwrite($socket, implode($eol, $http_request_lines));

        while (!feof($socket)) {
            $response .= fgets($socket, 1160);
        }

        fclose($socket);

        $response_lines = explode(str_repeat($eol, 2), $response, 2);

        return $response_lines[1];
    }

    /**
     * Return plugin user agent
     *
     * @return string
     */
    public static function getUserAgent(): string
    {
        global $wp_version;

        $plugin_data = get_plugin_data(CGIT_POSTMAN_PLUGIN);
        $plugin_version = $plugin_data['Version'] ?? '3.0.0';

        return "WordPress/$wp_version | Castlegate Postman/$plugin_version";
    }
}

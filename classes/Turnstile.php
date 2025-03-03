<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class Turnstile extends AbstractCaptcha
{
    public const FIELD_NAME = 'cf-turnstile-response';
    public const API_ENDPOINT_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    public const API_SCRIPT_URL = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
    public const API_SCRIPT_HANDLE = 'postman-turnstile-api';

    public const SITE_KEY_CONSTANTS = [
        'TURNSTILE_SITE_KEY',
    ];

    public const SECRET_KEY_CONSTANTS = [
        'TURNSTILE_SECRET_KEY',
    ];

    public const VIEW = 'views/turnstile.php';
}

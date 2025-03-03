<?php

declare(strict_types=1);

namespace Castlegate\Postman;

class ReCaptcha2 extends AbstractCaptcha
{
    public const FIELD_NAME = 'g-recaptcha-response';
    public const API_ENDPOINT_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public const API_SCRIPT_URL = 'https://www.google.com/recaptcha/api.js';
    public const API_SCRIPT_HANDLE = 'postman-recaptcha-api-v2';

    public const SITE_KEY_CONSTANTS = [
        'RECAPTCHA_2_SITE_KEY',
        'RECAPTCHA_SITE_KEY',
    ];

    public const SECRET_KEY_CONSTANTS = [
        'RECAPTCHA_2_SECRET_KEY',
        'RECAPTCHA_SECRET_KEY',
    ];

    public const VIEW = 'views/recaptcha-v2.php';
}

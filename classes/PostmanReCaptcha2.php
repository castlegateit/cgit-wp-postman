<?php

declare(strict_types=1);

namespace Castlegate\Postman;

trait PostmanReCaptcha2
{
    /**
     * ReCaptcha 2 class instance
     *
     * @var ReCaptcha2|null
     */
    private ?ReCaptcha2 $recaptcha2 = null;

    /**
     * ReCaptcha 2 error message
     *
     * @var string
     */
    private string $recaptcha2ErrorMessage = 'Please confirm you are not a robot';

    /**
     * Validate ReCaptcha 2 configuration
     *
     * @return void
     */
    private function validateReCaptcha2Conf(): void
    {
        if ($this->recaptcha2 instanceof ReCaptcha2 && !$this->recaptcha2->active()) {
            trigger_error('ReCaptcha enabled but API key missing.', E_USER_ERROR);
        }
    }

    /**
     * Enable ReCaptcha 2
     *
     * @param string|null $site_key
     * @param string|null $secret_key
     * @return void
     */
    public function enableReCaptcha2(?string $site_key = null, ?string $secret_key = null): void
    {
        if ($this->recaptcha2 instanceof ReCaptcha2) {
            trigger_error('ReCaptcha 2 already enabled', E_USER_ERROR);
            return;
        }

        $this->recaptcha2 = new ReCaptcha2($site_key, $secret_key);
    }

    /**
     * Is ReCaptcha 2 enabled?
     *
     * @return bool
     */
    public function hasReCaptcha2(): bool
    {
        return $this->recaptcha2 instanceof ReCaptcha2 &&
            $this->recaptcha2->active();
    }

    /**
     * Render ReCaptcha 2 field
     *
     * @return string|null
     */
    public function renderReCaptcha2(): ?string
    {
        if ($this->recaptcha2 instanceof ReCaptcha2) {
            if ($this->recaptcha2->active()) {
                return $this->recaptcha2->render();
            }

            trigger_error('ReCaptcha keys missing');
        }

        return null;
    }

    /**
     * Validate ReCaptcha 2 response
     *
     * @return void
     */
    public function validateReCaptcha2(): void
    {
        if (!$this->hasReCaptcha2()) {
            return;
        }

        $response = (string) ($_POST[ReCaptcha2::FIELD_NAME] ?? '');

        if ($this->recaptcha2->validate($response)) {
            return;
        }

        $this->errors[ReCaptcha2::FIELD_NAME] = $this->recaptcha2ErrorMessage;
    }
}

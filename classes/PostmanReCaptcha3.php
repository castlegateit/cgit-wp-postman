<?php

declare(strict_types=1);

namespace Castlegate\Postman;

trait PostmanReCaptcha3
{
    /**
     * ReCaptcha 3 class instance
     *
     * @var ReCaptcha3|null
     */
    private ?ReCaptcha3 $recaptcha3 = null;

    /**
     * ReCaptcha 3 error message
     *
     * @var string
     */
    private string $recaptcha3ErrorMessage = 'An issue occurred during the validation process. Please try again.';

    /**
     * Validate ReCaptcha 3 configuration
     *
     * @return void
     */
    private function validateReCaptcha3Conf(): void
    {
        if ($this->recaptcha3 instanceof ReCaptcha3 && !$this->recaptcha3->active()) {
            trigger_error('ReCaptcha enabled but API key missing.', E_USER_ERROR);
        }
    }

    /**
     * Enable ReCaptcha 3
     *
     * @param string|null $site_key
     * @param string|null $secret_key
     * @return void
     */
    public function enableReCaptcha3(?string $site_key = null, ?string $secret_key = null): void
    {
        if ($this->recaptcha3 instanceof ReCaptcha3) {
            trigger_error('ReCaptcha 3 already enabled', E_USER_ERROR);
            return;
        }

        $this->recaptcha3 = new ReCaptcha3($site_key, $secret_key);

        $this->recaptcha3->addForm($this->id);
        $this->recaptcha3->embedForms();
    }

    /**
     * Is ReCaptcha 3 enabled?
     *
     * @return bool
     */
    public function hasReCaptcha3(): bool
    {
        return $this->recaptcha3 instanceof ReCaptcha3 &&
            $this->recaptcha3->active();
    }

    /**
     * Validate ReCaptcha 3 response
     *
     * @return void
     */
    public function validateReCaptcha3(): void
    {
        if (!$this->hasReCaptcha3()) {
            return;
        }

        $response = (string) ($_POST[ReCaptcha3::FIELD_NAME] ?? '');

        if ($this->recaptcha3->validate($response)) {
            return;
        }

        $this->errors[ReCaptcha3::FIELD_NAME] = $this->recaptcha3ErrorMessage;
    }
}

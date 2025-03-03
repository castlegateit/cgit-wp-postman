<?php

declare(strict_types=1);

namespace Castlegate\Postman;

trait PostmanTurnstile
{
    /**
     * Turnstile class instance
     *
     * @var Turnstile|null
     */
    private ?Turnstile $turnstile = null;

    /**
     * Turnstile error message
     *
     * @var string
     */
    private string $turnstileErrorMessage = 'An issue occurred during the validation process. Please try again.';

    /**
     * Validate Turnstile configuration
     *
     * @return void
     */
    private function validateTurnstileConf(): void
    {
        if ($this->turnstile instanceof Turnstile && !$this->turnstile->active()) {
            trigger_error('Turnstile enabled but API key missing.', E_USER_ERROR);
        }
    }

    /**
     * Enable Turnstile
     *
     * @param string|null $site_key
     * @param string|null $secret_key
     * @return void
     */
    public function enableTurnstile(?string $site_key = null, ?string $secret_key = null): void
    {
        if ($this->turnstile instanceof Turnstile) {
            trigger_error('Turnstile already enabled', E_USER_ERROR);
            return;
        }

        $this->turnstile = new Turnstile($site_key, $secret_key);
    }

    /**
     * Is Turnstile enabled?
     *
     * @return bool
     */
    public function hasTurnstile(): bool
    {
        return $this->turnstile instanceof Turnstile &&
            $this->turnstile->active();
    }

    /**
     * Render Turnstile field
     *
     * @return string|null
     */
    public function renderTurnstile(): ?string
    {
        if ($this->turnstile instanceof Turnstile) {
            if ($this->turnstile->active()) {
                return $this->turnstile->render();
            }

            trigger_error('Turnstile keys missing');
        }

        return null;
    }

    /**
     * Validate Turnstile response
     *
     * @return void
     */
    public function validateTurnstile(): void
    {
        if (!$this->hasTurnstile()) {
            return;
        }

        $response = (string) ($_POST[Turnstile::FIELD_NAME] ?? '');

        if ($this->turnstile->validate($response)) {
            return;
        }

        $this->errors[Turnstile::FIELD_NAME] = $this->turnstileErrorMessage;
    }
}

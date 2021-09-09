<?php

namespace Cgit\Postman;

use Exception;

/**
 * Captcha handler class
 */
class Captcha
{

    /**
     * Captcha code
     *
     * @var string
     */
    public $captcha;

    /**
     * Captcha public key
     *
     * @var string
     */
    public $public_key;

    /**
     * Captcha private key
     *
     * @var string
     */
    private $private_key;

    /**
     * Why the Captcha failed to submit
     *
     * @var string
     */
    public $explainFailure;

    /**
     * Captcha payload
     *
     * @var string
     */
    public $payload;

    /**
    * Register the captcha field.
    *
    * If any of the required data is inaccessible, then return a useful error.
    * Otherwise, return the completed payload.
    * @param string $id The response post key.
    */

    public function registerCaptcha($id = 'g-recaptcha-response')
    {
        echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
        try {
            if (!defined('RECAPTCHA_SECRET_KEY') || !defined('RECAPTCHA_SITE_KEY')) {
                throw new Exception("Either the Public or Private keys are not defined. Please define RECAPTCHA_SECRET_KEY and RECAPTCHA_SITE_KEY.'");
            }

            $this->public_key = RECAPTCHA_SITE_KEY;
            $this->private_key = RECAPTCHA_SECRET_KEY;
        } catch (Exception $exception) {
            $this->explainFailure = $exception->getMessage();
            return false;
        }
    }

    /**
    * Build the payload to send to the API
    *
    * If any of the required data is inaccessible, then return a useful error.
    * Otherwise, return the completed payload.
    * @param mixed $submission The submissed captcha data.
    */

    private function buildPayload($submission)
    {
        if (empty($submission)) {
            throw new Exception("No Captcha data was found in the form submission. Please make sure you fill in the Captcha.");
        }

        if (empty($this->private_key)) {
            throw new Exception("No valid secret key was set for this Captcha. Please provide your secret key.");
        }

        $payload = array(
            'secret' => $this->private_key,
            'response' => $submission,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        );

        return $payload;
    }

    /**
     * Examine the response for error codes
     *
     * Returns a human-readable error if an error code is found in the response,
     * otherwise return a success response.
     * @param string $args The response from the API endpoint.
     * @return mixed
     */

    private function examineResponse($response)
    {
        if (empty($response)) {
            throw new Exception("No response was received. Please check that your API endpoint exists and the server can reach it.");
        }

        $response = json_decode($response);

        $hostname = parse_url(get_home_url(), PHP_URL_HOST);
        if (!isset($response->hostname) || $response->hostname !== $hostname) {
            throw new Exception("Captcha error: the hostname does not match.");
            return false;
        }

        if ($response->success) {
            return $response->success;
        }


        if (isset($response->{'error-codes'}) && $response->{'error-codes'}) {
            switch ($response->{'error-codes'}) {
                case 'missing-input-secret':
                    throw new Exception("The secret key parameter is missing.");
                    break;
                case 'invalid-input-secret':
                    throw new Exception("The secret key parameter is invalid or malformed.");
                    break;
                case 'missing-input-response':
                    throw new Exception("There was no response, or the data is missing.");
                    break;
                case 'invalid-input-response':
                    throw new Exception("The response was received, but is invalid or malformed.");
                    break;
                case 'bad-request':
                    throw new Exception("The request that was made was invalid or malformed.");
                    break;
                default:
                    throw new Exception("There was an error with the request, but the error code isn't recognised.");
                    break;
            }
        }
    }

    /**
     * Validate the recaptcha
     *
     * Attempts to validate the captcha by sending the payload to the API endpoint.
     * If the validation fails, then the method will return a useful error. If the
     * validation succeeds, the method will return true.
     * @param string $args The recaptcha data submitted by the form.
     * @return mixed
     */

    public function submitRecaptcha($submission, $data)
    {
        try {
            $payload = $this->buildPayload($submission);

            // Construct the request to the site verification API.

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($ch, CURLOPT_POST, count($payload));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // We should not do this.

            // Execute the submission.
            $result = curl_exec($ch);

            // Close the connection.
            curl_close($ch);

            $response = $this->examineResponse($result);

            return $response;
        } catch (Exception $exception) {
            $this->explainFailure = $exception->getMessage();
            die($exception->getMessage());
            return false;
        }

        return false;
    }
}

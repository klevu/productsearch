<?php

namespace Klevu\Search\Model\Api\Response;

use Klevu\Search\Model\Api\Response;

/**
 * @method setErrors($errors)
 */
class Invalid extends Response
{

    public function _construct()
    {
        $this->successful = false;
    }

    /**
     * Return the array of errors.
     *
     * @return array
     */
    public function getErrors()
    {
        $errors = $this->getData('errors');

        if (!$errors) {
            $errors = [];
        }

        if (!is_array($errors)) {
            $errors = [$errors];
        }

        return $errors;
    }

    /**
     * Return the response message.
     *
     * @return string
     */
    public function getMessage()
    {
        $message = "Invalid request";
        $errors = $this->getErrors();
        if (!empty($errors)) {
            $message = sprintf("%s: %s", $message, implode(", ", $errors));
        }

        return $message;
    }

    /**
     * Override the parse response method, this API response is doesn't use HTTP.
     *
     * @param mixed $response
     *
     * @return $this
     */
    protected function parseRawResponse($response)
    {
        // Do nothing
        return $this;
    }
}

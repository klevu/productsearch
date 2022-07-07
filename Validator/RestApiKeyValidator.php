<?php

namespace Klevu\Search\Validator;

use Magento\Framework\Validator\AbstractValidator;

class RestApiKeyValidator extends AbstractValidator
{
    const API_KEY_MIN_LENGTH = 10;

    /**
     * @param $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        return $this->isApiKeySet($value) &&
            $this->isApiKeyString($value) &&
            $this->isApiKeyCorrectLength($value);
    }

    /**
     * @param $apiKey
     *
     * @return bool
     */
    private function isApiKeySet($apiKey)
    {
        if (!isset($apiKey)) {
            $this->_addMessages([__('Rest API Key not provided')]);

            return false;
        }

        return true;
    }

    /**
     * @param $apiKey
     *
     * @return bool
     */
    private function isApiKeyString($apiKey)
    {
        if (!is_string($apiKey)) {
            $this->_addMessages([
                __(
                    'Rest API Key must be a string. Received "%1".',
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                    gettype($apiKey)
                )
            ]);

            return false;
        }

        return true;
    }

    /**
     * @param $apiKey
     *
     * @return bool
     */
    private function isApiKeyCorrectLength($apiKey)
    {
        if (strlen(trim($apiKey)) < self::API_KEY_MIN_LENGTH) {
            $this->_addMessages([__('Rest API Key must be at least %1 characters', static::API_KEY_MIN_LENGTH)]);

            return false;
        }

        return true;
    }
}

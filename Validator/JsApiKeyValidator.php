<?php

namespace Klevu\Search\Validator;

use Magento\Framework\Validator\AbstractValidator;

class JsApiKeyValidator extends AbstractValidator
{
    const JS_API_KEY_BEGINS = 'klevu-';

    /**
     * @param $value
     *
     * @return bool
     */
    public function isValid($value)
    {
        return $this->isApiKeySet($value) &&
            $this->isApiKeyString($value) &&
            $this->isApiKeyBeginningCorrect($value);
    }

    /**
     * @param $apiKey
     *
     * @return bool
     */
    private function isApiKeySet($apiKey)
    {
        if (!isset($apiKey)) {
            $this->_addMessages([__('JS API Key not provided')]);

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
                    'JS API Key must be a string. Received "%1".',
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
    private function isApiKeyBeginningCorrect($apiKey)
    {
        if (0 !== strpos($apiKey, static::JS_API_KEY_BEGINS)) {
            $this->_addMessages([__('JS API Key must begin with %1 ', static::JS_API_KEY_BEGINS)]);

            return false;
        }

        return true;
    }
}

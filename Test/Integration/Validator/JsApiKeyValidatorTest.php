<?php

namespace Klevu\Search\Test\Integration\Validator;

use Klevu\Search\Validator\JsApiKeyValidator;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class JsApiKeyValidatorTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @dataProvider validApiKeys
     */
    public function testIsValidReturnsTrueWhenKeyIsValid($apiKey)
    {
        $this->setUpPhp5();
        $validator = $this->objectManager->get(JsApiKeyValidator::class);

        $this->assertTrue($validator->isValid($apiKey));
    }

    /**
     * @dataProvider invalidApiKeys
     */
    public function testIsValidReturnsFalseWhenApiKeyIsInValid($apiKey)
    {
        $this->setUpPhp5();
        $validator = $this->objectManager->get(JsApiKeyValidator::class);

        $this->assertFalse($validator->isValid($apiKey));
    }

    /**
     * @return array
     */
    public function validApiKeys()
    {
        return [
            ['js_api_key' => JsApiKeyValidator::JS_API_KEY_BEGINS . '293857239857'],
            ['js_api_key' => JsApiKeyValidator::JS_API_KEY_BEGINS . 'sdkfwiufnwioufen'],
            ['js_api_key' => JsApiKeyValidator::JS_API_KEY_BEGINS . '<(*&@£(?'],
            ['js_api_key' => JsApiKeyValidator::JS_API_KEY_BEGINS]
        ];
    }

    /**
     * @return array
     */
    public function invalidApiKeys()
    {
        return [
            ['js_api_key' => null],
            ['js_api_key' => 0],
            ['js_api_key' => 123],
            ['js_api_key' => 'incorrect-format'],
            ['js_api_key' => ['array is not valid']],
            ['js_api_key' => 'x' . JsApiKeyValidator::JS_API_KEY_BEGINS . 'yz']
        ];
    }

    /**
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }
}

<?php

namespace Klevu\Search\Test\Integration\Validator;

use Klevu\Search\Validator\RestApiKeyValidator;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class RestApiValidatorTest extends TestCase
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
        $validator = $this->objectManager->get(RestApiKeyValidator::class);

        $this->assertTrue($validator->isValid($apiKey));
    }

    /**
     * @dataProvider invalidApiKeys
     */
    public function testIsValidReturnsFalseWhenApiKeyIsInValid($apiKey)
    {
        $this->setUpPhp5();
        $validator = $this->objectManager->get(RestApiKeyValidator::class);

        $this->assertFalse($validator->isValid($apiKey));
    }

    /**
     * @return array
     */
    public function validApiKeys()
    {
        return [
            ['rest_api_key' => '293857239857'],
            ['rest_api_key' => 'sdkfwiufnwioufen'],
            ['rest_api_key' => '<(*&@£(?)SFU(JHE23@IJD']
        ];
    }

    /**
     * @return array
     */
    public function invalidApiKeys()
    {
        return [
            ['rest_api_key' => null],
            ['rest_api_key' => 0],
            ['rest_api_key' => 123],
            ['rest_api_key' => 'too-short'],
            ['rest_api_key' => '   too-short   '],
            ['rest_api_key' => ['array is not valid']]
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

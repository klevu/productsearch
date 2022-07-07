<?php

namespace Klevu\Search\Test\Unit\Service\Account\KlevuApi;

use Klevu\Search\Service\Account\KlevuApi\GetAccountDetails;
use Klevu\Search\Service\Account\Model\AccountDetails;
use PHPUnit\Framework\TestCase;

class GetAccountDetailsApiBehaviourTest extends TestCase
{
    /**
     * @var mixed|null
     */
    private $restApiKey;
    /**
     * @var mixed|null
     */
    private $jsApiKey;
    /**
     * @var mixed|null
     */
    private $restApiUrl;

    public function testReturnsJson()
    {
        $this->setUpPhp5();
        if (!$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        $jsonResponse = $this->callApi();
        json_decode($jsonResponse);

        $this->assertSame(
            \JSON_ERROR_NONE,
            json_last_error(),
            'JSON decode failed: ' . json_last_error_msg() . PHP_EOL . $jsonResponse
        );
    }

    /**
     * @dataProvider  missingApiKeysDataProvider
     */
    public function testReturns400IfHeadersNotSpecified($restApiKey, $jsApiKey)
    {
        $this->setUpPhp5();
        if (!$this->restApiKey || ! $this->jsApiKey || !$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }

        $jsonResponse = $this->callApi($restApiKey, $jsApiKey);
        $response = json_decode($jsonResponse, true);

        $this->assertTrue(is_array($response), 'Is Array');
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_ERROR_STATUS, $response);
        $this->assertSame(400, $response[GetAccountDetails::RESPONSE_ERROR_STATUS]);
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_ERROR_MESSAGE, $response);
        $this->assertnotEmpty($response[GetAccountDetails::RESPONSE_ERROR_MESSAGE]);
    }

    /**
     * @dataProvider invalidApiKeysDataProvider
     */
    public function testReturns401ErrorWhenHeaderValuesAreInvalid($restApiKey, $jsApiKey)
    {
        $this->setUpPhp5();
        if (!$this->restApiKey || ! $this->jsApiKey || !$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }

        $jsonResponse = $this->callApi($restApiKey, $jsApiKey);
        $response = json_decode($jsonResponse, true);

        $this->assertTrue(is_array($response), 'Is Array');
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_ERROR_STATUS, $response);
        $this->assertSame(401, $response[GetAccountDetails::RESPONSE_ERROR_STATUS]);
        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_ERROR_MESSAGE, $response);
        $this->assertnotEmpty($response[GetAccountDetails::RESPONSE_ERROR_MESSAGE]);
    }

    public function testReturns200ResponseForValidAccount()
    {
        $this->setUpPhp5();
        if (!$this->restApiKey || !$this->jsApiKey || !$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }

        $jsonResponse = $this->callApi($this->restApiKey, $this->jsApiKey);
        $response = json_decode($jsonResponse, true);

        if (method_exists($this, 'assertIsArray')) {
            $this->assertIsArray($response);
        } else {
            $this->assertTrue(is_array($response), 'Is Array');
        }
        $this->assertArrayNotHasKey(GetAccountDetails::RESPONSE_ERROR_ERROR, $response);
        $this->assertArrayNotHasKey(GetAccountDetails::RESPONSE_ERROR_STATUS, $response);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_ACTIVE, $response);
        $this->assertTrue($response[GetAccountDetails::RESPONSE_SUCCESS_ACTIVE], 'Account is Active');

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_PLATFORM, $response);
        $this->assertSame(AccountDetails::PLATFORM_MAGENTO, $response[GetAccountDetails::RESPONSE_SUCCESS_PLATFORM]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_COMPANY_NAME, $response);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_EMAIL, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_EMAIL]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_URL_ANALYTICS]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_URL_CAT_NAV]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_URL_INDEXING]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_JS, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_URL_JS]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_URL_SEARCH]);

        $this->assertArrayHasKey(GetAccountDetails::RESPONSE_SUCCESS_URL_TIERS, $response);
        $this->assertNotEmpty($response[GetAccountDetails::RESPONSE_SUCCESS_URL_TIERS]);
    }

    /**
     * @return array
     */
    public function invalidApiKeysDataProvider()
    {
        $this->setUpPhp5();

        return [
            [$this->restApiKey, 'some-string'],
            [$this->restApiKey, 12873617824],
            ['this is not a valid key *&Y*&@', $this->jsApiKey],
            [1234, $this->jsApiKey],
            [$this->jsApiKey, $this->restApiKey],
        ];
    }

    /**
     * @return array
     */
    public function missingApiKeysDataProvider()
    {
        $this->setUpPhp5();

        return [
            [$this->restApiKey, null],
            [$this->restApiKey, ''],
            [null, $this->jsApiKey],
            ['', $this->jsApiKey],
            [null, null]
        ];
    }

    /**
     * @param string|null $restApiKey
     * @param string|null $jsApiKey
     *
     * @return bool|string
     */
    private function callApi($restApiKey = null, $jsApiKey = null)
    {
        $headers = (!$restApiKey && !$jsApiKey) ? [] : [
            'X-KLEVU-RESTAPIKEY: ' . $restApiKey,
            'X-KLEVU-JSAPIKEY: ' . $jsApiKey,
        ];

        $curlHandle = curl_init();
        curl_setopt($curlHandle, \CURLOPT_URL, 'https://' . $this->restApiUrl . GetAccountDetails::ENDPOINT);
        curl_setopt($curlHandle, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, \CURLOPT_HTTPHEADER, $headers);
        $jsonResponse = curl_exec($curlHandle);
        curl_close($curlHandle);

        return $jsonResponse;
    }

    /**
     * @return void
     * @todo remove once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        /**
         * This test requires your Klevu API keys
         * These API keys can be set in dev/tests/unit/phpunit.xml
         * <phpunit>
         *     <testsuites>
         *      ...
         *     </testsuites>
         *     <php>
         *         ...
         *         <env name="KLEVU_JS_API_KEY" value="" force="true" />
         *         <env name="KLEVU_REST_API_KEY" value="" force="true" />
         *         <env name="KLEVU_API_REST_URL" value="https://api-qa.ksearchnet.com" force="true" />
         *     </php>
         */
        $this->restApiKey = isset($_ENV['KLEVU_REST_API_KEY']) ? $_ENV['KLEVU_REST_API_KEY'] : null;
        $this->jsApiKey = isset($_ENV['KLEVU_JS_API_KEY']) ? $_ENV['KLEVU_JS_API_KEY'] : null;
        $this->restApiUrl = isset($_ENV['KLEVU_API_REST_URL']) ? $_ENV['KLEVU_API_REST_URL'] : null;
    }
}

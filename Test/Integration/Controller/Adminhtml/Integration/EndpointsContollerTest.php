<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\Integration;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation disabled
 */
class EndpointsContollerTest extends AbstractIntegrationControllerTestCase
{
    /**
     * @var string
     */
    protected $uri = '/klevu_search/integration/endpoints';
    /**
     * @var string
     */
    protected $resource = 'Klevu_Search::integration';
    /**
     * @var string
     */
    protected $httpMethod = 'POST';

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testCanBeCalled()
    {
        $this->setUpPhp5();
        if (!$this->restApiKey || !$this->jsApiKey || !$this->restApiUrl) {
            $this->markTestSkipped('Klevu API keys are not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        $this->createRequest();

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $response = $this->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        $expected = 'Success: Klevu Endpoints Updated';
        $actual = $response->getReasonPhrase();
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString($expected, $actual);
        } else {
            $this->assertContains($expected, $actual);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @dataProvider invalidApiKeysDataProvider
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testControllerReturns400ErrorIfValidationFails($jsApiKey, $restApiKey)
    {
        $this->setUpPhp5();
        if (!$this->restApiUrl) {
            $this->markTestSkipped('Klevu API URL is not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        $this->createRequest($jsApiKey, $restApiKey);

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $response = $this->getResponse();

        $this->assertSame(400, $response->getStatusCode());
        $this->assertNoneLocalizedExceptionWasNotThrown($response);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @dataProvider missingApiKeysDataProvider
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testControllerReturns401ErrorIfApiKeysMissing($jsApiKey, $restApiKey)
    {
        $this->setUpPhp5();
        if (!$this->restApiUrl) {
            $this->markTestSkipped('Klevu API URL is not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        $this->createRequest($jsApiKey, $restApiKey);

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $response = $this->getResponse();

        $this->assertSame(401, $response->getStatusCode());
        $this->assertNoneLocalizedExceptionWasNotThrown($response);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testReturns404ErrorForIncorrectStore()
    {
        $this->setUpPhp5();
        if (!$this->restApiUrl) {
            $this->markTestSkipped('Klevu API URL is not set in `dev/tests/unit/phpunit.xml`. Test Skipped');
        }
        $this->createRequest($this->jsApiKey, $this->restApiKey, 99999999999);

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $content = json_decode($response->getContent(), true);

        $this->assertSame(404, $response->getStatusCode());

        $this->assertArrayHasKey('message', $content);
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('The store that was requested wasn\'t found.', $content['message']);
        } else {
            $this->assertContains('The store that was requested wasn\'t found.', $content['message']);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return array
     */
    public function invalidApiKeysDataProvider()
    {
        return [
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => ['array is not valid']],
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => 'too-short'],
            ['js_api_key' => 'incorrect-format', 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => 123, 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => ['array is not valid'], 'rest_api_key' => 'some-valid-key'],
        ];
    }

    /**
     * @return array
     */
    public function missingApiKeysDataProvider()
    {
        return [
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => 0],
            ['js_api_key' => 'klevu-valid-key', 'rest_api_key' => null],
            ['js_api_key' => null, 'rest_api_key' => 'some-valid-key'],
            ['js_api_key' => 0, 'rest_api_key' => 'some-valid-key'],
        ];
    }
}

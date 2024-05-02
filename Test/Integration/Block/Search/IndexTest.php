<?php

namespace Klevu\Search\Test\Integration\Block\Search;

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class IndexTest extends AbstractControllerTestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     */
    public function testSRLPContentOutput_ThemeV1()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('<div class="kuContainer" id="kuMainContainer">', $responseBody);
            $this->assertStringContainsString('&#x2F;klevu-js-v1&#x2F;js-1-1&#x2F;klevu-landing.js', $responseBody);
        } else {
            $this->assertContains('<div class="kuContainer" id="kuMainContainer">', $responseBody);
            $this->assertContains('&#x2F;klevu-js-v1&#x2F;js-1-1&#x2F;klevu-landing.js', $responseBody);
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testSRLPContentNotOutput_ThemeV2()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('<div class="kuContainer" id="kuMainContainer">', $responseBody);
            $this->assertStringNotContainsString('&#x2F;klevu-js-v1&#x2F;js-1-1&#x2F;klevu-landing.js', $responseBody);
        } else {
            $this->assertNotContains('<div class="kuContainer" id="kuMainContainer">', $responseBody);
            $this->assertNotContains('&#x2F;klevu-js-v1&#x2F;js-1-1&#x2F;klevu-landing.js', $responseBody);
        }
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }
}

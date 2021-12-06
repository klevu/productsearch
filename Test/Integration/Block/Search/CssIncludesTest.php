<?php

namespace Klevu\Search\Test\Integration\Block\Search;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class CssIncludesTest extends AbstractControllerTestCase
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
    public function testThemeV1CssOutputForSearch_ThemeV1()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('css/klevu-landing-page-style.css', $responseBody);
            $this->assertStringContainsString('css/klevu-landing-responsive.css', $responseBody);
        } else {
            $this->assertContains('css/klevu-landing-page-style.css', $responseBody);
            $this->assertContains('css/klevu-landing-responsive.css', $responseBody);
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
    public function testThemeV1CssNotOutputForSearch_ThemeV2()
    {
        $this->setupPhp5();

        $this->dispatch('search/?q=simple');

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('css/klevu-landing-page-style.css', $responseBody);
            $this->assertStringNotContainsString('css/klevu-landing-responsive.css', $responseBody);
        } else {
            $this->assertNotContains('css/klevu-landing-page-style.css', $responseBody);
            $this->assertNotContains('css/klevu-landing-responsive.css', $responseBody);
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

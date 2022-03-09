<?php

namespace Klevu\Search\Test\Integration\Block\ThemeV2;

use Magento\Framework\App\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class PageOutputTest extends AbstractControllerTestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default_store klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default/klevu_search/categorylanding/enabledcategorynavigation 1
     * @magentoConfigFixture default_store klevu_search/categorylanding/enabledcategorynavigation 1
     */
    public function testThemeV2JavaScriptOutputToHomePageWhenConfigured()
    {
        $this->setupPhp5();

        $this->dispatch('/');

        $response = $this->getResponse();
        $this->assertSame(200, $response->getHttpResponseCode());
        $responseBody = $response->getBody();

        // Theme V2
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertStringContainsString(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertStringContainsString(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        } else {
            $this->assertContains(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertContains(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertContains(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression(
                '#var klevu_lang.*js\.klevu\.com/core/v2/klevu\.js#s',
                $responseBody,
                'Klevu Lang JS variable defined before core library include'
            );
        } else {
            $this->assertRegExp(
                '#var klevu_lang.*js\.klevu\.com/core/v2/klevu\.js#s',
                $responseBody,
                'Klevu Lang JS variable defined before core library include'
            );
        }

        // Theme V1
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
        } else {
            $this->assertNotContains(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
        }
    }

    /**
     * @depends testThemeV2JavaScriptOutputToHomePageWhenConfigured
     * @todo Implement testThemeV2JavaScriptOutputToHomePageWhenConfigured_MultiCurrency
     */
//    public function testThemeV2JavaScriptOutputToHomePageWhenConfigured_MultiCurrency()
//    {
//        $this->markTestSkipped('Todo: Multicurrency');
//    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 0
     * @magentoConfigFixture default_store klevu_search/general/enabled 0
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default_store klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default/klevu_search/categorylanding/enabledcategorynavigation 1
     * @magentoConfigFixture default_store klevu_search/categorylanding/enabledcategorynavigation 1
     */
    public function testThemeV2JavaScriptOutputToHomePage_FrontendDisabled()
    {
        $this->setupPhp5();

        $this->dispatch('/');

        $response = $this->getResponse();
        $this->assertSame(200, $response->getHttpResponseCode());
        $responseBody = $response->getBody();

        // Theme V2
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertStringNotContainsString(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        } else {
            $this->assertNotContains(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertNotContains(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        }

        // Theme V1
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
        } else {
            $this->assertNotContains(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/js_api_key
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default_store klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default/klevu_search/categorylanding/enabledcategorynavigation 1
     * @magentoConfigFixture default_store klevu_search/categorylanding/enabledcategorynavigation 1
     */
    public function testThemeV2JavaScriptOutputToHomePage_JsApiKeyMissing()
    {
        $this->setupPhp5();

        $this->dispatch('/');

        $response = $this->getResponse();
        $this->assertSame(200, $response->getHttpResponseCode());
        $responseBody = $response->getBody();

        // Theme V2
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertStringNotContainsString(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        } else {
            $this->assertNotContains(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertNotContains(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        }

        // Theme V1
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
        } else {
            $this->assertNotContains(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
        }
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     * @magentoConfigFixture default/klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default_store klevu_search/recommendations/enabled 0
     * @magentoConfigFixture default/klevu_search/categorylanding/enabledcategorynavigation 1
     * @magentoConfigFixture default_store klevu_search/categorylanding/enabledcategorynavigation 1
     */
    public function testThemeV2JavaScriptOutputToHomePage_ThemeV1()
    {
        $this->setupPhp5();

        $this->dispatch('/');

        $response = $this->getResponse();
        $this->assertSame(200, $response->getHttpResponseCode());
        $responseBody = $response->getBody();

        // Theme V2
        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertStringNotContainsString(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertStringNotContainsString(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        } else {
            $this->assertNotContains(
                '<script type="text/javascript" src="https://js.klevu.com/core/v2/klevu.js"></script>',
                $responseBody,
                'Library JS include is present in response body'
            );
            $this->assertNotContains(
                '<script type="text/javascript" id="klevu_jsinteractive">',
                $responseBody,
                'Initialisation script is present in response body'
            );
            $this->assertNotContains(
                '"url":{"protocol":"https:","landing":"\/search"',
                $responseBody,
                'JS options contain landing page URL'
            );
        }

        // Theme V1
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
            $this->assertStringContainsString(
                "klevu_lang = '",
                $responseBody,
                'Klevu Lang JS variable output'
            );
        } else {
            $this->assertContains(
                'var search_input = allInputs[i];',
                $responseBody,
                'v1 Search Input JS is present in response body'
            );
            $this->assertContains(
                "klevu_lang = '",
                $responseBody,
                'Klevu Lang JS variable output'
            );
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

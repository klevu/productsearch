<?php

namespace Klevu\Search\Test\Integration\Block\Search\Index;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController as AbstractControllerTestCase;

class SyncTest extends AbstractControllerTestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @@magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store klevu_search/general/js_api_key
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     */
    public function testSyncstoreOutput_ThemeV1_NotIntegrated()
    {
        $this->setupPhp5();

        $this->dispatch('search/index/syncstore/store/1/hashkey/' . hash('sha256', 'ABCDE12345'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Manually Data Sync', $responseBody);
            $this->assertStringContainsString('Hash key found invalid for requested store.', $responseBody);
        } else {
            $this->assertContains('Manually Data Sync', $responseBody);
            $this->assertContains('Hash key found invalid for requested store.', $responseBody);
        }

        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        } else {
            $this->assertNotRegExp("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('function callAjaxRecurrsively()', $responseBody);
            $this->assertStringNotContainsString('klevu.interactive', $responseBody);
        } else {
            $this->assertNotContains('function callAjaxRecurrsively()', $responseBody);
            $this->assertNotContains('klevu.interactive', $responseBody);
        }
    }

    /**
     * @@magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     */
    public function testSyncstoreOutput_ThemeV1_Integrated()
    {
        $this->setupPhp5();

        $this->dispatch('search/index/syncstore/store/1/hashkey/' . hash('sha256', 'ABCDE12345'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Manually Data Sync', $responseBody);
            $this->assertStringContainsString('function callAjaxRecurrsively()', $responseBody);
        } else {
            $this->assertContains('Manually Data Sync', $responseBody);
            $this->assertContains('function callAjaxRecurrsively()', $responseBody);
        }

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        } else {
            $this->assertRegExp("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Hash key found invalid for requested store.', $responseBody);
            $this->assertStringNotContainsString('klevu.interactive', $responseBody);
        } else {
            $this->assertNotContains('Hash key found invalid for requested store.', $responseBody);
            $this->assertNotContains('klevu.interactive', $responseBody);
        }
    }

    /**
     * @@magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     */
    public function testSyncstoreOutput_ThemeV1_Integrated_InvalidKey()
    {
        $this->setupPhp5();

        $this->dispatch('search/index/syncstore/store/1/hashkey/' . hash('sha256', 'FGHIJ67890'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Manually Data Sync', $responseBody);
            $this->assertStringContainsString('Hash key found invalid for requested store.', $responseBody);
        } else {
            $this->assertContains('Manually Data Sync', $responseBody);
            $this->assertContains('Hash key found invalid for requested store.', $responseBody);
        }

        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        } else {
            $this->assertNotRegExp("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('function callAjaxRecurrsively()', $responseBody);
            $this->assertStringNotContainsString('klevu.interactive', $responseBody);
        } else {
            $this->assertNotContains('function callAjaxRecurrsively()', $responseBody);
            $this->assertNotContains('klevu.interactive', $responseBody);
        }
    }

    /**
     * @@magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store klevu_search/general/js_api_key
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testSyncstoreOutput_ThemeV2_NotIntegrated()
    {
        $this->setupPhp5();

        $this->dispatch('search/index/syncstore/store/1/hashkey/' . hash('sha256', 'ABCDE12345'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Manually Data Sync', $responseBody);
            $this->assertStringContainsString('Hash key found invalid for requested store.', $responseBody);
        } else {
            $this->assertContains('Manually Data Sync', $responseBody);
            $this->assertContains('Hash key found invalid for requested store.', $responseBody);
        }

        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression("#callAjaxRecurrsively\(\);#m", $responseBody);
        } else {
            $this->assertNotRegExp("#callAjaxRecurrsively\(\);#m", $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('function callAjaxRecurrsively()', $responseBody);
            $this->assertStringNotContainsString('klevu.interactive', $responseBody);
        } else {
            $this->assertNotContains('function callAjaxRecurrsively()', $responseBody);
            $this->assertNotContains('klevu.interactive', $responseBody);
        }
    }

    /**
     * @@magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testSyncstoreOutput_ThemeV2_Integrated()
    {
        $this->setupPhp5();

        $this->dispatch('search/index/syncstore/store/1/hashkey/' . hash('sha256', 'ABCDE12345'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Manually Data Sync', $responseBody);
            $this->assertStringContainsString('function callAjaxRecurrsively()', $responseBody);
        } else {
            $this->assertContains('Manually Data Sync', $responseBody);
            $this->assertContains('function callAjaxRecurrsively()', $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Hash key found invalid for requested store.', $responseBody);
            $this->assertStringNotContainsString('klevu.interactive', $responseBody);
        } else {
            $this->assertNotContains('Hash key found invalid for requested store.', $responseBody);
            $this->assertNotContains('klevu.interactive', $responseBody);
        }
    }

    /**
     * @@magentoAppArea frontend
     * @magentoCache all disabled
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/rest_api_key ABCDE12345
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testSyncstoreOutput_ThemeV2_Integrated_InvalidKey()
    {
        $this->setupPhp5();

        $this->dispatch('search/index/syncstore/store/1/hashkey/' . hash('sha256', 'FGHIJ67890'));

        $response = $this->getResponse();
        $responseBody = $response->getBody();
        $this->assertSame(200, $response->getHttpResponseCode());

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Manually Data Sync', $responseBody);
            $this->assertStringContainsString('Hash key found invalid for requested store.', $responseBody);
        } else {
            $this->assertContains('Manually Data Sync', $responseBody);
            $this->assertContains('Hash key found invalid for requested store.', $responseBody);
        }

        if (method_exists($this, 'assertDoesNotMatchRegularExpression')) {
            $this->assertDoesNotMatchRegularExpression("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        } else {
            $this->assertNotRegExp("#\s*callAjaxRecurrsively\(\);\s*#m", $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('function callAjaxRecurrsively()', $responseBody);
            $this->assertStringNotContainsString('klevu.interactive', $responseBody);
        } else {
            $this->assertNotContains('function callAjaxRecurrsively()', $responseBody);
            $this->assertNotContains('klevu.interactive', $responseBody);
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

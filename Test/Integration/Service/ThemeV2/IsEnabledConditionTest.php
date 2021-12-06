<?php

namespace Klevu\Search\Test\Integration\Service\ThemeV2;

use Klevu\Search\Service\ThemeV2\IsEnabledCondition;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class IsEnabledConditionTest extends TestCase
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
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testExecute_AllConditionsEnabled()
    {
        $this->setupPhp5();

        /** @var IsEnabledCondition $isEnabledCondition */
        $isEnabledCondition = $this->objectManager->create(IsEnabledCondition::class);

        $this->assertTrue($isEnabledCondition->execute(1));
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 0
     * @magentoConfigFixture default_store klevu_search/general/enabled 0
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testExecute_FrontendDisabled()
    {
        $this->setupPhp5();

        /** @var IsEnabledCondition $isEnabledCondition */
        $isEnabledCondition = $this->objectManager->create(IsEnabledCondition::class);

        $this->assertFalse($isEnabledCondition->execute(1));
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/enabled 1
     * @magentoConfigFixture default_store klevu_search/general/js_api_key
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     */
    public function testExecute_JsApiKeyMissing()
    {
        $this->setupPhp5();

        /** @var IsEnabledCondition $isEnabledCondition */
        $isEnabledCondition = $this->objectManager->create(IsEnabledCondition::class);

        $this->assertFalse($isEnabledCondition->execute(1));
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
     * @magentoConfigFixture default/klevu_search/developer/theme_version v1
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v1
     */
    public function testExecute_ThemeV1()
    {
        $this->setupPhp5();

        /** @var IsEnabledCondition $isEnabledCondition */
        $isEnabledCondition = $this->objectManager->create(IsEnabledCondition::class);

        $this->assertFalse($isEnabledCondition->execute(1));
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

<?php

namespace Klevu\Search\Test\Integration\Service\ThemeV2;

use Klevu\Search\Service\ThemeV2\InteractiveOptionsProvider;
use Klevu\Search\Service\ThemeV2\IsEnabledCondition;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class InteractiveOptionsProviderTest extends TestCase
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
     * @magentoConfigFixture default/klevu_search/searchlanding/landenabled 2
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 2
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/quicksearch_selector input#test
     * @magentoConfigFixture default_store klevu_search/developer/quicksearch_selector input#test
     */
    public function testExecuteEnabled_ThemeLayout()
    {
        $this->setupPhp5();

        /** @var InteractiveOptionsProvider $interactiveOptionsProvider */
        $interactiveOptionsProvider = $this->objectManager->create(InteractiveOptionsProvider::class, [
            'isEnabledCondition' => $this->getIsEnabledConditionMock(true)
        ]);

        $expectedResult = [
            'url' => [
                'landing' => '/search',
                'search' => '//eucs999v2.klevu.com/cs/v2/search',
            ],
            'search' => [
                'minChars' => 0,
                'searchBoxSelector' => 'input#test,.kuSearchInput',
                'apiKey' => 'klevu-1234567890',
            ],
            'analytics' => [
                'apiKey' => 'klevu-1234567890',
            ],
        ];

        $this->assertSame($expectedResult, $interactiveOptionsProvider->execute(1));
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 1
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/quicksearch_selector input#test
     * @magentoConfigFixture default_store klevu_search/developer/quicksearch_selector input#test
     */
    public function testExecuteEnabled_PreserveLayout()
    {
        $this->setupPhp5();

        /** @var InteractiveOptionsProvider $interactiveOptionsProvider */
        $interactiveOptionsProvider = $this->objectManager->create(InteractiveOptionsProvider::class, [
            'isEnabledCondition' => $this->getIsEnabledConditionMock(true)
        ]);

        $expectedResult = [
            'url' => [
                'landing' => '/catalogsearch/result',
                'search' => '//eucs999v2.klevu.com/cs/v2/search',
            ],
            'search' => [
                'minChars' => 0,
                'searchBoxSelector' => 'input#test,.kuSearchInput',
                'apiKey' => 'klevu-1234567890',
            ],
            'analytics' => [
                'apiKey' => 'klevu-1234567890',
            ],
        ];

        $this->assertSame($expectedResult, $interactiveOptionsProvider->execute(1));
    }

    /**
     * @magentoAppArea frontend
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoCache all disabled
     * @magentoConfigFixture default/klevu_search/searchlanding/landenabled 2
     * @magentoConfigFixture default_store klevu_search/searchlanding/landenabled 2
     * @magentoConfigFixture default/klevu_search/developer/theme_version v2
     * @magentoConfigFixture default_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture default/klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default_store klevu_search/general/js_api_key klevu-1234567890
     * @magentoConfigFixture default/klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default_store klevu_search/general/cloud_search_v2_url eucs999v2.klevu.com
     * @magentoConfigFixture default/klevu_search/developer/quicksearch_selector input#test
     * @magentoConfigFixture default_store klevu_search/developer/quicksearch_selector input#test
     */
    public function testExecuteDisabled()
    {
        $this->setupPhp5();

        /** @var InteractiveOptionsProvider $interactiveOptionsProvider */
        $interactiveOptionsProvider = $this->objectManager->create(InteractiveOptionsProvider::class, [
            'isEnabledCondition' => $this->getIsEnabledConditionMock(false)
        ]);

        $expectedResult = [];

        $this->assertSame($expectedResult, $interactiveOptionsProvider->execute(1));
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @param bool $executeReturn
     * @return IsEnabledCondition|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getIsEnabledConditionMock($executeReturn)
    {
        if (!method_exists($this, 'createMock')) {
            return $this->getIsEnabledConditionMockLegacy($executeReturn);
        }

        $isEnabledConditionMock = $this->createMock(IsEnabledCondition::class);
        $isEnabledConditionMock->method('execute')->willReturn($executeReturn);

        return $isEnabledConditionMock;
    }

    /**
     * @param bool $executeReturn
     * @return IsEnabledCondition|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getIsEnabledConditionMockLegacy($executeReturn)
    {
        $isEnabledConditionMock = $this->getMockBuilder(IsEnabledCondition::class)
            ->disableOriginalConstructor()
            ->getMock();
        $isEnabledConditionMock->expects($this->any())
            ->method('execute')
            ->willReturn($executeReturn);

        return $isEnabledConditionMock;
    }
}

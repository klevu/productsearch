<?php

namespace Klevu\Search\Test\Integration\Plugin\Search\ViewModel\ConfigProvider;

use Klevu\Search\Plugin\Search\ViewModel\ConfigProviderPlugin;
use Magento\Framework\App\Area;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Search\ViewModel\ConfigProvider;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\App\State as AppAreaState;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class IsSuggestionAllowedTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var string
     */
    private $pluginName = 'Klevu_Search::SearchConfigProviderDisableSuggestionsIfKlevuEnabled';

    public function testTheModuleDoesNotInterceptsCallsToTheConfigProviderInGlobalScope()
    {
        $this->setupPhp5();

        $this->setArea(Area::AREA_GLOBAL);
        $pluginInfo = $this->getSystemConfigFormFieldPluginInfo();
        $this->assertArrayNotHasKey($this->pluginName, $pluginInfo);

        $this->tearDownPhp5();
    }

    public function testTheModuleInterceptsCallsToTheConfigProviderInFrontendScope()
    {
        $this->setupPhp5();

        $this->setArea(Area::AREA_FRONTEND);
        $pluginInfo = $this->getSystemConfigFormFieldPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store catalog/search/search_suggestion_enabled 1
     */
    public function testIsSuggestionAllowedReturnsFalseWhenKlevuSearchFrontendIsEnabled()
    {
        $this->setupPhp5();

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetadata->getVersion(), '2.3.0', '<')) {
            $this->markTestSkipped('ViewModels not available prior to Magento 2.3');
        }
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $configProvider = $this->objectManager->get(ConfigProvider::class);
        $isSuggestionsAllowed = $configProvider->isSuggestionsAllowed();

        $plugin = $this->objectManager->get(ConfigProviderPlugin::class);
        $isSuggestionsAllowed = $plugin->afterIsSuggestionsAllowed($configProvider, $isSuggestionsAllowed);

        $this->assertFalse($isSuggestionsAllowed);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store catalog/search/search_suggestion_enabled 1
     */
    public function testIsSuggestionAllowedReturnsTrueWhenKlevuSearchFrontendIsDisabledAndSuggestionEnable()
    {
        $this->setupPhp5();

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetadata->getVersion(), '2.3.0', '<')) {
            $this->markTestSkipped('ViewModels not available prior to Magento 2.3');
        }
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $configProvider = $this->objectManager->get(ConfigProvider::class);
        $isSuggestionsAllowed = $configProvider->isSuggestionsAllowed();

        $plugin = $this->objectManager->get(ConfigProviderPlugin::class);
        $isSuggestionsAllowed = $plugin->afterIsSuggestionsAllowed($configProvider, $isSuggestionsAllowed);

        $this->assertTrue($isSuggestionsAllowed);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 0
     * @magentoConfigFixture klevu_test_store_1_store catalog/search/search_suggestion_enabled 0
     */
    public function testIsSuggestionAllowedReturnsFalseWhenKlevuSearchFrontendIsDisabledAndSuggestionDisabled()
    {
        $this->setupPhp5();

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetadata->getVersion(), '2.3.0', '<')) {
            $this->markTestSkipped('ViewModels not available prior to Magento 2.3');
        }
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $configProvider = $this->objectManager->get(ConfigProvider::class);
        $isSuggestionsAllowed = $configProvider->isSuggestionsAllowed();

        $plugin = $this->objectManager->get(ConfigProviderPlugin::class);
        $isSuggestionsAllowed = $plugin->afterIsSuggestionsAllowed($configProvider, $isSuggestionsAllowed);

        $this->assertFalse($isSuggestionsAllowed);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store catalog/search/search_suggestion_enabled 0
     */
    public function testIsSuggestionAllowedReturnsFalseWhenKlevuSearchFrontendIsEnabledAndSuggestionDisabled()
    {
        $this->setupPhp5();

        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetadata->getVersion(), '2.3.0', '<')) {
            $this->markTestSkipped('ViewModels not available prior to Magento 2.3');
        }
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $configProvider = $this->objectManager->get(ConfigProvider::class);
        $isSuggestionsAllowed = $configProvider->isSuggestionsAllowed();

        $plugin = $this->objectManager->get(ConfigProviderPlugin::class);
        $isSuggestionsAllowed = $plugin->afterIsSuggestionsAllowed($configProvider, $isSuggestionsAllowed);

        $this->assertFalse($isSuggestionsAllowed);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param string $code
     *
     * @return void
     * @throws LocalizedException
     */
    private function setArea($code)
    {
        /** @var AppAreaState $appArea */
        $appArea = $this->objectManager->get(AppAreaState::class);
        $appArea->setAreaCode($code);
    }

    /**
     * @return array[]
     */
    private function getSystemConfigFormFieldPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(ConfigProvider::class, []);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function getStore($storeCode = 'klevu_test_store_1')
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
    }

    /**
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
    private function setupPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return void
     * @todo Move to tearDown when PHP 5.x is no longer supported
     */
    private function tearDownPhp5()
    {
        $this->setArea(Area::AREA_GLOBAL);
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../_files/websiteFixtures_rollback.php';
    }
}
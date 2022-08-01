<?php

namespace Klevu\Search\Test\Integration\Plugin\Search\Helper;

use Klevu\Search\Plugin\Search\Helper\DataPlugin as SearchHelperPlugin;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\App\State as AppAreaState;
use Magento\TestFramework\Interception\PluginList;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class GetResultUrlTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var string
     */
    private $pluginName = 'Klevu_Search::SearchHelperGetResultUrlIfKlevuEnabled';

    public function testTheModuleDoesNotInterceptsCallsToTheConfigProviderInGlobalScope()
    {
        $this->setupPhp5();

        $this->setArea(Area::AREA_GLOBAL);
        $pluginInfo = $this->getSearchHelperPluginInfo();
        $this->assertArrayNotHasKey($this->pluginName, $pluginInfo);

        $this->tearDownPhp5();
    }

    public function testTheModuleInterceptsCallsToTheConfigProviderInFrontendScope()
    {
        $this->setupPhp5();

        $this->setArea(Area::AREA_FRONTEND);
        $pluginInfo = $this->getSearchHelperPluginInfo();
        $this->assertArrayHasKey($this->pluginName, $pluginInfo);

        $this->tearDownPhp5();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/url/use_store 0
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 0
     */
    public function testReturnsMagentoResultUrlWhenKlevuDisabled()
    {
        $this->setupPhp5();
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $searchHelper = $this->objectManager->get(SearchHelper::class);
        $resultUrl = $searchHelper->getResultUrl();

        $plugin = $this->objectManager->get(SearchHelperPlugin::class);
        $resultUrl = $plugin->afterGetResultUrl($searchHelper, $resultUrl);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('/catalogsearch/result', $resultUrl);
        } else {
            $this->assertContains('/catalogsearch/result', $resultUrl);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/url/use_store 0
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/searchlanding/landenabled 0
     */
    public function testReturnsMagentoResultUrlWhenKlevuEnabledAndLandingDisabled()
    {
        $this->setupPhp5();
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $searchHelper = $this->objectManager->get(SearchHelper::class);
        $resultUrl = $searchHelper->getResultUrl();

        $plugin = $this->objectManager->get(SearchHelperPlugin::class);
        $resultUrl = $plugin->afterGetResultUrl($searchHelper, $resultUrl);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('/catalogsearch/result', $resultUrl);
        } else {
            $this->assertContains('/catalogsearch/result', $resultUrl);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/url/use_store 0
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/searchlanding/landenabled 1
     */
    public function testReturnsMagentoResultUrlWhenKlevuEnabledAndPreserveLayoutEnabled()
    {
        $this->setupPhp5();
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $searchHelper = $this->objectManager->get(SearchHelper::class);
        $resultUrl = $searchHelper->getResultUrl();

        $plugin = $this->objectManager->get(SearchHelperPlugin::class);
        $resultUrl = $plugin->afterGetResultUrl($searchHelper, $resultUrl);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('/catalogsearch/result', $resultUrl);
        } else {
            $this->assertContains('/catalogsearch/result', $resultUrl);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea frontend
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/url/use_store 0
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/enabled 1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v2
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/searchlanding/landenabled 2
     */
    public function testReturnsMagentoResultUrlWhenKlevuEnabledAndKlevuThemeEnabled()
    {
        $this->setupPhp5();
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        $storeManager->setCurrentStore($this->getStore());

        $searchHelper = $this->objectManager->get(SearchHelper::class);
        $resultUrl = $searchHelper->getResultUrl();

        $plugin = $this->objectManager->get(SearchHelperPlugin::class);
        $resultUrl = $plugin->afterGetResultUrl($searchHelper, $resultUrl);

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('catalogsearch/result', $resultUrl);
        } else {
            $this->assertNotContains('catalogsearch/result', $resultUrl);
        }
        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('/search/', $resultUrl);
        } else {
            $this->assertContains('/search/', $resultUrl);
        }

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return array[]
     */
    private function getSearchHelperPluginInfo()
    {
        /** @var PluginList $pluginList */
        $pluginList = $this->objectManager->get(PluginList::class);

        return $pluginList->get(SearchHelper::class, []);
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
        include __DIR__ . '/../../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../_files/websiteFixtures_rollback.php';
    }
}
<?php

namespace Klevu\Search\Test\Integration\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Observer\Backend\SetCloudSearchV2UrlConfigValueObserver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface as ScopeConfigWriterInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class SetCloudSearchV2UrlConfigValueObserverTest extends TestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    private $objectManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var EventManager
     */
    private $eventManager;
    /**
     * @var ScopeConfigWriterInterface
     */
    private $scopeConfigWriter;

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/cloud_search_url eucs26.ksearchnet.com
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/cloud_search_v2_url eucsv2.klevu.com
     */
    public function testObserverSetsSearchV2Url()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $storeId = $store->getId();

        $this->dispatchEvent($storeId);

        $newV2CloudUrl = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $this->assertSame('eucs26v2.ksearchnet.com', $newV2CloudUrl);

        $this->rollBack($storeId);
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoAppArea adminhtml
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/developer/theme_version v1
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/cloud_search_url eucs26.ksearchnet.com
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/cloud_search_v2_url some.test.url
     */
    public function testObserverDoesNotSetSearchV2UrlIfAlreadySet()
    {
        $this->setupPhp5();
        $store = $this->getStore();
        $storeId = $store->getId();

        $this->dispatchEvent($storeId);

        $newV2CloudUrl = $this->scopeConfig->getValue(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
        $this->assertSame('some.test.url', $newV2CloudUrl);

        $this->rollBack($storeId);
        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param $storeId
     *
     * @return void
     */
    private function dispatchEvent($storeId)
    {
        $this->request->setParams([
            'store' => $storeId,
            'section' => SetCloudSearchV2UrlConfigValueObserver::CONFIG_SECTION
        ]);

        $configData = [
            'section' => SetCloudSearchV2UrlConfigValueObserver::CONFIG_SECTION,
            'store' => $storeId,
            'groups' => [
                'developer' => [
                    'fields' => [
                        'theme_version' => ['value' => 'v2']
                    ]
                ]
            ]
        ];

        $this->eventManager->dispatch(
            SetCloudSearchV2UrlConfigValueObserver::EVENT_NAME,
            [
                'configData' => $configData,
                'request' => $this->request
            ]
        );
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

        $this->scopeConfig =  $this->objectManager->get(ScopeConfigInterface::class);

        $this->scopeConfigWriter = $this->objectManager->create(ScopeConfigWriterInterface::class);

        $this->request =  $this->objectManager->get(RequestInterface::class);
        $this->request->setRouteName('adminhtml_system');
        $this->request->setControllerName('config');
        $this->request->setActionName('save');

        $this->eventManager = $this->objectManager->get(EventManager::class);
    }

    /**
     * @param $storeId
     *
     * @return void
     */
    private function rollBack($storeId)
    {
        $this->scopeConfigWriter->delete(
            ConfigHelper::XML_PATH_CLOUD_SEARCH_V2_URL,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * Loads website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../_files/websiteFixtures.php';
    }

    /**
     * Rolls back website creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../_files/websiteFixtures_rollback.php';
    }
}

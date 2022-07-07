<?php

namespace Klevu\Search\Test\Integration\Observer\Backend;

use Klevu\Search\Helper\Config as ConfigHelper;
use Klevu\Search\Service\Account\GetFeatures;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class ResetFeaturesApiTimeStampOnUpdateOfRestApiKeyTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/features_api/last_sync_date 123456789
     */
    public function testDbValueIsReturnedWhenDispatchIsNotCalled()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $newSyncDate = $this->getLastSyncDate($store->getId());
        $this->assertSame('123456789', $newSyncDate);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoAppArea adminhtml
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/features_api/last_sync_date 123456789
     */
    public function testObserverSetsFeaturesApiSyncTimestampOnRestApiKeyUpdate()
    {
        $this->setupPhp5();
        $store = $this->getStore();

        $this->dispatchEvent($store);

        $newSyncDate = $this->getLastSyncDate($store->getId());
        $this->assertSame('0', $newSyncDate);

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    private function getLastSyncDate($storeId)
    {
        /** @var ScopeConfigInterface $scopeConfig */
        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);

        return $scopeConfig->getValue(
            GetFeatures::XML_PATH_FEATURES_LAST_SYNC_DATE,
            ScopeInterface::SCOPE_STORES,
            $storeId
        );
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    private function dispatchEvent(StoreInterface $store)
    {
        $section = 'klevu_search';

        /** @var EventManager $eventManager */
        $eventManager = $this->objectManager->get(EventManager::class);
        $eventManager->dispatch(
            "admin_system_config_changed_section_{$section}",
            [
                'website' => 'base',
                'store' => $store->getCode(),
                'changed_paths' => [ConfigHelper::XML_PATH_REST_API_KEY]
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

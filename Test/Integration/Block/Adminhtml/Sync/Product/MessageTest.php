<?php

namespace Klevu\Search\Test\Integration\Block\Adminhtml\Sync\Product;

use Klevu\Search\Api\Service\Account\IntegrationStatusInterface;
use Klevu\Search\Block\Adminhtml\Sync\Product\Message;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var RequestInterface&MockObject
     */
    private $mockRequest;

    public function testShowStoreLevelWarningReturnsTrueWhenNotInStoreScope()
    {
        $this->setUpPhp5();

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn(null);

        $messageBlock = $this->instantiateSyncProductMessageBlock();
        $showWarning = $messageBlock->showStoreLevelWarning();

        $this->assertTrue($showWarning, 'Show Store Level Warning');
    }

    public function testShowStoreLevelWarningReturnsFalseWhenNotStoreScope()
    {
        $this->setUpPhp5();

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn('1');

        $messageBlock = $this->instantiateSyncProductMessageBlock();
        $showWarning = $messageBlock->showStoreLevelWarning();

        $this->assertFalse($showWarning, 'Show Store Level Warning');
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadWebsiteFixtures
     */
    public function testIsStoreIntegratedReturnsFalseWhenNotIntegrated()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $messageBlock = $this->instantiateSyncProductMessageBlock();
        $isIntegrated = $messageBlock->isStoreIntegrated();

        $this->assertFalse($isIntegrated, 'Is Store Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api_key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api_key
     */
    public function testIsStoreIntegratedReturnsTrueWhenIntegrated()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $messageBlock = $this->instantiateSyncProductMessageBlock();
        $isIntegrated = $messageBlock->isStoreIntegrated();

        $this->assertTrue($isIntegrated, 'Is Store Integrated');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testHasSyncDisabledForStoreReturnsTrueForStoreWithSyncDisabled()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $messageBlock = $this->instantiateSyncProductMessageBlock();
        $syncDisabled = $messageBlock->hasSyncDisabledForStore();

        $this->assertTrue($syncDisabled, 'Sync Disabled for Store');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @magentoDbIsolation enabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testHasSyncDisabledForStoreReturnsFalseForStoreWithSyncEnabled()
    {
        $this->setUpPhp5();
        $store = $this->getStore('klevu_test_store_1');

        $this->mockRequest->expects($this->once())
            ->method('getParam')
            ->with('store')
            ->willReturn($store->getId());

        $messageBlock = $this->instantiateSyncProductMessageBlock();
        $syncDisabled = $messageBlock->hasSyncDisabledForStore();

        $this->assertFalse($syncDisabled, 'Sync Disabled for Store');

        static::loadWebsiteFixturesRollback();
    }

    /**
     * @return void
     * @TODO remove once support for PHP5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->mockRequest = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Message
     */
    private function instantiateSyncProductMessageBlock()
    {
        $context = $this->objectManager->create(Context::class, [
            'request' => $this->mockRequest
        ]);
        $integrationStatus = $this->objectManager->create(IntegrationStatusInterface::class, [
            'request' => $this->mockRequest
        ]);

        return $this->objectManager->create(Message::class, [
            'context' => $context,
            'integrationStatus' => $integrationStatus
        ]);
    }

    /**
     * @param string $storeCode
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    private function getStore($storeCode)
    {
        /** @var StoreRepositoryInterface $storeRepository */
        $storeRepository = $this->objectManager->get(StoreRepositoryInterface::class);

        return $storeRepository->get($storeCode);
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

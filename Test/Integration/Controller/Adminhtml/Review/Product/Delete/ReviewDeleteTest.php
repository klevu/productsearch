<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\Review\Product\Delete;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\AreaList;
use Magento\Framework\Authorization;
use Magento\Framework\AuthorizationInterface;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;
use Psr\Log\LoggerInterface;

class ReviewDeleteTest extends AbstractBackendControllerTestCase
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var int[]
     */
    private $storeIds = [];

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_ApprovedToPending
     * @magentoDataFixture loadReviewFixtures_ApprovedToPending
     */
    public function testDelete_Approved_WithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(60, $productFixtureStore1->getData('rating'));
        $this->assertEquals(2, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Approved To Pending: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());

        $this->dispatch($this->getAdminFrontName() . '/review/product/delete/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_ApprovedToPending
     * @magentoDataFixture loadReviewFixtures_ApprovedToPending
     */
    public function testDelete_Approved_WithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(60, $productFixtureStore1->getData('rating'));
        $this->assertEquals(2, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Approved To Pending: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());

        $this->dispatch($this->getAdminFrontName() . '/review/product/delete/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(60, $productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtopending', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_PendingToApproved
     * @magentoDataFixture loadReviewFixtures_PendingToApproved
     */
    public function testDelete_Pending_WithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingtoapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingtoapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingtoapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Pending To Approved: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());

        $this->dispatch($this->getAdminFrontName() . '/review/product/delete/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingtoapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingtoapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingtoapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_DisapprovedToApproved
     * @magentoDataFixture loadReviewFixtures_DisapprovedToApproved
     */
    public function testDelete_Disapproved_WithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Disapproved To Approved: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());

        $this->dispatch($this->getAdminFrontName() . '/review/product/delete/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));
    }

    public function testAclHasAccess()
    {
        // No tests
    }

    public function testAclNoAccess()
    {
        // No tests
    }

    private function setupPhp5()
    {
        $authorizationMock = $this->getMockBuilder(AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authorizationMock->method('isAllowed')
            ->willReturn(true);
        $this->_objectManager->addSharedInstance($authorizationMock, AuthorizationInterface::class);
        $this->_objectManager->addSharedInstance($authorizationMock, Authorization::class);

        $this->productRepository = $this->_objectManager->get(ProductRepositoryInterface::class);

        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->_objectManager->get(StoreManagerInterface::class);
        foreach ($storeManager->getStores() as $store) {
            $this->storeIds[$store->getCode()] = (int)$store->getId();
        }

        $loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggerMock->expects($this->never())->method('emergency');
        $loggerMock->expects($this->never())->method('critical');
        $loggerMock->expects($this->never())->method('alert');
        $loggerMock->expects($this->never())->method('error');
        $loggerMock->expects($this->never())->method('warning');
        $loggerMock->expects($this->never())->method('notice');
        $loggerMock->expects($this->never())->method('info');
        $loggerMock->expects($this->never())->method('debug');
        $this->_objectManager->addSharedInstance($loggerMock, LoggerInterface::class);
        $this->_objectManager->addSharedInstance($loggerMock, 'Klevu\Search\Logger\Logger\Search');
        $this->_objectManager->addSharedInstance($loggerMock, 'Magento\TestFramework\ErrorLog\Logger');
    }

    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    private function getAdminFrontName()
    {
        /** @var AreaList $areaList */
        $areaList = $this->_objectManager->get(AreaList::class);
        $adminFrontName = $areaList->getFrontName('adminhtml');
        if (!$adminFrontName) {
            /** @var FrontNameResolver $backendFrontNameResolver */
            $backendFrontNameResolver = $this->_objectManager->get(FrontNameResolver::class);
            $adminFrontName = $backendFrontNameResolver->getFrontName(true);
        }

        return (string)$adminFrontName;
    }

    private function getReviewFixture($title)
    {
        /** @var ReviewCollectionFactory $reviewCollectionFactory */
        $reviewCollectionFactory = $this->_objectManager->get(ReviewCollectionFactory::class);
        $reviewCollection = $reviewCollectionFactory->create();
        $reviewCollection->addFieldToFilter('nickname', 'Integration Test Fixture');
        $reviewCollection->addFieldToFilter('title', $title);

        if (!$reviewCollection->getSize()) {
            throw new \LogicException(sprintf(
                'Could not find review fixture with title "%s"',
                $title
            ));
        }

        return $reviewCollection->getFirstItem();
    }

    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures.php';
    }

    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures_rollback.php';
    }

    public static function loadProductFixtures_ApprovedToPending()
    {
        self::loadProductFixtures('ApprovedToPending');
    }

    public static function loadProductFixtures_PendingToApproved()
    {
        self::loadProductFixtures('PendingToApproved');
    }

    public static function loadProductFixtures_DisapprovedToApproved()
    {
        self::loadProductFixtures('DisapprovedToApproved');
    }

    public static function loadProductFixtures($group = null)
    {
        $PRODUCT_FIXTURES_GROUP = $group;

        include __DIR__ . '/../_files/productFixtures.php';
    }

    public static function loadProductFixtures_ApprovedToPendingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_PendingToApprovedRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixturesDisapprovedToApprovedRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_rollback.php';
    }

    public static function loadReviewFixtures_ApprovedToPending()
    {
        self::loadReviewFixtures('ApprovedToPending');
    }

    public static function loadReviewFixtures_PendingToApproved()
    {
        self::loadReviewFixtures('PendingToApproved');
    }

    public static function loadReviewFixtures_DisapprovedToApproved()
    {
        self::loadReviewFixtures('DisapprovedToApproved');
    }

    public static function loadReviewFixtures($group = null)
    {
        $REVIEW_FIXTURES_GROUP = $group;

        include __DIR__ . '/../_files/reviewFixtures.php';
    }

    public static function loadReviewFixtures_ApprovedToPendingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_PendingToApprovedRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_DisapprovedToApprovedRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../_files/reviewFixtures_rollback.php';
    }
}

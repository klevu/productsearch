<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\Review\Product\Edit;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\AreaList;
use Magento\Framework\Authorization;
use Magento\Framework\AuthorizationInterface;
use Magento\Review\Model\Rating\Option;
use Magento\Review\Model\ResourceModel\Rating\CollectionFactory as RatingCollectionFactory;
use Magento\Review\Model\ResourceModel\Rating\Option\CollectionFactory as RatingOptionCollectionFactory;
use Magento\Review\Model\ResourceModel\Review\CollectionFactory as ReviewCollectionFactory;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;
use Psr\Log\LoggerInterface;

class ReviewUpdateTest extends AbstractBackendControllerTestCase
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
     * @var array[]
     */
    private $ratingIds = [];

    /**
     * @var int[]
     */
    private $ratingOptionIds = [];

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
    public function testUpdateStatus_PendingToApproved_UpdateWithRating()
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
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

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
        $this->assertEquals(80, $productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

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
     * @magentoDataFixture loadProductFixtures_PendingToApproved
     * @magentoDataFixture loadReviewFixtures_PendingToApproved
     */
    public function testUpdateStatus_PendingToApproved_UpdateWithoutRating()
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

        $reviewFixture = $this->getReviewFixture('Pending To Approved: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

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
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

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
    public function testUpdateStatus_DisapprovedToApproved_UpdateWithRating()
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
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

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
        $this->assertEquals(60, $productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, $this->storeIds['klevu_test_store_2']);
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
    public function testUpdateStatus_DisapprovedToApproved_UpdateWithoutRating()
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

        $reviewFixture = $this->getReviewFixture('Disapproved To Approved: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

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
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtoapproved', false, $this->storeIds['klevu_test_store_2']);
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
    public function testUpdateStatus_ApprovedToPending_UpdateWithRating()
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
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_PENDING,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

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
    public function testUpdateStatus_ApprovedToPending_UpdateWithoutRating()
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
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_PENDING,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

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
     * @magentoDataFixture loadProductFixtures_DisapprovedToPending
     * @magentoDataFixture loadReviewFixtures_DisapprovedToPending
     */
    public function testUpdateStatus_DisapprovedToPending_UpdateWithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Disapproved To Pending: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_PENDING,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEquals(0, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_DisapprovedToPending
     * @magentoDataFixture loadReviewFixtures_DisapprovedToPending
     */
    public function testUpdateStatus_DisapprovedToPending_UpdateWithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Disapproved To Pending: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_PENDING,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEquals(0, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedtopending', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_ApprovedToDisapproved
     * @magentoDataFixture loadReviewFixtures_ApprovedToDisapproved
     */
    public function testUpdateStatus_ApprovedToDisapproved_UpdateWithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureGlobal->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(40, $productFixtureStore1->getData('rating'), __LINE__);
        $this->assertEquals(2, $productFixtureStore1->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureStore2->getData('review_count'), __LINE__);

        $reviewFixture = $this->getReviewFixture('Approved To Disapproved: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_NOT_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureGlobal->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'), __LINE__);
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureStore2->getData('review_count'), __LINE__);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_ApprovedToDisapproved
     * @magentoDataFixture loadReviewFixtures_ApprovedToDisapproved
     */
    public function testUpdateStatus_ApprovedToDisapproved_UpdateWithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureGlobal->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(40, $productFixtureStore1->getData('rating'), __LINE__);
        $this->assertEquals(2, $productFixtureStore1->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureStore2->getData('review_count'), __LINE__);

        $reviewFixture = $this->getReviewFixture('Approved To Disapproved: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_NOT_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureGlobal->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(40, $productFixtureStore1->getData('rating'), __LINE__);
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'), __LINE__);

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedtodisapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'), __LINE__);
        $this->assertEmpty($productFixtureStore2->getData('review_count'), __LINE__);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoConfigFixture default/admin/url/use_custom 1
     * @magentoConfigFixture default_store admin/url/use_custom 1
     * @magentoConfigFixture default/admin/url/custom http://localhost/
     * @magentoConfigFixture default_store admin/url/custom http://localhost/
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures_PendingToDisapproved
     * @magentoDataFixture loadReviewFixtures_PendingToDisapproved
     */
    public function testUpdateStatus_PendingToDisapproved_UpdateWithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Pending To Disapproved: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_NOT_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_PendingToDisapproved
     * @magentoDataFixture loadReviewFixtures_PendingToDisapproved
     */
    public function testUpdateStatus_PendingToDisapproved_UpdateWithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Pending To Disapproved: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'status_id' => Review::STATUS_NOT_APPROVED,
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingtodisapproved', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_ApprovedWithRating
     * @magentoDataFixture loadReviewFixtures_ApprovedWithRating
     */
    public function testUpdateRatingValue_Approved_WithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedwithrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(40, $productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithrating', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Approved: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'ratings' => $this->createRatingDataFixture([1, 1, 4]),
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedwithrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(40, $productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithrating', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_PendingWithRating
     * @magentoDataFixture loadReviewFixtures_PendingWithRating
     */
    public function testUpdateRatingValue_Pending_WithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingwithrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithrating', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Pending: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'ratings' => $this->createRatingDataFixture([5, 5, 5]),
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingwithrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithrating', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_DisapprovedWithRating
     * @magentoDataFixture loadReviewFixtures_DisapprovedWithRating
     */
    public function testUpdateRatingValue_Disapproved_WithRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithrating', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Disapproved: With Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'ratings' => $this->createRatingDataFixture([5, 5, 5]),
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithrating', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_ApprovedWithoutRating
     * @magentoDataFixture loadReviewFixtures_ApprovedWithoutRating
     */
    public function testUpdateRatingValue_Approved_WithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedwithoutrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithoutrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithoutrating', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Approved: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'ratings' => $this->createRatingDataFixture([3, 4, 5]),
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_approvedwithoutrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithoutrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertEquals(80, $productFixtureStore1->getData('rating'));
        $this->assertEquals(1, $productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_approvedwithoutrating', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_PendingWithoutRating
     * @magentoDataFixture loadReviewFixtures_PendingWithoutRating
     */
    public function testUpdateRatingValue_Pending_WithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingwithoutrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithoutrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithoutrating', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Pending: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'ratings' => $this->createRatingDataFixture([5, 5, 5]),
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_pendingwithoutrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithoutrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_pendingwithoutrating', false, $this->storeIds['klevu_test_store_2']);
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
     * @magentoDataFixture loadProductFixtures_DisapprovedWithoutRating
     * @magentoDataFixture loadReviewFixtures_DisapprovedWithoutRating
     */
    public function testUpdateRatingValue_Disapproved_WithoutRating()
    {
        $this->setupPhp5();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating', false, $this->storeIds['klevu_test_store_2']);
        $this->assertNull($productFixtureStore2->getData('rating'));
        $this->assertEmpty($productFixtureStore2->getData('review_count'));

        $reviewFixture = $this->getReviewFixture('Disapproved: Without Rating');

        $request = $this->getRequest();
        $request->setMethod('POST');
        $request->setParam('id', $reviewFixture->getId());
        $request->setPostValue(array_merge(
            $reviewFixture->getData(),
            [
                'ratings' => $this->createRatingDataFixture([5, 5, 5]),
            ]
        ));

        $this->dispatch($this->getAdminFrontName() . '/review/product/save/');

        $response = $this->getResponse();
        $httpResponseCode = $response->getHttpResponseCode();
        $this->assertSame(302, $httpResponseCode);

        $this->productRepository->cleanCache();

        /** @var Product $productFixtureGlobal */
        $productFixtureGlobal = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating', false, 0);
        $this->assertNull($productFixtureGlobal->getData('rating'));
        $this->assertEmpty($productFixtureGlobal->getData('review_count'));

        /** @var Product $productFixtureStore1 */
        $productFixtureStore1 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating', false, $this->storeIds['klevu_test_store_1']);
        $this->assertNull($productFixtureStore1->getData('rating'));
        $this->assertEmpty($productFixtureStore1->getData('review_count'));

        /** @var Product $productFixtureStore2 */
        $productFixtureStore2 = $this->productRepository->get('klevu_simple_reviewtest_disapprovedwithoutrating', false, $this->storeIds['klevu_test_store_2']);
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

    /**
     * Alternative setup method to accommodate lack of return type casting in PHP5.6,
     *  given setUp() requires a void return type
     *
     * @return void
     * @todo Move to setUp when PHP 5.x is no longer supported
     */
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

        /** @var RatingCollectionFactory $ratingCollectionFactory */
        $ratingCollectionFactory = $this->_objectManager->get(RatingCollectionFactory::class);
        $ratingCollection = $ratingCollectionFactory->create();
        $ratingCollection->setPageSize(3);
        $ratingCollection->setCurPage(1);
        $this->ratingIds = $ratingCollection->getColumnValues('rating_id');

        /** @var RatingOptionCollectionFactory $ratingOptionCollectionFactory */
        $ratingOptionCollectionFactory = $this->_objectManager->get(RatingOptionCollectionFactory::class);
        $ratingOptionCollection = $ratingOptionCollectionFactory->create();
        $this->ratingOptionIds = [];
        foreach ($ratingOptionCollection as $ratingOption) {
            /** @var Option $ratingOption */
            if (!isset($this->ratingOptionIds[$ratingOption->getRatingId()])) {
                $this->ratingOptionIds[$ratingOption->getRatingId()] = [];
            }

            $this->ratingOptionIds[$ratingOption->getRatingId()][$ratingOption->getValue()] = $ratingOption->getId();
        }
        array_walk($this->ratingOptionIds, 'ksort');

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

    /**
     * @param array $ratingValues
     * @return array
     */
    private function createRatingDataFixture(array $ratingValues)
    {
        $return = [];
        foreach ($ratingValues as $i => $ratingValue) {
            if (!isset($this->ratingIds[$i])) {
                break;
            }

            $ratingId = $this->ratingIds[$i];
            if (!isset($this->ratingOptionIds[$ratingId])) {
                continue;
            }

            $return[$ratingId] = $this->ratingOptionIds[$ratingId][$ratingValue];
        }

        return $return;
    }

    public static function loadWebsiteFixtures()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures.php';
    }

    public static function loadWebsiteFixturesRollback()
    {
        include __DIR__ . '/../../../../../_files/websiteFixtures_rollback.php';
    }

    public static function loadProductFixtures_PendingToApproved()
    {
        self::loadProductFixtures('PendingToApproved');
    }

    public static function loadProductFixtures_DisapprovedToApproved()
    {
        self::loadProductFixtures('DisapprovedToApproved');
    }

    public static function loadProductFixtures_ApprovedToPending()
    {
        self::loadProductFixtures('ApprovedToPending');
    }

    public static function loadProductFixtures_DisapprovedToPending()
    {
        self::loadProductFixtures('DisapprovedToPending');
    }

    public static function loadProductFixtures_ApprovedToDisapproved()
    {
        self::loadProductFixtures('ApprovedToDisapproved');
    }

    public static function loadProductFixtures_PendingToDisapproved()
    {
        self::loadProductFixtures('PendingToDisapproved');
    }

    public static function loadProductFixtures_ApprovedWithRating()
    {
        self::loadProductFixtures('ApprovedWithRating');
    }

    public static function loadProductFixtures_ApprovedWithoutRating()
    {
        self::loadProductFixtures('ApprovedWithoutRating');
    }

    public static function loadProductFixtures_PendingWithRating()
    {
        self::loadProductFixtures('PendingWithRating');
    }

    public static function loadProductFixtures_PendingWithoutRating()
    {
        self::loadProductFixtures('PendingWithoutRating');
    }

    public static function loadProductFixtures_DisapprovedWithRating()
    {
        self::loadProductFixtures('DisapprovedWithRating');
    }

    public static function loadProductFixtures_DisapprovedWithoutRating()
    {
        self::loadProductFixtures('DisapprovedWithoutRating');
    }

    public static function loadProductFixtures($group = null)
    {
        $PRODUCT_FIXTURES_GROUP = $group;

        include __DIR__ . '/../_files/productFixtures.php';
    }

    public static function loadProductFixtures_PendingToApprovedRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_DisapprovedToApprovedRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_ApprovedToPendingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_DisapprovedToPendingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_ApprovedToDisapprovedRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_PendingToDisapprovedRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_ApprovedWithRatingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_ApprovedWithoutRatingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_PendingWithRatingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_PendingWithoutRatingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_DisapprovedWithRatingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixtures_DisapprovedWithoutRatingRollback()
    {
        self::loadProductFixturesRollback();
    }

    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../_files/productFixtures_rollback.php';
    }

    public static function loadReviewFixtures_PendingToApproved()
    {
        self::loadReviewFixtures('PendingToApproved');
    }

    public static function loadReviewFixtures_DisapprovedToApproved()
    {
        self::loadReviewFixtures('DisapprovedToApproved');
    }

    public static function loadReviewFixtures_ApprovedToPending()
    {
        self::loadReviewFixtures('ApprovedToPending');
    }

    public static function loadReviewFixtures_DisapprovedToPending()
    {
        self::loadReviewFixtures('DisapprovedToPending');
    }

    public static function loadReviewFixtures_ApprovedToDisapproved()
    {
        self::loadReviewFixtures('ApprovedToDisapproved');
    }

    public static function loadReviewFixtures_PendingToDisapproved()
    {
        self::loadReviewFixtures('PendingToDisapproved');
    }

    public static function loadReviewFixtures_ApprovedWithRating()
    {
        self::loadReviewFixtures('ApprovedWithRating');
    }

    public static function loadReviewFixtures_ApprovedWithoutRating()
    {
        self::loadReviewFixtures('ApprovedWithoutRating');
    }

    public static function loadReviewFixtures_PendingWithRating()
    {
        self::loadReviewFixtures('PendingWithRating');
    }

    public static function loadReviewFixtures_PendingWithoutRating()
    {
        self::loadReviewFixtures('PendingWithoutRating');
    }

    public static function loadReviewFixtures_DisapprovedWithRating()
    {
        self::loadReviewFixtures('DisapprovedWithRating');
    }

    public static function loadReviewFixtures_DisapprovedWithoutRating()
    {
        self::loadReviewFixtures('DisapprovedWithoutRating');
    }

    public static function loadReviewFixtures($group = null)
    {
        $REVIEW_FIXTURES_GROUP = $group;

        include __DIR__ . '/../_files/reviewFixtures.php';
    }

    public static function loadReviewFixtures_PendingToApprovedRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_DisapprovedToApprovedRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_ApprovedToPendingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_DisapprovedToPendingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_ApprovedToDisapprovedRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_PendingToDisapprovedRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_ApprovedWithRatingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_ApprovedWithoutRatingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_PendingWithRatingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_PendingWithoutRatingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_DisapprovedWithRatingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixtures_DisapprovedWithoutRatingRollback()
    {
        self::loadReviewFixturesRollback();
    }

    public static function loadReviewFixturesRollback()
    {
        include __DIR__ . '/../_files/reviewFixtures_rollback.php';
    }
}

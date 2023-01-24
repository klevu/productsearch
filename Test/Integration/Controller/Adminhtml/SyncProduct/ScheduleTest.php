<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\SyncProduct;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\Auth as BackendAuth;
use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Acl\Builder;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Authorization;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Security\Model\Plugin\Auth as AuthPlugin;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

class ScheduleTest extends AbstractBackendControllerTestCase
{
    const REDIRECT_URL = 'klevu_search/syncproduct/index';

    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Klevu_Search::sync_product_grid';
    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = '/klevu_search/syncproduct/schedule';
    /**
     * Expected no access response
     *
     * @var int
     */
    protected $expectedNoAccessResponseCode = 403;
    /**
     * @var string
     */
    protected $httpMethod = 'POST';
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testAclHasAccess()
    {
        $this->setUpPhp5();

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $response = $this->getResponse();
        $this->assertNotSame(404, $response->getHttpResponseCode());
        $this->assertNotSame($this->expectedNoAccessResponseCode, $response->getHttpResponseCode());
    }

    public function testAclNoAccess()
    {
        $this->setUpPhp5();

        $this->_objectManager->get(Builder::class)
            ->getAcl()
            ->deny(null, $this->resource);

        $this->dispatch($this->getAdminFrontName() . $this->uri);
        $this->assertSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     */
    public function testReturnsErrorMessageIfStoreNotIntegrated()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $uniqueProductId = '0-' . $product->getId() . '-0';
        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setParam('id', $uniqueProductId);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $this->assertMessageShows(
            sprintf(
                'Requested store %s is not integrated with Klevu. Sync can not be triggered or scheduled.',
                $store->getCode()
            )
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testReturnsErrorMessageIfStoreSyncDisabled()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $uniqueProductId = '0-' . $product->getId() . '-0';
        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setParam('id', $uniqueProductId);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $this->assertMessageShows(
            sprintf(
                'Sync for store is %s disabled.',
                $store->getCode()
            )
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testResponseIsRedirectToIndex()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $uniqueProductId = '0-' . $product->getId() . '-0';
        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setParam('id', $uniqueProductId);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $this->assertMessageShows(
            'Product SKU (' . $product->getSku() . ') has been added to Sync Schedule in ' . $store->getName()
        );
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @dataProvider invalidStoreIdsDataProvider
     */
    public function testReturnsErrorMessageIfStoreIdNotProvided($invalidStoreId)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $uniqueProductIds = '0-' . $product->getId() . '-0';
        $request = $this->getRequest();
        $request->setParam('store', $invalidStoreId);
        $request->setParam('id', $uniqueProductIds);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $this->assertMessageShows('Invalid Store ID Provided.');
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoDataFixture loadProductFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testReturnsErrorMessageIfStoreNotFound()
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');
        $product = $this->getProduct('klevu_simple_1');

        $uniqueProductId = '0-' . $product->getId() . '-0';
        $request = $this->getRequest();
        $request->setParam('store', 99999999999);
        $request->setParam('id', $uniqueProductId);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $productMetaData = $this->objectManager->get(ProductMetadataInterface::class);
        if (version_compare($productMetaData->getVersion(), 2.3) < 0) {
            $this->assertMessageShows('Requested store is not found');
        } else {
            $this->assertMessageShows(
                'The store that was requested wasn&#039;t found. Verify the store and try again.'
            );
        }
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @dataProvider missingProductIdsDataProvider
     */
    public function testReturnsErrorMessageIfNoProductIdsProvided($missingProductIds)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setParam('id', $missingProductIds);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $this->assertMessageShows('No entity IDs provided for product sync.');
    }

    /**
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     * @dataProvider invalidProductIdsDataProvider
     */
    public function testReturnsErrorMessageIfInvalidProductIdsProvided($invalidProductIds)
    {
        $this->setUpPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setParam('id', $invalidProductIds);
        $request->setParam(RedirectInterface::PARAM_NAME_REFERER_URL, $this->getRefererUrl($store));
        if ($this->httpMethod) {
            $request->setMethod($this->httpMethod);
        }
        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $this->assertRedirectToGird($store);
        $this->assertMessageShows('No valid entity IDs provided for product sync.');
    }

    /**
     * @return array
     */
    public function invalidStoreIdsDataProvider()
    {
        return [
            [0],
            [null],
            [false],
            [true],
            ['some string'],
            [['1']]
        ];
    }

    /**
     * @return array
     */
    public function missingProductIdsDataProvider()
    {
        return [
            [null],
            [false],
            [0]
        ];
    }

    /**
     * @return array
     */
    public function invalidProductIdsDataProvider()
    {
        return [
            [1],
            [true],
            ['1'],
            ['1-2'],
            [['1-2-3']],
            [['1-2-3'], ['4-5-6'], ['7-8-9']]
        ];
    }

    /**
     * @param StoreInterface $store
     *
     * @return void
     */
    private function assertRedirectToGird(StoreInterface $store)
    {
        $this->assertNotSame($this->expectedNoAccessResponseCode, $this->getResponse()->getHttpResponseCode());

        $this->assertRedirect(
            $this->stringContains(self::REDIRECT_URL)
        );
        $this->assertRedirect(
            $this->stringContains('/store/' . $store->getId())
        );
    }

    /**
     * @param $expectedMessage
     *
     * @return void
     */
    private function assertMessageShows($expectedMessage)
    {
        if (method_exists($this, 'assertSessionMessages') && method_exists($this, 'containsEqual')) {
            $this->assertSessionMessages($this->containsEqual($expectedMessage));
        } else {
            $this->assertContains($expectedMessage, $this->getMessages());
        }
    }

    /**
     * @todo switch to "protected function setUp()" once support for PHP 5.6 is dropped
     */
    private function setUpPhp5()
    {
        $this->objectManager = ObjectManager::getInstance();
        $this->login();
    }

    /**
     * @return void
     * @throws AuthenticationException
     */
    private function login()
    {
        $this->_assertSessionErrors = false;

        $this->_objectManager->removeSharedInstance(ResponseInterface::class);
        $this->_objectManager->removeSharedInstance(RequestInterface::class);

        $url = $this->_objectManager->get(UrlInterface::class);
        $url->turnOffSecretKey();

        $this->_objectManager->removeSharedInstance(
            Authorization::class
        );

        $this->_auth = $this->_objectManager->get(BackendAuth::class);
        $this->_session = $this->_auth->getAuthStorage();
        $credentials = $this->_getAdminCredentials();
        $this->_auth->login($credentials['user'], $credentials['password']);

        $authPlugin = $this->_objectManager->get(AuthPlugin::class);
        $authPlugin->afterLogin($this->_auth);
    }

    /**
     * Returns configured admin front name for use in dispatching controller requests
     *
     * @return string
     */
    private function getAdminFrontName()
    {
        /** @var AreaList $areaList */
        $areaList = $this->objectManager->create(AreaList::class);
        $adminFrontName = $areaList->getFrontName('adminhtml');
        if (!$adminFrontName) {
            /** @var FrontNameResolver $backendFrontNameResolver */
            $backendFrontNameResolver = $this->objectManager->create(FrontNameResolver::class);
            $adminFrontName = $backendFrontNameResolver->getFrontName(true);
        }

        return (string)$adminFrontName;
    }

    /**
     * @param StoreInterface $store
     *
     * @return string
     */
    private function getRefererUrl(StoreInterface $store)
    {
        return $this->objectManager->get(UrlInterface::class)->getUrl(
            self::REDIRECT_URL,
            ['store' => $store->getId()]
        );
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
     * @param string $sku
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($sku)
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);

        return $productRepository->get($sku);
    }

    /**
     * Loads product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixtures()
    {
        include __DIR__ . '/../../../_files/productFixtures.php';
    }

    /**
     * Rolls back product creation scripts because annotations use a relative path
     *  from integration tests root
     */
    public static function loadProductFixturesRollback()
    {
        include __DIR__ . '/../../../_files/productFixtures_rollback.php';
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

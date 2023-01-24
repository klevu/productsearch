<?php

namespace Klevu\Search\Test\Integration\Controller\Adminhtml\SyncProduct;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\Auth as BackendAuth;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Acl\Builder;
use Magento\Framework\App\AreaList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Authorization;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Security\Model\Plugin\Auth as AuthPlugin;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\AbstractBackendController as AbstractBackendControllerTestCase;

class IndexTest extends AbstractBackendControllerTestCase
{
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
    protected $uri = '/klevu_search/syncproduct/index';
    /**
     * Expected no access response
     *
     * @var int
     */
    protected $expectedNoAccessResponseCode = 403;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function testAclHasAccess()
    {
        $this->setUpPhp5();

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $this->assertNotSame(404, $this->getResponse()->getHttpResponseCode());
        $this->assertNotSame($this->expectedNoAccessResponseCode, $response->getHttpResponseCode());
    }

    public function testAclNoAccess()
    {
        $this->setUpPhp5();

        $this->_objectManager->get(Builder::class)
            ->getAcl()
            ->deny(null, $this->resource);

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $this->assertSame($this->expectedNoAccessResponseCode, $response->getHttpResponseCode());
    }

    public function testRendersProductSyncMenuItems()
    {
        $this->setupPhp5();

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $matches = [];
        preg_match(
            '#<li\ *?data-ui-id="menu-klevu-search-catalog-sync".*?>.*?</li>#s',
            $responseBody,
            $matches
        );
        $this->assertCount(1, $matches, 'Warning Message Block');

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Klevu Catalog Sync', $responseBody);
        } else {
            $this->assertContains('Klevu Catalog Sync', $responseBody);
        }

        $matches = [];
        preg_match(
            '#<li\ *?data-ui-id="menu-klevu-search-catalog-sync-product".*?>.*?</li>#s',
            $responseBody,
            $matches
        );
        $this->assertCount(1, $matches, 'Sync Products Menu');

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Sync Products', $responseBody);
        } else {
            $this->assertContains('Sync Products', $responseBody);
        }
    }

    public function testRendersWarningMessageInGlobalScope()
    {
        $this->setupPhp5();

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $responseBody = $response->getBody();

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Klevu Sync - Products', $responseBody);
        } else {
            $this->assertContains('Klevu Sync - Products', $responseBody);
        }

        $this->assertWarningMessageIsDisplayed($responseBody);
        $this->assertInfoMessageIsDisplayed($responseBody);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                'Select scope to store view level to sync product data.',
                $responseBody
            );
        } else {
            $this->assertContains('Select scope to store view level to sync product data.', $responseBody);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testRendersWarningWhenStoreIsNotIntegrated()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertWarningMessageIsDisplayed($responseBody);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                'You need to integrate this store with Klevu to sync products.',
                $responseBody
            );
            $this->assertStringContainsString(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Please navigate to Stores &gt; Configuration &gt; Klevu &gt; Integration, and click on Integrate Now against this store.',
                $responseBody
            );
        } else {
            $this->assertContains(
                'You need to integrate this store with Klevu to sync products.',
                $responseBody
            );
            $this->assertContains(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Please navigate to Stores &gt; Configuration &gt; Klevu &gt; Integration, and click on Integrate Now against this store.',
                $responseBody
            );
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'Select scope to store view level to sync product data.',
                $responseBody
            );
        } else {
            $this->assertNotContains('Select scope to store view level to sync product data.', $responseBody);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 0
     */
    public function testRendersWarningWhenStoreHasSyncDisabled()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertWarningMessageIsDisplayed($responseBody);

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString('Product sync is disabled for this store.', $responseBody);
        } else {
            $this->assertContains('Product sync is disabled for this store.', $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'You need to integrate this store with Klevu to sync products.',
                $responseBody
            );
            $this->assertStringNotContainsString(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Please navigate to Stores &gt; Configuration &gt; Klevu &gt; Integration, and click on Integrate Now against this store.',
                $responseBody
            );
        } else {
            $this->assertNotContains(
                'You need to integrate this store with Klevu to sync products.',
                $responseBody
            );
            $this->assertNotContains(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Please navigate to Stores &gt; Configuration &gt; Klevu &gt; Integration, and click on Integrate Now against this store.',
                $responseBody
            );
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'Select scope to store view level to sync product data.',
                $responseBody
            );
        } else {
            $this->assertNotContains('Select scope to store view level to sync product data.', $responseBody);
        }
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     * @magentoDataFixture loadWebsiteFixtures
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/js_api_key klevu-js-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/general/rest_api_key klevu-rest-api-key
     * @magentoConfigFixture klevu_test_store_1_store klevu_search/product_sync/enabled 1
     */
    public function testRendersNoWarningMessageAtStoreLevelForIntegratedStoreWithSyncEnabled()
    {
        $this->setupPhp5();

        $store = $this->getStore('klevu_test_store_1');

        $request = $this->getRequest();
        $request->setParam('store', $store->getId());
        $request->setMethod('GET');

        $this->dispatch($this->getAdminFrontName() . $this->uri);

        $response = $this->getResponse();
        $responseBody = $response->getBody();

        $this->assertInfoMessageIsDisplayed($responseBody);

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString('Product sync is disabled for this store', $responseBody);
        } else {
            $this->assertNotContains('Product sync is disabled for this store', $responseBody);
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'You need to integrate this store with Klevu to sync products.',
                $responseBody
            );
            $this->assertStringNotContainsString(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Please navigate to Stores &gt; Configuration &gt; Klevu &gt; Integration, and click on Integrate Now against this store.',
                $responseBody
            );
        } else {
            $this->assertNotContains(
                'You need to integrate this store with Klevu to sync products.',
                $responseBody
            );
            $this->assertNotContains(
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'Please navigate to Stores &gt; Configuration &gt; Klevu &gt; Integration, and click on Integrate Now against this store.',
                $responseBody
            );
        }

        if (method_exists($this, 'assertStringNotContainsString')) {
            $this->assertStringNotContainsString(
                'Select scope to store view level to sync product data.',
                $responseBody
            );
        } else {
            $this->assertNotContains('Select scope to store view level to sync product data.', $responseBody);
        }
    }

    /**
     * @param $responseBody
     *
     * @return void
     */
    private function assertWarningMessageIsDisplayed($responseBody)
    {
        $matches = [];
        preg_match(
            '#<div class="messages">.*?<div class=".*?message.*?message-warning.*?">.*?</div>#s',
            $responseBody,
            $matches
        );
        $this->assertCount(1, $matches, 'Warning Message Block Is Displayed');
    }

    /**
     * @param $responseBody
     *
     * @return void
     */
    private function assertInfoMessageIsDisplayed($responseBody)
    {
        $matches = [];
        preg_match(
            '#<div class="messages">.*?<div class=".*?message.*?message-info.*?">.*?</div>#s',
            $responseBody,
            $matches
        );
        $this->assertCount(1, $matches, 'Info Message Block Is Displayed');

        if (method_exists($this, 'assertStringContainsString')) {
            $this->assertStringContainsString(
                'We recommend to use this grid when you wish to sync a (group of) product changes with Klevu.',
                $responseBody
            );
            $this->assertStringContainsString(
                'You can choose to sync now or schedule to sync at the next scheduled CRON job.',
                $responseBody
            );
            $this->assertStringContainsString(
                'You can also view sync history for troubleshooting.',
                $responseBody
            );
        } else {
            $this->assertContains(
                'We recommend to use this grid when you wish to sync a (group of) product changes with Klevu.',
                $responseBody
            );
            $this->assertContains(
                'You can choose to sync now or schedule to sync at the next scheduled CRON job.',
                $responseBody
            );
            $this->assertContains(
                'You can also view sync history for troubleshooting.',
                $responseBody
            );
        }
    }

    /**
     * @todo remove once support for PHP 5.6 is dropped
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
